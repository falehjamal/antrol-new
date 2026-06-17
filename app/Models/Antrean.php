<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Antrean extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'no_antrian';
    public $timestamps = false;

    public function pendaftaran_online()
    {
        return $this->belongsTo(AgPendaftaranOnline::class, 'id_online', 'id');
    }

    public function pendaftaran()
    {
        return $this->belongsTo(Pendaftaran::class, 'idxdaftar', 'IDXDAFTAR');
    }

    public function poli()
    {
        return $this->belongsTo(Poli::class, 'kd_poli', 'kode');
    }
}
