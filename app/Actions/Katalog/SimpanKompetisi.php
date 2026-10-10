<?php

namespace App\Actions\Katalog;

use App\Contracts\PenyimpananBerkas;
use App\Enums\JenisTautan;
use App\Models\Kompetisi;
use App\Models\User;
use App\Rules\TautanBerkasValid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/** Membuat/mengubah kompetisi beserta prodi sasaran dan tautan poster (tanpa unggahan, K-tautan). */
class SimpanKompetisi
{
    public const KOLOM = [
        'judul', 'penyelenggara', 'jenis_kegiatan_id', 'bidang', 'kategori_lomba_id', 'tingkat_id', 'deskripsi', 'url_pendaftaran',
        'url_panduan', 'tanggal_buka', 'tenggat_pendaftaran', 'tanggal_pelaksanaan_mulai', 'tanggal_pelaksanaan_selesai', 'moda',
        'lokasi', 'biaya_pendaftaran', 'kontak_narahubung',
    ];

    public function __construct(private readonly PenyimpananBerkas $penyimpanan) {}

    /**
     * @param  array<string, mixed>  $data  kolom kompetisi + `prodi_sasaran` (list id) + `url_poster`
     */
    public function jalankan(?Kompetisi $kompetisi, array $data, User $pelaku): Kompetisi
    {
        $gate = Gate::forUser($pelaku);
        $kompetisi === null ? $gate->authorize('create', Kompetisi::class) : $gate->authorize('update', $kompetisi);

        $nilai = Validator::make($data, [
            'judul' => ['required', 'string', 'max:255'],
            'penyelenggara' => ['required', 'string', 'max:255'],
            'jenis_kegiatan_id' => ['required', 'exists:jenis_kegiatan,id'],
            'tenggat_pendaftaran' => ['required', 'date'],
            'url_pendaftaran' => ['nullable', 'url', 'max:500'],
            'url_panduan' => ['nullable', 'url', 'max:500'],
            'biaya_pendaftaran' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'url_poster' => ['nullable', new TautanBerkasValid],
        ], [], ['url_poster' => 'tautan poster'])->validate();

        $prodi = $this->prodiSasaran($data, $pelaku);
        $atribut = collect($data)->only(self::KOLOM)->map(fn ($v) => is_string($v) && trim($v) === '' ? null : $v)->all();

        return DB::transaction(function () use ($kompetisi, $atribut, $prodi, $nilai, $pelaku): Kompetisi {
            if ($kompetisi === null) {
                $kompetisi = new Kompetisi($atribut);
                $kompetisi->forceFill(['dibuat_oleh' => $pelaku->getKey(), 'status' => 'draf'])->save();
            } else {
                $kompetisi->update($atribut);
            }

            $kompetisi->prodiSasaran()->sync($prodi);
            $this->simpanPoster($kompetisi, $nilai['url_poster'] ?? null, $pelaku);

            return $kompetisi;
        });
    }

    /**
     * Admin-prodi dipaksa menyasar prodinya sendiri (BR-23); admin-fakultas bebas (kosong = semua prodi).
     *
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function prodiSasaran(array $data, User $pelaku): array
    {
        if ($pelaku->can('kompetisi.kelola')) {
            return array_values(array_filter((array) ($data['prodi_sasaran'] ?? [])));
        }

        if ($pelaku->prodi_id === null) {
            throw ValidationException::withMessages(['prodi_sasaran' => 'Akun Anda belum terhubung dengan prodi.']);
        }

        return [$pelaku->prodi_id];
    }

    private function simpanPoster(Kompetisi $kompetisi, ?string $url, User $pelaku): void
    {
        if (blank($url)) {
            return;
        }

        $poster = $kompetisi->poster()->first();
        if ($poster === null) {
            $this->penyimpanan->simpanTautan($kompetisi, JenisTautan::Poster, $url, 'Poster', $pelaku);
        } elseif ($poster->url !== trim($url)) {
            $poster->update(['url' => trim($url)]);
        }
    }
}
