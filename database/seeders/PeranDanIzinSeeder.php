<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class PeranDanIzinSeeder extends Seeder
{
    /** Seluruh permission (PRD §3). */
    public const IZIN = [
        'pengguna.kelola', 'master.kelola', 'mahasiswa.impor', 'dosen.impor',
        'prestasi.lihat-semua', 'prestasi.lihat-prodi', 'prestasi.lihat-bimbingan',
        'prestasi.buat-milik', 'prestasi.buat-atas-nama',
        'prestasi.verifikasi-prodi', 'prestasi.verifikasi', 'prestasi.buka-verifikasi',
        'duplikat.tinjau', 'pembimbing.konfirmasi',
        'poin.kelola', 'poin.hitung-ulang',
        'kompetisi.kelola', 'kompetisi.kelola-prodi',
        'galeri.moderasi', 'laporan.lihat', 'laporan.ekspor', 'simkatmawa.ekspor',
        'surat.terbitkan', 'pengaturan.kelola', 'audit.lihat', 'horizon.akses',
    ];

    /** Pemetaan peran ke permission; super-admin menerima semua izin. */
    public const PERAN = [
        'super-admin' => ['*'],
        'admin-fakultas' => [
            'master.kelola', 'mahasiswa.impor', 'dosen.impor', 'prestasi.lihat-semua', 'prestasi.buat-atas-nama',
            'prestasi.verifikasi', 'prestasi.buka-verifikasi', 'duplikat.tinjau', 'poin.kelola', 'poin.hitung-ulang',
            'kompetisi.kelola', 'galeri.moderasi', 'laporan.lihat', 'laporan.ekspor', 'simkatmawa.ekspor',
            'surat.terbitkan', 'pengaturan.kelola', 'audit.lihat',
        ],
        'admin-prodi' => ['prestasi.lihat-prodi', 'prestasi.verifikasi-prodi', 'kompetisi.kelola-prodi', 'laporan.lihat', 'laporan.ekspor'],
        'pimpinan' => ['prestasi.lihat-semua', 'laporan.lihat', 'laporan.ekspor'],
        'dosen' => ['prestasi.lihat-bimbingan', 'pembimbing.konfirmasi'],
        'mahasiswa' => ['prestasi.buat-milik'],
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::IZIN as $izin) {
            Permission::firstOrCreate(['name' => $izin, 'guard_name' => 'web']);
        }

        foreach (self::PERAN as $nama => $izinPeran) {
            $peran = Role::firstOrCreate(['name' => $nama, 'guard_name' => 'web']);
            $peran->syncPermissions($izinPeran === ['*'] ? self::IZIN : $izinPeran);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
