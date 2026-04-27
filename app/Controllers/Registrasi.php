<?php

namespace App\Controllers;

use App\Libraries\LaravelApiClient;

class Registrasi extends BaseController
{
    private $client;

    public function __construct()
    {
        $this->client = new LaravelApiClient();
    }

    public function index()
    {
        $guruList = $this->client->get('guru') ?: [];
        $devices = $this->client->get('iot-admin/devices') ?: [];
        $sessions = $this->client->get('iot-admin/sessions') ?: [];

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
        $selectedSiswaName = trim((string) old('nama_siswa', (string) $this->request->getGet('nama_siswa')));

        if ($selectedType === 'siswa' && $selectedSiswaName === '' && $selectedTargetId > 0) {
            $siswa = $this->client->get('siswa/' . $selectedTargetId);
            if ($siswa && !isset($siswa['message'])) {
                $selectedSiswaName = $siswa['nama'];
            }
        }

        return view('registrasi_admin', [
            'devices' => $devices,
            'activeSession' => $activeSession,
            'initialRfid' => $initialRfid,
            'initialFace' => $initialFace,
            'onlineWindowSec' => 20,
            'guruList' => $guruList,
            'selectedType' => $selectedType,
            'selectedTargetId' => $selectedTargetId,
            'selectedSiswaName' => $selectedSiswaName,
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
        $sessions = $this->client->get('iot-admin/sessions') ?: [];
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
        $payload = [
            'target_type' => $this->request->getPost('target_type'),
            'nama_siswa' => $this->request->getPost('nama_siswa'),
            'target_id' => $this->request->getPost('target_id'),
            'id_rfid' => $this->request->getPost('id_rfid'),
            'foto_wajah' => $this->request->getPost('foto_wajah'),
        ];

        $sessionId = (int) $this->request->getPost('registration_session_id');

        if ($sessionId > 0) {
            $response = $this->client->post('iot-admin/session/' . $sessionId . '/save', $payload);
            if (isset($response['message']) && $response['message'] === 'Saved') {
                return redirect()->to('/admin/registrasi')->with('success', 'Registrasi berhasil disimpan.');
            }
        }

        return redirect()->back()->withInput()->with('error', 'Gagal menyimpan registrasi.');
    }

    public function pemetaan()
    {
        $candidates = $this->client->get('iot-admin/candidates') ?: [];
        $pendingList = array_filter($candidates, fn($c) => $c['status'] === 'pending');
        $mappedRecent = array_filter($candidates, fn($c) => $c['status'] === 'mapped');

        $siswaList = $this->client->get('siswa') ?: [];
        $guruList = $this->client->get('guru') ?: [];

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
}
