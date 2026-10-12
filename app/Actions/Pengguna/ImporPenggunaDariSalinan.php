<?php

namespace App\Actions\Pengguna;

use App\Models\Prodi;
use App\Models\User;
use Database\Seeders\PeranDanIzinSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Impor massal pengguna dari teks hasil salin-tempel (Excel/Sheets menempel sebagai TSV; CSV dan titik koma juga diterima).
 * Upsert menurut surel. Peran super-admin tidak dapat diberikan atau diubah lewat impor. Satu baris gagal tidak
 * membatalkan baris lain; setiap baris diproses lewat SimpanPengguna sehingga aturannya sama dengan formulir.
 */
class ImporPenggunaDariSalinan
{
    public const MAKS_BARIS = 500;

    /** Urutan kolom bila tidak ada baris judul. */
    public const KOLOM = ['nama', 'email', 'peran', 'kode_prodi', 'npm', 'nidn', 'nip', 'nomor_wa', 'sandi'];

    private const ALIAS = [
        'name' => 'nama', 'nama' => 'nama', 'email' => 'email', 'surel' => 'email', 'peran' => 'peran', 'role' => 'peran',
        'kode_prodi' => 'kode_prodi', 'prodi' => 'kode_prodi', 'npm' => 'npm', 'nidn' => 'nidn', 'nip' => 'nip',
        'nomor_wa' => 'nomor_wa', 'wa' => 'nomor_wa', 'hp' => 'nomor_wa',
        'sandi' => 'sandi', 'password' => 'sandi', 'kata_sandi' => 'sandi',
    ];

    /**
     * Memeriksa teks tanpa menyimpan apa pun.
     *
     * @return list<array{no: int, status: 'baru'|'ubah'|'galat', data: array<string, string>, galat: list<string>}>
     */
    public function periksa(string $teks): array
    {
        $baris = $this->urai($teks);
        $sudah = [];
        $hasil = [];
        $prodi = Prodi::pluck('id', 'kode')->mapWithKeys(fn ($id, $kode) => [mb_strtolower((string) $kode) => $id])->all();

        foreach ($baris as $no => $data) {
            $galat = [];
            $email = mb_strtolower($data['email']);

            if (Validator::make($data, ['nama' => ['required', 'max:255'], 'email' => ['required', 'email', 'max:255']])->fails()) {
                $galat[] = 'Nama dan surel wajib diisi dengan benar.';
            }

            if ($email !== '' && isset($sudah[$email])) {
                $galat[] = "Surel sama dengan baris {$sudah[$email]}.";
            }
            $sudah[$email] ??= $no;

            $peran = $this->peranDari($data['peran']);
            if ($peran === []) {
                $galat[] = 'Peran wajib diisi.';
            }
            foreach ($peran as $p) {
                if ($p === 'super-admin') {
                    $galat[] = 'Peran super-admin tidak dapat diberikan lewat impor.';
                } elseif (! array_key_exists($p, PeranDanIzinSeeder::PERAN)) {
                    $galat[] = "Peran \"{$p}\" tidak dikenal.";
                }
            }

            if ($data['kode_prodi'] !== '' && ! isset($prodi[mb_strtolower($data['kode_prodi'])])) {
                $galat[] = "Kode prodi \"{$data['kode_prodi']}\" tidak ditemukan.";
            }
            if (in_array('admin-prodi', $peran, true) && $data['kode_prodi'] === '') {
                $galat[] = 'Admin-prodi wajib memiliki kode prodi.';
            }

            $ada = $email === '' ? null : User::where('email', $email)->first();
            if ($ada?->hasRole('super-admin')) {
                $galat[] = 'Akun super-admin tidak dapat diubah lewat impor.';
            }

            $hasil[] = [
                'no' => $no,
                'status' => $galat !== [] ? 'galat' : ($ada ? 'ubah' : 'baru'),
                'data' => $data,
                'galat' => $galat,
            ];
        }

        return $hasil;
    }

