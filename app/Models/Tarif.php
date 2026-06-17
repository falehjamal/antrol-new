<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tarif extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'm_tarif2012';
    public $timestamps = false;
}
