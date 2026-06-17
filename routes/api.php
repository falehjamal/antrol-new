<?php

use App\Http\Controllers\AntreanController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\JadwalOperasiController;
use Illuminate\Support\Facades\Route;

Route::get('login', [AuthController::class, 'login']);

Route::middleware('jwt')->group(function () {
    Route::post('jadwal_operasi/rs', [JadwalOperasiController::class, 'all']);
    Route::post('jadwal_operasi/pasien', [JadwalOperasiController::class, 'pasien']);

    Route::post('antrean/status', [AntreanController::class, 'status']);
    Route::post('antrean/pasien_baru', [AntreanController::class, 'pasien_baru']);
    Route::post('antrean/ambil', [AntreanController::class, 'ambilv3']);
    Route::post('antrean/sisa', [AntreanController::class, 'sisa']);
    Route::post('antrean/batal', [AntreanController::class, 'batal']);
    Route::post('antrean/checkin', [AntreanController::class, 'checkin']);
});
