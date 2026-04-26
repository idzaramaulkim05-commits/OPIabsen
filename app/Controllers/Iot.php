<?php

namespace App\Controllers;

use App\Libraries\FaceGatewayClient;
use App\Models\GuruModel;
use App\Models\IotDeviceModel;
use App\Models\IotRegistrationSessionModel;
use App\Models\IotScanLogModel;
use App\Models\JadwalModel;
use App\Models\PresensiModel;
use App\Models\SiswaModel;
use Config\IotDevice;
use RuntimeException;

class Iot extends BaseController
{
    public function health()
    {
        $tokenCheck = $this->authorizeDevice();
        if ($tokenCheck !== null) {
            return $tokenCheck;
        }

        return $this->response->setJSON([
            'status' => 'ok',
            'message' => 'IoT endpoint ready.',
            'server_time' => date('c'),
        ]);
    }

    public function scan()
    {
        $tokenCheck = $this->authorizeDevice();
        if ($tokenCheck !== null) {
            return $tokenCheck;
        }

        $rfidUid = trim((string) $this->request->getPost('rfid_uid'));
        $image = $this->request->getFile('image');
        $hasImage = $image && $image->isValid();

        if ($rfidUid === '' && ! $hasImage) {
            return $this->jsonError(422, 'Isi minimal salah satu data: rfid_uid atau image.');
        }

        if ($rfidUid !== '' && $hasImage) {
            return $this->scanWithRfidAndFace($rfidUid, $image);
        }

        if ($rfidUid !== '') {
            return $this->scanWithRfidOnly($rfidUid);
        }

        return $this->scanWithFaceOnly($image);
    }

    private function scanWithRfidAndFace(string $rfidUid, $image)
    {
        $identity = $this->findIdentityByRfid($rfidUid);
        if ($identity === null) {
            $this->saveScanLog([
                'entity_type' => 'unknown',
                'entity_id' => null,
                'rfid_uid' => $rfidUid,
                'expected_employee_id' => null,
                'matched_employee_id' => null,
                'gateway_status' => 'unknown',
                'confidence' => null,
                'result' => 'rejected',
                'message' => 'RFID tidak terdaftar.',
                'raw_response' => null,
            ]);

            return $this->jsonError(404, 'RFID tidak terdaftar di sistem.');
        }

        $faceClient = new FaceGatewayClient();
        $expectedEmployeeId = $this->expectedEmployeeIdByIdentity($identity, $faceClient);

        try {
            $gateway = $faceClient->attendanceFromFile(
                $image->getTempName(),
                $image->getClientName(),
                $image->getClientMimeType() ?: 'image/jpeg'
            );
        } catch (RuntimeException $e) {
            $this->saveScanLog([
                'entity_type' => $identity['type'],
                'entity_id' => (int) $identity['id'],
                'rfid_uid' => $rfidUid,
                'expected_employee_id' => $expectedEmployeeId,
                'matched_employee_id' => null,
                'gateway_status' => 'error',
                'confidence' => null,
                'result' => 'error',
                'message' => $e->getMessage(),
                'raw_response' => null,
            ]);

            return $this->jsonError(502, 'Gateway face recognition gagal: ' . $e->getMessage());
        }

        [$gatewayStatus, $confidence, $matchedEmployeeId] = $this->extractGatewaySummary($gateway);
        $isVerified = $gatewayStatus === 'matched'
            && $matchedEmployeeId !== null
            && $matchedEmployeeId === $expectedEmployeeId;

        $autoPresensi = null;
        if ($isVerified && $identity['type'] === 'siswa' && $this->autoRecordSiswaAttendance()) {
            $autoPresensi = $this->recordSiswaAttendance($identity, 'rfid_face');
        }

        $message = $isVerified
            ? 'Verifikasi RFID + wajah berhasil.'
            : 'Verifikasi gagal. Wajah tidak cocok dengan kartu RFID.';

        $this->saveScanLog([
            'entity_type' => $identity['type'],
            'entity_id' => (int) $identity['id'],
            'rfid_uid' => $rfidUid,
            'expected_employee_id' => $expectedEmployeeId,
            'matched_employee_id' => $matchedEmployeeId,
            'gateway_status' => $gatewayStatus,
            'confidence' => $confidence,
            'result' => $isVerified ? 'verified' : 'rejected',
            'message' => $message,
            'raw_response' => json_encode($gateway, JSON_UNESCAPED_UNICODE),
        ]);

        return $this->response->setStatusCode($isVerified ? 200 : 422)->setJSON([
            'status' => $isVerified ? 'verified' : 'rejected',
            'auth_mode' => 'rfid_face',
            'message' => $message,
            'identity' => [
                'type' => $identity['type'],
                'id' => (int) $identity['id'],
                'name' => $identity['name'],
                'kelas' => $identity['kelas'],
                'rfid_uid' => $rfidUid,
            ],
            'face' => [
                'gateway_status' => $gatewayStatus,
                'confidence' => $confidence,
                'expected_employee_id' => $expectedEmployeeId,
                'matched_employee_id' => $matchedEmployeeId,
            ],
            'presensi' => $autoPresensi,
            'gateway_raw' => $gateway,
            'server_time' => date('c'),
        ]);
    }

