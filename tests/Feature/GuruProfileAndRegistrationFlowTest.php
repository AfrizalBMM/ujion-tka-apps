<?php

namespace Tests\Feature;

use App\Models\PersonalQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GuruProfileAndRegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_points_to_guru_registration_route(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('register.guru.form'));
        $this->assertStringContainsString(
            "route('register.guru.form')",
            file_get_contents(resource_path('views/landing.blade.php'))
        );
    }

    public function test_guru_registration_creates_pending_account(): void
    {
        $response = $this->post(route('register.guru'), [
            'name' => 'Guru Baru',
            'email' => 'guru.baru@example.com',
            'jenjang' => 'SMP',
            'tingkat' => '7',
            'satuan_pendidikan' => 'SMPN 1 Contoh',
            'no_wa' => '0812-3456-789',
        ]);

        $response->assertRedirect(route('register.guru.pending'));
        $this->assertDatabaseHas('users', [
            'name' => 'Guru Baru',
            'email' => 'guru.baru@example.com',
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'no_wa' => '08123456789',
        ]);

        $user = User::where('email', 'guru.baru@example.com')->firstOrFail();
        $this->assertFalse(Hash::check('password', $user->password));
    }

    public function test_guru_can_update_complete_profile_fields(): void
    {
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'jenjang' => 'SD',
            'tingkat' => '4',
            'satuan_pendidikan' => 'SD Lama',
            'no_wa' => '08111',
        ]);

        $response = $this->actingAs($guru)->post(route('guru.profile.update'), [
            'name' => 'Guru Update',
            'email' => 'guru-update@example.com',
            'jenjang' => 'SMP',
            'tingkat' => '8',
            'satuan_pendidikan' => 'SMP Baru',
            'no_wa' => '089999',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $guru->id,
            'name' => 'Guru Update',
            'email' => 'guru-update@example.com',
            'jenjang' => 'SMP',
            'tingkat' => '8',
            'satuan_pendidikan' => 'SMP Baru',
            'no_wa' => '089999',
        ]);
    }

    public function test_guru_cannot_delete_other_users_personal_question(): void
    {
        $owner = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $otherGuru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $question = PersonalQuestion::create([
            'user_id' => $owner->id,
            'jenjang' => 'SMP',
            'kategori' => 'Sedang',
            'tipe' => 'PG',
            'pertanyaan' => 'Soal milik owner',
            'opsi' => ['A', 'B'],
            'jawaban_benar' => 'A',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($otherGuru)->post(route('guru.personal-questions.destroy', $question));

        $response->assertNotFound();
        $this->assertDatabaseHas('personal_questions', ['id' => $question->id]);
    }

    public function test_personal_question_builder_only_replaces_current_guru_questions(): void
    {
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $otherGuru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        PersonalQuestion::create([
            'user_id' => $guru->id,
            'jenjang' => 'SMP',
            'kategori' => 'Sedang',
            'tipe' => 'PG',
            'pertanyaan' => 'Soal lama guru A',
            'opsi' => ['A', 'B'],
            'jawaban_benar' => 'A',
            'status' => 'draft',
        ]);

        $otherQuestion = PersonalQuestion::create([
            'user_id' => $otherGuru->id,
            'jenjang' => 'SD',
            'kategori' => 'Mudah',
            'tipe' => 'PG',
            'pertanyaan' => 'Soal guru B',
            'opsi' => ['A', 'B'],
            'jawaban_benar' => 'B',
            'status' => 'terbit',
        ]);

        $response = $this->actingAs($guru)->post(route('guru.personal-questions.builder.save'), [
            'questions' => [
                [
                    'jenjang' => 'SMP',
                    'kategori' => 'Sulit',
                    'tipe' => 'PG',
                    'pertanyaan' => 'Soal baru guru A',
                    'opsi' => ['1', '2', '3', '4'],
                    'jawaban_benar' => '4',
                    'pembahasan' => 'Pembahasan',
                    'image' => null,
                    'status' => 'draft',
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('personal_questions', [
            'user_id' => $guru->id,
            'pertanyaan' => 'Soal lama guru A',
        ]);
        $this->assertDatabaseHas('personal_questions', [
            'user_id' => $guru->id,
            'pertanyaan' => 'Soal baru guru A',
        ]);
        $this->assertDatabaseHas('personal_questions', [
            'id' => $otherQuestion->id,
            'user_id' => $otherGuru->id,
            'pertanyaan' => 'Soal guru B',
        ]);
    }
}
