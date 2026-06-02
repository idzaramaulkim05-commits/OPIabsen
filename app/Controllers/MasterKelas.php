<?php

namespace App\Controllers;

use App\Libraries\LaravelApiClient;

class MasterKelas extends BaseController
{
    private $client;

    public function __construct()
    {
        $this->client = new LaravelApiClient();
    }

    public function index()
    {
        $kelas = $this->safeList($this->client->get('master-kelas'));
        $siswa = $this->safeList($this->client->get('siswa'));

        return view('data_kelas', [
            'kelas' => $kelas,
            'usageMap' => $this->buildUsageMap($siswa),
        ]);
    }

    public function simpan()
    {
        $namaKelas = trim((string) $this->request->getPost('nama_kelas'));

        $response = $this->client->post('master-kelas', ['nama_kelas' => $namaKelas]);

        if (isset($response['message']) && !isset($response['id_kelas'])) {
            return redirect()->back()->withInput()->with('error', $response['message']);
        }

        return redirect()->to('/master-data/kelas')->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        $namaKelas = trim((string) $this->request->getPost('nama_kelas'));

        $response = $this->client->put('master-kelas/' . $id, ['nama_kelas' => $namaKelas]);

        if (isset($response['message']) && !isset($response['id_kelas'])) {
            return redirect()->back()->withInput()->with('error', $response['message']);
        }

        return redirect()->to('/master-data/kelas')->with('success', 'Data kelas berhasil diperbarui.');
    }

    public function hapus(int $id)
    {
        $kelas = $this->client->get('master-kelas/' . $id);
        if (! is_array($kelas) || isset($kelas['message'])) {
            return redirect()->to('/master-data/kelas')->with('error', 'Data kelas tidak ditemukan.');
        }

        $namaKelas = trim((string) ($kelas['nama_kelas'] ?? ''));
        $usageMap = $this->buildUsageMap($this->safeList($this->client->get('siswa')));
        $usedCount = (int) ($usageMap[$namaKelas] ?? 0);

        if ($usedCount > 0) {
            return redirect()->to('/master-data/kelas')->with('error', 'Kelas ' . $namaKelas . ' masih dipakai oleh ' . $usedCount . ' siswa dan tidak bisa dihapus.');
        }

        $response = $this->client->delete('master-kelas/' . $id);

        if (isset($response['message']) && $response['message'] !== 'Deleted') {
            return redirect()->to('/master-data/kelas')->with('error', $response['message']);
        }

        return redirect()->to('/master-data/kelas')->with('success', 'Data kelas berhasil dihapus.');
    }

    private function buildUsageMap(array $siswa): array
    {
        $usageMap = [];

        foreach ($siswa as $item) {
            if (! is_array($item)) {
                continue;
            }

            $kelas = trim((string) ($item['kelas'] ?? ''));
            if ($kelas === '') {
                continue;
            }

            $usageMap[$kelas] = (int) ($usageMap[$kelas] ?? 0) + 1;
        }

        return $usageMap;
    }

    private function safeList($response): array
    {
        if (! is_array($response) || array_key_exists('message', $response)) {
            return [];
        }

        return array_values($response);
    }
}
