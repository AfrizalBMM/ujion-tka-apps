<?php

namespace Tests\Feature;

use App\Models\PaketSoal;
use App\Models\User;
use App\Models\Jenjang;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaketSoalManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_create_package_and_default_mapel(): void
    {
        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $jenjangId = Jenjang::firstOrCreate(
            ['kode' => 'SMP'],
            ['nama' => 'Sekolah Menengah Pertama', 'urutan' => 2]
        )->id;

        $response = $this->actingAs($superadmin)->post(route('superadmin.paket-soal.store'), [
            'jenjang_id' => $jenjangId,
            'nama' => 'Paket SMP Reguler',
            'tahun_ajaran' => '2025/2026',
            'is_active' => 1,
        ]);

        $paket = PaketSoal::where('nama', 'Paket SMP Reguler')->first();

        $this->assertNotNull($paket);
        $response->assertRedirect(route('superadmin.paket-soal.show', $paket));
        $this->assertSame('paket_lengkap', $paket->assessment_type);
        $this->assertCount(4, $paket->mapelPakets);
        $this->assertEqualsCanonicalizing(
            ['bahasa_indonesia', 'matematika', 'survey_karakter', 'sulingjar'],
            $paket->mapelPakets->pluck('nama_mapel')->all()
        );
    }

    public function test_superadmin_update_keeps_package_structure_as_full_bundle(): void
    {
        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $jenjangId = Jenjang::firstOrCreate(
            ['kode' => 'SMP'],
            ['nama' => 'Sekolah Menengah Pertama', 'urutan' => 2]
        )->id;

        $paket = PaketSoal::create([
            'jenjang_id' => $jenjangId,
            'assessment_type' => 'paket_lengkap',
            'nama' => 'Paket Awal',
            'tahun_ajaran' => '2025/2026',
            'is_active' => true,
            'created_by' => $superadmin->id,
        ]);

        $response = $this->actingAs($superadmin)->put(route('superadmin.paket-soal.update', $paket), [
            'jenjang_id' => $jenjangId,
            'assessment_type' => 'survey_karakter',
            'nama' => 'Paket Awal Revisi',
            'tahun_ajaran' => '2026/2027',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('superadmin.paket-soal.show', $paket));
        $paket->refresh();

        $this->assertSame('paket_lengkap', $paket->assessment_type);
        $this->assertSame('Paket Awal Revisi', $paket->nama);
        $this->assertSame('2026/2027', $paket->tahun_ajaran);
    }
}
