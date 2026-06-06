<?php

namespace App\Controllers;

use App\Libraries\LaravelApiClient;

class Guru extends BaseController
{
    private $client;

    public function __construct()
    {
        $this->client = new LaravelApiClient();
    }

    public function index()
    {
        $response = $this->client->get('guru');
        if ($this->isApiError($response)) {
            session()->setFlashdata('error', $response['message']);
        }

        return view('data_guru', [
            'guru' => $this->safeApiList($response),
        ]);
    }

    public function tambah()
    {
        return view('form_guru', [
            'title' => 'Tambah Guru',
            'action' => base_url('guru/simpan'),
            'guru' => null,
            'kelasOptions' => $this->getMasterKelasList(),
        ]);
    }

    public function edit(int $id)
    {
        $guru = $this->client->get('guru/' . $id);

        if ($this->isApiError($guru)) {
            return redirect()->to('/guru')->with('error', $guru['message']);
        }

        if (! $guru || isset($guru['message'])) {
            return redirect()->to('/guru')->with('error', 'Data guru tidak ditemukan.');
        }

        return view('form_guru', [
            'title' => 'Edit Guru',
            'action' => base_url('guru/update/' . $id),
            'guru' => $guru,
            'kelasOptions' => $this->getMasterKelasList([(string) ($guru['kelas_wali'] ?? '')]),
        ]);
    }

    public function simpan()
    {
        $payload = $this->buildPayload();
        $plainPassword = (string) $this->request->getPost('password');
        
        if (trim($plainPassword) !== '') {
            $payload['password'] = password_hash($plainPassword, PASSWORD_DEFAULT);
        } else {
            return redirect()->back()->withInput()->with('error', 'Password wajib diisi untuk guru baru.');
        }

        $response = $this->client->post('guru', $payload);

        if (isset($response['message']) && !isset($response['id_guru'])) {
            return redirect()->back()->withInput()->with('error', $response['message']);
        }

        return redirect()->to('/guru')->with('success', 'Data guru berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        $payload = $this->buildPayload();
        $plainPassword = trim((string) $this->request->getPost('password'));

        if ($plainPassword !== '') {
            $payload['password'] = password_hash($plainPassword, PASSWORD_DEFAULT);
        }

        $response = $this->client->put('guru/' . $id, $payload);

        if (isset($response['message']) && !isset($response['id_guru'])) {
            return redirect()->back()->withInput()->with('error', $response['message']);
        }

        return redirect()->to('/guru')->with('success', 'Data guru berhasil diperbarui.');
    }

    public function hapus(int $id)
    {
        $this->client->delete('guru/' . $id);

        return redirect()->to('/guru')->with('success', 'Data guru berhasil dihapus.');
    }

    private function buildPayload(): array
    {
        $isWali = $this->request->getPost('is_wali_kelas') ? 1 : 0;
        $kelasWali = trim((string) $this->request->getPost('kelas_wali'));

        return [
            'nama' => trim((string) $this->request->getPost('nama')),
            'nip' => (($nip = trim((string) $this->request->getPost('nip'))) !== '' ? $nip : null),
            'username' => trim((string) $this->request->getPost('username')),
            'kelas_wali' => $isWali === 1 ? ($kelasWali !== '' ? $kelasWali : null) : null,
            'is_wali_kelas' => $isWali,
        ];
    }
}
