<?php

namespace App\Controllers;

use App\Models\GuruModel;

class Guru extends BaseController
{
    public function index()
    {
        $model = new GuruModel();

        return view('data_guru', [
            'guru' => $model->orderBy('nama', 'ASC')->findAll(),
        ]);
    }

    public function tambah()
    {
        return view('form_guru', [
            'title' => 'Tambah Guru',
            'action' => base_url('guru/simpan'),
            'guru' => null,
        ]);
    }

    public function edit(int $id)
    {
        $model = new GuruModel();
        $guru = $model->find($id);

        if (! $guru) {
            return redirect()->to('/guru')->with('error', 'Data guru tidak ditemukan.');
        }

        return view('form_guru', [
            'title' => 'Edit Guru',
            'action' => base_url('guru/update/' . $id),
            'guru' => $guru,
        ]);
    }

    public function simpan()
    {
        $model = new GuruModel();
        $payload = $this->buildPayload();
        $plainPassword = (string) $this->request->getPost('password');

        if (! $this->validateGuru($payload, null, true)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $payload['password'] = password_hash($plainPassword, PASSWORD_DEFAULT);
        $payload['created_at'] = date('Y-m-d H:i:s');
        $payload['updated_at'] = date('Y-m-d H:i:s');

        $model->insert($payload);

        return redirect()->to('/guru')->with('success', 'Data guru berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        $model = new GuruModel();
        $existing = $model->find($id);

        if (! $existing) {
            return redirect()->to('/guru')->with('error', 'Data guru tidak ditemukan.');
        }

        $payload = $this->buildPayload();
        $plainPassword = trim((string) $this->request->getPost('password'));

        if ($payload['foto_wajah'] === null) {
            $payload['foto_wajah'] = $existing['foto_wajah'];
        }

        if (! $this->validateGuru($payload, $id, false)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        if ($plainPassword !== '') {
            $payload['password'] = password_hash($plainPassword, PASSWORD_DEFAULT);
        }

        $payload['updated_at'] = date('Y-m-d H:i:s');
        $model->update($id, $payload);

        return redirect()->to('/guru')->with('success', 'Data guru berhasil diperbarui.');
    }

    public function hapus(int $id)
    {
        $model = new GuruModel();

        if (! $model->find($id)) {
            return redirect()->to('/guru')->with('error', 'Data guru tidak ditemukan.');
        }

        $model->delete($id);

        return redirect()->to('/guru')->with('success', 'Data guru berhasil dihapus.');
    }

    private function buildPayload(): array
    {
        $isWali = $this->request->getPost('is_wali_kelas') ? 1 : 0;
        $fotoWajah = trim((string) $this->request->getPost('foto_wajah'));
        $kelasWali = trim((string) $this->request->getPost('kelas_wali'));

        return [
            'nama' => trim((string) $this->request->getPost('nama')),
            'nip' => trim((string) $this->request->getPost('nip')),
            'username' => trim((string) $this->request->getPost('username')),
            'kelas_wali' => $isWali === 1 ? ($kelasWali !== '' ? $kelasWali : null) : null,
            'is_wali_kelas' => $isWali,
            'id_rfid' => trim((string) $this->request->getPost('id_rfid')) ?: null,
            'foto_wajah' => $fotoWajah !== '' ? $fotoWajah : null,
        ];
    }

    private function validateGuru(array $payload, ?int $id, bool $isCreate): bool
    {
        $nipRule = 'required|min_length[8]';
        $usernameRule = 'required|min_length[4]';
        $rfidRule = 'permit_empty';

        if ($id === null) {
            $nipRule .= '|is_unique[guru.nip]';
            $usernameRule .= '|is_unique[guru.username]';
            $rfidRule .= '|is_unique[guru.id_rfid]';
        } else {
            $nipRule .= '|is_unique[guru.nip,id_guru,' . $id . ']';
            $usernameRule .= '|is_unique[guru.username,id_guru,' . $id . ']';
            $rfidRule .= '|is_unique[guru.id_rfid,id_guru,' . $id . ']';
        }

        $rules = [
            'nama' => 'required|min_length[3]',
            'nip' => $nipRule,
            'username' => $usernameRule,
            'id_rfid' => $rfidRule,
            'kelas_wali' => $payload['is_wali_kelas'] === 1 ? 'required|min_length[2]' : 'permit_empty|min_length[2]',
        ];

        if ($isCreate) {
            $rules['password'] = 'required|min_length[6]';
        } else {
            $rules['password'] = 'permit_empty|min_length[6]';
        }

        $dataToValidate = $payload;
        $dataToValidate['password'] = trim((string) $this->request->getPost('password'));

        return $this->validateData($dataToValidate, $rules);
    }
}
