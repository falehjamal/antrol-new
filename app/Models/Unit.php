<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'm_unit';

    public function poli()
    {
        return $this->belongsTo(Poli::class, 'kode_unit', 'kode');
    }
}
