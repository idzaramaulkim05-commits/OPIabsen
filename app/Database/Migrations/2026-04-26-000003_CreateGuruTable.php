<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGuruTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_guru' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nama' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'nip' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'username' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'password' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'kelas_wali' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'is_wali_kelas' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'id_rfid' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'foto_wajah' => [
                'type' => 'LONGTEXT',
                'null' => true,
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

        $this->forge->addKey('id_guru', true);
        $this->forge->addUniqueKey('nip');
        $this->forge->addUniqueKey('username');
        $this->forge->addUniqueKey('id_rfid');
        $this->forge->createTable('guru', true);
    }

    public function down()
    {
        $this->forge->dropTable('guru', true);
    }
}
