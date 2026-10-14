<?php

namespace App\Console\Commands;

use App\Models\GlobalQuestion;
use App\Models\Jenjang;
use App\Models\Material;
use Illuminate\Console\Command;

class SyncMateriSoal extends Command
{
    protected $signature = 'tka:sync-materi-soal {--dry-run : Tampilkan rencana tanpa menyimpan}';

    protected $description = 'Backfill relasi soal global ke materi (material_id + snapshot mapel/curriculum/subelement/unit/sub_unit) berdasarkan pencocokan kata kunci.';

    private const MAPEL_MATEMATIKA = 'Matematika';

    private const MAPEL_BAHASA = 'Bahasa Indonesia';

    /**
     * Peta kata kunci -> sub_unit materi. Urutan penting: yang lebih spesifik lebih dulu.
     */
    private function keywordMap(): array
    {
        return [
            'Penjumlahan & Pengurangan Bilangan Bulat' => ['lumba-lumba', 'kedalaman 15m', 'suhu kutub', 'selisih suhu', 'suhu naik', 'bulat negatif'],
            'Perkalian & Pembagian Bilangan Bulat' => ['12 x (-4)', '(-150)', 'perkalian bilangan bulat', 'pembagian bilangan bulat'],
            'Aturan Prioritas Operasi Hitung (Kabataku)' => ['250 + 40 x 15', '(56 + 24)', 'kabataku', 'prioritas operasi', 'operasi campuran'],
            'Operasi Hitung Campuran Berbagai Bentuk Pecahan' => ['2,5 kg telur', '3,5 x 1/2', 'campuran pecahan', 'bentuk pecahan'],
            'Bagian-bagian Lingkaran (Juring, Tembereng)' => ['daerah lingkaran dibatasi dua jari-jari', 'busur', 'juring', 'tembereng', 'tali busur'],
            'Menghitung Keliling Lingkaran' => ['keliling lingkaran', 'keliling taman', 'diameter 28', 'keliling roda'],
            'Menghitung Luas Lingkaran' => ['luas lingkaran', 'jari-jari 7', 'diameter 20'],
            'Sifat Prisma Segitiga dan Tabung' => ['rusuk prisma', 'bangun ruang tanpa titik sudut', 'sifat prisma'],
            'Sifat Limas, Kerucut, dan Bola' => ['limas segiempat', 'titik sudut limas', 'sifat kerucut', 'banyaknya sisi pada bangun kerucut'],
            'Volume Prisma dan Tabung' => ['volume tabung', 'luas alas prisma', 'volume prisma'],
            'Volume Limas dan Kerucut' => ['volume limas', 'volume kerucut'],
            'Luas Permukaan Prisma dan Tabung' => ['luas permukaan kubus', 'permukaan tabung tanpa tutup', 'luas permukaan tabung', 'l.permukaan kubus rusuk'],
            'Luas Permukaan Gabungan Bangun Ruang' => ['volume gabungan balok', 'permukaan 2 kubus', 'l.permukaan balok pasang sisi', 'gabungan bangun'],
            'Pengumpulan Data (Wawancara & Kuisioner)' => ['daftar pertanyaan tertulis', 'mencatat nomor sepatu', 'kuesioner', 'wawancara'],
            'Menentukan Modus Data Tunggal' => ['modus dari', 'nilai 80', 'modus data'],
            'Menghitung Median (Nilai Tengah)' => ['median dari'],
            'Menghitung Mean (Rata-rata)' => ['mean dari', 'mean 5 siswa', 'mean 10 orang'],
            'Membaca Diagram Lingkaran (Derajat)' => ['sudut hobi', 'musik 60', 'tari 25%', 'diagram lingkaran derajat'],
            'Membaca Diagram Lingkaran (Persen)' => ['sisa data 10%', 'diagram lingkaran persen'],
            'Aplikasi Mean dalam Soal Cerita Gabungan' => ['mean 20 tamu', 'aplikasi mean'],
            'Nilai Tempat Bilangan Persepuluhan' => ['12,358', 'nilai tempat', 'persepuluhan'],
            'Perkalian Pecahan dengan Bilangan Bulat' => ['4 x 2/5', 'desimal dari 3/8', '10m tali dipotong per 1/4', 'perkalian pecahan'],
            'Konsep Perbandingan dan Rasio Sederhana' => ['rasio andi', 'rasio 20 laki-laki', 'perbandingan sederhana'],
            'Penerapan Rasio pada Resep dan Campuran' => ['3 gelas butuh 6 jeruk', 'harga 5 buku', 'resep dan campuran'],
            'Skala dan Peta' => ['skala 1:200.000', 'jarak peta', 'skala jika 50 km'],
            'KPK pada Penjadwalan Berulang' => ['lampu a(8 dtk)', 'bus x(15m)', 'kpk', 'penjadwalan berulang'],
            'FPB pada Pembagian Barang Sama Banyak' => ['24 cokelat', 'tali 15m & 25m', 'fpb', 'dibagi rata'],
            'Mengidentifikasi Pola Bilangan Geometri' => ['suku berikutnya 2, 4, 8', 'suku ke-6 pola', 'pola bilangan'],
            'Kalimat Matematika dengan Variabel' => ['x + 15 = 40', '3 pensil = y', 'variabel'],
            'Estimasi Luas Bangun Tak Beraturan' => ['estimasi luas', '12 petak penuh'],
            'Hubungan Jarak, Waktu, dan Kecepatan' => ['120 km dalam 2 jam', '15 km/jam', 'kecepatan'],
            'Konsep Volume per Satuan Waktu (Debit)' => ['30 liter dalam 5 menit', 'debit', '100 liter'],
            'Membuat Jaring-jaring Balok dan Kubus' => ['jaring-jaring kubus', 'jaring-jaring'],
            'Komposisi dan Dekomposisi Bangun Datar' => ['gabung 2 persegi', 'jajargenjang potong diagonal', 'komposisi bangun'],
            'Simetri Lipat dan Putar Tingkat Lanjut' => ['simetri lipat', 'simetri putar', 'huruf dengan simetri'],
            'Membaca Infografis (Literasi Numerasi)' => ['40% dari 2 ton', 'infografis numerasi'],
            'Rentang Data (Nilai Maks - Min)' => ['rentang data', 'nilai min 60', 'rentang'],
            'Mengubah Tabel ke Diagram Garis' => ['diagram tepat untuk suhu', 'diagram garis', 'tabel ke diagram'],
            'Konsep Peluang Kejadian Sederhana' => ['peluang muncul angka 7', '3 merah, 2 biru', 'peluang'],
            'Memprediksi Tren Data Masa Depan' => ['tren naik 4 bln', 'mean 20 tamu', 'prediksi'],
            'Ide Pokok Paragraf Laporan' => ['gagasan dasar pengembangan paragraf', 'padi sumber pangan', 'ide pokok'],
            'Menarik Kesimpulan Teks Laporan' => ['pendapat akhir uraian', 'menyimpulkan'],
            'Menemukan Informasi (5W+1H)' => ['kata tanya untuk alasan', '5w+1h', 'teks eksplanasi menerangkan'],
            'Ciri-ciri Kalimat Efektif' => ['kalimat sesuai kaidah', 'kalimat efektif'],
            'Kata Baku dan Tidak Baku (KBBI)' => ['kata sesuai puebi', 'penulisan baku', 'kata baku'],
            'Mengidentifikasi Kata Ajakan' => ['marilah menjaga kebersihan', 'kalimat persuasi'],
            'Unsur Intrinsik Puisi (Rima/Majas)' => ['persamaan bunyi akhir', 'angin berbisik', 'rima'],
            'Tokoh dan Penokohan (Watak)' => ['tokoh berwatak baik', 'cara gambarkan watak', 'penokohan'],
            'Latar dan Amanat Cerita' => ['tempat & waktu cerita', 'pesan moral pengarang', 'latar dan amanat'],
            'Kerangka Pidato Perpisahan' => ['struktur pidato', 'semoga bermanfaat', 'kerangka pidato'],
            'Cara Mengisi Formulir & LJK' => ['lembar identitas diri', 'data wajib pendaftaran', 'formulir'],
            'Surat Resmi vs Surat Pribadi' => ['surat instansi', 'bagian surat berisi nama instansi', 'surat resmi'],
            'Parafrase Puisi menjadi Prosa' => ['ubah puisi ke prosa', 'parafrase'],
            'Titik Dua dan Koma pada Daftar' => ['tanda baca pemisah rincian', 'fungsi (:)', 'titik dua'],
            'Kapitalisasi Geografi & Gelar' => ['penulisan geografi', 'kalimat kapital'],
            'Membandingkan Dua Watak Tokoh' => ['penantang tokoh utama', 'bandingkan watak'],
            'Kata Kunci dari Judul Teks' => ['kata kunci judul', 'manfaat lidah buaya'],
            'Paragraf Deskripsi Objek Wisata' => ['teks gambarkan objek', 'pantai pasir putih', 'deskripsi objek'],
            'Etika Menyampaikan Pendapat' => ['sikap tdk setuju diskusi', 'mohon maaf, saya kurang setuju', 'etika pendapat'],
            'Fungsi Imbuhan me-, di-, ter-' => ['imbuhan "meminum"', 'makna "ter-"', 'imbuhan'],
            'Membedakan Fakta dan Opini' => ['pernyataan dpt dibuktikan', 'kalimat opini', 'fakta dan opini'],
            'Kesesuaian Gambar dengan Teks' => ['kesesuaian gambar & teks', 'teks tanam padi'],
            'Menemukan Pesan Tersirat' => ['pesan tdk tertulis langsung', 'berbagi saat lapar'],
            'Analisis Perasaan Tokoh' => ['perasaan tokoh diketahui', 'wajah merah, tangan mengepal'],
            'Mengidentifikasi Tujuan Penulis' => ['tujuan teks prosedur', 'mengapa narasi pakai kiasan'],
            'Hubungan Sebab-Akibat Fenomena' => ['hutan gundul banjir', 'pemanasan global'],
            'Memahami Homonim (Bisa, Rapat)' => ['tulisan sama makna beda', 'bisa ular'],
            'Sinonim dan Antonim Kontekstual' => ['antonim "induksi"', 'sinonim "dampak"', 'sinonim dan antonim'],
            'Personifikasi dan Hiperbola' => ['rumah sentuh langit', 'pena menari-nari', 'majas'],
            'Menyusun Instruksi Kompleks' => ['bagian prosedur isi alat/bahan', 'tanda akhir perintah prosedur', 'teks prosedur'],
            'Meringkas Teks Kalimat Sendiri' => ['ringkasan dilarang ubah', 'meringkas bahasa sendiri'],
            'Menulis Laporan Hasil Wawancara' => ['pemberi info wawancara', 'kata tanya awal wawancara'],
            'Informasi dari Infografis' => ['infografis sampaikan info', 'data infografis'],
            'Teknik Bertanya Terbuka' => ['tanya "pendapat bapak', 'sikap benar wawancara'],
            'Verifikasi Informasi Digital' => ['sebelum sebar info internet', 'hoax biasanya'],
            'Menulis Puisi Bebas Lingkungan' => ['puisi bebas tdk terikat', 'awal nulis puisi lingkungan'],
            'Kalimat Majemuk Setara/Bertingkat' => ['ibu masak dan ayah sapu', 'kata hubung waktu', 'kalimat majemuk'],
            'Memahami Simbol pada Peta' => ['simbol "segitiga merah"', 'legenda peta'],
            'Jurnal Refleksi Pembelajaran' => ['jurnal refleksi berisi', 'manfaat jurnal refleksi'],
            'Argumen untuk Debat Sederhana' => ['argumen kuat debat', 'saya setuju karena data'],
        ];
    }

