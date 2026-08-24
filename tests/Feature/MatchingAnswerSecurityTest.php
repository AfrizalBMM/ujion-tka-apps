<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Jenjang;
use App\Models\MapelPaket;
use App\Models\PaketSoal;
use App\Models\PasanganMenjodohkan;
use App\Models\Soal;
use App\Models\UjianSesi;
use App\Models\User;
use App\Support\MatchingKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MatchingAnswerSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_matching_payload_does_not_expose_raw_pair_ids(): void
    {
        [$sesi, , , $pasangan] = $this->createMatchingSession();

        $response = $this->withSession(['participant_token' => $sesi->session_token])
            ->get(route('siswa.ujian'));

        $response->assertOk();
        $html = $response->getContent();
        $json = $this->extractExamPayload($html);

        $this->assertNotNull($json);

        $first = $json['questions'][0];
        $this->assertNotEmpty($first['pasangan']);
        $this->assertNotEmpty($first['matching_options']);

        foreach ($first['pasangan'] as $row) {
            $this->assertArrayNotHasKey('id', $row);
            $this->assertArrayNotHasKey('teks_kanan', $row);
            $this->assertArrayHasKey('key', $row);
            $this->assertNotSame($row['key'], '');
        }

        foreach ($first['matching_options'] as $opt) {
            $this->assertArrayNotHasKey('id', $opt);
            $this->assertArrayHasKey('key', $opt);
            $this->assertArrayHasKey('label', $opt);
        }

        $rowKeys = array_column($first['pasangan'], 'key');
        $optKeys = array_column($first['matching_options'], 'key');
        $this->assertSame($first['pasangan'][0]['key'], $rowKeys[0]);
        $this->assertEmpty(array_intersect($rowKeys, $optKeys), 'Row key dan option key tidak boleh overlap.');
    }

    public function test_matching_save_resolves_opaque_keys_to_pair_ids(): void
    {
        [$sesi, $soal, $mapel, $pasangan] = $this->createMatchingSession();
        $seed = $sesi->session_token;

        $payload = $pasangan->mapWithKeys(fn ($pair, $i) => [
            $pair->id => [
                'row_key' => MatchingKey::rowKey($pair->id, $seed),
                'opt_key' => MatchingKey::optKey($pasangan[$i]->id, $seed),
            ],
        ])->values()->all();

        $response = $this->withSession([
            'participant_token' => $sesi->session_token,
            '_token' => 'matching-token',
        ])
            ->withHeader('X-CSRF-TOKEN', 'matching-token')
            ->postJson(route('siswa.api.save_answer'), [
                'question_id' => $soal->id,
                'mapel_paket_id' => $mapel->id,
                'tipe_soal' => 'menjodohkan',
                'jawaban_menjodohkan' => $payload,
                'is_ragu' => false,
            ]);

        $response->assertOk();

        $sesi->refresh();
        $stored = $sesi->jawabanSiswas->firstWhere('soal_id', $soal->id);
        $this->assertNotNull($stored);

        $storedPairs = collect($stored->jawaban_menjodohkan)
            ->mapWithKeys(fn ($item) => [$item['pair_id'] => $item['match_id']]);

        foreach ($pasangan as $pair) {
            $this->assertSame($pair->id, $storedPairs->get($pair->id), 'Pasangan benar tersimpan sebagai pair_id = match_id.');
        }
    }

    public function test_matching_save_rejects_unknown_keys(): void
    {
        [$sesi, $soal, $mapel] = $this->createMatchingSession();

        $response = $this->withSession([
            'participant_token' => $sesi->session_token,
            '_token' => 'matching-token',
        ])
            ->withHeader('X-CSRF-TOKEN', 'matching-token')
            ->postJson(route('siswa.api.save_answer'), [
                'question_id' => $soal->id,
                'mapel_paket_id' => $mapel->id,
                'tipe_soal' => 'menjodohkan',
                'jawaban_menjodohkan' => [
                    ['row_key' => 'bogusrowkey12345', 'opt_key' => 'bogusoptkey12345'],
                ],
                'is_ragu' => false,
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('jawaban_siswas', [
            'ujian_sesi_id' => $sesi->id,
            'soal_id' => $soal->id,
        ]);
    }

    public function test_simulation_sessions_are_excluded_from_student_results(): void
    {
        [$sesi, , $mapel] = $this->createMatchingSession();
        $sesi->update(['status' => 'selesai', 'skor' => 55]);

        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'jenjang' => 'SMP',
            'access_token' => 'GURUTOKEN1',
        ]);

        UjianSesi::create([
            'exam_id' => $sesi->exam_id,
            'paket_soal_id' => $sesi->paket_soal_id,
            'mapel_paket_id' => $mapel->id,
            'user_id' => $guru->id,
            'nama' => 'SimulasiGuruTersembunyi',
            'session_token' => Str::random(40),
            'status' => 'selesai',
            'skor' => 100,
        ]);

        $response = $this->actingAs($guru)->get(route('guru.results.mapel', [$sesi->exam, $mapel]));

        $response->assertOk();
        $response->assertDontSee('SimulasiGuruTersembunyi');
        $response->assertSee($sesi->nama);
    }

    private function extractExamPayload(string $html): ?array
    {
        if (! preg_match('/<script id="exam-data" type="application\/json">(.*?)<\/script>/s', $html, $matches)) {
            return null;
        }

        return json_decode(html_entity_decode($matches[1], ENT_QUOTES), true);
    }

    private function createMatchingSession(): array
    {
        $user = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $jenjang = Jenjang::where('kode', 'SMP')->firstOrFail();
        $paket = PaketSoal::create([
            'jenjang_id' => $jenjang->id,
            'nama' => 'Paket Matching',
            'tahun_ajaran' => '2025/2026',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $mapel = MapelPaket::create([
            'paket_soal_id' => $paket->id,
            'nama_mapel' => 'matematika',
            'jumlah_soal' => 1,
            'durasi_menit' => 60,
            'urutan' => 1,
        ]);

        $soal = Soal::create([
            'mapel_paket_id' => $mapel->id,
            'nomor_soal' => 1,
            'tipe_soal' => 'menjodohkan',
            'indikator' => 'Indikator',
            'pertanyaan' => 'Jodohkan pasangan berikut',
            'bobot' => 1,
        ]);

        $pasangan = collect([
            ['teks_kiri' => 'Ibu Kota Indonesia', 'teks_kanan' => 'Jakarta'],
            ['teks_kiri' => 'Ibu Kota Jawa Barat', 'teks_kanan' => 'Bandung'],
            ['teks_kiri' => 'Ibu Kota Jawa Timur', 'teks_kanan' => 'Surabaya'],
        ])->map(fn ($pair) => PasanganMenjodohkan::create([
            'soal_id' => $soal->id,
            'teks_kiri' => $pair['teks_kiri'],
            'teks_kanan' => $pair['teks_kanan'],
        ]));

        $exam = Exam::create([
            'user_id' => $user->id,
            'paket_soal_id' => $paket->id,
            'judul' => 'Ujian Matching',
            'tanggal_terbit' => now(),
            'max_peserta' => 50,
            'timer' => 60,
            'status' => 'terbit',
            'is_active' => true,
        ]);

        $sesi = UjianSesi::create([
            'exam_id' => $exam->id,
            'paket_soal_id' => $paket->id,
            'mapel_paket_id' => $mapel->id,
            'nama' => 'Siswa Matching',
            'session_token' => Str::random(60),
            'status' => 'mengerjakan',
            'waktu_mulai' => now(),
            'timer_state' => [
                $mapel->id => [
                    'duration_seconds' => 3600,
                    'remaining_seconds' => 3600,
                    'started_at' => now()->toIso8601String(),
                    'finished_at' => null,
                ],
            ],
        ]);

        return [$sesi, $soal, $mapel, $pasangan];
    }
}
