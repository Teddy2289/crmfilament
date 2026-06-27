<?php

namespace App\Filament\NsConseil\Widgets;

use App\Services\RingoverService;
use Filament\Widgets\Widget;

class RingoverAppelsRecents extends Widget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '60s';

    protected static string $view = 'filament.ns-conseil.widgets.ringover-appels-recents';

    protected static bool $isLazy = true;

    public array $calls = [];

    public int $page = 1;

    public int $perPage = 25;

    public string $filterDirection = '';

    public function mount(): void
    {
        $this->loadCalls();
    }

    public function loadCalls(): void
    {
        $filters = ['per_page' => $this->perPage, 'page' => $this->page, 'order' => 'desc'];
        if ($this->filterDirection) {
            $filters['direction'] = $this->filterDirection;
        }

        $this->calls = app(RingoverService::class)->getCalls($filters);
    }

    public function setDirection(string $direction): void
    {
        $this->filterDirection = $direction;
        $this->page = 1;
        $this->loadCalls();
    }

    public function nextPage(): void
    {
        $this->page++;
        $this->loadCalls();
    }

    public function prevPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
            $this->loadCalls();
        }
    }
}