    /**
     * @param  array{wajib_ganti_sandi?: bool, sandi_awal?: ?string}  $opsi
     * @return array{dibuat: int, diubah: int, gagal: list<array{no: int, pesan: string}>}
     */
    public function jalankan(string $teks, array $opsi, User $pelaku): array
    {
        if (! $pelaku->can('pengguna.kelola')) {
            throw new AuthorizationException('Anda tidak berhak mengelola pengguna.');
        }

        $simpan = app(SimpanPengguna::class);
        $prodi = Prodi::pluck('id', 'kode')->mapWithKeys(fn ($id, $kode) => [mb_strtolower((string) $kode) => $id])->all();
        $hasil = ['dibuat' => 0, 'diubah' => 0, 'gagal' => []];

        foreach ($this->periksa($teks) as $baris) {
            if ($baris['status'] === 'galat') {
                $hasil['gagal'][] = ['no' => $baris['no'], 'pesan' => implode(' ', $baris['galat'])];

                continue;
            }

            $d = $baris['data'];
            $ada = User::firstWhere('email', mb_strtolower($d['email']));
            $sandi = $d['sandi'] !== '' ? $d['sandi'] : ($opsi['sandi_awal'] ?? null);

            try {
                $simpan->jalankan($ada, [
                    'name' => $d['nama'],
                    'email' => mb_strtolower($d['email']),
                    'password' => $sandi,
                    'npm' => $d['npm'],
                    'nidn' => $d['nidn'],
                    'nip' => $d['nip'],
                    'nomor_wa' => $d['nomor_wa'],
                    'prodi_id' => $d['kode_prodi'] !== '' ? $prodi[mb_strtolower($d['kode_prodi'])] : $ada?->prodi_id,
                    'is_active' => $ada->is_active ?? true,
                    'wajib_ganti_sandi' => filled($sandi) && ($opsi['wajib_ganti_sandi'] ?? true) ? true : ($ada->wajib_ganti_sandi ?? false),
                    'peran' => $this->peranDari($d['peran']),
                ], $pelaku);
                $ada === null ? $hasil['dibuat']++ : $hasil['diubah']++;
            } catch (ValidationException $e) {
                $hasil['gagal'][] = ['no' => $baris['no'], 'pesan' => collect($e->errors())->flatten()->implode(' ')];
            } catch (Throwable $e) {
                report($e);
                $hasil['gagal'][] = ['no' => $baris['no'], 'pesan' => 'Gagal menyimpan baris ini.'];
            }
        }

        return $hasil;
    }

    /**
     * @return array<int, array<string, string>> nomor baris (1-based, tanpa baris judul) => kolom
     */
    public function urai(string $teks): array
    {
        $teks = trim(str_replace(["\r\n", "\r"], "\n", $teks));
        if ($teks === '') {
            return [];
        }

        $garis = array_values(array_filter(explode("\n", $teks), fn ($l) => trim($l) !== ''));
        if (count($garis) > self::MAKS_BARIS + 1) {
            throw ValidationException::withMessages(['teks' => 'Maksimal '.self::MAKS_BARIS.' baris per impor.']);
        }

        $pemisah = collect(["\t", ';', ','])->sortByDesc(fn ($p) => substr_count($garis[0], $p))->first();
        $semua = array_map(fn ($l) => array_map('trim', str_getcsv($l, $pemisah, '"', '')), $garis);

        $kolom = self::KOLOM;
        $pertama = array_map(fn ($c) => self::ALIAS[$this->kunci($c)] ?? null, $semua[0]);
        if (in_array('email', $pertama, true) && in_array('nama', $pertama, true)) {
            $kolom = array_map(fn ($k, $i) => $k ?? "kolom_{$i}", $pertama, array_keys($pertama));
            array_shift($semua);
        }

        $hasil = [];
        foreach ($semua as $i => $sel) {
            $baris = array_fill_keys(self::KOLOM, '');
            foreach ($sel as $j => $nilai) {
                if (isset($kolom[$j]) && array_key_exists($kolom[$j], $baris)) {
                    $baris[$kolom[$j]] = $nilai;
                }
            }
            $hasil[$i + 1] = $baris;
        }

        return $hasil;
    }

    private function kunci(string $judul): string
    {
        return str_replace([' ', '-'], '_', mb_strtolower(trim($judul)));
    }

    /** @return list<string> */
    private function peranDari(string $nilai): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn ($p) => str_replace(' ', '-', mb_strtolower(trim($p))),
            preg_split('/[|\/]/', $nilai) ?: [],
        ))));
    }
}
