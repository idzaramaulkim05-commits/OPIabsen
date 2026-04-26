<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateIotRegistrationSessionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_session' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'device_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'session_token' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'waiting_device',
            ],
            'requested_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'target_type' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'target_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'captured_rfid' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'captured_face' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'captured_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'command_issued_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'completed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'error_message' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
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

        $this->forge->addKey('id_session', true);
        $this->forge->addUniqueKey('session_token');
        $this->forge->addKey('device_id');
        $this->forge->addKey('status');
        $this->forge->addKey('created_at');
        $this->forge->addForeignKey('device_id', 'iot_devices', 'id_device', 'CASCADE', 'CASCADE');
        $this->forge->createTable('iot_registration_sessions', true);
    }

    public function down()
    {
        $this->forge->dropTable('iot_registration_sessions', true);
    }
}