    private function materialKey(?string $jenjang, ?string $mapel, ?string $subUnit): string
    {
        return strtoupper(trim((string) $jenjang)).'|'.strtolower(trim((string) $mapel)).'|'.trim((string) $subUnit);
    }

    private function resolveMaterial($materials, GlobalQuestion $gq, string $subUnit): ?Material
    {
        $jenjangKode = $gq->jenjang?->kode;

        if ($jenjangKode === null) {
            $jenjangKode = Jenjang::query()->whereKey($gq->jenjang_id)->value('kode');
        }

        $candidates = $materials->filter(fn (Material $m) => trim((string) $m->sub_unit) === $subUnit);

        if ($jenjangKode !== null) {
            $byJenjang = $candidates->filter(
                fn (Material $m) => strtoupper(trim((string) $m->jenjang)) === strtoupper(trim((string) $jenjangKode))
            );

            if ($byJenjang->count() === 1) {
                return $byJenjang->first();
            }

            if ($gq->material_mapel) {
                $byMapel = $byJenjang->filter(
                    fn (Material $m) => strtolower(trim((string) $m->mapel)) === strtolower(trim((string) $gq->material_mapel))
                );

                if ($byMapel->count() === 1) {
                    return $byMapel->first();
                }
            }

            if ($byJenjang->isNotEmpty()) {
                return $byJenjang->sortBy('id')->first();
            }
        }

        return $candidates->count() === 1 ? $candidates->first() : null;
    }

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $materials = Material::query()
            ->whereNotNull('sub_unit')->where('sub_unit', '!=', '')
            ->orderBy('id')
            ->get()
            ->keyBy(fn (Material $m) => $this->materialKey($m->jenjang, $m->mapel, $m->sub_unit));

