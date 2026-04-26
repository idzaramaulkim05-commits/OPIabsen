<?php

namespace App\Models;

use CodeIgniter\Model;

class IotRegistrationCandidateModel extends Model
{
    protected $table = 'iot_registration_candidates';
    protected $primaryKey = 'id_candidate';
    protected $returnType = 'array';

    protected $allowedFields = [
        'nama_registrasi',
        'id_rfid',
        'foto_wajah',
        'source_session_id',
        'status',
        'mapped_target_type',
        'mapped_target_id',
        'mapped_at',
        'created_by',
        'created_at',
        'updated_at',
    ];
}
