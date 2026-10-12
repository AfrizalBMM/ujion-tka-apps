<?php

namespace Tests\Feature;

use App\Models\LandingContentItem;
use App\Models\Material;
use App\Models\User;
use Database\Seeders\LandingContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LandingContentRevisiTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_renders_200_with_accurate_fallback_text(): void
    {
        $resp = $this->get('/');

        $resp->assertOk();
        $resp->assertViewHas('hero', fn (array $hero) => str_contains($hero['body'] ?? '', '0-100 untuk SD dan SMP'));
        $resp->assertViewHas('sections', fn (array $sections) => ($sections['bank_soal']['kicker'] ?? null) === 'Bank soal dan materi');
        $resp->assertViewHas('sections', fn (array $sections) => str_contains($sections['footer']['body'] ?? '', 'bukan penyelenggara ujian TKA resmi'));
        $resp->assertViewHas('items', fn (array $items) => count($items['hero_point'] ?? []) === 3 && count($items['audience'] ?? []) === 4);
        $resp->assertViewHas('faqs', fn (array $faqs) => str_contains(json_encode($faqs), 'Nomor 47 Tahun 2025'));
        $resp->assertViewHas('faqs', fn (array $faqs) => ! str_contains(json_encode($faqs), 'menjawaban'));
    }

    public function test_stats_are_per_jenjang_with_coming_soon_flag(): void
    {
        Material::query()->create([
            'jenjang' => 'SD',
            'mapel' => 'Umum',
            'curriculum' => 'K-13',
            'subelement' => 'SE',
            'unit' => 'U',
            'sub_unit' => 'SU',
        ]);

        DB::table('questions')->insert([
            [
                'material_id' => Material::query()->first()->id,
                'jenjang' => 'SD',
                'kategori' => 'Mudah',
                'tipe' => 'PG',
                'pertanyaan' => 'Soal uji SD 1',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'material_id' => Material::query()->first()->id,
                'jenjang' => 'SD',
                'kategori' => 'Mudah',
                'tipe' => 'PG',
                'pertanyaan' => 'Soal uji SD 2 nonaktif',
                'is_active' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $resp = $this->get('/');

        $resp->assertOk();
        $resp->assertViewHas('stats', function (array $stats) {
            $this->assertArrayHasKey('SD', $stats);
            $this->assertArrayHasKey('SMP', $stats);
            $this->assertSame(1, $stats['SD']['materi']);
            $this->assertSame(1, $stats['SD']['soal'], 'soal nonaktif tidak boleh dihitung');
            $this->assertFalse($stats['SD']['coming_soon']);
            $this->assertSame(0, $stats['SMP']['soal']);
            $this->assertTrue($stats['SMP']['coming_soon']);

            return true;
        });
    }

    public function test_superadmin_can_crud_landing_content_item(): void
    {
        $superadmin = User::factory()->create(['role' => User::ROLE_SUPERADMIN, 'account_status' => User::STATUS_ACTIVE]);

        $this->actingAs($superadmin)->post(route('superadmin.landing-settings.items.store'), [
            'section' => 'reason',
            'title' => 'JUDUL-ALASAN-UJI',
            'body' => 'Isi alasan uji.',
            'sort_order' => 5,
        ])->assertRedirect();

        $item = LandingContentItem::query()->where('title', 'JUDUL-ALASAN-UJI')->first();
        $this->assertNotNull($item);
        $this->assertSame('reason', $item->section);
        $this->assertTrue($item->is_active);

        $this->actingAs($superadmin)->post(route('superadmin.landing-settings.items.update', $item), [
            'title' => 'JUDUL-ALASAN-UJI-DIUBAH',
            'body' => 'Isi alasan uji diubah.',
            'sort_order' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('landing_content_items', [
            'id' => $item->id,
            'title' => 'JUDUL-ALASAN-UJI-DIUBAH',
            'sort_order' => 1,
        ]);

        $this->actingAs($superadmin)->post(route('superadmin.landing-settings.items.toggle', $item))->assertRedirect();
        $this->assertFalse($item->fresh()->is_active);

        $this->actingAs($superadmin)->post(route('superadmin.landing-settings.items.destroy', $item))->assertRedirect();
        $this->assertDatabaseMissing('landing_content_items', ['id' => $item->id]);
    }

    public function test_item_validation_rejects_invalid_section_and_overlong_fields(): void
    {
        $superadmin = User::factory()->create(['role' => User::ROLE_SUPERADMIN, 'account_status' => User::STATUS_ACTIVE]);

        $this->actingAs($superadmin)
            ->post(route('superadmin.landing-settings.items.store'), [
                'section' => 'faq',
                'title' => 'FAQ bukan item repeater',
            ])
            ->assertSessionHasErrors('section');

        $this->actingAs($superadmin)
            ->post(route('superadmin.landing-settings.items.store'), [
                'section' => 'reason',
                'title' => str_repeat('a', 221),
            ])
            ->assertSessionHasErrors('title');

        $this->actingAs($superadmin)
            ->post(route('superadmin.landing-settings.items.store'), [
                'section' => 'reason',
                'title' => 'Valid',
                'kicker' => str_repeat('k', 181),
            ])
            ->assertSessionHasErrors('kicker');

        $this->actingAs($superadmin)
            ->post(route('superadmin.landing-settings.items.store'), [
                'section' => 'reason',
                'title' => 'Valid',
                'body' => str_repeat('b', 2001),
            ])
            ->assertSessionHasErrors('body');

        $this->actingAs($superadmin)
            ->post(route('superadmin.landing-settings.items.store'), [
                'section' => 'reason',
                'title' => 'Valid',
                'caption' => str_repeat('c', 301),
            ])
            ->assertSessionHasErrors('caption');

        $this->assertSame(0, LandingContentItem::query()->count());
    }

    public function test_superadmin_landing_settings_index_renders_with_new_props(): void
    {
        $superadmin = User::factory()->create(['role' => User::ROLE_SUPERADMIN, 'account_status' => User::STATUS_ACTIVE]);

        $this->actingAs($superadmin)
            ->get(route('superadmin.landing-settings.index', ['tab' => 'content']))
            ->assertOk();

        $this->seed(LandingContentSeeder::class);

        $this->actingAs($superadmin)
            ->get(route('superadmin.landing-settings.index', ['tab' => 'content']))
            ->assertOk();

        $this->get('/')->assertOk();
    }

    public function test_save_content_supports_new_singleton_sections(): void
    {
        $superadmin = User::factory()->create(['role' => User::ROLE_SUPERADMIN, 'account_status' => User::STATUS_ACTIVE]);

        $this->actingAs($superadmin)->post(route('superadmin.landing-settings.content'), [
            'section' => 'bank_soal',
            'kicker' => 'Label bank soal uji',
            'title' => 'Judul bank soal uji',
            'body' => 'Rujukan peraturan uji.',
        ])->assertRedirect();

        $this->assertDatabaseHas('landing_contents', [
            'section' => 'bank_soal',
            'kicker' => 'Label bank soal uji',
        ]);

        $this->actingAs($superadmin)->post(route('superadmin.landing-settings.content'), [
            'section' => 'footer',
            'kicker' => 'Deskripsi footer uji',
            'title' => 'Masuk',
            'button_text' => 'Daftar',
            'note' => 'Ikuti Saluran WhatsApp uji',
        ])->assertRedirect();

        $this->assertDatabaseHas('landing_contents', [
            'section' => 'footer',
            'note' => 'Ikuti Saluran WhatsApp uji',
        ]);

        $this->actingAs($superadmin)->post(route('superadmin.landing-settings.content'), [
            'kicker' => 'Hero tanpa section eksplisit',
        ])->assertRedirect();

        $this->assertDatabaseHas('landing_contents', [
            'section' => 'hero',
            'kicker' => 'Hero tanpa section eksplisit',
        ]);
    }

    public function test_landing_content_seeder_is_idempotent(): void
    {
        $this->seed(LandingContentSeeder::class);
        $contentsFirst = DB::table('landing_contents')->count();
        $itemsFirst = DB::table('landing_content_items')->count();

        $this->seed(LandingContentSeeder::class);

        $this->assertSame($contentsFirst, DB::table('landing_contents')->count());
        $this->assertSame($itemsFirst, DB::table('landing_content_items')->count());
        $this->assertSame(8, $contentsFirst, 'jumlah section singleton tidak sesuai harapan');
        $this->assertSame(22, $itemsFirst, 'jumlah item repeater tidak sesuai harapan');

        $this->assertDatabaseHas('landing_contents', ['section' => 'bank_soal']);
        $this->assertDatabaseHas('landing_content_items', ['section' => 'step_siswa', 'title' => 'Selesai']);
    }
}
