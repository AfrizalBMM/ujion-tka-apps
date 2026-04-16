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
        $this->assertCount(2, $paket->mapelPakets);
        $this->assertEqualsCanonicalizing(
            ['bahasa_indonesia', 'matematika'],
            $paket->mapelPakets->pluck('nama_mapel')->all()
        );
    }
}
