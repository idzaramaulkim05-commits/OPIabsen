<?php

namespace App\Models;

use CodeIgniter\Model;

class JadwalModel extends Model
{
    protected $table = 'jadwal_mengajar';
    protected $primaryKey = 'id_jadwal';
    protected $returnType = 'array';

    protected $allowedFields = [
        'id_guru',
        'kelas',
        'mata_pelajaran',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'created_at',
        'updated_at',
    ];
}
