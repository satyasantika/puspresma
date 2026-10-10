<?php

namespace App\Actions\Surat;

use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\SuratKeteranganPrestasi;
use App\Models\User;
use App\Services\Pengaturan;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Menerbitkan surat (BR-24): hanya prestasi terverifikasi milik mahasiswa tsb; nomor ber-lock; hanya data yang disimpan.
 */
class TerbitkanSuratKeteranganPrestasi
{
    /**
     * @param  list<string>  $prestasiIds
     * @param  array<string, mixed>  $data  keperluan, penandatangan_nama, penandatangan_jabatan wajib; periode_mulai/selesai, penandatangan_nip opsional
     */
    public function jalankan(Mahasiswa $mahasiswa, array $prestasiIds, array $data, User $pelaku): SuratKeteranganPrestasi
    {
        if (! $pelaku->can('surat.terbitkan')) {
            throw new AuthorizationException('Anda tidak berhak menerbitkan surat keterangan.');
        }

        foreach (['keperluan', 'penandatangan_nama', 'penandatangan_jabatan'] as $wajib) {
            if (blank($data[$wajib] ?? null)) {
                throw ValidationException::withMessages([$wajib => 'Wajib diisi.']);
            }
        }

        $prestasi = Prestasi::query()->terverifikasi()->whereIn('id', $prestasiIds)->milikMahasiswa($mahasiswa->id)->get();
        if ($prestasiIds === [] || $prestasi->count() !== count(array_unique($prestasiIds))) {
            throw ValidationException::withMessages(['prestasi' => 'Pilih minimal satu prestasi; hanya prestasi terverifikasi milik mahasiswa ini yang dapat dimuat.']);
        }

        $tahun = now()->year;

        return Cache::lock("lock:nomor-surat-prestasi:{$tahun}", 10)->block(5, fn (): SuratKeteranganPrestasi => DB::transaction(function () use ($mahasiswa, $prestasi, $data, $pelaku, $tahun): SuratKeteranganPrestasi {
            $urut = SuratKeteranganPrestasi::query()->whereYear('tanggal_terbit', $tahun)->lockForUpdate()->count() + 1;
            $nomor = strtr((string) Pengaturan::ambil('format_nomor_surat_prestasi', '{urut}/PRS/FKIP/{tahun}'), [
                '{urut}' => str_pad((string) $urut, 3, '0', STR_PAD_LEFT), '{tahun}' => (string) $tahun,
            ]);

            $surat = SuratKeteranganPrestasi::create([
                'nomor' => $nomor, 'mahasiswa_id' => $mahasiswa->id, 'keperluan' => $data['keperluan'],
                'periode_mulai' => $data['periode_mulai'] ?? null, 'periode_selesai' => $data['periode_selesai'] ?? null,
                'tanggal_terbit' => now()->toDateString(), 'penandatangan_nama' => $data['penandatangan_nama'],
                'penandatangan_jabatan' => $data['penandatangan_jabatan'], 'penandatangan_nip' => $data['penandatangan_nip'] ?? null,
                'diterbitkan_oleh' => $pelaku->getKey(),
            ]);
            $prestasi->each(fn (Prestasi $p) => $surat->item()->create(['prestasi_id' => $p->id]));

            return $surat;
        }));
    }
}
