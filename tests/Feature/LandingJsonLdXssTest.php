<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingJsonLdXssTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        return User::factory()->create(['role' => User::ROLE_SUPERADMIN, 'account_status' => User::STATUS_ACTIVE]);
    }

    public function test_faq_answer_cannot_break_out_of_json_ld(): void
    {
        $payload = '</script><script>alert(document.cookie)</script>';

        $this->actingAs($this->superadmin())->post(route('superadmin.landing-settings.faq.store'), [
            'question' => 'Q probe',
            'answer' => $payload,
        ])->assertRedirect();

        $html = $this->get('/')->getContent();

        $this->assertStringNotContainsString($payload, $html);
    }

    public function test_faq_json_ld_stays_valid_json_after_escaping(): void
    {
        $this->actingAs($this->superadmin())->post(route('superadmin.landing-settings.faq.store'), [
            'question' => 'Q "berkutat" & <b>sudah</b>',
            'answer' => "Jawaban 'kutip' & </script> <b>tebal</b>",
        ])->assertRedirect();

        $html = $this->get('/')->getContent();

        $this->assertMatchesRegularExpression(
            '#<script type="application/ld\+json">\s*(\{.*?\})\s*</script>#s',
            $html
        );

        preg_match_all('#<script type="application/ld\+json">\s*(\{.*?\})\s*</script>#s', $html, $m);
        $this->assertNotEmpty($m[1]);
        foreach ($m[1] as $block) {
            $decoded = json_decode($block, true);
            $this->assertIsArray($decoded, 'blok JSON-LD harus valid JSON, dapat: '.$block);
            $this->assertSame('https://schema.org', $decoded['@context'] ?? null, 'key @context harus utuh (jangan sampai dimakan directive Blade)');
        }
    }
}
