<?php

namespace App\Services\Ekspor;

use App\Models\Prestasi;
use App\Models\User;
use Generator;

/** LAP-02: satu baris per prestasi × anggota internal. Kontak (surel/HP) hanya untuk pengelola master (BR-26). */
class RekapAnggotaLaporan extends LaporanEkspor
{
    public function jenis(): string
    {
        return 'rekap_anggota';
    }

    public function judul(): string
    {
        return 'Rekap Prestasi per Anggota';
    }

    /** @return list<string> */
    public function format(): array
    {
        return ['xlsx'];
    }

    public function header(User $pengguna): array
    {
        return [
            'No. Registrasi', 'Kegiatan', 'Tingkat', 'Capaian', 'Tahun', 'NPM', 'Nama', 'Prodi Anggota', 'Peran', 'Poin',
            ...($pengguna->can('master.kelola') ? ['Surel', 'Nomor HP'] : []),
        ];
    }

    public function jumlah(array $filter, User $pengguna): int
    {
        $prodi = $this->prodiTerbatas($pengguna);

        return QueryPrestasiLaporan::bangun($filter, $pengguna)->withCount(['anggota as n' => fn ($q) => $prodi === null ? $q : $q->where('prodi_id', $prodi)])->get()->sum('n');
    }

    public function baris(array $filter, User $pengguna): Generator
    {
        $prodi = $this->prodiTerbatas($pengguna);
        $kontak = $pengguna->can('master.kelola');

        /** @var Prestasi $p */
        foreach (QueryPrestasiLaporan::bangun($filter, $pengguna)->with(['tingkat', 'capaian', 'anggota.mahasiswa', 'anggota.prodi'])->lazyById(500, 'prestasi.id', 'id') as $p) {
            foreach ($p->anggota as $a) {
                if ($prodi !== null && $a->prodi_id !== $prodi) {
                    continue;
                }
                yield [
                    $p->nomor_registrasi, $p->nama_kegiatan, $p->tingkat->nama, $p->capaian->nama, $p->tahun, $a->mahasiswa->npm, $a->mahasiswa->nama,
                    $a->prodi->nama, $a->peran->label(), $a->poin === null ? null : (string) $a->poin,
                    ...($kontak ? [$a->mahasiswa->email, $a->mahasiswa->nomor_hp] : []),
                ];
            }
        }
    }

    /** admin-prodi hanya baris anggota dari prodinya. */
    private function prodiTerbatas(User $pengguna): ?string
    {
        return $pengguna->can('prestasi.lihat-semua') ? null : $pengguna->prodi_id;
    }
}
