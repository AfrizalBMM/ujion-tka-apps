<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteThrottleTest extends TestCase
{
    public function test_payment_start_route_has_throttle_middleware(): void
    {
        $route = Route::getRoutes()->getByName('payments.doku.start');

        $this->assertNotNull($route, "Route 'payments.doku.start' should exist.");
        $this->assertRouteHasThrottle($route, 'payments.doku.start');
    }

    public function test_exam_registration_route_has_throttle_middleware(): void
    {
        $route = Route::getRoutes()->getByName('ujian-online.register');

        $this->assertNotNull($route, "Route 'ujian-online.register' should exist.");
        $this->assertRouteHasThrottle($route, 'ujian-online.register');
    }

    public function test_exam_payment_start_route_has_throttle_middleware(): void
    {
        $route = Route::getRoutes()->getByName('ujian-online.pay.start');

        $this->assertNotNull($route, "Route 'ujian-online.pay.start' should exist.");
        $this->assertRouteHasThrottle($route, 'ujian-online.pay.start');
    }

    public function test_save_answer_route_has_throttle_30_middleware(): void
    {
        $route = Route::getRoutes()->getByName('siswa.api.save_answer');

        $this->assertNotNull($route, "Route 'siswa.api.save_answer' should exist.");
        $this->assertRouteHasThrottle($route, 'siswa.api.save_answer');
        $this->assertRouteHasThrottleLimit($route, 30);
    }

    public function test_siswa_mulai_route_has_throttle_middleware(): void
    {
        $route = Route::getRoutes()->getByName('siswa.mulai');

        $this->assertNotNull($route, "Route 'siswa.mulai' should exist.");
        $this->assertRouteHasThrottle($route, 'siswa.mulai');
    }

    public function test_logout_route_has_throttle_middleware(): void
    {
        $route = Route::getRoutes()->getByName('logout');

        $this->assertNotNull($route, "Route 'logout' should exist.");
        $this->assertRouteHasThrottle($route, 'logout');
    }

    public function test_siswa_selesai_submit_route_has_throttle_middleware(): void
    {
        $route = Route::getRoutes()->getByName('siswa.selesai.submit');

        $this->assertNotNull($route, "Route 'siswa.selesai.submit' should exist.");
        $this->assertRouteHasThrottle($route, 'siswa.selesai.submit');
    }

    public function test_materi_mulai_route_has_throttle_middleware(): void
    {
        $route = Route::getRoutes()->getByName('materi.mulai');

        $this->assertNotNull($route, "Route 'materi.mulai' should exist.");
        $this->assertRouteHasThrottle($route, 'materi.mulai');
    }

    public function test_materi_telaah_submit_route_has_throttle_middleware(): void
    {
        $route = Route::getRoutes()->getByName('materi.telaah.submit');

        $this->assertNotNull($route, "Route 'materi.telaah.submit' should exist.");
        $this->assertRouteHasThrottle($route, 'materi.telaah.submit');
    }

    public function test_materi_paket_submit_route_has_throttle_middleware(): void
    {
        $route = Route::getRoutes()->getByName('materi.paket.submit');

        $this->assertNotNull($route, "Route 'materi.paket.submit' should exist.");
        $this->assertRouteHasThrottle($route, 'materi.paket.submit');
    }

    public function test_siswa_practice_mulai_route_has_throttle_middleware(): void
    {
        $route = Route::getRoutes()->getByName('siswa.practice.mulai');

        $this->assertNotNull($route, "Route 'siswa.practice.mulai' should exist.");
        $this->assertRouteHasThrottle($route, 'siswa.practice.mulai');
    }

    public function test_siswa_practice_telaah_submit_route_has_throttle_middleware(): void
    {
        $route = Route::getRoutes()->getByName('siswa.practice.telaah.submit');

        $this->assertNotNull($route, "Route 'siswa.practice.telaah.submit' should exist.");
        $this->assertRouteHasThrottle($route, 'siswa.practice.telaah.submit');
    }

    public function test_siswa_practice_paket_submit_route_has_throttle_middleware(): void
    {
        $route = Route::getRoutes()->getByName('siswa.practice.paket.submit');

        $this->assertNotNull($route, "Route 'siswa.practice.paket.submit' should exist.");
        $this->assertRouteHasThrottle($route, 'siswa.practice.paket.submit');
    }

    public function test_materi_token_validate_route_has_throttle_middleware(): void
    {
        $route = Route::getRoutes()->getByName('materi.token.validate');

        $this->assertNotNull($route, "Route 'materi.token.validate' should exist.");
        $this->assertRouteHasThrottle($route, 'materi.token.validate');
    }

    public function test_thirteen_post_routes_have_throttle_middleware(): void
    {
        $throttledNames = [
            'payments.doku.start',
            'ujian-online.register',
            'ujian-online.pay.start',
            'logout',
            'siswa.mulai',
            'siswa.api.save_answer',
            'siswa.selesai.submit',
            'siswa.practice.mulai',
            'siswa.practice.telaah.submit',
            'siswa.practice.paket.submit',
            'materi.mulai',
            'materi.telaah.submit',
            'materi.paket.submit',
        ];

        $missing = [];

        foreach ($throttledNames as $name) {
            $route = Route::getRoutes()->getByName($name);

            if ($route === null) {
                $missing[] = $name.' (route not found)';

                continue;
            }

            $middleware = $route->gatherMiddleware();
            $hasThrottle = collect($middleware)->contains(
                fn ($m) => is_string($m) && str_starts_with($m, 'throttle')
            );

            if (! $hasThrottle) {
                $missing[] = $name;
            }
        }

        $this->assertEmpty(
            $missing,
            'The following routes should have throttle middleware but do not: '.implode(', ', $missing)
        );
    }

    private function assertRouteHasThrottle($route, string $name): void
    {
        $middleware = $route->gatherMiddleware();

        $hasThrottle = collect($middleware)->contains(
            fn ($m) => is_string($m) && str_starts_with($m, 'throttle')
        );

        $this->assertTrue(
            $hasThrottle,
            "Route '{$name}' should have throttle middleware. Got: "
            .implode(', ', array_map(fn ($m) => is_string($m) ? $m : gettype($m), $middleware))
        );
    }

    private function assertRouteHasThrottleLimit($route, int $limit): void
    {
        $middleware = $route->gatherMiddleware();

        $hasLimit = collect($middleware)->contains(
            fn ($m) => is_string($m) && str_starts_with($m, "throttle:{$limit}")
        );

        $this->assertTrue(
            $hasLimit,
            "Route should have throttle:{$limit} middleware. Got: "
            .implode(', ', array_map(fn ($m) => is_string($m) ? $m : gettype($m), $middleware))
        );
    }
}
