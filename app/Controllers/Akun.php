<?php

namespace App\Controllers;

use App\Models\OperatorModel;

class Akun extends BaseController
{
    public function index()
    {
        $model = new OperatorModel();

        return view('data_akun', [
            'akun' => $model->orderBy('id_admin', 'ASC')->findAll(),
        ]);
    }

    public function tambah()
    {
        return view('form_akun', [
            'title' => 'Tambah Akun Admin',
            'action' => base_url('admin/akun/simpan'),
            'akun' => null,
        ]);
    }

    public function simpan()
    {
        $payload = [
            'username' => trim((string) $this->request->getPost('username')),
            'password' => (string) $this->request->getPost('password'),
        ];

        if (! $this->validateData($payload, [
            'username' => 'required|min_length[4]|is_unique[operator.username]',
            'password' => 'required|min_length[6]',
        ])) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $model = new OperatorModel();
        $model->insert([
            'username' => $payload['username'],
            'password' => password_hash($payload['password'], PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/admin/akun')->with('success', 'Akun admin berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $model = new OperatorModel();
        $akun = $model->find($id);

        if (! $akun) {
            return redirect()->to('/admin/akun')->with('error', 'Akun admin tidak ditemukan.');
        }

        return view('form_akun', [
            'title' => 'Edit Akun Admin',
            'action' => base_url('admin/akun/update/' . $id),
            'akun' => $akun,
        ]);
    }

    public function update(int $id)
    {
        $model = new OperatorModel();
        $existing = $model->find($id);

        if (! $existing) {
            return redirect()->to('/admin/akun')->with('error', 'Akun admin tidak ditemukan.');
        }

        $username = trim((string) $this->request->getPost('username'));
        $password = trim((string) $this->request->getPost('password'));

        if (! $this->validateData([
            'username' => $username,
            'password' => $password,
        ], [
            'username' => 'required|min_length[4]|is_unique[operator.username,id_admin,' . $id . ']',
            'password' => 'permit_empty|min_length[6]',
        ])) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $payload = [
            'username' => $username,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($password !== '') {
            $payload['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $model->update($id, $payload);

        return redirect()->to('/admin/akun')->with('success', 'Akun admin berhasil diperbarui.');
    }

    public function hapus(int $id)
    {
        $currentId = (int) session()->get('user_id');
        if ($id === $currentId) {
            return redirect()->to('/admin/akun')->with('error', 'Akun yang sedang dipakai tidak bisa dihapus.');
        }

        $model = new OperatorModel();
        if (! $model->find($id)) {
            return redirect()->to('/admin/akun')->with('error', 'Akun admin tidak ditemukan.');
        }

        $model->delete($id);

        return redirect()->to('/admin/akun')->with('success', 'Akun admin berhasil dihapus.');
    }
}
