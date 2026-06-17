<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LogController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth('admin')->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::middleware('guest:admin')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:admin')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
    Route::get('/logs/data', [LogController::class, 'data'])->name('logs.data');
    Route::get('/logs/{log}', [LogController::class, 'show'])->name('logs.show');
});
