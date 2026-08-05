<?php

namespace App\Filament\NsConseil\Widgets;

use App\Services\RingoverService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

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

    public ?string $filterFrom = null;

    public ?string $filterTo = null;

    public string $filterText = '';

    public string $filterTextType = '';

    public bool $filterHasRecording = false;

    public int $resultCount = 0;

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

        if ($this->filterFrom) {
            try {
                $filters['from'] = Carbon::parse($this->filterFrom)->startOfDay()->timestamp;
            } catch (\Throwable) {
                // ignore invalid date
            }
        }

        if ($this->filterTo) {
            try {
                $filters['to'] = Carbon::parse($this->filterTo)->endOfDay()->timestamp;
            } catch (\Throwable) {
                // ignore invalid date
            }
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

        $this->resultCount = count($filteredCalls);
        $this->hasMore = $this->resultCount > $this->page * $this->perPage;
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

        if ($this->filterHasRecording && ! $this->hasRecording($call)) {
            return false;
        }

        if ($this->filterText) {
            $needle = mb_strtolower(trim($this->filterText));
            $haystack = '';

            if ($this->filterTextType === 'note') {
                $haystack = mb_strtolower($this->extractCallNote($call) ?? '');
            } elseif ($this->filterTextType === 'transcription') {
                $haystack = mb_strtolower($this->extractCallTranscription($call) ?? '');
            } else {
                $haystack = mb_strtolower(implode(' ', array_filter([
                    $this->extractCallNote($call),
                    $this->extractCallSummary($call),
                    $this->extractCallTranscription($call),
                ])));
            }

            if ($needle === '' || $haystack === '' || str_contains($haystack, $needle) === false) {
                return false;
            }
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

    protected function extractCallNote(array $call): ?string
    {
        return $this->stringValue(
            data_get($call, 'comments.0.content')
            ?? data_get($call, 'comments.0.text')
            ?? data_get($call, 'comment')
        );
    }

    protected function extractCallSummary(array $call): ?string
    {
        return $this->stringValue(
            data_get($call, 'summary')
            ?? data_get($call, 'title')
        );
    }

    protected function extractCallTranscription(array $call): ?string
    {
        return $this->stringValue(
            data_get($call, 'transcription')
            ?? data_get($call, 'record.transcription')
            ?? data_get($call, 'recording.transcription')
            ?? data_get($call, 'call.transcription')
        );
    }

    protected function hasRecording(array $call): bool
    {
        return ! empty(data_get($call, 'record'))
            || ! empty(data_get($call, 'recording'))
            || ! empty(data_get($call, 'recording_url'));
    }

    protected function stringValue(mixed $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (is_array($value)) {
            return trim(implode(' ', array_filter(array_map('strval', $value))));
        }

        return trim((string) $value);
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

    public function updatedFilterTextType(): void
    {
        $this->page = 1;
        $this->loadCalls();
    }

    public function updatedFilterFrom(): void
    {
        $this->page = 1;
        $this->loadCalls();
    }

    public function updatedFilterTo(): void
    {
        $this->page = 1;
        $this->loadCalls();
    }

    public function updatedFilterText(): void
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
        $this->filterFrom = null;
        $this->filterTo = null;
        $this->filterText = '';
        $this->filterTextType = '';
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
