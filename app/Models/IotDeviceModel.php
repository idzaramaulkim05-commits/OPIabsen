<?php

namespace App\Models;

use CodeIgniter\Model;

class IotDeviceModel extends Model
{
    protected $table = 'iot_devices';
    protected $primaryKey = 'id_device';
    protected $returnType = 'array';

    protected $allowedFields = [
        'device_code',
        'device_name',
        'device_type',
        'status_mode',
        'last_seen_at',
        'last_ip',
        'last_message',
        'firmware_version',
        'created_at',
        'updated_at',
    ];
}
