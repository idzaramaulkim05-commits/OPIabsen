<?php

namespace App\Controllers;

use App\Libraries\LaravelApiClient;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    private $apiClient;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->apiClient = new LaravelApiClient();
    }

    protected function getMasterKelasList(array $extraKelas = []): array
    {
        $response = $this->apiClient->get('master-kelas');
        $master = [];
        
        foreach ($this->safeApiList($response) as $item) {
            if (! is_array($item)) {
                continue;
            }

            if (! empty($item['nama_kelas'])) {
                $master[] = $item['nama_kelas'];
            }
        }

        return $this->mergeKelasList($master, $extraKelas);
    }

    protected function kelasExistsInMaster(string $kelas): bool
    {
        $kelas = trim($kelas);
        if ($kelas === '') return true;

        $masterList = $this->getMasterKelasList();
        return in_array($kelas, $masterList, true);
    }

    protected function mergeKelasList(array ...$lists): array
    {
        $set = [];
        foreach ($lists as $list) {
            foreach ($list as $item) {
                $value = trim((string) $item);
                if ($value !== '') {
                    $set[$value] = true;
                }
            }
        }

        $kelas = array_keys($set);
        sort($kelas, SORT_NATURAL | SORT_FLAG_CASE);

        return $kelas;
    }

    protected function safeApiList($response): array
    {
        if (! is_array($response) || array_key_exists('message', $response)) {
            return [];
        }

        return array_values($response);
    }

    protected function isApiError($response): bool
    {
        return is_array($response) && (bool) ($response['_api_error'] ?? false);
    }

    protected function apiMessage($response, string $fallback): string
    {
        if (is_array($response)) {
            $message = trim((string) ($response['message'] ?? ''));
            if ($message !== '') {
                return $message;
            }
        }

        return $fallback;
    }
}