    private function scanWithRfidOnly(string $rfidUid)
    {
        $identity = $this->findIdentityByRfid($rfidUid);
        if ($identity === null) {
            $this->saveScanLog([
                'entity_type' => 'unknown',
                'entity_id' => null,
                'rfid_uid' => $rfidUid,
                'expected_employee_id' => null,
                'matched_employee_id' => null,
                'gateway_status' => 'rfid_only',
                'confidence' => null,
                'result' => 'rejected',
                'message' => 'RFID tidak terdaftar.',
                'raw_response' => null,
            ]);

            return $this->jsonError(404, 'RFID tidak terdaftar di sistem.');
        }

        $faceClient = new FaceGatewayClient();
        $expectedEmployeeId = $this->expectedEmployeeIdByIdentity($identity, $faceClient);
        $autoPresensi = null;

        if ($identity['type'] === 'siswa' && $this->autoRecordSiswaAttendance()) {
            $autoPresensi = $this->recordSiswaAttendance($identity, 'rfid_only');
        }

        $message = 'Verifikasi RFID berhasil.';
        $this->saveScanLog([
            'entity_type' => $identity['type'],
            'entity_id' => (int) $identity['id'],
            'rfid_uid' => $rfidUid,
            'expected_employee_id' => $expectedEmployeeId,
            'matched_employee_id' => $expectedEmployeeId,
            'gateway_status' => 'rfid_only',
            'confidence' => null,
            'result' => 'verified',
            'message' => $message,
            'raw_response' => null,
        ]);

        return $this->response->setJSON([
            'status' => 'verified',
            'auth_mode' => 'rfid_only',
            'message' => $message,
            'identity' => [
                'type' => $identity['type'],
                'id' => (int) $identity['id'],
                'name' => $identity['name'],
                'kelas' => $identity['kelas'],
                'rfid_uid' => $rfidUid,
            ],
            'face' => null,
            'presensi' => $autoPresensi,
            'server_time' => date('c'),
        ]);
    }

