<?php

namespace App\Controllers;

use App\Libraries\FaceGatewayClient;
use App\Models\GuruModel;
use App\Models\IotDeviceModel;
use App\Models\IotRegistrationCandidateModel;
use App\Models\IotRegistrationSessionModel;
use App\Models\SiswaModel;
use Config\IotDevice;
use RuntimeException;

class Registrasi extends BaseController
{
    public function index()
    {
        $this->expireStaleSessions();

        $guruList = (new GuruModel())
            ->select('id_guru, nama, nip, id_rfid, foto_wajah')
            ->orderBy('nama', 'ASC')
            ->findAll();

        $devices = $this->decorateDevices((new IotDeviceModel())->orderBy('last_seen_at', 'DESC')->findAll());
        $activeSession = $this->resolveSessionForView((int) $this->request->getGet('session'));

        $initialRfid = '';
        $initialFace = '';
        $selectedType = trim((string) old(
            'target_type',
            (string) ($this->request->getGet('target_type') ?: $this->request->getGet('target'))
        ));
        if (! in_array($selectedType, ['siswa', 'guru'], true)) {
            $selectedType = 'siswa';
        }
        $selectedTargetId = (int) old(
            'target_id',
            (int) ($this->request->getGet('target_id') ?: $this->request->getGet('id'))
        );
        $selectedSiswaName = trim((string) old(
            'nama_siswa',
            (string) $this->request->getGet('nama_siswa')
        ));

        if ($selectedType === 'siswa' && $selectedSiswaName === '' && $selectedTargetId > 0) {
            $selectedSiswa = (new SiswaModel())->find($selectedTargetId);
            if ($selectedSiswa) {
                $selectedSiswaName = trim((string) ($selectedSiswa['nama'] ?? ''));
            }
        }

        if ($activeSession !== null) {
            $initialRfid = trim((string) ($activeSession['captured_rfid'] ?? ''));
            $initialFace = trim((string) ($activeSession['captured_face'] ?? ''));
        }

        return view('registrasi_admin', [
            'devices' => $devices,
            'activeSession' => $activeSession,
            'initialRfid' => $initialRfid,
            'initialFace' => $initialFace,
            'onlineWindowSec' => $this->deviceOnlineWindowSec(),
            'guruList' => $guruList,
            'selectedType' => $selectedType,
            'selectedTargetId' => $selectedTargetId,
            'selectedSiswaName' => $selectedSiswaName,
        ]);
    }

    public function mulaiModeRegis()
    {
        $deviceId = (int) $this->request->getPost('device_id');
        if ($deviceId <= 0) {
            return redirect()->to('/admin/registrasi')
                ->with('error', 'Pilih alat yang akan masuk mode registrasi.');
        }

        $device = (new IotDeviceModel())->find($deviceId);
        if (! $device) {
            return redirect()->to('/admin/registrasi')
                ->with('error', 'Data alat tidak ditemukan.');
        }

        if (! $this->isDeviceOnline($device)) {
            return redirect()->to('/admin/registrasi')
                ->with('error', 'Alat belum tersambung ke sistem. Pastikan service IoT berjalan lalu coba lagi.');
        }

        $this->expireStaleSessions();
        $sessionModel = new IotRegistrationSessionModel();
        $pending = $sessionModel
            ->where('device_id', $deviceId)
            ->where('status', 'waiting_device')
            ->orderBy('id_session', 'DESC')
            ->first();

        if ($pending) {
            return redirect()->to('/admin/registrasi?session=' . (int) $pending['id_session'])
                ->with('success', 'Mode registrasi sudah aktif pada alat terpilih. Menunggu hasil capture...');
        }

        $now = date('Y-m-d H:i:s');
        $insertId = $sessionModel->insert([
            'device_id' => $deviceId,
            'session_token' => bin2hex(random_bytes(20)),
            'status' => 'waiting_device',
            'requested_by' => (int) (session()->get('user_id') ?: 0),
            'command_issued_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ], true);

        (new IotDeviceModel())->update($deviceId, [
            'status_mode' => 'register',
            'last_message' => 'Menunggu capture registrasi...',
            'updated_at' => $now,
        ]);

        return redirect()->to('/admin/registrasi?session=' . (int) $insertId)
            ->with('success', 'Mode registrasi berhasil diaktifkan. Silakan tap kartu dan capture wajah pada alat.');
    }

    public function statusSesi(int $idSession)
    {
        $this->expireStaleSessions();
        $session = $this->resolveSessionForView($idSession);

        if ($session === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 'error',
                'message' => 'Session registrasi tidak ditemukan.',
            ]);
        }

        return $this->response->setJSON([
            'status' => 'ok',
            'data' => $session,
            'server_time' => date('c'),
        ]);
    }

