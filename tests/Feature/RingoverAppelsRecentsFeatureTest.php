<?php

namespace Tests\Feature;

use App\Filament\NsConseil\Widgets\RingoverAppelsRecents;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RingoverAppelsRecentsFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function recent_call_widget_buttons_do_not_submit_the_parent_form(): void
    {
        $paths = [
            resource_path('views/filament/ns-conseil/widgets/ringover-appels-recents.blade.php'),
            base_path('private/resources/views/filament/ns-conseil/widgets/ringover-appels-recents.blade.php'),
        ];

        foreach ($paths as $path) {
            $this->assertFileExists($path);

            $view = file_get_contents($path);

            $this->assertStringContainsString('type="button"', $view);
            $this->assertStringContainsString('wire:click="setDirection(\'\')"', $view);
            $this->assertStringContainsString('wire:click="clearFilters"', $view);
            $this->assertStringContainsString('wire:click="nextPage"', $view);
        }
    }
}
