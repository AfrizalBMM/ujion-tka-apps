<?php

namespace App\Console\Commands;

use App\Models\GlobalQuestion;
use App\Models\MapelPaket;
use App\Models\PaketSoal;
use App\Models\PilihanJawaban;
use App\Models\Soal;
use App\Models\TeksBacaan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncPaketSoal extends Command
{
    protected $signature = 'tka:sync-paket-soal
        {--paket= : ID paket soal yang diisi}
        {--dry-run : Tampilkan rencana tanpa menyimpan}';

    protected $description = 'Isi mapel paket (akademik) dengan soal global yang sudah ter-link ke materi. Idempotent: lewati soal yang sudah ada.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $paketId = $this->option('paket');

        $paketQuery = PaketSoal::query()->orderBy('id');
        if ($paketId !== null) {
            $paketQuery->where('id', (int) $paketId);
        }

        $pakets = $paketQuery->get();

        if ($pakets->isEmpty()) {
            $this->error('Tidak ada paket soal.');

            return self::FAILURE;
        }

        foreach ($pakets as $paket) {
            $this->info("=== Paket #{$paket->id}: {$paket->nama} ===");

            $mapels = MapelPaket::query()
                ->where('paket_soal_id', $paket->id)
                ->orderBy('urutan')
                ->get();

            foreach ($mapels as $mapel) {
                if ($mapel->isSurvey()) {
                    $this->line("  - {$mapel->nama_label}: dilewati (survey)");

                    continue;
                }

                $targetMapel = match ($mapel->nama_mapel) {
                    MapelPaket::NAMA_MATEMATIKA => 'Matematika',
                    MapelPaket::NAMA_BAHASA_INDONESIA => 'Bahasa Indonesia',
                    default => null,
                };

                if ($targetMapel === null) {
                    $this->line("  - {$mapel->nama_label}: dilewati (mapel tidak dikenal)");

                    continue;
                }

                $candidates = GlobalQuestion::query()
                    ->where('is_active', true)
                    ->whereNotNull('material_id')
                    ->whereRaw("LOWER(TRIM(COALESCE(material_mapel,''))) = ?", [strtolower($targetMapel)])
                    ->orderBy('material_id')
                    ->orderBy('id')
                    ->get();

                $existingPertanyaan = Soal::query()
                    ->where('mapel_paket_id', $mapel->id)
                    ->pluck('pertanyaan')
                    ->map(fn ($t) => (string) $t)
                    ->all();
                $existingSet = array_flip($existingPertanyaan);

                $currentCount = count($existingPertanyaan);
                $maxSoal = (int) $mapel->jumlah_soal;
                $nextNomor = (int) (Soal::query()->where('mapel_paket_id', $mapel->id)->max('nomor_soal'));

                $inserted = 0;
                $skippedDuplicate = 0;
                $skippedLimit = 0;

                foreach ($candidates as $gq) {
                    $text = (string) $gq->question_text;

                    if (isset($existingSet[$text])) {
                        $skippedDuplicate++;

                        continue;
                    }

                    if ($currentCount >= $maxSoal) {
                        $skippedLimit++;

                        continue;
                    }

                    $tipeSoal = match ($gq->question_type) {
                        'multiple_choice', 'short_answer' => 'pilihan_ganda',
                        'matching' => 'menjodohkan',
                        default => 'pilihan_ganda',
                    };

                    if ($tipeSoal !== 'pilihan_ganda') {
                        continue;
                    }

                    if ($dry) {
                        $inserted++;
                        $currentCount++;
                        $existingSet[$text] = true;

                        continue;
                    }

                    DB::transaction(function () use ($mapel, $gq, $tipeSoal, $text, &$nextNomor, &$inserted, &$currentCount, &$existingSet): void {
                        $nextNomor++;
                        $currentCount++;

                        $teksBacaanId = null;
                        if ($gq->reading_passage) {
                            $teksBacaan = TeksBacaan::create([
                                'mapel_paket_id' => $mapel->id,
                                'judul' => Str::limit($text, 60),
                                'konten' => $gq->reading_passage,
                            ]);
                            $teksBacaanId = $teksBacaan->id;
                        }

                        $soal = Soal::create([
                            'mapel_paket_id' => $mapel->id,
                            'teks_bacaan_id' => $teksBacaanId,
                            'nomor_soal' => $nextNomor,
                            'tipe_soal' => $tipeSoal,
                            'jenis_instrumen' => 'akademik',
                            'indikator' => $gq->material_sub_unit ?? $gq->material_unit ?? 'Dari bank soal',
                            'pertanyaan' => $text,
                            'pembahasan' => $gq->explanation,
                            'bobot' => 1,
                        ]);

                        $kodes = ['A', 'B', 'C', 'D'];
                        $answerKeyText = (string) $gq->answer_key;

                        foreach (array_values((array) $gq->options) as $idx => $optionText) {
                            if ($idx > 3) {
                                break;
                            }
                            $kode = $kodes[$idx];

                            PilihanJawaban::create([
                                'soal_id' => $soal->id,
                                'kode' => $kode,
                                'teks' => (string) $optionText,
                                'is_benar' => (string) $optionText === $answerKeyText,
                            ]);
                        }

                        $existingSet[$text] = true;
                        $inserted++;
                    });
                }

                $prefix = $dry ? '[DRY-RUN] ' : '';
                $this->line("  - {$mapel->nama_label}: {$prefix}+{$inserted} soal (total {$currentCount}/{$maxSoal}; duplikat {$skippedDuplicate}, limit {$skippedLimit})");
            }
        }

        return self::SUCCESS;
    }
}
