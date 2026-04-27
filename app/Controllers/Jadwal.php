<?php

namespace App\Controllers;

use App\Libraries\LaravelApiClient;

class Jadwal extends BaseController
{
    private $client;

    public function __construct()
    {
        $this->client = new LaravelApiClient();
    }

    public function index()
    {
        $response = $this->client->get('jadwal');
        $jadwalList = $response ?: [];

        // Map guru name manually
        $guruResponse = $this->client->get('guru') ?: [];
        $guruMap = [];
        foreach ($guruResponse as $g) {
            $guruMap[$g['id_guru']] = $g['nama'];
        }

        foreach ($jadwalList as &$j) {
            $j['nama_guru'] = $guruMap[$j['id_guru']] ?? 'Unknown';
        }

        return view('data_jadwal', [
            'jadwal' => $jadwalList,
        ]);
    }

    public function tambah()
    {
        $guruResponse = $this->client->get('guru') ?: [];

        return view('form_jadwal', [
            'title' => 'Tambah Jadwal Mengajar',
            'action' => base_url('jadwal/simpan'),
            'jadwal' => null,
            'guruList' => $guruResponse,
            'hariList' => $this->hariList(),
            'kelasOptions' => $this->getMasterKelasList(),
        ]);
    }

    public function edit(int $id)
    {
        $jadwal = $this->client->get('jadwal/' . $id);

        if (! $jadwal || isset($jadwal['message'])) {
            return redirect()->to('/jadwal')->with('error', 'Jadwal tidak ditemukan.');
        }

        $guruResponse = $this->client->get('guru') ?: [];

        return view('form_jadwal', [
            'title' => 'Edit Jadwal Mengajar',
            'action' => base_url('jadwal/update/' . $id),
            'jadwal' => $jadwal,
            'guruList' => $guruResponse,
            'hariList' => $this->hariList(),
            'kelasOptions' => $this->getMasterKelasList([(string) ($jadwal['kelas'] ?? '')]),
        ]);
    }

    public function simpan()
    {
        $payload = $this->buildPayload();

        $response = $this->client->post('jadwal', $payload);

        if (isset($response['message']) && !isset($response['id_jadwal'])) {
            return redirect()->back()->withInput()->with('error', $response['message']);
        }

        return redirect()->to('/jadwal')->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        $payload = $this->buildPayload();

        $response = $this->client->put('jadwal/' . $id, $payload);

        if (isset($response['message']) && !isset($response['id_jadwal'])) {
            return redirect()->back()->withInput()->with('error', $response['message']);
        }

        return redirect()->to('/jadwal')->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function hapus(int $id)
    {
        $this->client->delete('jadwal/' . $id);

        return redirect()->to('/jadwal')->with('success', 'Jadwal berhasil dihapus.');
    }

    private function buildPayload(): array
    {
        return [
            'id_guru' => (int) $this->request->getPost('id_guru'),
            'kelas' => trim((string) $this->request->getPost('kelas')),
            'mata_pelajaran' => trim((string) $this->request->getPost('mata_pelajaran')),
            'hari' => trim((string) $this->request->getPost('hari')),
            'jam_mulai' => trim((string) $this->request->getPost('jam_mulai')),
            'jam_selesai' => trim((string) $this->request->getPost('jam_selesai')),
        ];
    }

    private function hariList(): array
    {
        return ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    }
}
