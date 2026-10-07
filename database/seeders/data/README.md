# Data seeder

`prodi.csv` (kolom `kode,kode_pddikti,nama,jenjang`) hanya berisi dua baris CONTOH.
Fakultas WAJIB menggantinya dengan data resmi program studi sebelum dipakai produksi,
lalu jalankan `php artisan db:seed --class=ProdiSeeder` (upsert berdasarkan `kode`, aman diulang).
