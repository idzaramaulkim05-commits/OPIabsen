<?php

namespace App\Libraries;

use Config\FaceGateway;
use RuntimeException;

class FaceGatewayClient
{
    private FaceGateway $config;

    public function __construct(?FaceGateway $config = null)
    {
        /** @var FaceGateway $resolved */
        $resolved = $config ?? config('FaceGateway');
        $this->config = $resolved;
    }

    public function registerSiswa(int $idSiswa, string $namaSiswa, string $imageBase64): array
    {
        $employeeId = $this->expectedEmployeeIdSiswa($idSiswa);
        $employeeName = 'SISWA - ' . trim($namaSiswa);

        return $this->registerFace($employeeId, $employeeName, $imageBase64);
    }

    public function registerGuru(int $idGuru, string $namaGuru, string $imageBase64): array
    {
        $employeeId = $this->expectedEmployeeIdGuru($idGuru);
        $employeeName = 'GURU - ' . trim($namaGuru);

        return $this->registerFace($employeeId, $employeeName, $imageBase64);
    }

    public function expectedEmployeeIdSiswa(int $idSiswa): int
    {
        return $this->config->siswaNamespaceOffset + $idSiswa;
    }

    public function expectedEmployeeIdGuru(int $idGuru): int
    {
        return $this->config->guruNamespaceOffset + $idGuru;
    }

    public function attendanceFromFile(string $imagePath, string $filename = 'capture.jpg', string $mime = 'image/jpeg'): array
    {
        if (! is_file($imagePath)) {
            throw new RuntimeException('File gambar absensi tidak ditemukan.');
        }

        return $this->postToGateway('/face/attendance', [
            'image' => curl_file_create($imagePath, $mime, $filename),
        ]);
    }