    private function scanWithFaceOnly($image)
    {
        $faceClient = new FaceGatewayClient();

        try {
            $gateway = $faceClient->attendanceFromFile(
                $image->getTempName(),
                $image->getClientName(),
                $image->getClientMimeType() ?: 'image/jpeg'
            );
        } catch (RuntimeException $e) {
            $this->saveScanLog([
                'entity_type' => 'unknown',
                'entity_id' => null,
                'rfid_uid' => null,
                'expected_employee_id' => null,
                'matched_employee_id' => null,
                'gateway_status' => 'error',
                'confidence' => null,
                'result' => 'error',
                'message' => $e->getMessage(),
                'raw_response' => null,
            ]);

            return $this->jsonError(502, 'Gateway face recognition gagal: ' . $e->getMessage());
        }

        [$gatewayStatus, $confidence, $matchedEmployeeId] = $this->extractGatewaySummary($gateway);
        $identity = $matchedEmployeeId !== null ? $this->findIdentityByEmployeeId($matchedEmployeeId) : null;
        $isVerified = $gatewayStatus === 'matched' && $identity !== null;

        $autoPresensi = null;
        if ($isVerified && $identity['type'] === 'siswa' && $this->autoRecordSiswaAttendance()) {
            $autoPresensi = $this->recordSiswaAttendance($identity, 'face_only');
        }

        $message = $isVerified
            ? 'Verifikasi wajah berhasil.'
            : 'Verifikasi wajah gagal. Wajah tidak dikenal.';

        $this->saveScanLog([
            'entity_type' => $identity['type'] ?? 'unknown',
            'entity_id' => isset($identity['id']) ? (int) $identity['id'] : null,
            'rfid_uid' => null,
            'expected_employee_id' => $matchedEmployeeId,
            'matched_employee_id' => $matchedEmployeeId,
            'gateway_status' => $gatewayStatus,
            'confidence' => $confidence,
            'result' => $isVerified ? 'verified' : 'rejected',
            'message' => $message,
            'raw_response' => json_encode($gateway, JSON_UNESCAPED_UNICODE),
        ]);

        return $this->response->setStatusCode($isVerified ? 200 : 422)->setJSON([
            'status' => $isVerified ? 'verified' : 'rejected',
            'auth_mode' => 'face_only',
            'message' => $message,
            'identity' => $identity ? [
                'type' => $identity['type'],
                'id' => (int) $identity['id'],
                'name' => $identity['name'],
                'kelas' => $identity['kelas'],
                'rfid_uid' => $identity['rfid_uid'] ?? '',
            ] : null,
            'face' => [
                'gateway_status' => $gatewayStatus,
                'confidence' => $confidence,
                'expected_employee_id' => $matchedEmployeeId,
                'matched_employee_id' => $matchedEmployeeId,
            ],
            'presensi' => $autoPresensi,
            'gateway_raw' => $gateway,
            'server_time' => date('c'),
        ]);
    }

    public function deviceHeartbeat()
    {
        $tokenCheck = $this->authorizeDevice();
        if ($tokenCheck !== null) {
            return $tokenCheck;
        }

        $deviceCode = $this->sanitizeDeviceCode((string) $this->request->getPost('device_code'));
        if ($deviceCode === '') {
            return $this->jsonError(422, 'Field device_code wajib diisi.');
        }

        $deviceName = trim((string) $this->request->getPost('device_name'));
        $mode = trim((string) $this->request->getPost('mode'));
        $message = trim((string) $this->request->getPost('message'));
        $firmware = trim((string) $this->request->getPost('firmware_version'));

        $device = $this->upsertDeviceFromPayload($deviceCode, $deviceName, $mode, $message, $firmware);
        $this->expireStaleRegisterSessions((int) $device['id_device']);
        $pending = $this->resolvePendingRegisterSession((int) $device['id_device']);

        return $this->response->setJSON([
            'status' => 'ok',
            'message' => 'Heartbeat accepted.',
            'device' => [
                'id_device' => (int) $device['id_device'],
                'device_code' => (string) $device['device_code'],
                'device_name' => (string) ($device['device_name'] ?? ''),
                'mode' => (string) ($device['status_mode'] ?? 'attendance'),
                'last_seen_at' => (string) ($device['last_seen_at'] ?? ''),
            ],
            'mode' => $pending ? 'register' : 'attendance',
            'register_session' => $pending,
            'server_time' => date('c'),
        ]);
    }

