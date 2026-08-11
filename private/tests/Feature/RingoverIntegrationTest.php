<?php

namespace Tests\Feature;

use App\Services\RingoverService;
use App\Services\RingoverCallSyncService;
use App\Services\RingoverTagService;
use App\Services\RingoverUserMapper;
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
        $this->assertInstanceOf(RingoverCallSyncService::class, app(RingoverCallSyncService::class));
        $this->assertInstanceOf(RingoverTagService::class, app(RingoverTagService::class));
        $this->assertInstanceOf(RingoverUserMapper::class, app(RingoverUserMapper::class));

        $this->assertSame('https://public-api.ringover.com/v2', config('ringover.base_url'));
        $this->assertSame('Bearer', config('ringover.auth_scheme'));
        $this->assertSame('tel:{phone}', config('ringover.dial_url_template'));
        $this->assertArrayHasKey('rdv', config('ringover.status_tags'));

        $this->assertTrue($this->routeExists('ns-conseil/ringover/recording/{callId}'));
        $this->assertTrue($this->routeExists('api/ringover/webhook', 'POST'));
        $this->assertFalse($this->routeExists('ns-conseil/aircall/recording/{callId}'));

        $this->assertFileDoesNotExist(config_path('aircall.php'));
        $this->assertFileDoesNotExist(app_path('Filament/NsConseil/Widgets/AircallKpisChart.php'));
        $this->assertFileExists(config_path('ringover.php'));
    }

    private function routeExists(string $uri, string $method = 'GET'): bool
    {
        foreach (Route::getRoutes() as $route) {
            if ($route->uri() === $uri && in_array($method, $route->methods(), true)) {
                return true;
            }
        }

        return false;
    }
}
