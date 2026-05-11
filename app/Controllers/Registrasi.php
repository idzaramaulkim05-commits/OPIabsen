<?php

namespace App\Controllers;

use App\Libraries\LaravelApiClient;
use Config\IotDevice;

class Registrasi extends BaseController
{
    private $client;

    public function __construct()
    {
        $this->client = new LaravelApiClient();
    }

    public function index()
    {
        $devices = $this->safeList($this->client->get('iot-admin/devices'));
        $sessions = $this->safeList($this->client->get('iot-admin/sessions'));

        $activeSession = null;
        $requestedSessionId = (int) $this->request->getGet('session');

        if ($requestedSessionId > 0) {
            foreach ($sessions as $s) {
                if ($s['id_session'] == $requestedSessionId) {
                    $activeSession = $s;
                    break;
                }
            }
        } else {
            foreach ($sessions as $s) {
                if (in_array($s['status'], ['waiting_device', 'captured'])) {
                    $activeSession = $s;
                    break;
                }
            }
        }

        $initialRfid = $activeSession['captured_rfid'] ?? '';
        $initialFace = $activeSession['captured_face'] ?? '';

        $selectedType = trim((string) old('target_type', (string) $this->request->getGet('target_type')));
        if (!in_array($selectedType, ['siswa', 'guru'])) $selectedType = 'siswa';

        $selectedTargetId = (int) old('target_id', (int) $this->request->getGet('target_id'));
        $formValues = $this->resolveFormValues($selectedType, $selectedTargetId);

        $kelasExtras = [];
        if (! empty($formValues['kelas'])) {
            $kelasExtras[] = $formValues['kelas'];
        }
        if (! empty($formValues['kelas_wali'])) {
            $kelasExtras[] = $formValues['kelas_wali'];
        }

        $kelasList = $this->getMasterKelasList($kelasExtras);

        /** @var IotDevice $cfg */
        $cfg = config('IotDevice');
        $onlineWindowSec = max(20, (int) $cfg->deviceOnlineWindowSec);

        return view('registrasi_admin', [
            'devices' => $devices,
            'activeSession' => $activeSession,
            'initialRfid' => $initialRfid,
            'initialFace' => $initialFace,
            'onlineWindowSec' => $onlineWindowSec,
            'kelasList' => $kelasList,
            'selectedType' => $selectedType,
            'selectedTargetId' => $selectedTargetId,
            'formValues' => $formValues,
        ]);
    }

    public function mulaiModeRegis()
    {
        $deviceId = (int) $this->request->getPost('device_id');
        if ($deviceId <= 0) return redirect()->to('/admin/registrasi')->with('error', 'Pilih alat.');

        $response = $this->client->post('iot-admin/session/start', ['device_id' => $deviceId]);

        if (isset($response['id_session'])) {
            return redirect()->to('/admin/registrasi?session=' . $response['id_session'])->with('success', 'Mode registrasi aktif.');
        }

        return redirect()->to('/admin/registrasi')->with('error', 'Gagal memulai mode registrasi.');
    }

    public function statusSesi(int $idSession)
    {
        $sessions = $this->safeList($this->client->get('iot-admin/sessions'));
        $activeSession = null;
        foreach ($sessions as $s) {
            if ($s['id_session'] == $idSession) {
                $activeSession = $s;
                break;
            }
        }

        if (!$activeSession) return $this->response->setJSON(['status' => 'error', 'message' => 'Not found']);
        return $this->response->setJSON(['status' => 'ok', 'data' => $activeSession]);
    }

    public function batalSesi(int $idSession)
    {
        $this->client->post('iot-admin/session/' . $idSession . '/cancel', []);
        return redirect()->to('/admin/registrasi')->with('success', 'Session registrasi dibatalkan.');
    }

