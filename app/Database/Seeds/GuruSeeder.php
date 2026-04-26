<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class GuruSeeder extends Seeder
{
    public function run()
    {
        $table = $this->db->table('guru');
        $existing = $table->where('username', 'guru01')->get()->getRowArray();

        $data = [
            'nama' => 'Guru Wali Kelas',
            'nip' => '19881231202401001',
            'username' => 'guru01',
            'password' => password_hash('guru123', PASSWORD_DEFAULT),
            'kelas_wali' => 'X-IPA-1',
            'is_wali_kelas' => 1,
            'id_rfid' => 'RFID-GURU-001',
            'foto_wajah' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $table->where('id_guru', $existing['id_guru'])->update($data);
            return;
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        $table->insert($data);
    }
}