    public function deviceCommand()
    {
        $tokenCheck = $this->authorizeDevice();
        if ($tokenCheck !== null) {
            return $tokenCheck;
        }

        $deviceCode = $this->sanitizeDeviceCode((string) $this->request->getGet('device_code'));
        if ($deviceCode === '') {
            return $this->jsonError(422, 'Query device_code wajib diisi.');
        }

        $deviceName = trim((string) $this->request->getGet('device_name'));
        $mode = trim((string) $this->request->getGet('mode'));
        $message = trim((string) $this->request->getGet('message'));
        $firmware = trim((string) $this->request->getGet('firmware_version'));

        $device = $this->upsertDeviceFromPayload($deviceCode, $deviceName, $mode, $message, $firmware);
        $this->expireStaleRegisterSessions((int) $device['id_device']);
        $pending = $this->resolvePendingRegisterSession((int) $device['id_device']);

        return $this->response->setJSON([
            'status' => 'ok',
            'mode' => $pending ? 'register' : 'attendance',
            'register_session' => $pending,
            'device' => [
                'device_code' => (string) $device['device_code'],
                'mode' => (string) ($device['status_mode'] ?? 'attendance'),
                'last_seen_at' => (string) ($device['last_seen_at'] ?? ''),
            ],
            'server_time' => date('c'),
        ]);
    }

    public function registerCapture()
    {
        $tokenCheck = $this->authorizeDevice();
        if ($tokenCheck !== null) {
            return $tokenCheck;
        }

        $deviceCode = $this->sanitizeDeviceCode((string) $this->request->getPost('device_code'));
        if ($deviceCode === '') {
            return $this->jsonError(422, 'Field device_code wajib diisi.');
        }

        $rfidUid = trim((string) $this->request->getPost('rfid_uid'));
        if ($rfidUid === '') {
            return $this->jsonError(422, 'Field rfid_uid wajib diisi.');
        }

        $sessionToken = trim((string) $this->request->getPost('session_token'));
        if ($sessionToken === '') {
            return $this->jsonError(422, 'Field session_token wajib diisi.');
        }

        $image = $this->request->getFile('image');
        if (! $image || ! $image->isValid()) {
            return $this->jsonError(422, 'File image wajib diisi dan valid.');
        }

        $device = $this->findDeviceByCode($deviceCode);
        if (! $device) {
            return $this->jsonError(404, 'Device belum terdaftar di sistem.');
        }

        $this->expireStaleRegisterSessions((int) $device['id_device']);
        $sessionModel = new IotRegistrationSessionModel();
        $session = $sessionModel
            ->where('device_id', (int) $device['id_device'])
            ->where('session_token', $sessionToken)
            ->first();

        if (! $session) {
            return $this->jsonError(404, 'Session registrasi tidak ditemukan untuk device ini.');
        }

        $status = (string) ($session['status'] ?? '');
        if (! in_array($status, ['waiting_device', 'captured'], true)) {
            return $this->jsonError(409, 'Session registrasi sudah ditutup.');
        }

        try {
            $faceData = $this->convertImageToDataUri($image->getTempName(), $image->getClientMimeType() ?: 'image/jpeg');
        } catch (RuntimeException $e) {
            return $this->jsonError(422, 'Gagal memproses gambar registrasi: ' . $e->getMessage());
        }

        $now = date('Y-m-d H:i:s');
        $sessionModel->update((int) $session['id_session'], [
            'status' => 'captured',
            'captured_rfid' => $rfidUid,
            'captured_face' => $faceData,
            'captured_at' => $now,
            'updated_at' => $now,
            'error_message' => null,
        ]);

        (new IotDeviceModel())->update((int) $device['id_device'], [
            'status_mode' => 'attendance',
            'last_seen_at' => $now,
            'last_ip' => (string) $this->request->getIPAddress(),
            'last_message' => 'Register capture success',
            'updated_at' => $now,
        ]);

        return $this->response->setJSON([
            'status' => 'captured',
            'message' => 'Data registrasi berhasil diterima server.',
            'session' => [
                'id_session' => (int) $session['id_session'],
                'session_token' => (string) $session['session_token'],
                'rfid_uid' => $rfidUid,
                'captured_at' => $now,
            ],
            'server_time' => date('c'),
        ]);
    }

