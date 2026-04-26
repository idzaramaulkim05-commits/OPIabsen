<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterSiswaOptionalFields extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('siswa')) {
            return;
        }

        if ($this->db->fieldExists('no_induk', 'siswa')) {
            $this->forge->modifyColumn('siswa', [
                'no_induk' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                ],
            ]);
        }

        if ($this->db->fieldExists('kelas', 'siswa')) {
            $this->forge->modifyColumn('siswa', [
                'kelas' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                ],
            ]);
        }

        if (! $this->db->fieldExists('alamat', 'siswa')) {
            $this->forge->addColumn('siswa', [
                'alamat' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'after' => 'kelas',
                ],
            ]);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('siswa')) {
            return;
        }

        if ($this->db->fieldExists('alamat', 'siswa')) {
            $this->forge->dropColumn('siswa', 'alamat');
        }

        if ($this->db->fieldExists('kelas', 'siswa')) {
            $this->forge->modifyColumn('siswa', [
                'kelas' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => false,
                ],
            ]);
        }

        if ($this->db->fieldExists('no_induk', 'siswa')) {
            $this->forge->modifyColumn('siswa', [
                'no_induk' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => false,
                ],
            ]);
        }
    }
}