    public function simpan()
    {
        $targetType = trim((string) $this->request->getPost('target_type'));
        if (! in_array($targetType, ['siswa', 'guru'], true)) {
            return redirect()->back()->withInput()->with('error', 'Tipe data tidak valid.');
        }
        if ($targetType === 'guru') {
            return redirect()->back()->withInput()->with('error', 'Registrasi RFID/wajah hanya untuk siswa. Guru hanya monitoring.');
        }

        $sessionId = (int) $this->request->getPost('registration_session_id');
        $targetId = (int) $this->request->getPost('target_id');
        $nama = trim((string) $this->request->getPost('nama'));

        if ($nama === '') {
            return redirect()->back()->withInput()->with('error', 'Nama wajib diisi.');
        }

        $idRfid = trim((string) $this->request->getPost('id_rfid'));
        $fotoWajah = trim((string) $this->request->getPost('foto_wajah'));
        $masterKelas = $this->getMasterKelasList();
        $savedId = 0;

        if ($targetType === 'siswa') {
            $kelas = trim((string) $this->request->getPost('kelas'));
            $kelasBaru = trim((string) $this->request->getPost('kelas_baru'));
            $kelasFinal = $kelasBaru !== '' ? $kelasBaru : $kelas;

            $kelasError = $this->createMasterKelasIfNeeded($kelasFinal, $masterKelas);
            if ($kelasError !== null) {
                return redirect()->back()->withInput()->with('error', $kelasError);
            }

            $payload = [
                'nama' => $nama,
                'no_induk' => $this->nullableString($this->request->getPost('no_induk')),
                'kelas' => $kelasFinal !== '' ? $kelasFinal : null,
                'alamat' => $this->nullableString($this->request->getPost('alamat')),
                'id_rfid' => $idRfid !== '' ? $idRfid : null,
                'foto_wajah' => $fotoWajah !== '' ? $fotoWajah : null,
            ];

            if ($targetId > 0) {
                $payload = $this->stripNull($payload);
                $response = $this->client->put('siswa/' . $targetId, $payload);
                if ($this->apiFailed($response, 'id')) {
                    return redirect()->back()->withInput()->with('error', $this->apiErrorMessage($response));
                }
                $savedId = $targetId;
            } else {
                $response = $this->client->post('siswa', $payload);
                if ($this->apiFailed($response, 'id')) {
                    return redirect()->back()->withInput()->with('error', $this->apiErrorMessage($response));
                }
                $savedId = (int) ($response['id'] ?? 0);
            }
        } else {
            $nip = trim((string) $this->request->getPost('nip'));
            $username = trim((string) $this->request->getPost('username'));

            if ($username === '') {
                return redirect()->back()->withInput()->with('error', 'Username wajib diisi.');
            }

            $kelasWali = trim((string) $this->request->getPost('kelas_wali'));
            $kelasWaliBaru = trim((string) $this->request->getPost('kelas_wali_baru'));
            $kelasWaliFinal = $kelasWaliBaru !== '' ? $kelasWaliBaru : $kelasWali;
            $isWali = $this->request->getPost('is_wali_kelas') ? 1 : 0;
            if ($kelasWaliFinal !== '') {
                $isWali = 1;
            }

            if ($isWali === 1 && $kelasWaliFinal === '') {
                return redirect()->back()->withInput()->with('error', 'Kelas wali wajib diisi.');
            }

            if ($isWali === 1) {
                $kelasError = $this->createMasterKelasIfNeeded($kelasWaliFinal, $masterKelas);
                if ($kelasError !== null) {
                    return redirect()->back()->withInput()->with('error', $kelasError);
                }
            } else {
                $kelasWaliFinal = '';
            }

            $payload = [
                'nama' => $nama,
                'nip' => $nip,
                'username' => $username,
                'kelas_wali' => $isWali === 1 && $kelasWaliFinal !== '' ? $kelasWaliFinal : null,
                'is_wali_kelas' => $isWali,
                'id_rfid' => $idRfid !== '' ? $idRfid : null,
                'foto_wajah' => $fotoWajah !== '' ? $fotoWajah : null,
            ];

            if ($targetId > 0) {
                $plainPassword = trim((string) $this->request->getPost('password'));
                if ($plainPassword !== '') {
                    $payload['password'] = password_hash($plainPassword, PASSWORD_DEFAULT);
                }

                $payload = $this->stripNull($payload);
                if ($isWali === 0) {
                    $payload['kelas_wali'] = null;
                }
                $response = $this->client->put('guru/' . $targetId, $payload);
                if ($this->apiFailed($response, 'id_guru')) {
                    return redirect()->back()->withInput()->with('error', $this->apiErrorMessage($response));
                }
                $savedId = $targetId;
            } else {
                $plainPassword = trim((string) $this->request->getPost('password'));
                if ($plainPassword === '') {
                    return redirect()->back()->withInput()->with('error', 'Password wajib diisi untuk guru baru.');
                }
                $payload['password'] = password_hash($plainPassword, PASSWORD_DEFAULT);

                $response = $this->client->post('guru', $payload);
                if ($this->apiFailed($response, 'id_guru')) {
                    return redirect()->back()->withInput()->with('error', $this->apiErrorMessage($response));
                }
                $savedId = (int) ($response['id_guru'] ?? 0);
            }
        }

        if ($savedId <= 0) {
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan data tujuan.');
        }

        if ($sessionId > 0) {
            $sessionPayload = [
                'target_type' => $targetType,
                'target_id' => $savedId,
                'nama_siswa' => $targetType === 'siswa' ? $nama : null,
                'id_rfid' => $idRfid !== '' ? $idRfid : null,
                'foto_wajah' => $fotoWajah !== '' ? $fotoWajah : null,
            ];

            $response = $this->client->post('iot-admin/session/' . $sessionId . '/save', $this->stripNull($sessionPayload));
            if (! isset($response['message']) || $response['message'] !== 'Saved') {
                return redirect()->to('/admin/registrasi')->with('error', 'Data tersimpan, tetapi gagal menutup sesi registrasi.');
            }
        }

        return redirect()->to('/admin/registrasi')->with('success', 'Registrasi berhasil disimpan.');
    }