    private function authorizeDevice()
    {
        /** @var IotDevice $cfg */
        $cfg = config('IotDevice');
        $expected = trim((string) $cfg->deviceToken);

        if ($expected === '') {
            return $this->jsonError(500, 'Konfigurasi iotDevice.deviceToken belum diatur di server.');
        }

        $provided = trim((string) $this->request->getHeaderLine('X-Device-Token'));
        if ($provided === '' || ! hash_equals($expected, $provided)) {
            return $this->jsonError(401, 'Unauthorized device token.');
        }

        return null;
    }

    private function autoRecordSiswaAttendance(): bool
    {
        /** @var IotDevice $cfg */
        $cfg = config('IotDevice');

        return (bool) $cfg->autoRecordSiswaAttendance;
    }

    private function resolvePendingRegisterSession(int $deviceId): ?array
    {
        $row = (new IotRegistrationSessionModel())
            ->where('device_id', $deviceId)
            ->where('status', 'waiting_device')
            ->orderBy('id_session', 'DESC')
            ->first();

        if (! $row) {
            return null;
        }

        $createdAt = trim((string) ($row['created_at'] ?? ''));
        $createdTs = $createdAt !== '' ? strtotime($createdAt) : false;
        $expiresAt = null;

        if ($createdTs !== false) {
            $expiresAt = date('Y-m-d H:i:s', $createdTs + $this->registerSessionTimeoutSec());
        }

        return [
            'id_session' => (int) $row['id_session'],
            'session_token' => (string) $row['session_token'],
            'status' => (string) $row['status'],
            'created_at' => $createdAt,
            'command_issued_at' => (string) ($row['command_issued_at'] ?? ''),
            'expires_at' => (string) ($expiresAt ?? ''),
        ];
    }

