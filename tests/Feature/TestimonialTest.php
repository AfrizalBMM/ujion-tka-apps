<?php

namespace Tests\Feature;

use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TestimonialTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
        ]);
    }

    public function test_superadmin_can_create_testimonial(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->superadmin())->post(route('superadmin.testimonials.store'), [
            'name' => 'Siti Aisyah',
            'role' => 'Guru SD',
            'content' => 'Platform ini sangat membantu persiapan TKA.',
            'rating' => 5,
            'photo' => UploadedFile::fake()->image('foto.jpg'),
            'sort_order' => 1,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('superadmin.testimonials.index'));

        $this->assertDatabaseHas('testimonials', [
            'name' => 'Siti Aisyah',
            'role' => 'Guru SD',
            'rating' => 5,
            'is_active' => true,
        ]);

        $testimonial = Testimonial::first();
        $this->assertNotNull($testimonial->photo_path);
        Storage::disk('public')->assertExists($testimonial->photo_path);
    }

    public function test_superadmin_can_toggle_and_delete_testimonial(): void
    {
        $testimonial = Testimonial::create([
            'name' => 'Budi',
            'content' => 'Sangat bagus.',
            'rating' => 4,
            'is_active' => false,
            'sort_order' => 0,
        ]);

        $admin = $this->superadmin();

        $this->actingAs($admin)->post(route('superadmin.testimonials.toggle', $testimonial));
        $this->assertTrue($testimonial->fresh()->is_active);

        $this->actingAs($admin)->post(route('superadmin.testimonials.destroy', $testimonial));
        $this->assertDatabaseMissing('testimonials', ['id' => $testimonial->id]);
    }

    public function test_landing_shows_only_active_testimonials_ordered(): void
    {
        Testimonial::create([
            'name' => 'Tidak Aktif',
            'content' => 'Jangan tampil.',
            'rating' => 3,
            'is_active' => false,
            'sort_order' => 0,
        ]);

        Testimonial::create([
            'name' => 'Aktif Kedua',
            'content' => 'Tampil kedua.',
            'rating' => 5,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        Testimonial::create([
            'name' => 'Aktif Pertama',
            'content' => 'Tampil pertama.',
            'rating' => 4,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->get(route('landing'));

        $response->assertOk();
        $response->assertSee('Mereka sudah mencoba');
        $response->assertSee('Aktif Pertama');
        $response->assertSee('Aktif Kedua');
        $response->assertDontSee('Tidak Aktif');
        $response->assertSee('data-testimonial-slider', false);

        $content = $response->getContent();
        $this->assertTrue(
            strpos($content, 'Aktif Pertama') < strpos($content, 'Aktif Kedua')
        );
    }

    public function test_guest_cannot_access_testimonial_admin(): void
    {
        $this->get(route('superadmin.testimonials.index'))->assertRedirect();
    }
}
