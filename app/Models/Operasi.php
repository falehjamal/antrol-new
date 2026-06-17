<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Operasi extends Model
{
    protected $connection = 'mysql2';
    protected $table = 't_operasi';

    public function pendaftaran()
    {
        return $this->belongsTo(Pendaftaran::class, 'IDXDAFTAR', 'IDXDAFTAR');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'KDUNIT', 'kode_unit');
    }

    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'nomr', 'NOMR');
    }
}
