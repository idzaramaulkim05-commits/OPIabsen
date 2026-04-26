<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateIotDevicesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_device' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'device_code' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'device_name' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => true,
            ],
            'device_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'status_mode' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'attendance',
            ],
            'last_seen_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'last_ip' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true,
            ],
            'last_message' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'firmware_version' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
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

        $this->forge->addKey('id_device', true);
        $this->forge->addUniqueKey('device_code');
        $this->forge->addKey('last_seen_at');
        $this->forge->createTable('iot_devices', true);
    }

    public function down()
    {
        $this->forge->dropTable('iot_devices', true);
    }
}
