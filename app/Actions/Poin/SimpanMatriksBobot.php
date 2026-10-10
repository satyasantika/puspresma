<?php

namespace App\Actions\Poin;

use App\Models\BobotPoin;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Menyimpan matriks bobot (capaian × tingkat) sebagai periode baru. Periode lama yang masih terbuka
 * ditutup sehari sebelum berlaku_mulai baru (BR-18). Sel kosong tidak mengubah apa pun.
 */
class SimpanMatriksBobot
{
    /**
     * @param  array<string, array<string, mixed>>  $matriks  [capaian_id][tingkat_id] => poin ("40" / "40.50" / kosong)
     * @return int Jumlah sel yang disimpan
     */
    public function jalankan(string $jenisId, CarbonImmutable $berlakuMulai, string $dasarPenetapan, array $matriks, User $pelaku): int
    {
        if (! $pelaku->can('poin.kelola')) {
            throw new AuthorizationException('Anda tidak berhak mengelola bobot poin.');
        }

        if (trim($dasarPenetapan) === '') {
            throw ValidationException::withMessages(['dasar_penetapan' => 'Dasar penetapan (mis. nomor SK Dekan) wajib diisi.']);
        }

        $sel = $this->kumpulkan($matriks);

        return DB::transaction(function () use ($jenisId, $berlakuMulai, $dasarPenetapan, $sel): int {
            foreach ($sel as [$capaianId, $tingkatId, $poin]) {
                $kunci = ['jenis_kegiatan_id' => $jenisId, 'capaian_id' => $capaianId, 'tingkat_id' => $tingkatId];

                $sudahAdaLebihBaru = BobotPoin::where($kunci)->where('berlaku_mulai', '>', $berlakuMulai->toDateString())->exists();
                if ($sudahAdaLebihBaru) {
                    throw ValidationException::withMessages(['berlaku_mulai' => 'Sudah ada periode bobot yang berlaku setelah tanggal ini.']);
                }

                BobotPoin::where($kunci)->whereNull('berlaku_sampai')
                    ->where('berlaku_mulai', '<', $berlakuMulai->toDateString())
                    ->get()
                    ->each(fn (BobotPoin $lama) => $lama->update(['berlaku_sampai' => $berlakuMulai->subDay()->toDateString()]));

                BobotPoin::updateOrCreate(
                    $kunci + ['berlaku_mulai' => $berlakuMulai->toDateString()],
                    ['poin' => $poin, 'berlaku_sampai' => null, 'dasar_penetapan' => $dasarPenetapan],
                );
            }

            return count($sel);
        });
    }

    /**
     * @param  array<string, array<string, mixed>>  $matriks
     * @return list<array{0: string, 1: string, 2: string}>
     */
    private function kumpulkan(array $matriks): array
    {
        $hasil = [];
        foreach ($matriks as $capaianId => $baris) {
            foreach ($baris as $tingkatId => $nilai) {
                $nilai = is_string($nilai) || is_numeric($nilai) ? trim((string) $nilai) : '';
                if ($nilai === '') {
                    continue;
                }
                $nilai = str_replace(',', '.', $nilai);
                if (! preg_match('/^\d{1,6}(\.\d{1,2})?$/', $nilai)) {
                    throw ValidationException::withMessages(['matriks' => "Nilai poin '{$nilai}' tidak valid (maksimal 6 digit, 2 desimal)."]);
                }
                [$bulat, $pecahan] = array_pad(explode('.', $nilai), 2, '');
                $hasil[] = [(string) $capaianId, (string) $tingkatId, $bulat.'.'.str_pad($pecahan, 2, '0')];
            }
        }

        return $hasil;
    }
}