    public function pemetaan()
    {
        $candidates = $this->safeList($this->client->get('iot-admin/candidates'));
        $pendingList = array_filter($candidates, fn($c) => $c['status'] === 'pending');
        $mappedRecent = array_filter($candidates, fn($c) => $c['status'] === 'mapped');

        $siswaList = $this->safeList($this->client->get('siswa'));
        $guruList = $this->safeList($this->client->get('guru'));

        $kelasDariSiswa = array_unique(array_column($siswaList, 'kelas'));
        $kelasList = $this->getMasterKelasList($kelasDariSiswa);

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
        $payload = [
            'candidate_id' => $this->request->getPost('candidate_id'),
            'target_type' => $this->request->getPost('target_type'),
            'target_id' => $this->request->getPost('target_id'),
            'kelas_siswa' => $this->request->getPost('kelas_siswa'),
        ];

        $response = $this->client->post('iot-admin/candidates/map', $payload);

        if (isset($response['message']) && $response['message'] === 'Mapped') {
            return redirect()->to('/admin/registrasi/pemetaan')->with('success', 'Pemetaan berhasil.');
        }

        return redirect()->back()->withInput()->with('error', 'Gagal memetakan.');
    }

    private function resolveFormValues(string $targetType, int $targetId): array
    {
        if ($targetId <= 0) {
            return [];
        }

        $endpoint = $targetType === 'guru' ? 'guru/' . $targetId : 'siswa/' . $targetId;
        $response = $this->client->get($endpoint);

        if (! is_array($response) || isset($response['message'])) {
            return [];
        }

        if ($targetType === 'guru') {
            return [
                'nama' => (string) ($response['nama'] ?? ''),
                'nip' => (string) ($response['nip'] ?? ''),
                'username' => (string) ($response['username'] ?? ''),
                'kelas_wali' => (string) ($response['kelas_wali'] ?? ''),
                'is_wali_kelas' => (int) ($response['is_wali_kelas'] ?? 0),
                'id_rfid' => (string) ($response['id_rfid'] ?? ''),
                'foto_wajah' => (string) ($response['foto_wajah'] ?? ''),
            ];
        }

        return [
            'nama' => (string) ($response['nama'] ?? ''),
            'no_induk' => (string) ($response['no_induk'] ?? ''),
            'kelas' => (string) ($response['kelas'] ?? ''),
            'alamat' => (string) ($response['alamat'] ?? ''),
            'id_rfid' => (string) ($response['id_rfid'] ?? ''),
            'foto_wajah' => (string) ($response['foto_wajah'] ?? ''),
        ];
    }

    private function createMasterKelasIfNeeded(string $kelas, array &$masterList): ?string
    {
        $kelas = trim($kelas);
        if ($kelas === '') {
            return null;
        }

        if (in_array($kelas, $masterList, true)) {
            return null;
        }

        $response = $this->client->post('master-kelas', ['nama_kelas' => $kelas]);
        if (isset($response['message']) && ! isset($response['id_kelas'])) {
            return (string) $response['message'];
        }

        $masterList[] = $kelas;
        return null;
    }

    private function stripNull(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if ($value === null) {
                unset($payload[$key]);
            }
        }

        return $payload;
    }

    private function nullableString($value): ?string
    {
        $value = trim((string) $value);
        return $value !== '' ? $value : null;
    }

    private function apiFailed($response, string $idKey): bool
    {
        if (! is_array($response)) {
            return true;
        }

        if (isset($response['message']) && ! isset($response[$idKey])) {
            return true;
        }

        return false;
    }

    private function apiErrorMessage($response): string
    {
        if (is_array($response) && isset($response['message'])) {
            return (string) $response['message'];
        }

        return 'Gagal menyimpan data ke API.';
    }

    private function safeList($response): array
    {
        if (! is_array($response)) {
            return [];
        }

        if (array_key_exists('message', $response)) {
            return [];
        }

        return array_values($response);
    }
}
