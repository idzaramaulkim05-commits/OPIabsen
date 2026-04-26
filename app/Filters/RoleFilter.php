<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('logged_in')) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $role = (string) session()->get('role');
        $allowedRoles = $arguments ?? [];

        if ($allowedRoles === [] || in_array($role, $allowedRoles, true)) {
            return;
        }

        return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak akses ke halaman tersebut.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No post-processing required.
    }
}
