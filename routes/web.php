<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'role:super_admin,hr_admin'])->name('dashboard');

Route::get('/scanner/dashboard', function () {
    return view('scanner.dashboard');
})->middleware(['auth', 'role:panitia'])->name('scanner.dashboard');

Route::get('/pegawai/dashboard', function () {
    return view('pegawai.dashboard');
})->middleware(['auth', 'role:pegawai'])->name('pegawai.dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
