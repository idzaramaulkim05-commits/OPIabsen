<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateIotRegistrationCandidatesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_candidate' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'nama_registrasi' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
            ],
            'id_rfid' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'foto_wajah' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'source_session_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'pending',
            ],
            'mapped_target_type' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'mapped_target_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'mapped_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
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

        $this->forge->addKey('id_candidate', true);
        $this->forge->addKey('status');
        $this->forge->addKey('id_rfid');
        $this->forge->addKey('source_session_id');
        $this->forge->addKey('created_at');
        $this->forge->createTable('iot_registration_candidates', true);
    }

    public function down()
    {
        $this->forge->dropTable('iot_registration_candidates', true);
    }
}
