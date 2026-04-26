<?php

namespace App\Controllers;

use App\Models\GuruModel;
use App\Models\OperatorModel;

class Auth extends BaseController
{
    public function landing()
    {
        return view('landing');
    }

    public function login()
    {
        if (session()->get('logged_in')) {
            return redirect()->to('/dashboard');
        }

        $captcha = rand(1000, 9999);
        session()->set('captcha', (string) $captcha);

        return view('login', ['captcha' => $captcha]);
    }

    public function loginProcess()
    {
        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');
        $captchaInput = trim((string) $this->request->getPost('captcha'));

        $captchaSession = (string) session()->get('captcha');
        if ($captchaInput !== $captchaSession) {
            return redirect()->back()->withInput()->with('error', 'Captcha salah.');
        }

        $operatorModel = new OperatorModel();
        $admin = $operatorModel->where('username', $username)->first();

        if ($admin && $this->passwordMatches($password, $admin['password'])) {
            session()->regenerate(true);
            session()->set([
                'logged_in' => true,
                'role' => 'admin',
                'user_id' => (int) $admin['id_admin'],
                'username' => $admin['username'],
                'nama' => $admin['username'],
            ]);

            return redirect()->to('/dashboard');
        }

        $guruModel = new GuruModel();
        $guru = $guruModel->where('username', $username)->first();

        if ($guru && $this->passwordMatches($password, $guru['password'])) {
            $kelasWali = trim((string) ($guru['kelas_wali'] ?? ''));
            $isWaliKelas = (int) ($guru['is_wali_kelas'] ?? 0) === 1 && $kelasWali !== '';

            session()->regenerate(true);
            session()->set([
                'logged_in' => true,
                'role' => 'guru',
                'user_id' => (int) $guru['id_guru'],
                'id_guru' => (int) $guru['id_guru'],
                'username' => $guru['username'],
                'nama' => $guru['nama'],
                'is_wali_kelas' => $isWaliKelas ? 1 : 0,
                'kelas_wali' => $isWaliKelas ? $kelasWali : '',
            ]);

            return redirect()->to('/dashboard');
        }

        return redirect()->back()->withInput()->with('error', 'Username atau password tidak valid.');
    }

    public function logout()
    {
        session()->destroy();

        return redirect()->to('/login');
    }

    private function passwordMatches(string $input, ?string $stored): bool
    {
        if ($stored === null || $stored === '') {
            return false;
        }

        if (password_verify($input, $stored)) {
            return true;
        }

        return hash_equals($stored, $input);
    }
}
