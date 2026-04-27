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
        $response = $this->client->get('master-kelas');
        return view('data_kelas', [
            'kelas' => $response ?: [],
            'usageMap' => [], // Disable usage map for now or fetch from API
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
        $response = $this->client->delete('master-kelas/' . $id);

        if (isset($response['message']) && $response['message'] !== 'Deleted') {
            return redirect()->to('/master-data/kelas')->with('error', $response['message']);
        }

        return redirect()->to('/master-data/kelas')->with('success', 'Data kelas berhasil dihapus.');
    }
}