    private function expireStaleRegisterSessions(int $deviceId): void
    {
        $timeout = $this->registerSessionTimeoutSec();
        if ($timeout <= 0) {
            return;
        }

        $threshold = date('Y-m-d H:i:s', time() - $timeout);
        $model = new IotRegistrationSessionModel();
        $rows = $model
            ->where('device_id', $deviceId)
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
                'error_message' => 'Timeout menunggu capture dari perangkat.',
                'completed_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function registerSessionTimeoutSec(): int
    {
        /** @var IotDevice $cfg */
        $cfg = config('IotDevice');

        $timeout = (int) $cfg->registerSessionTimeoutSec;
        if ($timeout < 60) {
            return 60;
        }

        return $timeout;
    }

    private function sanitizeDeviceCode(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        return (string) preg_replace('/[^A-Za-z0-9._-]/', '', $raw);
    }

    private function upsertDeviceFromPayload(
        string $deviceCode,
        string $deviceName = '',
        string $mode = 'attendance',
        string $message = '',
        string $firmware = ''
    ): array {
        $mode = strtolower(trim($mode));
        if (! in_array($mode, ['attendance', 'register', 'idle'], true)) {
            $mode = 'attendance';
        }

        $model = new IotDeviceModel();
        $existing = $model->where('device_code', $deviceCode)->first();
        $existingName = is_array($existing) ? trim((string) ($existing['device_name'] ?? '')) : '';
        $existingFirmware = is_array($existing) ? trim((string) ($existing['firmware_version'] ?? '')) : '';
        $now = date('Y-m-d H:i:s');

        $payload = [
            'device_name' => $deviceName !== '' ? $deviceName : ($existingName !== '' ? $existingName : $deviceCode),
            'device_type' => 'orangepi-zero3',
            'status_mode' => $mode,
            'last_seen_at' => $now,
            'last_ip' => (string) $this->request->getIPAddress(),
            'last_message' => $message !== '' ? substr($message, 0, 255) : null,
            'firmware_version' => $firmware !== '' ? substr($firmware, 0, 100) : ($existingFirmware !== '' ? $existingFirmware : null),
            'updated_at' => $now,
        ];

        if (! $existing) {
            $payload['device_code'] = $deviceCode;
            $payload['created_at'] = $now;
            $insertId = $model->insert($payload, true);

            return (array) $model->find((int) $insertId);
        }

        $model->update((int) $existing['id_device'], $payload);

        return (array) $model->find((int) $existing['id_device']);
    }

    private function findDeviceByCode(string $deviceCode): ?array
    {
        $row = (new IotDeviceModel())
            ->where('device_code', $deviceCode)
            ->first();

        return is_array($row) ? $row : null;
    }

    private function convertImageToDataUri(string $path, string $mime): string
    {
        if (! is_file($path)) {
            throw new RuntimeException('File image tidak ditemukan.');
        }

        $binary = file_get_contents($path);
        if ($binary === false || $binary === '') {
            throw new RuntimeException('File image kosong atau gagal dibaca.');
        }

        $mime = trim($mime);
        if ($mime === '' || ! str_starts_with(strtolower($mime), 'image/')) {
            $mime = 'image/jpeg';
        }

        return 'data:' . $mime . ';base64,' . base64_encode($binary);
    }

    private function expectedEmployeeIdByIdentity(array $identity, FaceGatewayClient $faceClient): int
    {
        if (($identity['type'] ?? '') === 'guru') {
            return $faceClient->expectedEmployeeIdGuru((int) $identity['id']);
        }

        return $faceClient->expectedEmployeeIdSiswa((int) $identity['id']);
    }

    /**
     * @return array{0:string,1:?float,2:?int}
     */
    private function extractGatewaySummary(array $gateway): array
    {
        $gatewayData = is_array($gateway['data'] ?? null) ? $gateway['data'] : [];
        $gatewayStatus = strtolower((string) ($gatewayData['status'] ?? 'unknown'));
        $confidence = isset($gatewayData['confidence']) ? (float) $gatewayData['confidence'] : null;
        $matchedEmployeeId = isset($gatewayData['employee']['id']) ? (int) $gatewayData['employee']['id'] : null;

        return [$gatewayStatus, $confidence, $matchedEmployeeId];
    }

    private function findIdentityByEmployeeId(int $employeeId): ?array
    {
        $employeeId = (int) $employeeId;
        if ($employeeId <= 0) {
            return null;
        }

        $faceCfg = config('FaceGateway');
        $siswaOffset = (int) $faceCfg->siswaNamespaceOffset;
        $guruOffset = (int) $faceCfg->guruNamespaceOffset;

        if ($employeeId >= $guruOffset) {
            $idGuru = $employeeId - $guruOffset;
            if ($idGuru > 0) {
                $guru = (new GuruModel())->find($idGuru);
                if ($guru) {
                    return [
                        'type' => 'guru',
                        'id' => (int) $guru['id_guru'],
                        'name' => (string) $guru['nama'],
                        'kelas' => null,
                        'rfid_uid' => (string) ($guru['id_rfid'] ?? ''),
                    ];
                }
            }
        }

        if ($employeeId >= $siswaOffset) {
            $idSiswa = $employeeId - $siswaOffset;
            if ($idSiswa > 0) {
                $siswa = (new SiswaModel())->find($idSiswa);
                if ($siswa) {
                    return [
                        'type' => 'siswa',
                        'id' => (int) $siswa['id'],
                        'name' => (string) $siswa['nama'],
                        'kelas' => (string) $siswa['kelas'],
                        'rfid_uid' => (string) ($siswa['id_rfid'] ?? ''),
                    ];
                }
            }
        }

        return null;
    }

    private function findIdentityByRfid(string $rfidUid): ?array
    {
        $siswa = (new SiswaModel())->where('id_rfid', $rfidUid)->first();
        if ($siswa) {
            return [
                'type' => 'siswa',
                'id' => (int) $siswa['id'],
                'name' => (string) $siswa['nama'],
                'kelas' => (string) $siswa['kelas'],
                'rfid_uid' => (string) ($siswa['id_rfid'] ?? ''),
            ];
        }

        $guru = (new GuruModel())->where('id_rfid', $rfidUid)->first();
        if ($guru) {
            return [
                'type' => 'guru',
                'id' => (int) $guru['id_guru'],
                'name' => (string) $guru['nama'],
                'kelas' => null,
                'rfid_uid' => (string) ($guru['id_rfid'] ?? ''),
            ];
        }

        return null;
    }

    private function recordSiswaAttendance(array $identity, string $authMode = 'rfid_face'): array
    {
        $kelas = (string) ($identity['kelas'] ?? '');
        $hariIni = $this->hariIndonesia(date('l'));
        $jamSekarang = date('H:i:s');

        if ($kelas === '') {
            return [
                'saved' => false,
                'reason' => 'Kelas siswa tidak tersedia.',
            ];
        }

        $jadwal = (new JadwalModel())
            ->where('kelas', $kelas)
            ->where('hari', $hariIni)
            ->where('jam_mulai <=', $jamSekarang)
            ->where('jam_selesai >=', $jamSekarang)
            ->orderBy('jam_mulai', 'ASC')
            ->first();

        if (! $jadwal) {
            return [
                'saved' => false,
                'reason' => 'Tidak ada jadwal aktif untuk kelas ' . $kelas . '.',
            ];
        }

        $model = new PresensiModel();
        $tanggal = date('Y-m-d');
        $existing = $model
            ->where('id_siswa', (int) $identity['id'])
            ->where('id_jadwal', (int) $jadwal['id_jadwal'])
            ->where('tanggal', $tanggal)
            ->first();

        $payload = [
            'id_siswa' => (int) $identity['id'],
            'id_guru' => (int) $jadwal['id_guru'],
            'id_jadwal' => (int) $jadwal['id_jadwal'],
            'kelas' => (string) $jadwal['kelas'],
            'tanggal' => $tanggal,
            'jam' => date('H:i:s'),
            'status' => 'hadir',
            'metode' => 'iot',
            'catatan' => $this->presensiCatatanByAuthMode($authMode),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $model->update((int) $existing['id_presensi'], $payload);

            return [
                'saved' => true,
                'updated' => true,
                'id_presensi' => (int) $existing['id_presensi'],
                'id_jadwal' => (int) $jadwal['id_jadwal'],
            ];
        }

        $payload['created_at'] = date('Y-m-d H:i:s');
        $insertId = $model->insert($payload, true);

        return [
            'saved' => true,
            'updated' => false,
            'id_presensi' => (int) $insertId,
            'id_jadwal' => (int) $jadwal['id_jadwal'],
        ];
    }

    private function presensiCatatanByAuthMode(string $authMode): string
    {
        return match (strtolower(trim($authMode))) {
            'rfid_only' => 'Validasi IoT RFID',
            'face_only' => 'Validasi IoT Face Recognition',
            default => 'Validasi IoT RFID + Face',
        };
    }

    private function saveScanLog(array $payload): void
    {
        $payload['request_time'] = date('Y-m-d H:i:s');
        $payload['created_at'] = date('Y-m-d H:i:s');

        try {
            (new IotScanLogModel())->insert($payload);
        } catch (\Throwable) {
            // Jangan gagalkan alur scan jika tabel log belum dimigrasi.
        }
    }

    private function jsonError(int $statusCode, string $message)
    {
        return $this->response->setStatusCode($statusCode)->setJSON([
            'status' => 'error',
            'message' => $message,
            'server_time' => date('c'),
        ]);
    }

    private function hariIndonesia(string $englishDay): string
    {
        $map = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu',
        ];

        return $map[$englishDay] ?? $englishDay;
    }
}
