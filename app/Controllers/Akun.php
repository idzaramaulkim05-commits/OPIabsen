<?php

namespace App\Controllers;

use App\Models\GuruModel;
use App\Models\OperatorModel;

class Akun extends BaseController
{
    public function index()
    {
        $adminModel = new OperatorModel();
        $guruModel = new GuruModel();
        $rows = [];

        foreach ($adminModel->orderBy('id_admin', 'ASC')->findAll() as $row) {
            $rows[] = [
                'role' => 'admin',
                'id' => (int) ($row['id_admin'] ?? 0),
                'display_name' => (string) ($row['username'] ?? ''),
                'username' => (string) ($row['username'] ?? ''),
            ];
        }

        foreach ($guruModel->orderBy('id_guru', 'ASC')->findAll() as $row) {
            $username = trim((string) ($row['username'] ?? ''));
            if ($username === '') {
                continue;
            }

            $rows[] = [
                'role' => 'guru',
                'id' => (int) ($row['id_guru'] ?? 0),
                'display_name' => (string) (($row['nama'] ?? '') !== '' ? $row['nama'] : $username),
                'username' => $username,
            ];
        }

        usort($rows, static fn (array $a, array $b): int => strcmp($a['role'] . $a['username'], $b['role'] . $b['username']));

        return view('data_akun', ['akun' => $rows]);
    }

    public function tambah()
    {
        return view('form_akun', [
            'title' => 'Tambah Akun',
            'action' => base_url('admin/akun/simpan'),
            'akun' => null,
        ]);
    }

    public function simpan()
    {
        $payload = [
            'role' => trim((string) $this->request->getPost('role')),
            'nama' => trim((string) $this->request->getPost('nama')),
            'username' => trim((string) $this->request->getPost('username')),
            'password' => (string) $this->request->getPost('password'),
        ];

        if (! in_array($payload['role'], ['admin', 'guru'], true)) {
            return redirect()->back()->withInput()->with('error', 'Role akun tidak valid.');
        }

        if ($payload['role'] === 'admin') {
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

        if (! $this->validateData($payload, [
            'nama' => 'required|min_length[3]',
            'username' => 'required|min_length[4]',
            'password' => 'required|min_length[6]',
        ])) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $guruModel = new GuruModel();
        $existingGuru = $guruModel->where('username', $payload['username'])->first();
        if ($existingGuru) {
            return redirect()->back()->withInput()->with('error', 'Username guru sudah digunakan.');
        }

        $guruModel->insert([
            'nama' => $payload['nama'],
            'nip' => null,
            'username' => $payload['username'],
            'password' => password_hash($payload['password'], PASSWORD_DEFAULT),
            'kelas_wali' => null,
            'is_wali_kelas' => 0,
            'id_rfid' => null,
            'foto_wajah' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/admin/akun')->with('success', 'Akun guru berhasil ditambahkan.');
    }

    public function edit(string $role, int $id)
    {
        if ($role === 'admin') {
            $model = new OperatorModel();
            $akun = $model->find($id);
            if (! $akun) {
                return redirect()->to('/admin/akun')->with('error', 'Akun admin tidak ditemukan.');
            }
            $akun['role'] = 'admin';
            $akun['id'] = $id;
        } elseif ($role === 'guru') {
            $model = new GuruModel();
            $akun = $model->find($id);
            if (! $akun || trim((string) ($akun['username'] ?? '')) === '') {
                return redirect()->to('/admin/akun')->with('error', 'Akun guru tidak ditemukan.');
            }
            $akun['role'] = 'guru';
            $akun['id'] = $id;
            $akun['id_guru'] = $id;
        } else {
            return redirect()->to('/admin/akun')->with('error', 'Role akun tidak valid.');
        }

        return view('form_akun', [
            'title' => 'Edit Akun',
            'action' => base_url('admin/akun/update/' . $role . '/' . $id),
            'akun' => $akun,
        ]);
    }

    public function update(string $role, int $id)
    {
        $username = trim((string) $this->request->getPost('username'));
        $password = trim((string) $this->request->getPost('password'));

        if ($role === 'admin') {
            $model = new OperatorModel();
            $existing = $model->find($id);
            if (! $existing) {
                return redirect()->to('/admin/akun')->with('error', 'Akun admin tidak ditemukan.');
            }

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

        if ($role !== 'guru') {
            return redirect()->to('/admin/akun')->with('error', 'Role akun tidak valid.');
        }
        $model = new GuruModel();
        $existing = $model->find($id);
        if (! $existing) {
            return redirect()->to('/admin/akun')->with('error', 'Akun guru tidak ditemukan.');
        }
        if (! $this->validateData([
            'username' => $username,
            'password' => $password,
        ], [
            'username' => 'required|min_length[4]',
            'password' => 'permit_empty|min_length[6]',
        ])) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }
        $existingByUsername = $model->where('username', $username)->first();
        if ($existingByUsername && (int) ($existingByUsername['id_guru'] ?? 0) !== $id) {
            return redirect()->back()->withInput()->with('error', 'Username guru sudah digunakan.');
        }

        $payload = [
            'username' => $username,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($password !== '') {
            $payload['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $model->update($id, $payload);
        return redirect()->to('/admin/akun')->with('success', 'Akun guru berhasil diperbarui.');
    }

    public function hapus(string $role, int $id)
    {
        if ($role === 'admin') {
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

        if ($role !== 'guru') {
            return redirect()->to('/admin/akun')->with('error', 'Role akun tidak valid.');
        }
        $deleteMode = trim((string) $this->request->getPost('delete_guru_data'));
        if (! in_array($deleteMode, ['yes', 'no'], true)) {
            return redirect()->to('/admin/akun')->with('error', 'Pilih opsi hapus akun guru terlebih dahulu.');
        }

        $guruModel = new GuruModel();
        $guru = $guruModel->find($id);
        if (! $guru) {
            return redirect()->to('/admin/akun')->with('error', 'Akun guru tidak ditemukan.');
        }

        if ($deleteMode === 'yes') {
            $guruModel->delete($id);
            return redirect()->to('/admin/akun')->with('success', 'Akun dan data guru berhasil dihapus.');
        }

        $guruModel->update($id, [
            'username' => null,
            'password' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return redirect()->to('/admin/akun')->with('success', 'Akun login guru dihapus, data guru tetap tersimpan.');
    }
}
