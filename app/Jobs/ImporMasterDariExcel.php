<?php

namespace App\Jobs;

use App\Enums\StatusImpor;
use App\Models\Prodi;
use App\Models\RiwayatImpor;
use App\Notifications\ImporSelesai;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelReader;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Throwable;

/**
 * Dasar impor master dari Excel/CSV: baca streaming, validasi per baris, upsert per 500 baris,
 * tulis baris gagal ke XLSX galat, dan SELALU hapus berkas sumber (BR-19).
 */
abstract class ImporMasterDariExcel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const UKURAN_CHUNK = 500;

    public int $tries = 1;

    public int $timeout = 900;

    /** @var array<string, bool> */
    private array $terlihat = [];

    /** @var array<string, string>|null */
    private ?array $petaProdi = null;

    public function __construct(public RiwayatImpor $riwayat)
    {
        $this->onConnection(config('puspresma.koneksi_berat'))->onQueue(config('puspresma.antrean.impor'));
    }

    /** @return class-string<Model> */
    abstract protected function modelClass(): string;

    abstract protected function namaLock(): string;

    /** @return array<string, mixed> Aturan validasi untuk baris yang sudah dinormalisasi. */
    abstract protected function aturan(): array;

    /** @return list<string> Kolom bertabrakan unik (nilai tidak kosong) yang harus bebas tabrakan. */
    abstract protected function kolomUnik(): array;

    /**
     * Kolom kunci upsert untuk baris ini (null bila tidak ada kunci).
     *
     * @param  array<string, mixed>  $baris
     */
    abstract protected function kolomKunci(array $baris): ?string;

    /**
     * Petakan baris tervalidasi ke kolom basis data (nilai kosong = null = tidak menimpa).
     *
     * @param  array<string, mixed>  $baris
     * @return array<string, mixed>
     */
    abstract protected function petakan(array $baris): array;

    public function handle(): void
    {
        $lock = Cache::lock($this->namaLock(), 900);
        $punyaLock = false;

        try {
            $this->riwayat->update(['status' => StatusImpor::Proses]);

            if (! ($punyaLock = $lock->get())) {
                $this->riwayat->update(['status' => StatusImpor::Gagal, 'pesan' => 'Impor lain sedang berjalan.']);

                return;
            }

            $this->proses();
            $this->riwayat->update(['status' => StatusImpor::Selesai]);
        } catch (Throwable $e) {
            report($e);
            $this->riwayat->update(['status' => StatusImpor::Gagal, 'pesan' => Str::limit($e->getMessage(), 500)]);
        } finally {
            if ($punyaLock) {
                $lock->release();
            }
            $this->hapusSumber();
        }

        $this->riwayat->user?->notify(new ImporSelesai($this->riwayat->refresh()));
    }

    private function proses(): void
    {
        $disk = Storage::disk('tmp');
        $pembaca = SimpleExcelReader::create($disk->path((string) $this->riwayat->path_tmp))
            ->formatHeadersUsing(fn ($judul) => Str::snake(trim((string) $judul)));

        $penulis = null;
        $total = $berhasil = $gagal = 0;
        $nomor = 1;

        foreach ($pembaca->getRows()->chunk(self::UKURAN_CHUNK) as $kelompok) {
            $baris = [];
            foreach ($kelompok as $mentah) {
                $nomor++;
                $bersih = $this->normalisasi($mentah);
                if (collect($bersih)->every(fn ($nilai) => $nilai === null)) {
                    continue;
                }
                $baris[] = ['nomor' => $nomor, 'mentah' => $mentah, 'data' => $bersih];
            }

            [$sukses, $galat] = $this->prosesKelompok($baris);
            $total += count($baris);
            $berhasil += $sukses;
            $gagal += count($galat);

            foreach ($galat as [$mentah, $alasan]) {
                if ($penulis === null) {
                    $disk->makeDirectory('galat');
                    $this->riwayat->path_galat = 'galat/'.Str::uuid7().'.xlsx';
                    $penulis = SimpleExcelWriter::create($disk->path($this->riwayat->path_galat));
                }
                $penulis->addRow($mentah + ['alasan' => $alasan]);
            }
        }

        $penulis?->close();

        $this->riwayat->update([
            'total_baris' => $total,
            'berhasil' => $berhasil,
            'gagal' => $gagal,
            'path_galat' => $penulis === null ? null : $this->riwayat->path_galat,
            'kedaluwarsa_at' => $penulis === null ? null : now()->addHours(24),
        ]);
    }

    /**
     * @param  list<array{nomor: int, mentah: array<string, mixed>, data: array<string, mixed>}>  $baris
     * @return array{0: int, 1: list<array{0: array<string, mixed>, 1: string}>}
     */
    private function prosesKelompok(array $baris): array
    {
        $galat = [];
        $valid = [];

        foreach ($baris as $item) {
            $validator = Validator::make($item['data'], $this->aturan());
            if ($validator->fails()) {
                $galat[] = [$item['mentah'], $validator->errors()->first()];

                continue;
            }
            $valid[] = $item;
        }

        $ada = $this->tabrakanDiBasisData(array_column($valid, 'data'));
        $simpan = [];

        foreach ($valid as $item) {
            $alasan = $this->alasanTabrakan($item['data'], $ada);
            if ($alasan !== null) {
                $galat[] = [$item['mentah'], $alasan];

                continue;
            }
            $simpan[] = $this->petakan($item['data']) + ['__kunci' => $this->kolomKunci($item['data'])];
        }

        $this->upsert($simpan);

        return [count($simpan), $galat];
    }

    /**
     * Nilai unik yang sudah ada di basis data: kolom => nilai => id pemilik.
     *
     * @param  list<array<string, mixed>>  $data
     * @return array<string, array<string, string>>
     */
    private function tabrakanDiBasisData(array $data): array
    {
        $hasil = [];
        foreach ($this->kolomUnik() as $kolom) {
            $nilai = array_values(array_filter(array_column($data, $kolom)));
            $hasil[$kolom] = $nilai === []
                ? []
                : ($this->modelClass())::query()->withoutGlobalScopes()->whereIn($kolom, $nilai)->pluck('id', $kolom)->all();
        }

        return $hasil;
    }

    /**
     * Tolak baris yang ganda di dalam berkas, atau yang kolom uniknya milik rekaman lain.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, array<string, string>>  $ada
     */
    private function alasanTabrakan(array $data, array $ada): ?string
    {
        $kunci = $this->kolomKunci($data);
        $pemilikKunci = $kunci === null ? null : ($ada[$kunci][$data[$kunci]] ?? null);

        foreach ($this->kolomUnik() as $kolom) {
            $nilai = $data[$kolom] ?? null;
            if ($nilai === null) {
                continue;
            }

            if (isset($this->terlihat["{$kolom}:{$nilai}"])) {
                return strtoupper($kolom)." {$nilai} ganda di dalam berkas.";
            }

            $pemilik = $ada[$kolom][$nilai] ?? null;
            if ($pemilik !== null && $pemilik !== $pemilikKunci) {
                return strtoupper($kolom)." {$nilai} sudah dipakai data lain.";
            }
        }

        foreach ($this->kolomUnik() as $kolom) {
            if (($data[$kolom] ?? null) !== null) {
                $this->terlihat["{$kolom}:{$data[$kolom]}"] = true;
            }
        }

        return null;
    }

    /**
     * Upsert per kelompok kunci + kolom terisi: kolom kosong tidak menimpa data yang sudah ada.
     *
     * @param  list<array<string, mixed>>  $baris
     */
    private function upsert(array $baris): void
    {
        $kelompok = [];
        foreach ($baris as $baris1) {
            $kunci = $baris1['__kunci'];
            unset($baris1['__kunci']);
            $terisi = array_keys(array_filter($baris1, fn ($v) => $v !== null));
            sort($terisi);
            $kelompok[$kunci.'|'.implode(',', $terisi)][] = [$kunci, $baris1];
        }

        $sekarang = now();
        foreach ($kelompok as $isi) {
            $kunci = $isi[0][0];
            $rekaman = array_map(fn ($i) => array_filter($i[1], fn ($v) => $v !== null)
                + ['id' => (string) Str::uuid7(), 'created_at' => $sekarang, 'updated_at' => $sekarang], $isi);
            $kolomUbah = array_values(array_diff(array_keys($rekaman[0]), [$kunci, 'id', 'created_at']));

            ($this->modelClass())::query()->upsert($rekaman, [$kunci], $kolomUbah);
        }
    }

    /**
     * @param  array<string, mixed>  $mentah
     * @return array<string, string|null>
     */
    private function normalisasi(array $mentah): array
    {
        $bersih = [];
        foreach ($mentah as $kunci => $nilai) {
            if (is_float($nilai) && floor($nilai) === $nilai) {
                $nilai = (int) $nilai;
            }
            if ($nilai instanceof \DateTimeInterface) {
                $nilai = $nilai->format('Y-m-d');
            }
            $nilai = trim((string) $nilai);
            $bersih[(string) $kunci] = $nilai === '' ? null : $nilai;
        }

        if (isset($bersih['email'])) {
            $bersih['email'] = strtolower($bersih['email']);
        }

        return $bersih;
    }

    /** @return array<string, string> kode prodi (huruf besar) => id */
    protected function petaProdi(): array
    {
        return $this->petaProdi ??= Prodi::query()->pluck('id', 'kode')
            ->mapWithKeys(fn ($id, $kode) => [strtoupper((string) $kode) => $id])->all();
    }

    private function hapusSumber(): void
    {
        $path = $this->riwayat->path_tmp;
        if ($path !== null) {
            Storage::disk('tmp')->delete($path);
        }
        $this->riwayat->update(['path_tmp' => null]);
    }
}
