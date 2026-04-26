<?php

namespace App\Models;

use CodeIgniter\Model;

class GuruModel extends Model
{
    protected $table = 'guru';
    protected $primaryKey = 'id_guru';
    protected $returnType = 'array';

    protected $allowedFields = [
        'nama',
        'nip',
        'username',
        'password',
        'kelas_wali',
        'is_wali_kelas',
        'id_rfid',
        'foto_wajah',
        'created_at',
        'updated_at',
    ];
}
