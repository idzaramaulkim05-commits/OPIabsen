<?php

namespace App\Controllers;

use App\Models\GuruModel;
use App\Models\JadwalModel;

class Jadwal extends BaseController
{
    public function index()
    {
        $db = db_connect();
        $jadwal = $db->table('jadwal_mengajar jm')
            ->select('jm.*, g.nama as nama_guru')
            ->join('guru g', 'g.id_guru = jm.id_guru')
            ->orderBy('jm.hari', 'ASC')
            ->orderBy('jm.jam_mulai', 'ASC')
            ->get()
            ->getResultArray();

        return view('data_jadwal', [
            'jadwal' => $jadwal,
        ]);
    }

    public function tambah()
    {
        return view('form_jadwal', [
            'title' => 'Tambah Jadwal Mengajar',
            'action' => base_url('jadwal/simpan'),
            'jadwal' => null,
            'guruList' => (new GuruModel())->orderBy('nama', 'ASC')->findAll(),
            'hariList' => $this->hariList(),
        ]);
    }

    public function edit(int $id)
    {
        $model = new JadwalModel();
        $jadwal = $model->find($id);

        if (! $jadwal) {
            return redirect()->to('/jadwal')->with('error', 'Jadwal tidak ditemukan.');
        }

        return view('form_jadwal', [
            'title' => 'Edit Jadwal Mengajar',
            'action' => base_url('jadwal/update/' . $id),
            'jadwal' => $jadwal,
            'guruList' => (new GuruModel())->orderBy('nama', 'ASC')->findAll(),
            'hariList' => $this->hariList(),
        ]);
    }

    public function simpan()
    {
        $model = new JadwalModel();
        $payload = $this->buildPayload();

        if (! $this->validateJadwal($payload)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $payload['created_at'] = date('Y-m-d H:i:s');
        $payload['updated_at'] = date('Y-m-d H:i:s');

        $model->insert($payload);

        return redirect()->to('/jadwal')->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        $model = new JadwalModel();
        if (! $model->find($id)) {
            return redirect()->to('/jadwal')->with('error', 'Jadwal tidak ditemukan.');
        }

        $payload = $this->buildPayload();
        if (! $this->validateJadwal($payload)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $payload['updated_at'] = date('Y-m-d H:i:s');
        $model->update($id, $payload);

        return redirect()->to('/jadwal')->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function hapus(int $id)
    {
        $model = new JadwalModel();
        if (! $model->find($id)) {
            return redirect()->to('/jadwal')->with('error', 'Jadwal tidak ditemukan.');
        }

        $model->delete($id);

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

    private function validateJadwal(array $payload): bool
    {
        return $this->validateData($payload, [
            'id_guru' => 'required|integer',
            'kelas' => 'required|min_length[2]',
            'mata_pelajaran' => 'required|min_length[2]',
            'hari' => 'required|in_list[Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu]',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required',
        ]);
    }

    private function hariList(): array
    {
        return ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    }
}
