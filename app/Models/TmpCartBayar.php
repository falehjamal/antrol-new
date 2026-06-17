<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TmpCartBayar extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'tmp_cartbayar';
    public $timestamps = false;
}
