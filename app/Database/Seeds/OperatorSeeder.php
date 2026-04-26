<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class OperatorSeeder extends Seeder
{
    public function run()
    {
        $table = $this->db->table('operator');
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $existing = $table->where('username', 'admin')->get()->getRowArray();

        if ($existing) {
            $table->where('id_admin', $existing['id_admin'])->update([
                'password'   => $hashedPassword,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            return;
        }

        $table->insert([
            'username'   => 'admin',
            'password'   => $hashedPassword,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
