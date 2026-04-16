<?php

namespace Tests\Feature;

use App\Http\Controllers\Guru\MaterialController;
use App\Http\Controllers\Superadmin\ChatController;
use App\Http\Controllers\Superadmin\ExamAnalysisController;
use App\Http\Controllers\Superadmin\TeksBacaanController;
use App\Models\Chat;
use App\Models\Exam;
use App\Models\GlobalQuestion;
use App\Models\Jenjang;
use App\Models\MapelPaket;
use App\Models\Material;
use App\Models\PaketSoal;
use App\Models\PersonalQuestion;
use App\Models\Question;
use App\Models\Soal;
use App\Models\TeksBacaan;
use App\Models\UjianSesi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemainingFlowsHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_personal_question_quick_form_parses_comma_separated_options(): void
    {
        $guru = $this->createGuru();

        $response = $this->actingAs($guru)->post(route('guru.personal-questions.store'), [
            'jenjang' => 'SMP',
            'kategori' => 'Numerasi',
            'tipe' => 'PG',
            'pertanyaan' => 'Pilih bilangan genap',
            'options_raw' => '1, 2, 3, 4',
            'jawaban_benar' => '2',
            'pembahasan' => 'Bilangan genap habis dibagi 2',
            'status' => 'draft',
        ]);

        $response->assertRedirect();

        $question = PersonalQuestion::query()->firstOrFail();
        $this->assertSame(['1', '2', '3', '4'], $question->opsi);
    }

    public function test_superadmin_can_update_global_question_from_edit_flow(): void
    {
        $superadmin = $this->createSuperadmin();
        $question = GlobalQuestion::create([
            'question_type' => 'multiple_choice',
            'question_text' => 'Teks lama',
            'options' => ['A'],
            'answer_key' => 'A',
            'is_active' => true,
            'created_by' => $superadmin->id,
        ]);

        $response = $this->actingAs($superadmin)->post(route('superadmin.global-questions.update', $question), [
            'question_type' => 'multiple_choice',
            'question_text' => 'Teks baru',
            'options_raw' => 'Alpha, Beta',
            'answer_key' => 'Beta',
            'explanation' => 'Diperbarui',
            'is_active' => 0,
        ]);

        $response->assertRedirect();
        $question->refresh();

        $this->assertSame('Teks baru', $question->question_text);
        $this->assertSame(['Alpha', 'Beta'], $question->options);
        $this->assertSame('Beta', $question->answer_key);
        $this->assertFalse($question->is_active);
    }

    public function test_superadmin_chat_index_filters_to_selected_conversation(): void
    {
        $superadmin = $this->createSuperadmin();
        $teacherA = $this->createGuru('Guru A');
        $teacherB = $this->createGuru('Guru B');

        Chat::create([
            'from_user_id' => $teacherA->id,
            'to_user_id' => $superadmin->id,
            'message' => 'Pesan untuk A',
            'is_read' => false,
        ]);

        Chat::create([
            'from_user_id' => $teacherB->id,
            'to_user_id' => $superadmin->id,
            'message' => 'Pesan untuk B',
            'is_read' => false,
        ]);

        $this->be($superadmin);

        $request = Request::create(route('superadmin.chat.index', ['user' => $teacherA->id]), 'GET', ['user' => $teacherA->id]);
        $request->setUserResolver(fn () => $superadmin);

        $view = app(ChatController::class)->index($request);
        $data = $view->getData();

        $this->assertSame($teacherA->id, $data['selectedUser']->id);
        $this->assertCount(1, $data['chats']);
        $this->assertSame('Pesan untuk A', $data['chats']->first()->message);
    }

    public function test_material_detail_uses_global_and_snapshot_reference_counts(): void
    {
        $guru = $this->createGuru();
        $material = Material::create([
            'curriculum' => 'Merdeka',
            'subelement' => 'Literasi',
            'unit' => 'Teks',
            'sub_unit' => 'Membaca',
        ]);

        GlobalQuestion::create([
            'material_id' => $material->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Global',
            'options' => ['A', 'B'],
            'answer_key' => 'A',
            'is_active' => true,
            'created_by' => $guru->id,
        ]);

        Question::create([
            'material_id' => $material->id,
            'jenjang' => 'SMP',
            'tingkat' => '7',
            'kategori' => 'Sedang',
            'tipe' => 'PG',
            'pertanyaan' => 'Snapshot',
            'opsi' => ['A', 'B'],
            'jawaban_benar' => 'A',
            'status' => 'draft',
        ]);

        $this->be($guru);
        $request = Request::create(route('guru.materials.show', $material), 'GET');
        $request->setUserResolver(fn () => $guru);

        $view = app(MaterialController::class)->show($material);
        $data = $view->getData();

        $this->assertSame(1, $data['globalQuestionCount']);
        $this->assertSame(1, $data['examSnapshotCount']);
    }

    public function test_superadmin_cannot_delete_package_with_exam_dependencies(): void
    {
        $superadmin = $this->createSuperadmin();
        $paket = $this->createPaket($superadmin);

        Exam::create([
            'user_id' => $superadmin->id,
            'paket_soal_id' => $paket->id,
            'judul' => 'Ujian Aktif',
            'tanggal_terbit' => now(),
            'max_peserta' => 30,
            'token' => 'DEPEND1234',
            'timer' => 90,
            'status' => 'draft',
            'is_active' => true,
        ]);

        $response = $this->actingAs($superadmin)->delete(route('superadmin.paket-soal.destroy', $paket));

        $response->assertSessionHas('flash', fn (array $flash) => $flash['type'] === 'warning');
        $this->assertDatabaseHas('paket_soals', ['id' => $paket->id]);
    }

    public function test_superadmin_cannot_delete_teks_bacaan_that_is_still_used(): void
    {
        $superadmin = $this->createSuperadmin();
        [$paket, $mapel] = $this->createPaketWithMapel($superadmin);

        $bacaan = TeksBacaan::create([
            'mapel_paket_id' => $mapel->id,
            'judul' => 'Bacaan 1',
            'konten' => 'Isi bacaan',
        ]);

        Soal::create([
            'mapel_paket_id' => $mapel->id,
            'teks_bacaan_id' => $bacaan->id,
            'nomor_soal' => 1,
            'tipe_soal' => 'pilihan_ganda',
            'indikator' => 'Indikator',
            'pertanyaan' => 'Pertanyaan',
            'bobot' => 1,
        ]);

        $this->be($superadmin);
        $response = app(TeksBacaanController::class)->destroy($paket, $mapel, $bacaan);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertDatabaseHas('teks_bacaans', ['id' => $bacaan->id]);
    }

    public function test_superadmin_exports_dashboard_and_exam_analysis(): void
    {
        $superadmin = $this->createSuperadmin();
        $paket = $this->createPaket($superadmin);
        $exam = Exam::create([
            'user_id' => $superadmin->id,
            'paket_soal_id' => $paket->id,
            'judul' => 'Ujian Export',
            'tanggal_terbit' => now(),
            'max_peserta' => 30,
            'token' => 'EXPORT1234',
            'timer' => 90,
            'status' => 'draft',
            'is_active' => true,
        ]);

        UjianSesi::create([
            'exam_id' => $exam->id,
            'paket_soal_id' => $paket->id,
            'nama' => 'Peserta 1',
            'nomor_wa' => '08123456789',
            'session_token' => 'SESSIONTOKEN123',
            'status' => 'selesai',
            'timer_state' => [],
            'skor' => 88,
            'waktu_mulai' => now()->subMinutes(30),
            'waktu_selesai' => now(),
        ]);

        $dashboardCsv = $this->actingAs($superadmin)->get(route('superadmin.dashboard.export-csv'));
        $dashboardCsv->assertOk();
        $this->assertStringContainsString('active_teachers', $dashboardCsv->streamedContent());

        $analysisCsv = $this->actingAs($superadmin)->get(route('superadmin.exams.analysis.export-csv', $exam));
        $analysisCsv->assertOk();
        $this->assertStringContainsString('participants_count', $analysisCsv->streamedContent());

        $this->be($superadmin);
        $analysisPrint = app(ExamAnalysisController::class)->print($exam);
        $this->assertSame('superadmin.exports.exam-analysis-print', $analysisPrint->name());
    }

    private function createSuperadmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);
    }

    private function createGuru(string $name = 'Guru Test'): User
    {
        return User::factory()->create([
            'name' => $name,
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'jenjang' => 'SMP',
        ]);
    }

    private function createPaket(User $superadmin): PaketSoal
    {
        $jenjang = Jenjang::firstOrCreate(
            ['kode' => 'SMP'],
            ['nama' => 'Sekolah Menengah Pertama', 'urutan' => 2]
        );

        return PaketSoal::create([
            'jenjang_id' => $jenjang->id,
            'nama' => 'Paket Hardening',
            'tahun_ajaran' => '2025/2026',
            'is_active' => true,
            'created_by' => $superadmin->id,
        ]);
    }

    private function createPaketWithMapel(User $superadmin): array
    {
        $paket = $this->createPaket($superadmin);
        $mapel = MapelPaket::create([
            'paket_soal_id' => $paket->id,
            'nama_mapel' => 'matematika',
            'jumlah_soal' => 30,
            'durasi_menit' => 75,
            'urutan' => 1,
        ]);

        return [$paket, $mapel];
    }
}
