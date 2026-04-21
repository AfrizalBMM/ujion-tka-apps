<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\GlobalQuestion;
use App\Models\Jenjang;
use App\Models\Material;
use App\Models\MapelPaket;
use App\Models\PaketSoal;
use App\Models\Soal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SuperadminAccessAndExamBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_superadmin_dashboard(): void
    {
        $response = $this->get('/superadmin');

        $response->assertRedirect(route('login'));
    }

    public function test_guru_cannot_access_superadmin_dashboard(): void
    {
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($guru)
            ->getJson('/superadmin');

        $response->assertForbidden();
    }

    public function test_legacy_questions_route_redirects_to_global_question_page(): void
    {
        $superadmin = $this->createSuperadmin();

        $response = $this->actingAs($superadmin)->get(route('superadmin.questions.index'));

        $response->assertRedirect(route('superadmin.global-questions.index'));
    }

    public function test_superadmin_global_questions_page_renders(): void
    {
        $superadmin = $this->createSuperadmin();

        $response = $this->actingAs($superadmin)->get(route('superadmin.global-questions.index'));

        $response->assertOk();
        $response->assertSee('Bank Soal Global');
    }

    public function test_superadmin_exam_creation_stores_creator_id(): void
    {
        $superadmin = $this->createSuperadmin();
        $paket = $this->createPaket($superadmin);

        $response = $this->actingAs($superadmin)->post(route('superadmin.exams.store'), [
            'paket_soal_id' => $paket->id,
            'judul' => 'Ujian Creator Check',
            'tanggal_terbit' => now()->format('Y-m-d H:i:s'),
            'max_peserta' => 50,
            'timer' => 90,
            'status' => 'draft',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('exams', [
            'judul' => 'Ujian Creator Check',
            'user_id' => $superadmin->id,
            'paket_soal_id' => $paket->id,
        ]);

        $exam = Exam::where('judul', 'Ujian Creator Check')->firstOrFail();
    }



    public function test_guru_cannot_manage_superadmin_owned_package_content(): void
    {
        $superadmin = $this->createSuperadmin();
        $paket = $this->createPaket($superadmin);
        $mapel = MapelPaket::create([
            'paket_soal_id' => $paket->id,
            'nama_mapel' => 'matematika',
            'jumlah_soal' => 30,
            'durasi_menit' => 75,
            'urutan' => 1,
        ]);

        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'jenjang' => 'SMP',
        ]);

        $this->assertTrue(Gate::forUser($guru)->denies('create', [Soal::class, $mapel]));
    }

    public function test_superadmin_can_save_exam_builder_questions_using_pivot_schema(): void
    {
        $superadmin = $this->createSuperadmin();
        $paket = $this->createPaket($superadmin);
        $material = $this->createMaterial();
        $exam = $this->createExam($superadmin, $paket);

        $response = $this->actingAs($superadmin)->post(route('superadmin.exams.builder.save', $exam), [
            'questions' => [
                [
                    'material_id' => $material->id,
                    'tipe' => 'PG',
                    'pertanyaan' => '2 + 2 = ?',
                    'opsi' => ['1', '2', '3', '4'],
                    'jawaban_benar' => '4',
                    'pembahasan' => 'Jawaban benar adalah 4',
                    'image' => null,
                ],
            ],
        ]);

        $response->assertRedirect();
        $exam->refresh();

        $this->assertCount(1, $exam->questions);
        $this->assertDatabaseHas('questions', [
            'material_id' => $material->id,
            'pertanyaan' => '2 + 2 = ?',
            'status' => 'draft',
        ]);
        $this->assertDatabaseHas('exam_question', [
            'exam_id' => $exam->id,
            'question_id' => $exam->questions->first()->id,
            'order' => 1,
        ]);
    }

    public function test_superadmin_can_import_global_question_into_exam_builder(): void
    {
        $superadmin = $this->createSuperadmin();
        $paket = $this->createPaket($superadmin);
        $material = $this->createMaterial();
        $exam = $this->createExam($superadmin, $paket);

        $globalQuestion = GlobalQuestion::create([
            'material_id' => null,
            'question_type' => 'multiple_choice',
            'question_text' => 'Planet terdekat dari matahari?',
            'options' => ['Merkurius', 'Venus', 'Bumi'],
            'answer_key' => 'Merkurius',
            'explanation' => 'Merkurius berada paling dekat dengan matahari.',
            'is_active' => true,
            'created_by' => $superadmin->id,
        ]);

        $response = $this->actingAs($superadmin)->post(route('superadmin.exams.import-bank', $exam), [
            'global_question_ids' => [$globalQuestion->id],
        ]);

        $response->assertRedirect();
        $exam->refresh();

        $this->assertCount(1, $exam->questions);
        $this->assertDatabaseHas('questions', [
            'material_id' => $material->id,
            'pertanyaan' => 'Planet terdekat dari matahari?',
            'jawaban_benar' => 'Merkurius',
            'status' => 'terbit',
        ]);
    }

    private function createSuperadmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);
    }

    private function createPaket(User $superadmin): PaketSoal
    {
        $jenjang = Jenjang::where('kode', 'SMP')->firstOrFail();

        return PaketSoal::create([
            'jenjang_id' => $jenjang->id,
            'nama' => 'Paket Builder',
            'tahun_ajaran' => '2025/2026',
            'is_active' => true,
            'created_by' => $superadmin->id,
        ]);
    }

    private function createMaterial(): Material
    {
        return Material::create([
            'curriculum' => 'Merdeka',
            'subelement' => 'Numerasi',
            'unit' => 'Bilangan',
            'sub_unit' => 'Operasi Dasar',
        ]);
    }

    private function createExam(User $superadmin, PaketSoal $paket): Exam
    {
        return Exam::create([
            'user_id' => $superadmin->id,
            'paket_soal_id' => $paket->id,
            'judul' => 'Ujian Builder',
            'tanggal_terbit' => now(),
            'max_peserta' => 50,
            'timer' => 75,
            'status' => 'draft',
            'is_active' => true,
        ]);
    }
}
