<?php

namespace App\Services;

use App\Models\Prestasi;
use App\Models\PrestasiAnggota;

/**
 * Menerjemahkan kunci sumber (config/simkatmawa.php) menjadi nilai untuk satu baris ekspor. URL bukti diambil dari
 * snapshot_tautan_verifikasi TERBARU per tautan (BR-28), bukan dari tautan_berkas yang dapat berubah setelah verifikasi.
 */
class PemetaanSimkatmawa
{
    /**
     * Kunci sumber yang dikenali.
     *
     * @return list<string>
     */
    public static function sumberTersedia(): array
    {
        return [
            'mahasiswa.npm', 'mahasiswa.nama', 'prodi.kode_pddikti', 'prodi.nama', 'kegiatan.nama', 'kegiatan.penyelenggara',
            'jenis.kode_simkatmawa', 'kategori.kode_simkatmawa', 'tingkat.kode_simkatmawa', 'capaian.kode_simkatmawa', 'bentuk',
            'kegiatan.tanggal_mulai', 'kegiatan.tanggal_selesai', 'kegiatan.url', 'kegiatan.jumlah_peserta', 'kegiatan.jumlah_pt',
            'kegiatan.jumlah_negara', 'pembimbing.nidn', 'pembimbing.nama', 'bukti.url_sertifikat', 'bukti.url_foto', 'bukti.url_surat_tugas',
            'prestasi.nomor_registrasi', 'meta.catatan_pemetaan', 'meta.status_tautan',
        ];
    }

    /**
     * Satu baris sesuai urutan kolom config.
     *
     * @return list<string|int|null>
     */
    public function baris(Prestasi $p, PrestasiAnggota $a): array
    {
        $catatan = $this->catatanPemetaan($p);

        return array_values(array_map(fn (string $sumber) => $this->nilai($sumber, $p, $a, $catatan), config('simkatmawa.kolom')));
    }

    /** @return list<string> */
    public function header(): array
    {
        return array_keys(config('simkatmawa.kolom'));
    }

    /** @param  list<string>  $catatan */
    private function nilai(string $sumber, Prestasi $p, PrestasiAnggota $a, array $catatan): string|int|null
    {
        return match ($sumber) {
            'mahasiswa.npm' => $a->mahasiswa->npm,
            'mahasiswa.nama' => $a->mahasiswa->nama,
            'prodi.kode_pddikti' => $a->prodi->kode_pddikti,
            'prodi.nama' => $a->prodi->nama,
            'kegiatan.nama' => $p->nama_kegiatan,
            'kegiatan.penyelenggara' => $p->penyelenggara,
            'jenis.kode_simkatmawa' => $p->jenisKegiatan->kode_simkatmawa ?: $p->jenisKegiatan->nama,
            'kategori.kode_simkatmawa' => $p->kategoriLomba === null ? null : ($p->kategoriLomba->kode_simkatmawa ?: $p->kategoriLomba->nama),
            'tingkat.kode_simkatmawa' => $p->tingkat->kode_simkatmawa ?: $p->tingkat->nama,
            'capaian.kode_simkatmawa' => $p->capaian->kode_simkatmawa ?: $p->capaian->nama,
            'bentuk' => $p->bentuk->label(),
            'kegiatan.tanggal_mulai' => $p->tanggal_mulai->format('Y-m-d'),
            'kegiatan.tanggal_selesai' => $p->tanggal_selesai->format('Y-m-d'),
            'kegiatan.url' => $p->url_kegiatan,
            'kegiatan.jumlah_peserta' => $p->jumlah_peserta,
            'kegiatan.jumlah_pt' => $p->jumlah_pt_peserta,
            'kegiatan.jumlah_negara' => $p->jumlah_negara_peserta,
            'pembimbing.nidn' => $p->dosenPembimbing?->nidn,
            'pembimbing.nama' => $p->dosenPembimbing !== null ? $p->dosenPembimbing->nama_lengkap : $p->nama_pembimbing_lain,
            'bukti.url_sertifikat' => $this->urlBukti($p, 'sertifikat_piagam'),
            'bukti.url_foto' => $this->urlBukti($p, 'foto_penyerahan'),
            'bukti.url_surat_tugas' => $this->urlBukti($p, 'surat_tugas'),
            'prestasi.nomor_registrasi' => $p->nomor_registrasi,
            'meta.catatan_pemetaan' => $catatan === [] ? null : implode('; ', $catatan),
            'meta.status_tautan' => $p->tautanBukti->pluck('status_cek.value')->unique()->sort()->implode(', ') ?: null,
            default => null,
        };
    }

    /** URL dari snapshot terbaru per tautan untuk satu jenis, digabung " ; ". */
    private function urlBukti(Prestasi $p, string $jenis): ?string
    {
        $url = $p->snapshotTautan->where('jenis', $jenis)->groupBy('tautan_berkas_id')
            ->map(fn ($g) => $g->sortByDesc('created_at')->first()->url)->implode(' ; ');

        return $url === '' ? null : $url;
    }

    /** @return list<string> */
    private function catatanPemetaan(Prestasi $p): array
    {
        $kosong = [];
        foreach (['jenis' => $p->jenisKegiatan, 'kategori' => $p->kategoriLomba, 'tingkat' => $p->tingkat, 'capaian' => $p->capaian] as $nama => $master) {
            if ($master !== null && blank($master->kode_simkatmawa)) {
                $kosong[] = $nama;
            }
        }

        return $kosong === [] ? [] : ['Kode SIMKATMAWA kosong ('.implode(', ', $kosong).'); memakai nama master'];
    }
}
