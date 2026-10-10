<?php

use App\Http\Controllers\BerandaController;
use App\Http\Controllers\BukaTautanController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\KebijakanGaleriController;
use App\Livewire\Publik\DetailKompetisi;
use App\Livewire\Publik\DetailPrestasiPublik;
use App\Livewire\Publik\GaleriPrestasi;
use App\Livewire\Publik\KatalogKompetisi;
use Illuminate\Support\Facades\Route;

Route::get('/', BerandaController::class)->middleware('throttle:galeri')->name('beranda');

// Galeri prestasi publik (F6.4).
Route::middleware('throttle:galeri')->group(function () {
    Route::get('/galeri', GaleriPrestasi::class)->name('galeri.index');
    Route::get('/galeri/{prestasi}', DetailPrestasiPublik::class)->name('galeri.show');
    Route::get('/kebijakan-privasi-galeri', KebijakanGaleriController::class)->name('galeri.kebijakan');
});

// Katalog kompetisi publik (F6.2).
Route::middleware('throttle:katalog-publik')->group(function () {
    Route::get('/kompetisi', KatalogKompetisi::class)->name('kompetisi.index');
    Route::get('/kompetisi/{kompetisi:slug}', DetailKompetisi::class)->name('kompetisi.show');
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
