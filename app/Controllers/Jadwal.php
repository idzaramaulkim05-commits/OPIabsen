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

        return view('data_jadwal', [
            'jadwal' => $jadwalList,
        ]);
    }

    public function tambah()
    {
        return view('form_jadwal', [
            'title' => 'Tambah Jadwal Masuk & Keluar',
            'action' => base_url('jadwal/simpan'),
            'jadwal' => null,
            'hariList' => $this->hariList(),
        ]);
    }

    public function edit(int $id)
    {
        $jadwal = $this->client->get('jadwal/' . $id);

        if (! $jadwal || isset($jadwal['message'])) {
            return redirect()->to('/jadwal')->with('error', 'Jadwal tidak ditemukan.');
        }

        return view('form_jadwal', [
            'title' => 'Edit Jadwal Masuk & Keluar',
            'action' => base_url('jadwal/update/' . $id),
            'jadwal' => $jadwal,
            'hariList' => $this->hariList(),
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
            'hari' => $this->parseHari(),
            'shifts' => $this->parseShifts(),
        ];
    }

    private function parseHari(): array
    {
        $raw = $this->request->getPost('hari');
        $selected = is_array($raw) ? $raw : [$raw];
        $allowed = $this->hariList();
        $hari = [];

        foreach ($selected as $item) {
            $item = trim((string) $item);
            if (in_array($item, $allowed, true) && ! in_array($item, $hari, true)) {
                $hari[] = $item;
            }
        }

        return $hari;
    }

    private function parseShifts(): array
    {
        $shifts = [];
        $shiftCount = (int) $this->request->getPost('shift_count') ?? 0;

        for ($i = 0; $i < $shiftCount; $i++) {
            $nama = trim((string) $this->request->getPost("shift_{$i}_nama"));
            $masukAwal = trim((string) $this->request->getPost("shift_{$i}_masuk_awal"));
            $masukAkhir = trim((string) $this->request->getPost("shift_{$i}_masuk_akhir"));
            $pulangAwal = trim((string) $this->request->getPost("shift_{$i}_pulang_awal"));
            $pulangAkhir = trim((string) $this->request->getPost("shift_{$i}_pulang_akhir"));

            if ($masukAwal && $masukAkhir && $pulangAwal && $pulangAkhir) {
                $shifts[] = [
                    'nama' => $nama ?: "Shift " . ($i + 1),
                    'masuk_awal' => $masukAwal,
                    'masuk_akhir' => $masukAkhir,
                    'pulang_awal' => $pulangAwal,
                    'pulang_akhir' => $pulangAkhir,
                ];
            }
        }

        return $shifts;
    }

    private function hariList(): array
    {
        return ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    }
}