    public function batalSesi(int $idSession)
    {
        $sessionModel = new IotRegistrationSessionModel();
        $session = $sessionModel->find($idSession);

        if (! $session) {
            return redirect()->to('/admin/registrasi')
                ->with('error', 'Session registrasi tidak ditemukan.');
        }

        $status = (string) ($session['status'] ?? '');
        if (! in_array($status, ['waiting_device', 'captured'], true)) {
            return redirect()->to('/admin/registrasi')
                ->with('error', 'Session registrasi tidak dapat dibatalkan karena sudah ditutup.');
        }

        $now = date('Y-m-d H:i:s');
        $sessionModel->update($idSession, [
            'status' => 'cancelled',
            'completed_at' => $now,
            'updated_at' => $now,
            'error_message' => 'Dibatalkan oleh admin.',
        ]);

        if (! empty($session['device_id'])) {
            (new IotDeviceModel())->update((int) $session['device_id'], [
                'status_mode' => 'attendance',
                'last_message' => 'Mode registrasi dibatalkan.',
                'updated_at' => $now,
            ]);
        }

        return redirect()->to('/admin/registrasi')
            ->with('success', 'Session registrasi dibatalkan.');
    }

    public function simpan()
    {
        $targetType = trim((string) $this->request->getPost('target_type'));
        $namaSiswa = trim((string) $this->request->getPost('nama_siswa'));
        $targetId = (int) $this->request->getPost('target_id');
        $idRfid = trim((string) $this->request->getPost('id_rfid'));
        $fotoWajah = trim((string) $this->request->getPost('foto_wajah'));
        $sessionId = (int) $this->request->getPost('registration_session_id');
        $session = $sessionId > 0 ? (new IotRegistrationSessionModel())->find($sessionId) : null;

        if ($session && $idRfid === '') {
            $idRfid = trim((string) ($session['captured_rfid'] ?? ''));
        }
        if ($session && $fotoWajah === '') {
            $fotoWajah = trim((string) ($session['captured_face'] ?? ''));
        }

        if (! in_array($targetType, ['siswa', 'guru'], true)) {
            return redirect()->back()->withInput()->with('error', 'Pilih tipe data tujuan: siswa atau guru.');
        }

        if ($targetType === 'siswa' && strlen($namaSiswa) < 3) {
            return redirect()->back()->withInput()->with('error', 'Isi nama lengkap siswa minimal 3 karakter.');
        }

        if ($targetType === 'guru' && $targetId <= 0) {
            return redirect()->back()->withInput()->with('error', 'Pilih nama guru tujuan registrasi terlebih dahulu.');
        }

        if ($idRfid === '' && $fotoWajah === '') {
            return redirect()->back()->withInput()->with('error', 'Isi minimal RFID atau foto wajah sebelum menyimpan.');
        }

        try {
            if ($targetType === 'siswa') {
                $targetId = $this->createSiswaFromRegistration($namaSiswa, $idRfid, $fotoWajah);
            } else {
                $this->saveToGuruDirect($targetId, $idRfid, $fotoWajah);
            }
        } catch (RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        if ($sessionId > 0) {
            $this->markSessionAssigned($sessionId, $targetType, $targetId);
        }

        if ($targetType === 'siswa') {
            return redirect()->to('/siswa/data')
                ->with('success', 'Registrasi siswa berhasil. Nama baru sudah masuk ke data siswa. Silakan lengkapi kelas/NIS/alamat (opsional) dari menu edit.');
        }

        return redirect()->to('/admin/registrasi?target_type=' . $targetType . '&target_id=' . $targetId)
            ->with('success', 'Registrasi wajah/RFID berhasil disimpan ke data ' . $targetType . '.');
    }

    public function pemetaan()
    {
        $candidateModel = new IotRegistrationCandidateModel();
        $pendingList = $candidateModel
            ->where('status', 'pending')
            ->orderBy('id_candidate', 'DESC')
            ->findAll();

        $mappedRecent = $candidateModel
            ->where('status', 'mapped')
            ->orderBy('id_candidate', 'DESC')
            ->findAll(25);

        $siswaList = (new SiswaModel())
            ->select('id, nama, no_induk, kelas, id_rfid')
            ->orderBy('kelas', 'ASC')
            ->orderBy('nama', 'ASC')
            ->findAll();

        $guruList = (new GuruModel())
            ->select('id_guru, nama, nip, id_rfid')
            ->orderBy('nama', 'ASC')
            ->findAll();

        $kelasSet = [];
        foreach ($siswaList as $row) {
            $kelas = trim((string) ($row['kelas'] ?? ''));
            if ($kelas !== '') {
                $kelasSet[$kelas] = true;
            }
        }
        $kelasList = array_keys($kelasSet);
        sort($kelasList);

        return view('registrasi_pemetaan', [
            'pendingList' => $pendingList,
            'mappedRecent' => $mappedRecent,
            'siswaList' => $siswaList,
            'guruList' => $guruList,
            'kelasList' => $kelasList,
        ]);
    }

    public function simpanPemetaan()
    {
        $candidateId = (int) $this->request->getPost('candidate_id');
        $targetType = trim((string) $this->request->getPost('target_type'));
        $targetId = (int) $this->request->getPost('target_id');
        $kelasSiswa = trim((string) $this->request->getPost('kelas_siswa'));

        if ($candidateId <= 0) {
            return redirect()->back()->withInput()->with('error', 'Pilih calon registrasi terlebih dahulu.');
        }

        if (! in_array($targetType, ['siswa', 'guru'], true) || $targetId <= 0) {
            return redirect()->back()->withInput()->with('error', 'Data tujuan pemetaan tidak valid.');
        }

        $candidateModel = new IotRegistrationCandidateModel();
        $candidate = $candidateModel->find($candidateId);
        if (! $candidate) {
            return redirect()->back()->withInput()->with('error', 'Calon registrasi tidak ditemukan.');
        }

        if ((string) ($candidate['status'] ?? '') !== 'pending') {
            return redirect()->back()->withInput()->with('error', 'Calon registrasi sudah dipetakan atau tidak aktif.');
        }

        try {
            if ($targetType === 'siswa') {
                $this->applyToSiswa($candidate, $targetId, $kelasSiswa);
            } else {
                $this->applyToGuru($candidate, $targetId);
            }
        } catch (RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        $now = date('Y-m-d H:i:s');
        $candidateModel->update($candidateId, [
            'status' => 'mapped',
            'mapped_target_type' => $targetType,
            'mapped_target_id' => $targetId,
            'mapped_at' => $now,
            'updated_at' => $now,
        ]);

        if (! empty($candidate['source_session_id'])) {
            $this->markSessionAssigned((int) $candidate['source_session_id'], $targetType, $targetId);
        }

        return redirect()->to('/admin/registrasi/pemetaan')
            ->with('success', 'Pemetaan registrasi berhasil disimpan ke data ' . $targetType . '.');
    }

    private function applyToSiswa(array $candidate, int $targetId, string $kelasSiswa = ''): void
    {
        $model = new SiswaModel();
        $existing = $model->find($targetId);
        if (! $existing) {
            throw new RuntimeException('Data siswa tujuan tidak ditemukan.');
        }

        $idRfid = trim((string) ($candidate['id_rfid'] ?? ''));
        $fotoWajah = trim((string) ($candidate['foto_wajah'] ?? ''));

        if ($idRfid !== '') {
            $dupSiswa = $model
                ->where('id_rfid', $idRfid)
                ->where('id !=', $targetId)
                ->first();
            $dupGuru = (new GuruModel())->where('id_rfid', $idRfid)->first();
            if ($dupSiswa || $dupGuru) {
                throw new RuntimeException('ID RFID sudah dipakai data lain. Gunakan RFID berbeda.');
            }
        }

        if ($fotoWajah !== '') {
            (new FaceGatewayClient())->registerSiswa($targetId, (string) $existing['nama'], $fotoWajah);
        }

        $payload = [
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($idRfid !== '') {
            $payload['id_rfid'] = $idRfid;
        }
        if ($fotoWajah !== '') {
            $payload['foto_wajah'] = $fotoWajah;
        }
        if ($kelasSiswa !== '') {
            $payload['kelas'] = $kelasSiswa;
        }

        $model->update($targetId, $payload);
    }

    private function applyToGuru(array $candidate, int $targetId): void
    {
        $model = new GuruModel();
        $existing = $model->find($targetId);
        if (! $existing) {
            throw new RuntimeException('Data guru tujuan tidak ditemukan.');
        }

        $idRfid = trim((string) ($candidate['id_rfid'] ?? ''));
        $fotoWajah = trim((string) ($candidate['foto_wajah'] ?? ''));

        if ($idRfid !== '') {
            $dupGuru = $model
                ->where('id_rfid', $idRfid)
                ->where('id_guru !=', $targetId)
                ->first();
            $dupSiswa = (new SiswaModel())->where('id_rfid', $idRfid)->first();
            if ($dupGuru || $dupSiswa) {
                throw new RuntimeException('ID RFID sudah dipakai data lain. Gunakan RFID berbeda.');
            }
        }

        if ($fotoWajah !== '') {
            (new FaceGatewayClient())->registerGuru($targetId, (string) $existing['nama'], $fotoWajah);
        }

        $payload = [
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($idRfid !== '') {
            $payload['id_rfid'] = $idRfid;
        }
        if ($fotoWajah !== '') {
            $payload['foto_wajah'] = $fotoWajah;
        }

        $model->update($targetId, $payload);
    }

    private function markSessionRecorded(int $sessionId, int $candidateId): void
    {
        $model = new IotRegistrationSessionModel();
        $session = $model->find($sessionId);
        if (! $session) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $model->update($sessionId, [
            'status' => 'recorded',
            'target_type' => 'candidate',
            'target_id' => $candidateId,
            'completed_at' => $now,
            'updated_at' => $now,
            'error_message' => null,
        ]);

        if (! empty($session['device_id'])) {
            (new IotDeviceModel())->update((int) $session['device_id'], [
                'status_mode' => 'attendance',
                'last_message' => 'Data calon registrasi tersimpan.',
                'updated_at' => $now,
            ]);
        }
    }

    private function createSiswaFromRegistration(string $namaSiswa, string $idRfid, string $fotoWajah): int
    {
        $namaSiswa = trim($namaSiswa);
        if ($namaSiswa === '') {
            throw new RuntimeException('Nama siswa wajib diisi.');
        }

        if ($idRfid !== '') {
            $dupSiswa = (new SiswaModel())->where('id_rfid', $idRfid)->first();
            $dupGuru = (new GuruModel())->where('id_rfid', $idRfid)->first();
            if ($dupSiswa || $dupGuru) {
                throw new RuntimeException('ID RFID sudah dipakai data lain. Gunakan RFID berbeda.');
            }
        }

        $model = new SiswaModel();
        $now = date('Y-m-d H:i:s');
        $insertId = (int) $model->insert([
            'nama' => $namaSiswa,
            'no_induk' => null,
            'kelas' => null,
            'alamat' => null,
            'id_rfid' => $idRfid !== '' ? $idRfid : null,
            'foto_wajah' => $fotoWajah !== '' ? $fotoWajah : null,
            'created_at' => $now,
            'updated_at' => $now,
        ], true);

        if ($insertId <= 0) {
            throw new RuntimeException('Gagal membuat data siswa baru dari hasil registrasi.');
        }

        if ($fotoWajah !== '') {
            try {
                (new FaceGatewayClient())->registerSiswa($insertId, $namaSiswa, $fotoWajah);
            } catch (RuntimeException $e) {
                $model->delete($insertId);
                throw new RuntimeException('Sinkronisasi wajah ke gateway gagal: ' . $e->getMessage());
            }
        }

        return $insertId;
    }

    private function saveToGuruDirect(int $targetId, string $idRfid, string $fotoWajah): void
    {
        $model = new GuruModel();
        $existing = $model->find($targetId);
        if (! $existing) {
            throw new RuntimeException('Data guru tujuan tidak ditemukan.');
        }

        if ($idRfid !== '') {
            $dupGuru = $model
                ->where('id_rfid', $idRfid)
                ->where('id_guru !=', $targetId)
                ->first();
            $dupSiswa = (new SiswaModel())->where('id_rfid', $idRfid)->first();
            if ($dupGuru || $dupSiswa) {
                throw new RuntimeException('ID RFID sudah dipakai data lain. Gunakan RFID berbeda.');
            }
        }

        if ($fotoWajah !== '') {
            try {
                (new FaceGatewayClient())->registerGuru($targetId, (string) $existing['nama'], $fotoWajah);
            } catch (RuntimeException $e) {
                throw new RuntimeException('Sinkronisasi wajah ke gateway gagal: ' . $e->getMessage());
            }
        }

        $payload = ['updated_at' => date('Y-m-d H:i:s')];
        if ($idRfid !== '') {
            $payload['id_rfid'] = $idRfid;
        }
        if ($fotoWajah !== '') {
            $payload['foto_wajah'] = $fotoWajah;
        }

        $model->update($targetId, $payload);
    }

    private function markSessionAssigned(int $sessionId, string $targetType, int $targetId): void
    {
        $model = new IotRegistrationSessionModel();
        $session = $model->find($sessionId);
        if (! $session) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $model->update($sessionId, [
            'status' => 'assigned',
            'target_type' => $targetType,
            'target_id' => $targetId,
            'completed_at' => $now,
            'updated_at' => $now,
            'error_message' => null,
        ]);
    }

    private function resolveSessionForView(int $sessionId): ?array
    {
        if ($sessionId <= 0) {
            return null;
        }

        $session = (new IotRegistrationSessionModel())->find($sessionId);
        if (! $session) {
            return null;
        }

        $device = null;
        if (! empty($session['device_id'])) {
            $device = (new IotDeviceModel())->find((int) $session['device_id']);
        }

        return [
            'id_session' => (int) $session['id_session'],
            'session_token' => (string) $session['session_token'],
            'status' => (string) ($session['status'] ?? ''),
            'captured_rfid' => (string) ($session['captured_rfid'] ?? ''),
            'captured_face' => (string) ($session['captured_face'] ?? ''),
            'captured_at' => (string) ($session['captured_at'] ?? ''),
            'error_message' => (string) ($session['error_message'] ?? ''),
            'created_at' => (string) ($session['created_at'] ?? ''),
            'updated_at' => (string) ($session['updated_at'] ?? ''),
            'device' => [
                'id_device' => (int) ($device['id_device'] ?? 0),
                'device_code' => (string) ($device['device_code'] ?? '-'),
                'device_name' => (string) ($device['device_name'] ?? ''),
                'status_mode' => (string) ($device['status_mode'] ?? 'attendance'),
                'is_online' => $device ? $this->isDeviceOnline($device) : false,
                'last_seen_human' => $device ? $this->deviceLastSeenHuman($device) : 'Belum terhubung',
            ],
        ];
    }

    private function decorateDevices(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'id_device' => (int) $row['id_device'],
                'device_code' => (string) ($row['device_code'] ?? '-'),
                'device_name' => (string) ($row['device_name'] ?? ''),
                'status_mode' => (string) ($row['status_mode'] ?? 'attendance'),
                'is_online' => $this->isDeviceOnline($row),
                'last_seen_human' => $this->deviceLastSeenHuman($row),
                'last_message' => (string) ($row['last_message'] ?? ''),
            ];
        }

        return $result;
    }

    private function expireStaleSessions(): void
    {
        $timeout = max(60, (int) config('IotDevice')->registerSessionTimeoutSec);
        $threshold = date('Y-m-d H:i:s', time() - $timeout);
        $model = new IotRegistrationSessionModel();

        $rows = $model
            ->where('status', 'waiting_device')
            ->where('created_at <', $threshold)
            ->findAll();

        if (empty($rows)) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        foreach ($rows as $row) {
            $model->update((int) $row['id_session'], [
                'status' => 'expired',
                'error_message' => 'Timeout menunggu capture dari alat.',
                'completed_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function deviceOnlineWindowSec(): int
    {
        /** @var IotDevice $cfg */
        $cfg = config('IotDevice');

        return max(20, (int) $cfg->deviceOnlineWindowSec);
    }

    private function isDeviceOnline(array $device): bool
    {
        $lastSeen = trim((string) ($device['last_seen_at'] ?? ''));
        if ($lastSeen === '') {
            return false;
        }

        $ts = strtotime($lastSeen);
        if ($ts === false) {
            return false;
        }

        return (time() - $ts) <= $this->deviceOnlineWindowSec();
    }

    private function deviceLastSeenHuman(array $device): string
    {
        $lastSeen = trim((string) ($device['last_seen_at'] ?? ''));
        if ($lastSeen === '') {
            return 'Belum pernah heartbeat';
        }

        $ts = strtotime($lastSeen);
        if ($ts === false) {
            return $lastSeen;
        }

        $seconds = max(0, time() - $ts);
        if ($seconds < 60) {
            return $seconds . ' detik lalu';
        }
        if ($seconds < 3600) {
            return floor($seconds / 60) . ' menit lalu';
        }
        if ($seconds < 86400) {
            return floor($seconds / 3600) . ' jam lalu';
        }

        return floor($seconds / 86400) . ' hari lalu';
    }
}
