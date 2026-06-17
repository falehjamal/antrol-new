<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return 'Webservice RS BPJS - '.app()->environment().' - '.app()->version();
});
