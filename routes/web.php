<?php

use App\Http\Controllers\AuthController;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Mahasiswa\Dashboard as MahasiswaDashboard;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route(auth()->user()->dashboardRoute())
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', AdminDashboard::class)->name('dashboard');
    });

Route::middleware(['auth', 'role:mahasiswa'])
    ->prefix('mahasiswa')
    ->name('mahasiswa.')
    ->group(function () {
        Route::get('/', MahasiswaDashboard::class)->name('dashboard');
    });
