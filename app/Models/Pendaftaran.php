<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pendaftaran extends Model
{
    protected $connection = 'mysql2';
    protected $table = 't_pendaftaran';
    protected $primaryKey = 'IDXDAFTAR';
    public $timestamps = false;

    public function antrian()
    {
        return $this->hasOne(Antrean::class, 'kd_poli', 'KDPOLY');
    }
}
