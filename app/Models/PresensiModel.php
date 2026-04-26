<?php

namespace App\Models;

use CodeIgniter\Model;

class PresensiModel extends Model
{
    protected $table = 'presensi';
    protected $primaryKey = 'id_presensi';
    protected $returnType = 'array';

    protected $allowedFields = [
        'id_siswa',
        'id_guru',
        'id_jadwal',
        'kelas',
        'tanggal',
        'jam',
        'status',
        'metode',
        'catatan',
        'created_at',
        'updated_at',
    ];
}
