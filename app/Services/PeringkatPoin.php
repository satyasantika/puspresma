<?php

namespace App\Services;

use App\Models\Mahasiswa;
use App\Models\PrestasiAnggota;
use App\Models\User;
use App\Support\ProdiScope;
use Illuminate\Support\Collection;

/** Peringkat mahasiswa berdasarkan SUM(poin) prestasi terverifikasi (poin disimpan sebagai DECIMAL). */
class PeringkatPoin
{
    /**
     * @param  array{dari?: ?string, sampai?: ?string, prodi_id?: ?string, angkatan?: ?int}  $filter
     * @return Collection<int, array{peringkat: int, mahasiswa: Mahasiswa, jumlah_prestasi: int, total_poin: string}>
     */
    public function hitung(User $pelaku, array $filter = [], int $batas = 200): Collection
    {
        $baris = PrestasiAnggota::query()
            ->join('prestasi', 'prestasi.id', '=', 'prestasi_anggota.prestasi_id')
            ->where('prestasi.status', 'terverifikasi')->whereNull('prestasi.deleted_at')
            ->whereNotNull('prestasi_anggota.poin')
            ->when($filter['dari'] ?? null, fn ($q, $v) => $q->where('prestasi.tanggal_selesai', '>=', $v))
            ->when($filter['sampai'] ?? null, fn ($q, $v) => $q->where('prestasi.tanggal_selesai', '<=', $v))
            ->when($filter['prodi_id'] ?? null, fn ($q, $v) => $q->where('prestasi_anggota.prodi_id', $v))
            ->when($filter['angkatan'] ?? null, fn ($q, $v) => $q->whereIn('prestasi_anggota.mahasiswa_id', Mahasiswa::where('angkatan', $v)->select('id')));

        // admin-prodi hanya melihat anggota dari prodinya.
        $baris = ProdiScope::untuk($pelaku, $baris, 'prestasi_anggota.prodi_id');

        /** @var Collection<int, object{mahasiswa_id: string, jumlah_prestasi: int|string, total_poin: string}> $agregat */
        $agregat = $baris->selectRaw('prestasi_anggota.mahasiswa_id, COUNT(*) as jumlah_prestasi, SUM(prestasi_anggota.poin) as total_poin')
            ->groupBy('prestasi_anggota.mahasiswa_id')
            ->orderByRaw('SUM(prestasi_anggota.poin) DESC')
            ->limit($batas)
            ->toBase()
            ->get();

        $mahasiswa = Mahasiswa::with('prodi')->whereIn('id', $agregat->pluck('mahasiswa_id'))->get()->keyBy('id');

        return $agregat->values()->map(fn ($a, int $i) => [
            'peringkat' => $i + 1,
            'mahasiswa' => $mahasiswa->get($a->mahasiswa_id) ?? new Mahasiswa,
            'jumlah_prestasi' => (int) $a->jumlah_prestasi,
            'total_poin' => bcadd((string) $a->total_poin, '0', 2),
        ]);
    }
}
