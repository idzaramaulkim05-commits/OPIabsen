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
        
        if (is_array($response)) {
            foreach ($response as $item) {
                if (!empty($item['nama_kelas'])) {
                    $master[] = $item['nama_kelas'];
                }
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
}
