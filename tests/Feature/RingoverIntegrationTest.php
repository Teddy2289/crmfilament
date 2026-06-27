<?php

namespace Tests\Feature;

use App\Services\RingoverService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RingoverIntegrationTest extends TestCase
{
    #[Test]
    public function ringover_replaces_aircall_in_active_laravel_integration(): void
    {
        $this->assertArrayHasKey('ringover:sync', Artisan::all());
        $this->assertInstanceOf(RingoverService::class, app(RingoverService::class));

        $this->assertSame('https://public-api.ringover.com/v2', config('ringover.base_url'));
        $this->assertSame('Bearer', config('ringover.auth_scheme'));
        $this->assertSame('tel:{phone}', config('ringover.dial_url_template'));

        $this->assertTrue($this->routeExists('ns-conseil/ringover/recording/{callId}'));
        $this->assertFalse($this->routeExists('ns-conseil/aircall/recording/{callId}'));

        $this->assertFileDoesNotExist(config_path('aircall.php'));
        $this->assertFileExists(config_path('ringover.php'));
    }

    private function routeExists(string $uri): bool
    {
        foreach (Route::getRoutes() as $route) {
            if ($route->uri() === $uri) {
                return true;
            }
        }

        return false;
    }
}
