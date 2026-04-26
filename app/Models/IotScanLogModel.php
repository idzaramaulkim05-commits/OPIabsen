<?php

namespace App\Models;

use CodeIgniter\Model;

class IotScanLogModel extends Model
{
    protected $table = 'iot_scan_logs';
    protected $primaryKey = 'id_scan';
    protected $returnType = 'array';

    protected $allowedFields = [
        'entity_type',
        'entity_id',
        'rfid_uid',
        'expected_employee_id',
        'matched_employee_id',
        'gateway_status',
        'confidence',
        'result',
        'message',
        'request_time',
        'raw_response',
        'created_at',
    ];
}
