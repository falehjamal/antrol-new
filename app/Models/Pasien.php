<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pasien extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'm_pasien';

    public $timestamps = false;
}
