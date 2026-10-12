<?php

namespace Tests\Feature;

use App\Models\LandingContentItem;
use App\Models\LandingFaq;
use App\Models\Material;
use App\Models\User;
use Database\Seeders\LandingContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QaLandingCopyGate1Test extends TestCase
{
    use RefreshDatabase;

    private const SINGLETON_SECTIONS = [
        'bank_soal',
        'footer',
        'reasons_head',
        'audience_head',
        'steps_head',
        'features_head',
        'faq_head',
    ];

    private const ITEM_SECTIONS = [
        'hero_point',
        'reason',
        'audience',
        'step_guru',
        'step_siswa',
        'feature',
    ];

    private function superadmin(): User
    {
        return User::factory()->create(['role' => User::ROLE_SUPERADMIN, 'account_status' => User::STATUS_ACTIVE]);
    }

    public function test_item_1_home_200_empty_db_fallback_without_leaked_strings(): void
    {
        $resp = $this->get('/');

        $resp->assertOk();
        $html = (string) $resp->getContent();

        $fallbacks = [
            'Latih siswa untuk TKA dengan soal dan skor yang mirip ujian aslinya',
            'Bank soal dan materi',
            'Dibuat khusus untuk TKA, bukan ujian online serba guna.',
            'Untuk siapa Ujion',
            'Cara kerja untuk guru dan siswa',
            'Yang membuat Ujion cocok untuk persiapan TKA',
            'Pertanyaan yang sering diajukan',
            'bukan penyelenggara ujian TKA resmi',
            'Masukkan kode ujian',
            'Skor dan soal mirip TKA',
            'Bimbel',
            'Daftar akun guru',
        ];

        foreach ($fallbacks as $text) {
            $this->assertStringContainsString($text, $html, 'fallback hilang: '.$text);
        }

        foreach (['Tempel PNG', 'Arah mockup', 'Simulasi mengelola', 'menjawaban'] as $leak) {
            $this->assertStringNotContainsStringIgnoringCase($leak, $html, 'string bocor: '.$leak);
        }

        $this->assertStringNotContainsString('>null<', $html);
        $this->assertStringNotContainsString('{{', $html);
    }

    public function test_item_2_singleton_sections_crud(): void
    {
        $sa = $this->superadmin();

        foreach (self::SINGLETON_SECTIONS as $section) {
            $this->actingAs($sa)->post(route('superadmin.landing-settings.content'), [
                'section' => $section,
                'kicker' => 'KICK-'.$section,
                'title' => 'TITLE-'.$section,
                'body' => 'BODY-'.$section,
                'note' => 'NOTE-'.$section,
                'button_text' => 'BTN-'.$section,
            ])->assertRedirect();

            $this->assertDatabaseHas('landing_contents', [
                'section' => $section,
                'kicker' => 'KICK-'.$section,
                'title' => 'TITLE-'.$section,
                'body' => 'BODY-'.$section,
            ]);

            $this->actingAs($sa)->post(route('superadmin.landing-settings.content'), [
                'section' => $section,
                'kicker' => 'KICK2-'.$section,
                'title' => 'TITLE2-'.$section,
                'body' => 'BODY2-'.$section,
                'note' => 'NOTE2-'.$section,
                'button_text' => 'BTN2-'.$section,
            ])->assertRedirect();

            $this->assertDatabaseHas('landing_contents', [
                'section' => $section,
                'kicker' => 'KICK2-'.$section,
                'body' => 'BODY2-'.$section,
                'note' => 'NOTE2-'.$section,
            ]);

            $this->actingAs($sa)
                ->post(route('superadmin.landing-settings.sections.toggle', ['section' => $section]))
                ->assertRedirect();

            $this->assertDatabaseHas('landing_contents', ['section' => $section, 'is_active' => false]);

            $this->actingAs($sa)
                ->post(route('superadmin.landing-settings.sections.toggle', ['section' => $section]))
                ->assertRedirect();

            $this->assertDatabaseHas('landing_contents', ['section' => $section, 'is_active' => true]);
        }

        $this->actingAs($sa)
            ->post(route('superadmin.landing-settings.sections.toggle', ['section' => 'bukan_section']))
            ->assertNotFound();

        $this->actingAs($sa)
            ->post(route('superadmin.landing-settings.content'), ['section' => 'bukan_section'])
            ->assertSessionHasErrors('section');

        $this->actingAs($sa)
            ->post(route('superadmin.landing-settings.content'), ['section' => 'footer', 'title' => str_repeat('x', 221)])
            ->assertSessionHasErrors('title');
    }

    public function test_item_2b_section_toggle_changes_landing_output(): void
    {
        $sa = $this->superadmin();

        $this->get('/')->assertOk();
        $this->actingAs($sa)
            ->post(route('superadmin.landing-settings.sections.toggle', ['section' => 'hero']))
            ->assertRedirect();
        $this->actingAs($sa)
            ->post(route('superadmin.landing-settings.sections.toggle', ['section' => 'stats']))
            ->assertRedirect();
        $this->actingAs($sa)
            ->post(route('superadmin.landing-settings.sections.toggle', ['section' => 'faq']))
            ->assertRedirect();

        $html = (string) $this->get('/')->getContent();
        $this->assertStringNotContainsString('landing-hero-title', $html, 'hero masih tampil setelah dimatikan');
        $this->assertStringNotContainsString('Segera hadir', $html, 'section statistik masih tampil setelah dimatikan');
        $this->assertStringNotContainsString('Pertanyaan yang sering diajukan', $html, 'FAQ masih tampil setelah dimatikan');
        $this->assertStringNotContainsString('#tanya-jawab', $html, 'menu Tanya Jawab masih tampil setelah FAQ dimatikan');

        $this->actingAs($sa)
            ->post(route('superadmin.landing-settings.sections.toggle', ['section' => 'faq']))
            ->assertRedirect();

        $html = (string) $this->get('/')->getContent();
        $this->assertStringContainsString('Pertanyaan yang sering diajukan', $html, 'FAQ tidak kembali setelah diaktifkan');
        $this->assertStringContainsString('#tanya-jawab', $html, 'menu Tanya Jawab tidak kembali setelah FAQ diaktifkan');
    }

    public function test_item_3_generic_item_sections_crud_and_ordering(): void
    {
        $sa = $this->superadmin();

        foreach (self::ITEM_SECTIONS as $section) {
            $this->actingAs($sa)->post(route('superadmin.landing-settings.items.store'), [
                'section' => $section,
                'title' => 'QA-STORE-'.$section,
                'body' => 'QA-BODY-'.$section,
                'sort_order' => 7,
            ])->assertRedirect();

            $item = LandingContentItem::query()->where('title', 'QA-STORE-'.$section)->first();
            $this->assertNotNull($item, 'gagal simpan section '.$section);
            $this->assertSame($section, $item->section);
            $this->assertTrue((bool) $item->is_active);

            $this->actingAs($sa)->post(route('superadmin.landing-settings.items.update', $item), [
                'title' => 'QA-UPDATE-'.$section,
                'body' => 'QA-UPBODY-'.$section,
                'sort_order' => 3,
            ])->assertRedirect();

            $this->assertDatabaseHas('landing_content_items', [
                'id' => $item->id,
                'title' => 'QA-UPDATE-'.$section,
                'sort_order' => 3,
            ]);

            $this->actingAs($sa)->post(route('superadmin.landing-settings.items.toggle', $item))->assertRedirect();
            $this->assertFalse((bool) $item->fresh()->is_active);

            $this->actingAs($sa)->post(route('superadmin.landing-settings.items.toggle', $item))->assertRedirect();
            $this->assertTrue((bool) $item->fresh()->is_active);

            $this->actingAs($sa)->post(route('superadmin.landing-settings.items.destroy', $item))->assertRedirect();
            $this->assertDatabaseMissing('landing_content_items', ['id' => $item->id]);
        }

        $this->assertSame(0, LandingContentItem::query()->count());

        $this->actingAs($sa)->post(route('superadmin.landing-settings.items.store'), [
            'section' => 'reason',
            'title' => 'QA-ORDER-LATE',
            'sort_order' => 9,
        ])->assertRedirect();
        $this->actingAs($sa)->post(route('superadmin.landing-settings.items.store'), [
            'section' => 'reason',
            'title' => 'QA-ORDER-EARLY',
            'sort_order' => 1,
        ])->assertRedirect();

        $this->actingAs($sa)
            ->post(route('superadmin.landing-settings.items.store'), ['section' => 'step_guru', 'title' => str_repeat('a', 221)])
            ->assertSessionHasErrors('title');
        $this->actingAs($sa)
            ->post(route('superadmin.landing-settings.items.store'), ['section' => 'step_guru', 'title' => 'ok', 'body' => str_repeat('b', 2001)])
            ->assertSessionHasErrors('body');
        $this->actingAs($sa)
            ->post(route('superadmin.landing-settings.items.store'), ['section' => 'step_guru', 'title' => 'ok', 'kicker' => str_repeat('k', 181)])
            ->assertSessionHasErrors('kicker');
        $this->actingAs($sa)
            ->post(route('superadmin.landing-settings.items.store'), ['section' => 'step_guru', 'title' => 'ok', 'caption' => str_repeat('c', 301)])
            ->assertSessionHasErrors('caption');
        $this->actingAs($sa)
            ->post(route('superadmin.landing-settings.items.store'), ['section' => 'bukan_section', 'title' => 'ok'])
            ->assertSessionHasErrors('section');

        $html = (string) $this->get('/')->getContent();
        $early = strpos($html, 'QA-ORDER-EARLY');
        $late = strpos($html, 'QA-ORDER-LATE');
        $this->assertNotFalse($early, 'item terbaru tidak tampil di landing');
        $this->assertNotFalse($late, 'item kedua tidak tampil di landing');
        $this->assertLessThan($late, $early, 'urutan sort_order tidak dihormati di landing');
    }

    public function test_item_4_landing_reads_db_and_falls_back_when_deleted(): void
    {
        $sa = $this->superadmin();

        $this->actingAs($sa)->post(route('superadmin.landing-settings.content'), [
            'section' => 'reasons_head',
            'kicker' => 'QA-DB-REASONS-KICKER',
            'title' => 'QA-DB-REASONS-TITLE',
        ])->assertRedirect();

        $this->actingAs($sa)->post(route('superadmin.landing-settings.items.store'), [
            'section' => 'reason',
            'title' => 'QA-DB-REASON-TITLE',
            'body' => 'QA-DB-REASON-BODY',
        ])->assertRedirect();

        $this->actingAs($sa)->post(route('superadmin.landing-settings.faq.store'), [
            'question' => 'QA-DB-FAQ-Q?',
            'answer' => 'QA-DB-FAQ-A',
            'sort_order' => 1,
        ])->assertRedirect();

        $html = (string) $this->get('/')->getContent();
        $this->assertStringContainsString('QA-DB-REASONS-KICKER', $html);
        $this->assertStringContainsString('QA-DB-REASONS-TITLE', $html);
        $this->assertStringContainsString('QA-DB-REASON-TITLE', $html);
        $this->assertStringContainsString('QA-DB-REASON-BODY', $html);
        $this->assertStringContainsString('QA-DB-FAQ-Q?', $html);
        $this->assertStringContainsString('QA-DB-FAQ-A', $html);

        $this->actingAs($sa)->post(route('superadmin.landing-settings.faq.destroy', LandingFaq::query()->first()))->assertRedirect();

        $item = LandingContentItem::query()->where('title', 'QA-DB-REASON-TITLE')->first();
        $this->actingAs($sa)->post(route('superadmin.landing-settings.items.destroy', $item))->assertRedirect();

        $html = (string) $this->get('/')->getContent();
        $this->assertStringNotContainsString('QA-DB-REASON-TITLE', $html);
        $this->assertStringNotContainsString('QA-DB-FAQ-Q?', $html);
        $this->assertStringContainsString('Skor dan soal mirip TKA', $html, 'fallback item tidak kembali setelah hapus');
        $this->assertStringContainsString('Apa bedanya Ujion dengan aplikasi ujian online biasa?', $html, 'fallback FAQ tidak kembali setelah hapus');

        DB::table('landing_contents')->where('section', 'reasons_head')->delete();
        $html = (string) $this->get('/')->getContent();
        $this->assertStringContainsString('3 alasan Ujion berbeda dari ujian online biasa', $html, 'fallback section head tidak kembali setelah hapus');
    }

    public function test_item_5_stats_are_real_counts_and_zero_says_segera_hadir(): void
    {
        Material::query()->create([
            'jenjang' => 'SD',
            'mapel' => 'Umum',
            'curriculum' => 'K-13',
            'subelement' => 'SE',
            'unit' => 'U',
            'sub_unit' => 'SU',
        ]);
        Material::query()->create([
            'jenjang' => 'SD',
            'mapel' => 'Tambahan',
            'curriculum' => 'K-13',
            'subelement' => 'SE',
            'unit' => 'U',
            'sub_unit' => 'SU',
        ]);
        Material::query()->create([
            'jenjang' => 'SMA',
            'mapel' => 'Umum',
            'curriculum' => 'K-13',
            'subelement' => 'SE',
            'unit' => 'U',
            'sub_unit' => 'SU',
        ]);

        $materialId = Material::query()->where('jenjang', 'SD')->first()->id;
        DB::table('questions')->insert([
            ['material_id' => $materialId, 'jenjang' => 'SD', 'kategori' => 'Mudah', 'tipe' => 'PG', 'pertanyaan' => 'Q1', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['material_id' => $materialId, 'jenjang' => 'SD', 'kategori' => 'Mudah', 'tipe' => 'PG', 'pertanyaan' => 'Q2', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['material_id' => $materialId, 'jenjang' => 'SD', 'kategori' => 'Mudah', 'tipe' => 'PG', 'pertanyaan' => 'Q3 nonaktif', 'is_active' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $resp = $this->get('/');
        $resp->assertOk();
        $html = (string) $resp->getContent();

        $resp->assertViewHas('stats', function (array $stats) {
            $this->assertSame(2, $stats['SD']['materi']);
            $this->assertSame(2, $stats['SD']['soal']);
            $this->assertFalse($stats['SD']['coming_soon']);
            $this->assertSame(1, $stats['SMA']['materi']);
            $this->assertSame(0, $stats['SMA']['soal']);
            $this->assertTrue($stats['SMA']['coming_soon']);
            $this->assertTrue($stats['SMP']['coming_soon']);

            return true;
        });

        $dbSoal = (int) DB::table('questions')->where('is_active', 1)->where('jenjang', 'SD')->count();
        $resp->assertViewHas('stats', fn (array $stats) => $stats['SD']['soal'] === $dbSoal);

        $this->assertSame(2, substr_count($html, 'Segera hadir'), 'jenjang tanpa soal (SMP, SMA) wajib tampil Segera hadir');
        $this->assertStringContainsString('<td>2</td>', $html, 'jumlah soal nyata tidak tampil di tabel');
        $this->assertStringNotContainsString('Q3 nonaktif', $html, 'soal nonaktif bocor ke landing');
    }

    public function test_item_5b_stats_jenjang_list_matches_jenjangs_table(): void
    {
        $this->get('/')->assertViewHas('stats', function (array $stats) {
            $this->assertSame(['SD', 'SMP', 'SMA'], array_keys($stats));

            return true;
        });
    }

    public function test_item_6_no_banned_words_on_landing(): void
    {
        $html = (string) $this->get('/')->getContent();

        $this->assertDoesNotMatchRegularExpression('/\b(gratis|white-label|freemium|CBT|aligned)\b/i', $html);
    }

    public function test_item_6b_no_banned_words_after_seeder(): void
    {
        $this->seed(LandingContentSeeder::class);

        $html = (string) $this->get('/')->getContent();

        $this->assertDoesNotMatchRegularExpression('/\b(gratis|white-label|freemium|CBT|aligned)\b/i', $html);
        foreach (['Tempel PNG', 'Arah mockup', 'Simulasi mengelola', 'menjawaban'] as $leak) {
            $this->assertStringNotContainsStringIgnoringCase($leak, $html, 'string bocor setelah seeder: '.$leak);
        }
    }

    public function test_item_7_score_scale_regulation_and_disclaimer(): void
    {
        $html = (string) $this->get('/')->getContent();

        $this->assertStringContainsString('0-100 untuk SD dan SMP', $html);
        $this->assertStringContainsString('200-800 untuk SMA dan SMK', $html);
        $this->assertStringContainsString('0-100 untuk SD/SMP', $html);
        $this->assertStringContainsString('200-800 untuk SMA/SMK', $html);
        $this->assertStringContainsString('Nomor 47 Tahun 2025', $html);
        $this->assertStringContainsString('Nomor 45 Tahun 2025', $html);
        $this->assertStringContainsString('bukan penyelenggara ujian TKA resmi', $html);
        $this->assertStringContainsString('Peraturan Kepala Badan Standar, Kurikulum, dan Asesmen Pendidikan (BSKAP)', $html);
    }

    public function test_item_8_field_length_limits(): void
    {
        $sa = $this->superadmin();

        $title90 = str_repeat('Judul utama QA ', 5).'Judul utama QAQ'; // 90 karakter tanpa spasi ekor
        $this->assertSame(90, strlen($title90));

        $this->actingAs($sa)->post(route('superadmin.landing-settings.content'), [
            'section' => 'hero',
            'title' => $title90,
        ])->assertRedirect();

        $this->assertDatabaseHas('landing_contents', ['section' => 'hero', 'title' => $title90]);

        $html = (string) $this->get('/')->getContent();
        $this->assertStringContainsString($title90, $html, 'judul 90 karakter tidak tampil utuh');

        $this->actingAs($sa)
            ->post(route('superadmin.landing-settings.content'), ['section' => 'hero', 'title' => str_repeat('y', 221)])
            ->assertSessionHasErrors('title');

        $this->assertDatabaseHas('landing_contents', ['section' => 'hero', 'title' => $title90]);

        $this->actingAs($sa)
            ->post(route('superadmin.landing-settings.items.store'), ['section' => 'feature', 'title' => 'ok', 'body' => str_repeat('z', 2001)])
            ->assertSessionHasErrors('body');

        $this->actingAs($sa)
            ->post(route('superadmin.landing-settings.content'), ['section' => 'reasons_head', 'body' => str_repeat('w', 5001)])
            ->assertSessionHasErrors('body');

        $this->actingAs($sa)
            ->post(route('superadmin.landing-settings.content'), ['section' => 'hero', 'seo_title' => str_repeat('s', 121)])
            ->assertSessionHasErrors('seo_title');

        $this->actingAs($sa)
            ->post(route('superadmin.landing-settings.content'), ['section' => 'hero', 'seo_description' => str_repeat('d', 301)])
            ->assertSessionHasErrors('seo_description');
    }

    public function test_item_9_admin_text_rendered_escaped(): void
    {
        $sa = $this->superadmin();

        $payload = '<script>window.__qa_xss=1</script>';

        $this->actingAs($sa)->post(route('superadmin.landing-settings.content'), [
            'section' => 'reasons_head',
            'kicker' => $payload,
            'title' => '<img src=x onerror=window.__qa2=1>',
        ])->assertRedirect();

        $this->actingAs($sa)->post(route('superadmin.landing-settings.items.store'), [
            'section' => 'reason',
            'title' => $payload,
            'body' => '<b onmouseover="window.__qa3=1">bold</b>',
        ])->assertRedirect();

        $html = (string) $this->get('/')->getContent();

        $this->assertStringNotContainsString('<script>window.__qa_xss=1</script>', $html);
        $this->assertStringNotContainsString('<img src=x onerror=window.__qa2=1>', $html);
        $this->assertStringNotContainsString('<b onmouseover="window.__qa3=1">', $html);
        $this->assertStringContainsString('&lt;script&gt;window.__qa_xss=1&lt;/script&gt;', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=window.__qa2=1&gt;', $html);
        $this->assertStringContainsString('&lt;b onmouseover=&quot;window.__qa3=1&quot;&gt;', $html);
        $this->assertStringNotContainsString('window.__qa_xss=1;', $html);
    }

    public function test_item_10_siswa_entry_page_uses_kode_ujian_label(): void
    {
        $resp = $this->get('/siswa/login');
        $resp->assertOk();

        $vue = file_get_contents(base_path('resources/js/Pages/Siswa/Login.vue'));
        $this->assertStringContainsString('Kode Ujian', $vue);
        $this->assertStringContainsString('kode ujian yang diberikan guru', $vue);
        $this->assertDoesNotMatchRegularExpression('/<label[^>]*>\s*Token\s*<\/label>/i', $vue);

        $landing = (string) $this->get('/')->getContent();
        $this->assertDoesNotMatchRegularExpression('/\btoken\b/i', $landing);
    }

    public function test_finding_p2_siswa_login_error_copy_still_says_token(): void
    {
        $resp = $this->post('/siswa/login', ['token' => 'QA9999']);

        $resp->assertSessionHasErrors('token');

        $errors = session('errors')->get('token');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Token', $errors[0], 'temuan P2: pesan error login siswa masih memakai kata Token');
    }

    public function test_finding_p1_faq_json_ld_script_breakout_is_fixed(): void
    {
        $this->actingAs($this->superadmin())->post(route('superadmin.landing-settings.faq.store'), [
            'question' => 'QA-JSONLD-Q?',
            'answer' => '</script><script>window.__qa_jsonld=1</script>',
            'sort_order' => 1,
        ])->assertRedirect();

        $html = (string) $this->get('/')->getContent();

        $this->assertStringContainsString('application/ld+json', $html);
        $this->assertStringContainsString('QA-JSONLD-Q?', $html);

        $this->assertStringNotContainsString('</script><script>window.__qa_jsonld=1</script>', $html, 'regression F-1: jawaban FAQ tidak boleh menutup tag script JSON-LD (landing.blade.php)');
        $this->assertStringContainsString('"@context":"https:\\/\\/schema.org"', $html, 'regression F-1/F-7: key @context harus lolos utuh (Blade directive collision)');
    }

    public function test_stats_cache_note(): void
    {
        Material::query()->create([
            'jenjang' => 'SD',
            'mapel' => 'Umum',
            'curriculum' => 'K-13',
            'subelement' => 'SE',
            'unit' => 'U',
            'sub_unit' => 'SU',
        ]);
        $materialId = Material::query()->first()->id;

        $this->get('/')->assertOk();

        DB::table('questions')->insert([
            ['material_id' => $materialId, 'jenjang' => 'SD', 'kategori' => 'Mudah', 'tipe' => 'PG', 'pertanyaan' => 'Q-CACHE', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->get('/')->assertViewHas(
            'stats',
            fn (array $stats) => $stats['SD']['soal'] === 0,
            'stats masih pakai cache 10 menit (LandingController::jenjangStats) - new soal belum tampil'
        );
    }
}
