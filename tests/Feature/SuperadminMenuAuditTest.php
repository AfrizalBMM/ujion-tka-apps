<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Audit menu superadmin: crawl tiap GET route tanpa parameter,
 * pastikan tidak ada 500 / exception. Dipakai sebagai regression guard.
 *
 * Dibuat oleh Rokimah (UX/UI audit).
 */
class SuperadminMenuAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_parameterless_superadmin_get_route_renders(): void
    {
        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => in_array('GET', $r->methods(), true))
            ->filter(fn ($r) => str_starts_with($r->uri(), 'superadmin'))
            ->filter(fn ($r) => ! str_contains($r->uri(), '{'))
            ->sortBy(fn ($r) => $r->uri());

        $this->assertGreaterThan(15, $routes->count(), 'route superadmin tidak terbaca');

        $failures = [];
        $ok = [];

        foreach ($routes as $route) {
            $this->actingAs($superadmin);

            try {
                $response = $this->get('/'.$route->uri());
                $status = $response->getStatusCode();
            } catch (\Throwable $e) {
                $failures[] = sprintf('/%s (%s) EXCEPTION: %s', $route->uri(), $route->getName(), $e->getMessage());

                continue;
            }

            $line = sprintf('/%s (%s) -> %d', $route->uri(), $route->getName(), $status);

            if (! in_array($status, [200, 302], true)) {
                $failures[] = $line;
            } else {
                $ok[] = $line;
            }
        }

        fwrite(STDERR, "\n== OK (".count($ok).") ==\n".implode("\n", $ok)."\n");
        fwrite(STDERR, "\n== FAIL (".count($failures).") ==\n".implode("\n", $failures)."\n");

        $this->assertSame([], $failures, "Route superadmin gagal render:\n".implode("\n", $failures));
    }
}
