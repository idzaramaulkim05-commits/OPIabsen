<?php

namespace App\Controllers;

use App\Models\SiswaModel;

class Siswa extends BaseController
{
    public function index()
    {
        return $this->data();
    }

    public function data()
    {
        $model = new SiswaModel();
        $data['siswa'] = $model->orderBy('nama', 'ASC')->findAll();

        return view('data_siswa', $data);
    }

    public function tambah()
    {
        return view('form_siswa', [
            'title' => 'Tambah Siswa',
            'action' => base_url('siswa/simpan'),
            'siswa' => null,
        ]);
    }

    public function edit(int $id)
    {
        $model = new SiswaModel();
        $siswa = $model->find($id);

        if (! $siswa) {
            return redirect()->to('/siswa/data')->with('error', 'Data siswa tidak ditemukan.');
        }

        return view('form_siswa', [
            'title' => 'Edit Siswa',
            'action' => base_url('siswa/update/' . $id),
            'siswa' => $siswa,
        ]);
    }

    public function simpan()
    {
        $model = new SiswaModel();
        $payload = $this->buildPayload();

        if (! $this->isValidSiswa($payload)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $payload['created_at'] = date('Y-m-d H:i:s');
        $payload['updated_at'] = date('Y-m-d H:i:s');

        $model->insert($payload);

        return redirect()->to('/siswa/data')->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        $model = new SiswaModel();
        $existing = $model->find($id);

        if (! $existing) {
            return redirect()->to('/siswa/data')->with('error', 'Data siswa tidak ditemukan.');
        }

        $payload = $this->buildPayload();
        if ($payload['foto_wajah'] === null) {
            $payload['foto_wajah'] = $existing['foto_wajah'];
        }

        if (! $this->isValidSiswa($payload, $id)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $payload['updated_at'] = date('Y-m-d H:i:s');
        $model->update($id, $payload);

        return redirect()->to('/siswa/data')->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function hapus(int $id)
    {
        $model = new SiswaModel();

        if (! $model->find($id)) {
            return redirect()->to('/siswa/data')->with('error', 'Data siswa tidak ditemukan.');
        }

        $model->delete($id);

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

    private function isValidSiswa(array $payload, ?int $id = null): bool
    {
        $noIndukRule = 'permit_empty|min_length[3]';
        $rfidRule = 'permit_empty';

        if ($id === null) {
            $noIndukRule .= '|is_unique[siswa.no_induk]';
            $rfidRule .= '|is_unique[siswa.id_rfid]';
        } else {
            $noIndukRule .= '|is_unique[siswa.no_induk,id,' . $id . ']';
            $rfidRule .= '|is_unique[siswa.id_rfid,id,' . $id . ']';
        }

        return $this->validateData($payload, [
            'nama' => 'required|min_length[3]',
            'no_induk' => $noIndukRule,
            'kelas' => 'permit_empty|min_length[2]',
            'alamat' => 'permit_empty|max_length[255]',
            'id_rfid' => $rfidRule,
        ]);
    }
}
