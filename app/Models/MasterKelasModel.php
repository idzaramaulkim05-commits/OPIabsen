<?php

namespace App\Models;

use CodeIgniter\Model;

class MasterKelasModel extends Model
{
    protected $table = 'master_kelas';
    protected $primaryKey = 'id_kelas';
    protected $returnType = 'array';

    protected $allowedFields = [
        'nama_kelas',
        'created_at',
        'updated_at',
    ];
}
