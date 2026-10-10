<?php

namespace App\Actions\Prestasi;

use App\Enums\BentukPartisipasi;
use App\Enums\PeranAnggota;
use App\Enums\StatusKonfirmasiPembimbing;
use App\Events\AnggotaTimDitambahkan;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\PrestasiAnggota;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Sinkronisasi tim: anggota FKIP (kredit penuh, BR-02), anggota luar FKIP, dan dosen pembimbing (BR-11).
 * Bentuk prestasi dibaca dari record (disimpan lebih dulu oleh SimpanPrestasi).
 */
class SimpanAnggotaTim
{
    /**
     * @param  list<array{mahasiswa_id?: string, peran?: string}>  $internal  tanpa pengaju (selalu anggota)
     * @param  list<array{nama?: string, nomor_induk?: ?string, institusi?: string, program_studi?: ?string}>  $eksternal
     * @param  array{dosen_pembimbing_id?: ?string, nama_pembimbing_lain?: ?string}  $pembimbing
     */
    public function jalankan(Prestasi $prestasi, array $internal, array $eksternal, array $pembimbing, User $pelaku): Prestasi
    {
        Gate::forUser($pelaku)->authorize('update', $prestasi);

        $internal = $this->bersihkanInternal($prestasi, $internal);
        $eksternal = $this->validasiEksternal($eksternal);
        $pembimbing = $this->validasiPembimbing($pembimbing);
        $this->validasiJumlah($prestasi, $internal, $eksternal);

        $baru = [];
        DB::transaction(function () use ($prestasi, $internal, $eksternal, $pembimbing, &$baru): void {
            $baru = $this->sinkronInternal($prestasi, $internal);
            $this->sinkronEksternal($prestasi, $eksternal);
            $this->simpanPembimbing($prestasi, $pembimbing);
        });

        foreach ($baru as $mahasiswa) {
            AnggotaTimDitambahkan::dispatch($prestasi, $mahasiswa, $pelaku);
        }

        return $prestasi->refresh();
    }

    /**
     * @param  list<array<string, mixed>>  $internal
     * @return list<array{mahasiswa_id: string, peran: string}>
     */
    private function bersihkanInternal(Prestasi $prestasi, array $internal): array
    {
        $internal = array_values(array_filter($internal, fn ($baris) => filled($baris['mahasiswa_id'] ?? null)));

        $validator = Validator::make(['anggota' => $internal], [
            'anggota' => ['array'],
            'anggota.*.mahasiswa_id' => ['required', 'distinct', 'exists:mahasiswa,id', Rule::notIn([$prestasi->pengaju_id])],
            'anggota.*.peran' => ['nullable', Rule::enum(PeranAnggota::class)],
        ], [
            'anggota.*.mahasiswa_id.distinct' => 'Mahasiswa tidak boleh dipilih lebih dari sekali.',
            'anggota.*.mahasiswa_id.not_in' => 'Pengaju sudah otomatis menjadi anggota.',
        ], ['anggota.*.mahasiswa_id' => 'anggota', 'anggota.*.peran' => 'peran anggota']);

        $validator->validate();

        $aktif = Mahasiswa::whereIn('id', array_column($internal, 'mahasiswa_id'))->get()->keyBy('id');
        foreach ($internal as $baris) {
            if (! $aktif[$baris['mahasiswa_id']]->status_mahasiswa->bolehMasukPortal()) {
                throw ValidationException::withMessages(['anggota' => "Mahasiswa {$aktif[$baris['mahasiswa_id']]->npm} tidak berstatus aktif atau cuti."]);
            }
        }

        $ketua = array_filter($internal, fn ($b) => ($b['peran'] ?? null) === PeranAnggota::Ketua->value);
        if (count($ketua) > 1) {
            throw ValidationException::withMessages(['anggota' => 'Hanya boleh satu ketua tim.']);
        }

        return array_map(fn ($b) => ['mahasiswa_id' => $b['mahasiswa_id'], 'peran' => $b['peran'] ?? PeranAnggota::Anggota->value], $internal);
    }

    /**
     * @param  list<array<string, mixed>>  $eksternal
     * @return list<array<string, mixed>>
     */
    private function validasiEksternal(array $eksternal): array
    {
        $eksternal = array_values(array_filter($eksternal, fn ($b) => filled($b['nama'] ?? null) || filled($b['institusi'] ?? null)));

        return Validator::make(['eksternal' => $eksternal], [
            'eksternal.*.nama' => ['required', 'string', 'max:150'],
            'eksternal.*.nomor_induk' => ['nullable', 'string', 'max:30'],
            'eksternal.*.institusi' => ['required', 'string', 'max:150'],
            'eksternal.*.program_studi' => ['nullable', 'string', 'max:150'],
        ], [], ['eksternal.*.nama' => 'nama anggota luar', 'eksternal.*.institusi' => 'institusi'])->validate()['eksternal'] ?? [];
    }

