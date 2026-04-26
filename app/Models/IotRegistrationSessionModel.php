<?php

namespace App\Models;

use CodeIgniter\Model;

class IotRegistrationSessionModel extends Model
{
    protected $table = 'iot_registration_sessions';
    protected $primaryKey = 'id_session';
    protected $returnType = 'array';

    protected $allowedFields = [
        'device_id',
        'session_token',
        'status',
        'requested_by',
        'target_type',
        'target_id',
        'captured_rfid',
        'captured_face',
        'captured_at',
        'command_issued_at',
        'completed_at',
        'error_message',
        'created_at',
        'updated_at',
    ];
}