    public function fetchLandmark(string $userType, int $userId): array
    {
        if (! in_array($userType, ['siswa', 'guru'], true) || $userId <= 0) {
            throw new RuntimeException('Parameter landmark tidak valid.');
        }

        $baseURL = rtrim((string) $this->config->baseURL, '/');
        $token = trim((string) $this->config->bearerToken);
        if ($baseURL === '' || $token === '') {
            throw new RuntimeException('Konfigurasi Face Gateway belum lengkap.');
        }

        try {
            $client = service('curlrequest', [
                'timeout' => $this->config->timeout,
                'http_errors' => false,
            ]);
            $response = $client->get($baseURL . '/face/landmark', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ],
                'query' => [
                    'user_type' => $userType,
                    'user_id' => $userId,
                ],
            ]);
        } catch (\Throwable $e) {
            throw new RuntimeException('Gagal mengambil landmark wajah: ' . $e->getMessage(), 0, $e);
        }

        $statusCode = $response->getStatusCode();
        $payload = json_decode((string) $response->getBody(), true);
        if (! is_array($payload)) {
            return [
                'status' => 'error',
                'message' => 'Respons landmark wajah tidak valid.',
                'data' => null,
            ];
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            return [
                'status' => 'error',
                'message' => (string) ($payload['message'] ?? 'Gagal mengambil landmark wajah.'),
                'data' => $payload['data'] ?? null,
            ];
        }

        $data = is_array($payload['data'] ?? null) ? $payload['data'] : null;
        $hasLandmark = (bool) (($data['has_landmark'] ?? false) === true);
        $landmarkStatus = trim((string) ($data['landmark_status'] ?? ''));
        if ($landmarkStatus === '') {
            $landmarkStatus = $hasLandmark ? 'valid' : 'missing';
        }
        if (is_array($data)) {
            $data = $this->normalizeImageUrlField($data, 'vector_image_url');
            $data = $this->normalizeImageUrlField($data, 'overlay_image_url');
        }
        $status = $hasLandmark ? 'ok' : 'empty';
        if ($landmarkStatus === 'invalid') {
            $status = 'invalid';
        }

        return [
            'status' => $status,
            'found' => $hasLandmark,
            'message' => (string) ($payload['message'] ?? 'OK'),
            'data' => $data,
        ];
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function normalizeImageUrlField(array $data, string $field): array
    {
        $raw = trim((string) ($data[$field] ?? ''));
        if ($raw === '' || str_starts_with($raw, 'data:image/')) {
            return $data;
        }

        $fetchUrl = $raw;
        if (str_starts_with($raw, '/')) {
            $base = rtrim((string) $this->config->baseURL, '/');
            $parts = parse_url($base);
            $scheme = (string) ($parts['scheme'] ?? 'http');
            $host = (string) ($parts['host'] ?? '');
            $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
            if ($host !== '') {
                $fetchUrl = $scheme . '://' . $host . $port . $raw;
            }
        }

        try {
            $client = service('curlrequest', [
                'timeout' => $this->config->timeout,
                'http_errors' => false,
            ]);
            $response = $client->get($fetchUrl);
            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                return $data;
            }
            $bytes = (string) $response->getBody();
            if ($bytes === '') {
                return $data;
            }
            $contentType = (string) $response->getHeaderLine('Content-Type');
            if ($contentType === '') {
                $contentType = 'image/svg+xml';
            }
            $data[$field] = 'data:' . $contentType . ';base64,' . base64_encode($bytes);
        } catch (\Throwable) {
            // keep original url
        }

        return $data;
    }

    private function registerFace(int $employeeId, string $employeeName, string $imageBase64): array
    {
        $image = $this->decodeImage($imageBase64);

        try {
            return $this->postToGateway('/face/register', [
                'employee_id' => (string) $employeeId,
                'name' => substr($employeeName, 0, 255),
                'image' => curl_file_create($image['path'], $image['mime'], $image['filename']),
            ]);
        } finally {
            if (is_file($image['path'])) {
                @unlink($image['path']);
            }
        }
    }

    private function postToGateway(string $path, array $multipart): array
    {
        $baseURL = rtrim((string) $this->config->baseURL, '/');
        $token = trim((string) $this->config->bearerToken);

        if ($baseURL === '') {
            throw new RuntimeException('FACE Gateway base URL belum dikonfigurasi.');
        }

        if ($token === '') {
            throw new RuntimeException('FACE Gateway bearer token belum dikonfigurasi.');
        }

        try {
            $client = service('curlrequest', [
                'timeout' => $this->config->timeout,
                'http_errors' => false,
            ]);

            $response = $client->post($baseURL . $path, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ],
                'multipart' => $multipart,
            ]);
        } catch (\Throwable $e) {
            throw new RuntimeException('Gateway Face Recognition tidak bisa diakses: ' . $e->getMessage(), 0, $e);
        }

        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();
        $payload = json_decode($body, true);

        if ($statusCode < 200 || $statusCode >= 300) {
            $message = 'Sinkronisasi ke gateway gagal.';
            if (is_array($payload) && isset($payload['message']) && is_string($payload['message'])) {
                $message = $payload['message'];
            }

            throw new RuntimeException($message);
        }

        if (! is_array($payload)) {
            throw new RuntimeException('Gateway mengembalikan respons yang tidak valid.');
        }

        return $payload;
    }

    /**
     * @return array{path:string,mime:string,filename:string}
     */
    private function decodeImage(string $imageBase64): array
    {
        $imageBase64 = trim($imageBase64);
        if ($imageBase64 === '') {
            throw new RuntimeException('Data foto wajah kosong.');
        }

        $extension = 'png';
        $payload = $imageBase64;

        if (preg_match('/^data:image\/(png|jpe?g|webp);base64,(.+)$/i', $imageBase64, $matches) === 1) {
            $extension = strtolower($matches[1]);
            if ($extension === 'jpeg') {
                $extension = 'jpg';
            }
            $payload = $matches[2];
        }

        $binary = base64_decode(str_replace(' ', '+', $payload), true);
        if ($binary === false) {
            throw new RuntimeException('Format foto wajah tidak valid (base64).');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'face_');
        if ($tmp === false) {
            throw new RuntimeException('Gagal menyiapkan file sementara untuk foto wajah.');
        }

        $path = $tmp . '.' . $extension;
        if (! @rename($tmp, $path)) {
            $path = $tmp;
        }

        if (file_put_contents($path, $binary) === false) {
            @unlink($tmp);
            @unlink($path);
            throw new RuntimeException('Gagal menulis file foto sementara.');
        }

        $mime = match ($extension) {
            'jpg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        return [
            'path' => $path,
            'mime' => $mime,
            'filename' => 'face_' . date('Ymd_His') . '.' . $extension,
        ];
    }
}
