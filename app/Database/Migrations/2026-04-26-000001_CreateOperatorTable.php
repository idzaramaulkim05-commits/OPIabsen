<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOperatorTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_admin' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'username' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'password' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
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

        $this->forge->addKey('id_admin', true);
        $this->forge->addUniqueKey('username');
        $this->forge->createTable('operator', true);
    }

    public function down()
    {
        $this->forge->dropTable('operator', true);
    }
}
