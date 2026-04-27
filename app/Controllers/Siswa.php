<?php

namespace App\Controllers;

use App\Libraries\LaravelApiClient;

class Siswa extends BaseController
{
    private $client;

    public function __construct()
    {
        $this->client = new LaravelApiClient();
    }

    public function index()
    {
        return $this->data();
    }

    public function data()
    {
        $response = $this->client->get('siswa');
        $data['siswa'] = $response ?: [];

        return view('data_siswa', $data);
    }

    public function tambah()
    {
        return view('form_siswa', [
            'title' => 'Tambah Siswa',
            'action' => base_url('siswa/simpan'),
            'siswa' => null,
            'kelasOptions' => $this->getMasterKelasList(),
        ]);
    }

    public function edit(int $id)
    {
        $siswa = $this->client->get('siswa/' . $id);

        if (! $siswa || isset($siswa['message'])) {
            return redirect()->to('/siswa/data')->with('error', 'Data siswa tidak ditemukan.');
        }

        return view('form_siswa', [
            'title' => 'Edit Siswa',
            'action' => base_url('siswa/update/' . $id),
            'siswa' => $siswa,
            'kelasOptions' => $this->getMasterKelasList([(string) ($siswa['kelas'] ?? '')]),
        ]);
    }

    public function simpan()
    {
        $payload = $this->buildPayload();

        $response = $this->client->post('siswa', $payload);

        if (isset($response['message']) && !isset($response['id'])) {
            return redirect()->back()->withInput()->with('error', $response['message']);
        }

        return redirect()->to('/siswa/data')->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        $payload = $this->buildPayload();
        
        // Prevent removing existing face
        if ($payload['foto_wajah'] === null) {
            unset($payload['foto_wajah']);
        }

        $response = $this->client->put('siswa/' . $id, $payload);

        if (isset($response['message']) && !isset($response['id'])) {
            return redirect()->back()->withInput()->with('error', $response['message']);
        }

        return redirect()->to('/siswa/data')->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function hapus(int $id)
    {
        $this->client->delete('siswa/' . $id);

        return redirect()->to('/siswa/data')->with('success', 'Data siswa berhasil dihapus.');
    }

    private function buildPayload(): array
    {
        $fotoWajah = trim((string) $this->request->getPost('foto_wajah'));
        $noInduk = trim((string) $this->request->getPost('no_induk'));
        $kelas = trim((string) $this->request->getPost('kelas'));
        $alamat = trim((string) $this->request->getPost('alamat'));

        return [
            'nama' => trim((string) $this->request->getPost('nama')),
            'no_induk' => $noInduk !== '' ? $noInduk : null,
            'kelas' => $kelas !== '' ? $kelas : null,
            'alamat' => $alamat !== '' ? $alamat : null,
            'id_rfid' => trim((string) $this->request->getPost('id_rfid')) ?: null,
            'foto_wajah' => $fotoWajah !== '' ? $fotoWajah : null,
        ];
    }
}