    /**
     * @param  array<string, mixed>  $pembimbing
     * @return array{dosen_pembimbing_id: ?string, nama_pembimbing_lain: ?string}
     */
    private function validasiPembimbing(array $pembimbing): array
    {
        $data = Validator::make($pembimbing, [
            'dosen_pembimbing_id' => ['nullable', 'exists:dosen,id'],
            'nama_pembimbing_lain' => ['nullable', 'string', 'max:150'],
        ], [], ['dosen_pembimbing_id' => 'dosen pembimbing', 'nama_pembimbing_lain' => 'nama pembimbing lain'])->validate();

        $dosenId = filled($data['dosen_pembimbing_id'] ?? null) ? $data['dosen_pembimbing_id'] : null;

        return [
            'dosen_pembimbing_id' => $dosenId,
            // Pembimbing di luar master hanya dicatat bila tidak memilih dosen dari daftar.
            'nama_pembimbing_lain' => $dosenId === null && filled($data['nama_pembimbing_lain'] ?? null) ? $data['nama_pembimbing_lain'] : null,
        ];
    }

    /**
     * @param  list<array{mahasiswa_id: string, peran: string}>  $internal
     * @param  list<array<string, mixed>>  $eksternal
     */
    private function validasiJumlah(Prestasi $prestasi, array $internal, array $eksternal): void
    {
        if ($prestasi->bentuk === BentukPartisipasi::Individu && ($internal !== [] || $eksternal !== [])) {
            throw ValidationException::withMessages(['anggota' => 'Prestasi individu hanya memiliki pengaju, tanpa anggota lain.']);
        }

        if ($prestasi->bentuk === BentukPartisipasi::Kelompok && 1 + count($internal) + count($eksternal) < 2) {
            throw ValidationException::withMessages(['anggota' => 'Prestasi kelompok memerlukan minimal 2 anggota (FKIP dan luar FKIP).']);
        }
    }

    /**
     * @param  list<array{mahasiswa_id: string, peran: string}>  $internal
     * @return list<Mahasiswa> anggota yang baru ditambahkan
     */
    private function sinkronInternal(Prestasi $prestasi, array $internal): array
    {
        $ada = PrestasiAnggota::where('prestasi_id', $prestasi->id)->get()->keyBy('mahasiswa_id');
        $dipilih = array_column($internal, 'mahasiswa_id');

        // Hapus anggota yang tidak dipilih lagi (pengaju tidak pernah dihapus).
        $ada->each(function (PrestasiAnggota $a) use ($dipilih): void {
            if (! $a->is_pengaju && ! in_array($a->mahasiswa_id, $dipilih, true)) {
                $a->delete();
            }
        });

        $adaKetuaLain = collect($internal)->contains('peran', PeranAnggota::Ketua->value);
        $pengaju = $ada[$prestasi->pengaju_id] ?? null;
        $pengaju?->update(['peran' => $adaKetuaLain ? PeranAnggota::Anggota : PeranAnggota::Ketua]);

        $mahasiswa = Mahasiswa::whereIn('id', $dipilih)->get()->keyBy('id');
        $baru = [];
        foreach ($internal as $baris) {
            $m = $mahasiswa[$baris['mahasiswa_id']];
            if (isset($ada[$m->id])) {
                // Pertahankan setuju_publikasi dan snapshot prodi anggota yang sudah ada.
                $ada[$m->id]->update(['peran' => $baris['peran']]);

                continue;
            }
            PrestasiAnggota::create([
                'prestasi_id' => $prestasi->id,
                'mahasiswa_id' => $m->id,
                'prodi_id' => $m->prodi_id,
                'peran' => $baris['peran'],
                'is_pengaju' => false,
            ]);
            $baru[] = $m;
        }

        return $baru;
    }

    /** @param  list<array<string, mixed>>  $eksternal */
    private function sinkronEksternal(Prestasi $prestasi, array $eksternal): void
    {
        $prestasi->anggotaEksternal()->get()->each->delete();

        foreach ($eksternal as $baris) {
            $prestasi->anggotaEksternal()->create([
                'nama' => $baris['nama'],
                'nomor_induk' => filled($baris['nomor_induk'] ?? null) ? $baris['nomor_induk'] : null,
                'institusi' => $baris['institusi'],
                'program_studi' => filled($baris['program_studi'] ?? null) ? $baris['program_studi'] : null,
            ]);
        }
    }

    /** @param  array{dosen_pembimbing_id: ?string, nama_pembimbing_lain: ?string}  $pembimbing */
    private function simpanPembimbing(Prestasi $prestasi, array $pembimbing): void
    {
        $berubah = $prestasi->dosen_pembimbing_id !== $pembimbing['dosen_pembimbing_id'];

        $prestasi->fill($pembimbing);

        // Pembimbing diganti/dihapus: konfirmasi diulang (diset 'menunggu' saat diajukan, BR-11).
        if ($berubah) {
            $prestasi->status_konfirmasi_pembimbing = StatusKonfirmasiPembimbing::TidakAda;
            $prestasi->catatan_pembimbing = null;
            $prestasi->dikonfirmasi_pembimbing_at = null;
        }

        if ($pembimbing['dosen_pembimbing_id'] !== null) {
            Dosen::findOrFail($pembimbing['dosen_pembimbing_id']);
        }

        $prestasi->save();
    }
}
