<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Probe: CRUD superadmin benar-benar muncul di landing page publik?
 * Dibuat oleh Rokimah (UX/UI audit).
 */
class SuperadminLandingPropagationTest extends TestCase
{
    use RefreshDatabase;

    public function test_faq_created_in_superadmin_appears_on_landing(): void
    {
        $superadmin = User::factory()->create(['role' => User::ROLE_SUPERADMIN, 'account_status' => User::STATUS_ACTIVE]);

        $this->actingAs($superadmin)->post(route('superadmin.landing-settings.faq.store'), [
            'question' => 'PERTANYAAN-UJI-ROKIMAH?',
            'answer' => 'JAWABAN-UJI-ROKIMAH.',
            'sort_order' => 1,
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('landing_faqs', ['question' => 'PERTANYAAN-UJI-ROKIMAH?']);

        $html = $this->get('/')->getContent();

        $found = str_contains($html, 'PERTANYAAN-UJI-ROKIMAH');
        fwrite(STDERR, "\nFAQ tampil di landing: ".($found ? 'YA' : 'TIDAK')."\n");
        $this->assertTrue($found, 'FAQ yang dibuat superadmin tidak muncul di landing page publik');
    }

    public function test_testimonial_created_in_superadmin_appears_on_landing(): void
    {
        $superadmin = User::factory()->create(['role' => User::ROLE_SUPERADMIN, 'account_status' => User::STATUS_ACTIVE]);

        $this->actingAs($superadmin)->post(route('superadmin.testimonials.store'), [
            'name' => 'NAMA-UJI-ROKIMAH',
            'role' => 'Guru Matematika',
            'content' => 'KONTEN-UJI-ROKIMAH',
            'rating' => 5,
            'sort_order' => 1,
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('testimonials', ['name' => 'NAMA-UJI-ROKIMAH']);

        $html = $this->get('/')->getContent();
        $found = str_contains($html, 'KONTEN-UJI-ROKIMAH');
        fwrite(STDERR, "\nTestimoni tampil di landing: ".($found ? 'YA' : 'TIDAK')."\n");
        $this->assertTrue($found, 'Testimoni yang dibuat superadmin tidak muncul di landing page publik');
    }

    public function test_blog_post_created_in_superadmin_is_reachable_publicly(): void
    {
        $superadmin = User::factory()->create(['role' => User::ROLE_SUPERADMIN, 'account_status' => User::STATUS_ACTIVE]);

        $this->actingAs($superadmin)->post(route('superadmin.blog.store'), [
            'title' => 'JUDUL-UJI-ROKIMAH',
            'slug' => 'judul-uji-rokimah',
            'excerpt' => 'Ringkasan uji.',
            'content' => 'Isi artikel uji rokimah.',
            'is_published' => true,
        ])->assertRedirect();

        $post = BlogPost::where('slug', 'judul-uji-rokimah')->first();
        fwrite(STDERR, "\nBlogPost tersimpan: ".($post ? 'YA' : 'TIDAK')."\n");

        if ($post) {
            $resp = $this->get('/artikel/'.$post->slug);
            fwrite(STDERR, "halaman artikel status=".$resp->getStatusCode()."\n");
            $this->assertSame(200, $resp->getStatusCode());
        }

        $this->assertNotNull($post);
    }

    public function test_wa_blast_siswa_target_is_rejected_despite_being_offered_in_ui(): void
    {
        $superadmin = User::factory()->create(['role' => User::ROLE_SUPERADMIN, 'account_status' => User::STATUS_ACTIVE]);

        $resp = $this->actingAs($superadmin)->post(route('superadmin.wa-blast.send'), [
            'target' => 'siswa_all',
            'message' => 'Pesan uji',
        ]);

        $flash = session('flash');
        fwrite(STDERR, "\nWA blast siswa_all flash=".json_encode($flash)."\n");

        $this->assertSame('warning', $flash['type'] ?? null, 'target siswa_all tidak ditolak seperti yang diharapkan');
    }
}