        $questions = GlobalQuestion::query()->orderBy('id')->get();

        $matched = 0;
        $unmatched = [];

        $map = $this->keywordMap();

        foreach ($questions as $gq) {
            $text = strtolower((string) $gq->question_text);

            $target = null;
            foreach ($map as $subUnit => $keywords) {
                foreach ($keywords as $kw) {
                    if ($kw !== '' && str_contains($text, strtolower($kw))) {
                        $target = $subUnit;
                        break 2;
                    }
                }
            }

            if ($target === null) {
                $unmatched[] = $gq->id.'|'.mb_substr((string) $gq->question_text, 0, 60);

                continue;
            }

            $material = $this->resolveMaterial($materials, $gq, $target);

            if ($material === null) {
                $unmatched[] = $gq->id.'|'.mb_substr((string) $gq->question_text, 0, 60);

                continue;
            }

            $attributes = [
                'material_id' => $material->id,
                'material_mapel' => $material->mapel,
                'material_curriculum' => $material->curriculum,
                'material_subelement' => $material->subelement,
                'material_unit' => $material->unit,
                'material_sub_unit' => $material->sub_unit,
            ];

            $needsUpdate = false;
            foreach ($attributes as $key => $value) {
                if ((string) $gq->getAttribute($key) !== (string) $value) {
                    $needsUpdate = true;
                    break;
                }
            }

            if ($needsUpdate) {
                $matched++;
                if (! $dry) {
                    $gq->forceFill($attributes)->save();
                }
            }
        }

        $this->info(($dry ? '[DRY-RUN] ' : '')."Soal ter-update: {$matched} dari {$questions->count()}");

        if ($unmatched !== []) {
            $this->warn('Belum cocok: '.count($unmatched));
            foreach ($unmatched as $line) {
                $this->line('  '.$line);
            }
        }

        return self::SUCCESS;
    }
}
