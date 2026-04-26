<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateJadwalMengajarTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_jadwal' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_guru' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'kelas' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'mata_pelajaran' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
            ],
            'hari' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
            ],
            'jam_mulai' => [
                'type' => 'TIME',
            ],
            'jam_selesai' => [
                'type' => 'TIME',
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

        $this->forge->addKey('id_jadwal', true);
        $this->forge->addKey('id_guru');
        $this->forge->addForeignKey('id_guru', 'guru', 'id_guru', 'CASCADE', 'CASCADE');
        $this->forge->createTable('jadwal_mengajar', true);
    }

    public function down()
    {
        $this->forge->dropTable('jadwal_mengajar', true);
    }
}
