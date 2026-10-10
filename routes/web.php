<?php

use App\Http\Controllers\BukaTautanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Nama 'login' dipakai middleware auth untuk mengarahkan tamu (rute web bersama seperti tautan.buka).
Route::get('/masuk', fn () => redirect()->route('filament.admin.auth.login'))->name('login');

Route::get('/tautan/{tautanBerkas}/buka', BukaTautanController::class)
    ->middleware(['auth', 'throttle:buka-tautan'])
    ->name('tautan.buka');
