<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('membuat id pengguna berupa uuid versi 7', function () {
    $user = User::factory()->create();

    expect(Str::isUuid($user->id))->toBeTrue()
        ->and($user->id[14])->toBe('7');
});

it('menyimpan kolom id dan user_id sesi sebagai uuid', function () {
    $kolom = collect(Schema::getColumns('users'))->firstWhere('name', 'id');
    $sesi = collect(Schema::getColumns('sessions'))->firstWhere('name', 'user_id');

    expect($kolom['type_name'])->toBe('uuid')
        ->and($sesi['type_name'])->toBe('uuid');
});

it('menjalankan route model binding dengan uuid', function () {
    Route::middleware('web')->get('/uji-binding/{user}', fn (User $user) => $user->email)
        ->whereUuid('user');

    $user = User::factory()->create();

    $this->get("/uji-binding/{$user->id}")->assertOk()->assertSee($user->email);
    $this->get('/uji-binding/1')->assertNotFound();
});
