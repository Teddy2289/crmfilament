<?php

namespace App\Filament\NsConseil\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;

class ImportProgressWidget extends Widget
{
    protected static string $view = 'filament.ns-conseil.widgets.import-progress';

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    public ?string $importId = null;

    /** @var array<string, mixed> */
    public array $progress = [];

    public function mount(): void
    {
        $this->importId = Cache::get('user_active_import:'.auth()->id());
        $this->refreshProgress();
    }

    #[On('import-started')]
    public function onImportStarted(string $importId): void
    {
        $this->importId = $importId;
        $this->refreshProgress();
    }

    public function refreshProgress(): void
    {
        $this->progress = $this->importId
            ? (Cache::get('import_progress:'.$this->importId) ?? [])
            : [];
    }

    public function dismiss(): void
    {
        Cache::forget('user_active_import:'.auth()->id());
        $this->importId = null;
        $this->progress = [];
    }

    public function isFinished(): bool
    {
        return in_array($this->progress['status'] ?? null, ['done', 'failed'], true);
    }

    public function shouldPoll(): bool
    {
        return filled($this->importId) && ! $this->isFinished();
    }

    public function percent(): int
    {
        $total = $this->progress['total'] ?? 0;
        $processed = $this->progress['processed'] ?? 0;

        if (! $total) {
            return 0;
        }

        return (int) min(100, round(($processed / $total) * 100));
    }
}
