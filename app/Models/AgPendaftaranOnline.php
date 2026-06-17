<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgPendaftaranOnline extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'ag_pendaftaran_online';
    public $timestamps = false;

    public function poli()
    {
        return $this->belongsTo(Poli::class, 'kodepoly', 'kode');
    }

    public function dokter()
    {
        return $this->belongsTo(Dokter::class, 'kodedokter', 'KDDOKTER');
    }
}
