<?php

/**
 * Diagnostik: kenapa soal SD tidak muncul di Bank Builder paket SD.
 * Jalankan: php storage/diagnose-sd-bank.php
 *
 * Dibuat oleh Rokimah (UX/UI audit).
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== DIAGNOSTIK: soal SD vs Bank Builder paket SD ===\n\n";

if (! Schema::hasTable('global_questions')) {
    echo "Tabel global_questions tidak ada.\n";
    exit(1);
}

$jenjangs = DB::table('jenjangs')->orderBy('urutan')->get();
echo "Tabel jenjangs:\n";
foreach ($jenjangs as $j) {
    echo "  id={$j->id} kode={$j->kode} nama={$j->nama}\n";
}

$sd = $jenjangs->firstWhere('kode', 'SD');
if (! $sd) {
    echo "\n!! kode 'SD' tidak ada di tabel jenjangs. Cek isi tabel ini dulu.\n";
    exit(1);
}

echo "\n--- 1. Sebaran jenjang_id di bank soal global ---\n";
$rows = DB::select('
    SELECT jenjang_id, COUNT(*) AS jumlah,
           SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS aktif,
           SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END) AS soft_deleted
    FROM global_questions
    GROUP BY jenjang_id
    ORDER BY jenjang_id
');
foreach ($rows as $r) {
    $label = $r->jenjang_id === null
        ? 'NULL (tidak terkait jenjang apapun)'
        : ($jenjangs->firstWhere('id', $r->jenjang_id)?->kode ?? 'id='.$r->jenjang_id);
    echo sprintf("  jenjang_id=%-3s -> total=%-5d aktif=%-5d soft-deleted=%d\n", $label, $r->jumlah, $r->aktif, $r->soft_deleted);
}

echo "\n--- 2. Nilai material_mapel pada soal SD (aktif) ---\n";
$mapelRows = DB::table('global_questions')
    ->where('jenjang_id', $sd->id)
    ->where('is_active', true)
    ->whereNull('deleted_at')
    ->selectRaw("COALESCE(NULLIF(TRIM(material_mapel), ''), '(kosong)') AS m, COUNT(*) AS jumlah")
    ->groupBy('m') // MariaDB ONLY_FULL_GROUP_BY tidak mengenali ekspresi identik; GROUP BY alias aman
    ->orderByDesc('jumlah')
    ->get();

if ($mapelRows->isEmpty()) {
    echo "  !! Tidak ada soal aktif dengan jenjang_id = SD sama sekali.\n";
} else {
    foreach ($mapelRows as $r) {
        echo sprintf("  material_mapel=%-25s -> %d soal\n", '"'.$r->m.'"', $r->jumlah);
    }
}

echo "\n--- 3. Yang cocok vs tidak cocok dengan filter Bank Builder ---\n";
$pakets = DB::table('paket_soals')
    ->where('jenjang_id', $sd->id)
    ->get();

if ($pakets->isEmpty()) {
    echo "  Belum ada paket soal jenjang SD.\n";
} else {
    foreach ($pakets as $paket) {
        $mapels = DB::table('mapel_pakets')->where('paket_soal_id', $paket->id)->get();
        echo "\n  Paket: {$paket->nama} (id={$paket->id}, tahun_ajaran={$paket->tahun_ajaran})\n";

        foreach ($mapels as $mapel) {
            // PERSIS seperti PaketSoalController::mapelBlock → bank_builder_params
            // str($mapel->nama_mapel)->headline() — headline() ganti underscore jadi spasi
            $filterMapel = \Illuminate\Support\Str::headline((string) $mapel->nama_mapel);
            $filterJenjang = $paket->jenjang_id;

            $total = DB::table('global_questions')->where('jenjang_id', $filterJenjang)
                ->where('is_active', true)->whereNull('deleted_at')
                ->where('material_mapel', $filterMapel)->count();

            $tanpaFilterMapel = DB::table('global_questions')->where('jenjang_id', $filterJenjang)
                ->where('is_active', true)->whereNull('deleted_at')->count();

            $soalMasuk = DB::table('soals')
                ->join('mapel_pakets', 'mapel_pakets.id', '=', 'soals.mapel_paket_id')
                ->where('mapel_pakets.id', $mapel->id)->count();

            printf(
                "    Mapel %-22s (nama_mapel='%s' → filter='%s')\n",
                $filterMapel,
                $mapel->nama_mapel,
                $filterMapel
            );
            printf("      soal sudah masuk di paket : %d / %d slot\n", $soalMasuk, $mapel->jumlah_soal);
            printf("      kandidat bank (sd + aktif + mapel cocok): %d\n", $total);
            printf("      kandidat bank (sd + aktif, mapel diabaikan): %d\n", $tanpaFilterMapel);

            if ($total === 0 && $tanpaFilterMapel > 0) {
                echo "      >> MASALAH: filter material_mapel menutup semua kandidat.\n";
                echo "         Nilai material_mapel yang ada di soal SD:\n";
                $vals = DB::table('global_questions')
                    ->where('jenjang_id', $sd->id)->where('is_active', true)->whereNull('deleted_at')
                    ->selectRaw("COALESCE(NULLIF(TRIM(material_mapel), ''), '(kosong)') AS m, COUNT(*) AS j")
                    ->groupBy('m')->get(); // GROUP BY alias: MariaDB ONLY_FULL_GROUP_BY tidak mengenali ekspresi identik
                foreach ($vals as $v) {
                    echo "           - \"{$v->m}\" ({$v->j} soal)\n";
                }
            }
        }
    }
}

echo "\n--- 4. Soal SD yang tersembunyi karena is_active / soft delete ---\n";
$hidden = DB::table('global_questions')->where('jenjang_id', $sd->id)
    ->where(function ($q) {
        $q->where('is_active', false)->orWhereNotNull('deleted_at');
    })->count();
echo "  Jumlah soal SD nonaktif/soft-deleted: {$hidden}\n";
if ($hidden > 0) {
    echo "  >> Ini disembunyikan oleh filter is_active=true. Tidak ada toggle bulk di UI bank builder.\n";
}

echo "\n--- 5. Soal SD dengan jenjang_id NULL (impor tanpa pilih jenjang) ---\n";
$nullJenjang = DB::table('global_questions')->whereNull('jenjang_id')->count();
echo "  Jumlah: {$nullJenjang}\n";
if ($nullJenjang > 0) {
    echo "  >> Soal ini TIDAK AKAN PERNAH muncul di Bank Builder karena filter jenjang_id selalu terisi otomatis.\n";
    echo "     Fix: jalankan UPDATE global_questions SET jenjang_id = {$sd->id} WHERE jenjang_id IS NULL -- sesuaikan kode\n";
}

echo "\n=== Selesai ===\n";
