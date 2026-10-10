<?php

/**
 * Kebijakan tautan: tidak ada unggahan berkas ke server; satu-satunya input berkas adalah impor Excel/CSV master.
 */
it('tidak memakai FileUpload, UploadedFile, atau input file kecuali halaman impor', function () {
    $diizinkan = ['Filament/Admin/Pages/Impor', 'Filament/Admin/Concerns/HalamanImpor.php'];
    $pelanggar = [];

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path(), FilesystemIterator::SKIP_DOTS));
    $berkas = array_map(fn ($f) => $f->getPathname(), iterator_to_array($iterator));
    $berkas = array_merge($berkas, glob(resource_path('views').'/{,*/,*/*/,*/*/*/}*.blade.php', GLOB_BRACE));

    foreach ($berkas as $path) {
        $relatif = str_replace([app_path().'/', resource_path('views').'/'], '', $path);
        if (collect($diizinkan)->contains(fn ($ok) => str_contains($relatif, $ok))) {
            continue;
        }
        if (preg_match('/FileUpload|UploadedFile|type=["\']file["\']|SpatieMediaLibraryFileUpload/', file_get_contents($path))) {
            $pelanggar[] = $relatif;
        }
    }

    expect($pelanggar)->toBe([]);
});
