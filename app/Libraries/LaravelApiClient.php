<?php

namespace App\Libraries;

use CodeIgniter\Config\Services;

class LaravelApiClient
{
    private $client;
    private $baseUrl;
    private $token;

    public function __construct()
    {
        $this->baseUrl = getenv('faceGateway.baseURL') ?: 'http://127.0.0.1:8000/api';
        $this->token = getenv('faceGateway.bearerToken') ?: 'absensiiot2026-token';

        $this->client = Services::curlrequest([
            'baseURI' => rtrim($this->baseUrl, '/') . '/',
            'timeout' => 5,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Accept'        => 'application/json',
            ],
            'http_errors' => false,
        ]);
    }

    public function get(string $endpoint)
    {
        return $this->request('get', $endpoint);
    }

    public function post(string $endpoint, array $data)
    {
        return $this->request('post', $endpoint, ['json' => $data]);
    }

    public function put(string $endpoint, array $data)
    {
        return $this->request('put', $endpoint, ['json' => $data]);
    }

    public function delete(string $endpoint)
    {
        return $this->request('delete', $endpoint);
    }

    private function request(string $method, string $endpoint, array $options = []): array
    {
        try {
            $response = $this->client->{$method}($endpoint, $options);
        } catch (\Throwable $exception) {
            log_message('error', 'Laravel API request failed: {message}', ['message' => $exception->getMessage()]);

            return [
                'message' => 'Server API tidak dapat diakses. Pastikan layanan backend Laravel aktif.',
                '_api_error' => true,
            ];
        }

        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);
        if (! is_array($decoded)) {
            log_message('error', 'Laravel API returned invalid JSON from {endpoint}: {body}', [
                'endpoint' => $endpoint,
                'body' => substr($body, 0, 500),
            ]);

            return [
                'message' => 'Server API mengembalikan respons tidak valid.',
                '_api_error' => true,
                '_status' => $response->getStatusCode(),
            ];
        }

        if (! array_is_list($decoded)) {
            $decoded['_status'] = $response->getStatusCode();
        }

        return $decoded;
    }
}
