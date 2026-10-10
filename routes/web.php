<?php

use App\Http\Controllers\BukaTautanController;
use App\Http\Controllers\GoogleAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Nama 'login' dipakai middleware auth untuk mengarahkan tamu (rute web bersama seperti tautan.buka).
Route::get('/masuk', fn () => redirect()->route('filament.admin.auth.login'))->name('login');

Route::get('/tautan/{tautanBerkas}/buka', BukaTautanController::class)
    ->middleware(['auth', 'throttle:buka-tautan'])
    ->name('tautan.buka');

// Login Google akun kampus (opsional; 404 bila GOOGLE_LOGIN_ENABLED=false, BR-20).
Route::middleware(['guest', 'throttle:google-callback'])->prefix('auth/google')->group(function () {
    Route::get('redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});
