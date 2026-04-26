<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateIotScanLogsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_scan' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'entity_type' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'entity_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'rfid_uid' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'expected_employee_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'matched_employee_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'gateway_status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'confidence' => [
                'type' => 'DECIMAL',
                'constraint' => '6,4',
                'null' => true,
            ],
            'result' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
            ],
            'message' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'request_time' => [
                'type' => 'DATETIME',
            ],
            'raw_response' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id_scan', true);
        $this->forge->addKey('request_time');
        $this->forge->addKey('result');
        $this->forge->createTable('iot_scan_logs', true);
    }

    public function down()
    {
        $this->forge->dropTable('iot_scan_logs', true);
    }
}
