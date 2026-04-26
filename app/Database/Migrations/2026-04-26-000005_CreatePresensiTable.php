<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePresensiTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_presensi' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_siswa' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'id_guru' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'id_jadwal' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'kelas' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'tanggal' => [
                'type' => 'DATE',
            ],
            'jam' => [
                'type' => 'TIME',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'hadir',
            ],
            'metode' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'manual',
            ],
            'catatan' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id_presensi', true);
        $this->forge->addKey(['id_siswa', 'tanggal']);
        $this->forge->addUniqueKey(['id_siswa', 'id_jadwal', 'tanggal']);
        $this->forge->addForeignKey('id_siswa', 'siswa', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_guru', 'guru', 'id_guru', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_jadwal', 'jadwal_mengajar', 'id_jadwal', 'SET NULL', 'CASCADE');
        $this->forge->createTable('presensi', true);
    }

    public function down()
    {
        $this->forge->dropTable('presensi', true);
    }
}
