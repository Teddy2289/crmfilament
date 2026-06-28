<?php

namespace Tests\Feature;

use App\Models\Theme;
use App\Models\User;
use Database\Seeders\ThemeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ThemeManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function seeded_default_themes_keep_filament_native_chrome(): void
    {
        $this->seed(ThemeSeeder::class);

        $theme = Theme::resolveForPanel('ns-conseil');

        $this->assertNotNull($theme);
        $this->assertTrue($theme->is_default);
        $this->assertFalse($theme->usesEspoChrome());
        $this->assertFalse($theme->shouldApplyColors());
    }

    #[Test]
    public function explicit_user_theme_can_enable_espo_chrome_and_custom_colors(): void
    {
        $this->seed(ThemeSeeder::class);

        $user = User::factory()->create([
            'theme_preference' => 'ns-conseil-espo',
        ]);

        $theme = Theme::resolveForPanel('ns-conseil', $user);

        $this->assertNotNull($theme);
        $this->assertSame('ns-conseil-espo', $theme->name);
        $this->assertTrue($theme->usesEspoChrome());
        $this->assertTrue($theme->shouldApplyColors());
        $this->assertArrayHasKey('primary', $theme->getColors());
    }
}
