<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class FaceGateway extends BaseConfig
{
    public function __construct()
    {
        parent::__construct();

        $envBaseUrl = trim((string) env('faceGateway.baseURL', ''));
        if ($envBaseUrl !== '') {
            $this->baseURL = $envBaseUrl;
        }

        $envToken = trim((string) env('faceGateway.bearerToken', ''));
        if ($envToken !== '') {
            $this->bearerToken = $envToken;
        } else {
            log_message('warning', 'FaceGateway bearer token kosong. Cek faceGateway.bearerToken di .env');
        }

        $envTimeout = (int) env('faceGateway.timeout', 0);
        if ($envTimeout > 0) {
            $this->timeout = $envTimeout;
        }
    }

    /**
     * Base URL Laravel Gateway tanpa trailing slash.
     * Contoh: http://127.0.0.1:8000/api
     */
    public string $baseURL = 'http://127.0.0.1:8000/api';

    /**
     * Bearer token yang harus sama dengan FACE_GATEWAY_BEARER_TOKEN di Laravel Gateway.
     */
    public string $bearerToken = '';

    /**
     * Timeout request ke gateway (detik).
     */
    public int $timeout = 12;

    /**
     * Namespace ID agar data siswa dan guru tidak bentrok di tabel employees gateway.
     */
    public int $siswaNamespaceOffset = 1000000000000;
    public int $guruNamespaceOffset = 2000000000000;
}
