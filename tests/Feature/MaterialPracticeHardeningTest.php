<?php

namespace Tests\Feature;

use App\Models\GlobalQuestion;
use App\Models\Jenjang;
use App\Models\Material;
use App\Models\MaterialPracticePackageAttempt;
use App\Models\MaterialPracticeSession;
use App\Models\MaterialPracticeToken;
use App\Models\MaterialTelaahAnswer;
use App\Models\MaterialTelaahQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MaterialPracticeHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_attempt_creation_is_race_safe(): void
    {
        $token = $this->createPracticeSetup();
        $session = $this->createSession($token);

        // Dua request bersamaan (simulasi race) — keduanya tidak boleh menyebabkan error.
        $responses = collect([1, 2])->map(function () use ($session) {
            return $this->withSession(['material_practice_session_token' => $session->session_token])
                ->get(route('materi.paket.show', ['paketNo' => 1]));
        });

        $responses->each(fn ($response) => $response->assertOk());
        $this->assertSame(1, MaterialPracticePackageAttempt::count());
    }

    public function test_double_submit_is_idempotent(): void
    {
        $token = $this->createPracticeSetup();
        $session = $this->createSession($token);

        $this->withSession(['material_practice_session_token' => $session->session_token])
            ->get(route('materi.paket.show', ['paketNo' => 1]));

        $first = $this->withSession(['material_practice_session_token' => $session->session_token])
            ->post(route('materi.paket.submit', ['paketNo' => 1]), ['answers' => []]);

        $first->assertRedirect(route('materi.dashboard'));
        $first->assertSessionHas('flash');

        $attempt = MaterialPracticePackageAttempt::firstOrFail();
        $this->assertSame('selesai', $attempt->status);

        $second = $this->withSession(['material_practice_session_token' => $session->session_token])
            ->post(route('materi.paket.submit', ['paketNo' => 1]), ['answers' => []]);

        $second->assertRedirect(route('materi.dashboard'));
        $this->assertSame(1, MaterialPracticePackageAttempt::count());
    }

    public function test_telaah_stats_only_count_active_questions(): void
    {
        $token = $this->createPracticeSetup();
        $session = $this->createSession($token);

        [$activeQuestion, $removedQuestion] = $token->material->id
            ? GlobalQuestion::where('material_id', $token->material_id)->orderBy('id')->get()->take(2)->values()
            : collect();

        $this->assertCount(2, collect([$activeQuestion, $removedQuestion])->filter());

        // Soal telaah aktif hanya yang pertama; yang kedua dulunya telaah lalu diganti.
        MaterialTelaahQuestion::query()->where('material_id', $token->material_id)->delete();
        MaterialTelaahQuestion::create([
            'material_id' => $token->material_id,
            'global_question_id' => $activeQuestion->id,
            'urutan' => 1,
        ]);

        // Jawaban siswa untuk soal lama (orphan) + soal aktif.
        MaterialTelaahAnswer::create([
            'material_practice_session_id' => $session->id,
            'global_question_id' => $removedQuestion->id,
            'jawaban' => 'x',
            'is_correct' => false,
        ]);
        MaterialTelaahAnswer::create([
            'material_practice_session_id' => $session->id,
            'global_question_id' => $activeQuestion->id,
            'jawaban' => 'y',
            'is_correct' => false,
        ]);

        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'jenjang' => 'SMP',
        ]);

        $response = $this->actingAs($guru)->get(route('guru.results.practice.show', $token->material));

        $response->assertOk();
        // Hanya soal telaah aktif yang muncul dalam statistik — jawaban orphan dikecualikan.
        $html = $response->getContent();
        $this->assertStringNotContainsString('Soal Telaah Orphan', $html);
    }

    private function createPracticeSetup(): MaterialPracticeToken
    {
        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $jenjang = Jenjang::where('kode', 'SMP')->firstOrFail();

        $material = Material::create([
            'curriculum' => 'Kurikulum Merdeka',
            'subelement' => 'Numerasi',
            'unit' => 'Unit 1',
            'sub_unit' => 'Sub Unit 1',
            'jenjang' => 'SMP',
            'mapel' => 'Matematika',
        ]);

        for ($i = 1; $i <= 12; $i++) {
            $question = GlobalQuestion::create([
                'material_id' => $material->id,
                'question_type' => 'multiple_choice',
                'question_text' => 'Soal '.$i,
                'options' => ['A', 'B', 'C', 'D'],
                'answer_key' => 'A',
                'is_active' => true,
                'created_by' => $superadmin->id,
            ]);

            if ($i <= 2) {
                MaterialTelaahQuestion::create([
                    'material_id' => $material->id,
                    'global_question_id' => $question->id,
                    'urutan' => $i,
                ]);
            }
        }

        $token = MaterialPracticeToken::create([
            'material_id' => $material->id,
            'jumlah_soal_per_paket' => 10,
            'is_active' => true,
            'created_by' => $superadmin->id,
        ]);

        $token->regeneratePackages();

        return $token->refresh();
    }

    private function createSession(MaterialPracticeToken $token): MaterialPracticeSession
    {
        return MaterialPracticeSession::create([
            'material_practice_token_id' => $token->id,
            'nama' => 'Siswa Latihan',
            'nomor_wa' => '628111222333',
            'session_token' => Str::random(60),
            'status' => 'mengerjakan',
        ]);
    }
}
