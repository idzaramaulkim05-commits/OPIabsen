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
        $response = $this->client->get($endpoint);
        return json_decode($response->getBody(), true);
    }

    public function post(string $endpoint, array $data)
    {
        $response = $this->client->post($endpoint, ['json' => $data]);
        return json_decode($response->getBody(), true);
    }

    public function put(string $endpoint, array $data)
    {
        $response = $this->client->put($endpoint, ['json' => $data]);
        return json_decode($response->getBody(), true);
    }

    public function delete(string $endpoint)
    {
        $response = $this->client->delete($endpoint);
        return json_decode($response->getBody(), true);
    }
}
