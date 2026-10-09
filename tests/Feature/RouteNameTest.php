<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteNameTest extends TestCase
{
    public function test_materi_practice_login_route_exists(): void
    {
        $this->assertTrue(
            Route::has('materi.practice.login'),
            "Route 'materi.practice.login' should exist."
        );
    }

    public function test_materi_practice_dashboard_route_exists(): void
    {
        $this->assertTrue(
            Route::has('materi.practice.dashboard'),
            "Route 'materi.practice.dashboard' should exist."
        );
    }

    public function test_old_materi_login_route_name_does_not_exist(): void
    {
        $this->assertFalse(
            Route::has('materi.login'),
            "Old route name 'materi.login' should no longer exist (renamed to 'materi.practice.login')."
        );
    }

    public function test_old_materi_dashboard_route_name_does_not_exist(): void
    {
        $this->assertFalse(
            Route::has('materi.dashboard'),
            "Old route name 'materi.dashboard' should no longer exist (renamed to 'materi.practice.dashboard')."
        );
    }

    public function test_materi_practice_login_route_resolves_to_correct_uri(): void
    {
        $route = Route::getRoutes()->getByName('materi.practice.login');

        $this->assertNotNull($route);
        $this->assertSame('materi/login', $route->uri());
    }

    public function test_materi_practice_dashboard_route_resolves_to_correct_uri(): void
    {
        $route = Route::getRoutes()->getByName('materi.practice.dashboard');

        $this->assertNotNull($route);
        $this->assertSame('materi', $route->uri());
    }

    public function test_material_practice_controller_references_new_route_names(): void
    {
        $controllerPath = app_path('Http/Controllers/Siswa/MaterialPracticeController.php');
        $source = file_get_contents($controllerPath);

        $this->assertStringContainsString("materi.practice.login", $source);
        $this->assertStringContainsString("materi.practice.dashboard", $source);
        $this->assertStringNotContainsString("route('materi.login')", $source);
        $this->assertStringNotContainsString("route('materi.dashboard')", $source);
    }
}
