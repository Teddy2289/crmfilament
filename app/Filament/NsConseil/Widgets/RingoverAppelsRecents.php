<?php

namespace App\Filament\NsConseil\Widgets;

use App\Services\RingoverService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Log;

class RingoverAppelsRecents extends Widget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '60s';

    protected static string $view = 'filament.ns-conseil.widgets.ringover-appels-recents';

    protected static bool $isLazy = true;

    public ?string $errorMessage = null;

    public array $calls = [];

    public int $page = 1;

    public int $perPage = 25;

    public string $filterDirection = '';

    public string $filterNumber = '';

    public string $filterAgent = '';

    public string $filterAnswered = '';

    public bool $filterHasRecording = false;

    public array $agents = [];

    public bool $hasMore = false;

    public function mount(): void
    {
        $this->agents = $this->loadAgents();
        $this->loadCalls();
    }

    protected function loadAgents(): array
    {
        try {
            $users = app(RingoverService::class)->getUsers();
        } catch (\Exception $exception) {
            Log::error('RingoverAppelsRecents loadAgents failed', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);
            $this->errorMessage = 'Impossible de charger les utilisateurs Ringover.';

            return [];
        }

        return collect($users)
            ->map(function (array $user): array {
                $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($user['name'] ?? ($user['email'] ?? ''));

                return [
                    'id' => (string) ($user['id'] ?? $user['user_id'] ?? ''),
                    'name' => $name,
                ];
            })
            ->where('id')
            ->sortBy('name')
            ->values()
            ->all();

        return collect($users)
            ->map(function (array $user): array {
                $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($user['name'] ?? ($user['email'] ?? ''));

                return [
                    'id' => (string) ($user['id'] ?? $user['user_id'] ?? ''),
                    'name' => $name,
                ];
            })
            ->where('id')
            ->sortBy('name')
            ->values()
            ->all();
    }

    public function loadCalls(): void
    {
        $filters = [
            'limit_count' => max($this->perPage * 4, 100),
            'limit_offset' => 0,
        ];

        if ($this->filterDirection) {
            $filters['direction'] = $this->filterDirection;
        }

        try {
            $rawCalls = app(RingoverService::class)->getCalls($filters);
        } catch (\Exception $exception) {
            Log::error('RingoverAppelsRecents loadCalls failed', [
                'filters' => $filters,
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);
            $this->errorMessage = 'Impossible de charger les appels Ringover.';
            $this->calls = [];
            $this->hasMore = false;

            return;
        }

        $filteredCalls = array_values(array_filter($rawCalls, fn (array $call): bool => $this->passesFilters($call)));

        $this->hasMore = count($filteredCalls) > $this->page * $this->perPage;
        $this->calls = array_slice($filteredCalls, ($this->page - 1) * $this->perPage, $this->perPage);
    }

    protected function passesFilters(array $call): bool
    {
        if ($this->filterNumber && ! $this->matchesNumber($call)) {
            return false;
        }

        if ($this->filterAgent && ((string) data_get($call, 'user.id') !== $this->filterAgent)) {
            return false;
        }

        if ($this->filterAnswered === 'answered' && ! data_get($call, 'is_answered')) {
            return false;
        }

        if ($this->filterAnswered === 'missed' && data_get($call, 'is_answered')) {
            return false;
        }

        if ($this->filterHasRecording && empty(data_get($call, 'record'))) {
            return false;
        }

        return true;
    }

    protected function matchesNumber(array $call): bool
    {
        $needle = preg_replace('/\D+/', '', $this->filterNumber);
        if ($needle === '') {
            return true;
        }

        foreach (['contact_number', 'from_number', 'to_number'] as $field) {
            $value = data_get($call, $field);
            if (! empty($value) && str_contains(preg_replace('/\D+/', '', (string) $value), $needle)) {
                return true;
            }
        }

        return false;
    }

    public function setDirection(string $direction): void
    {
        $this->filterDirection = $direction;
        $this->page = 1;
        $this->loadCalls();
    }

    public function updatedFilterNumber(): void
    {
        $this->page = 1;
        $this->loadCalls();
    }

    public function updatedFilterAgent(): void
    {
        $this->page = 1;
        $this->loadCalls();
    }

    public function updatedFilterAnswered(): void
    {
        $this->page = 1;
        $this->loadCalls();
    }

    public function updatedFilterHasRecording(): void
    {
        $this->page = 1;
        $this->loadCalls();
    }

    public function clearFilters(): void
    {
        $this->filterDirection = '';
        $this->filterNumber = '';
        $this->filterAgent = '';
        $this->filterAnswered = '';
        $this->filterHasRecording = false;
        $this->page = 1;
        $this->loadCalls();
    }

    public function nextPage(): void
    {
        if ($this->hasMore) {
            $this->page++;
            $this->loadCalls();
        }
    }

    public function prevPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
            $this->loadCalls();
        }
    }
}
