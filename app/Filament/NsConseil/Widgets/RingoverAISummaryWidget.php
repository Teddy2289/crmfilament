<?php

namespace App\Filament\NsConseil\Widgets;

use App\Services\RingoverAiService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Log;

class RingoverAISummaryWidget extends Widget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '120s';

    protected static string $view = 'filament.ns-conseil.widgets.ringover-ai-summary';

    protected static bool $isLazy = true;

    public ?string $summary = null;

    public ?string $sentiment = null;

    public ?float $avgNote = null;

    public array $sentimentData = [];

    public array $stats = [];

    public function mount(): void
    {
        try {
            $service = app(RingoverAiService::class);
            
            $this->summary = $service->getGlobalAiSummary(30);
            $this->avgNote = $service->getAverageAiNote(30);
            $this->sentiment = $service->getGlobalAiSentiment(30);
            $this->stats = $service->getAiStatistics(30);

            // Formatter le sentiment
            if ($this->sentiment) {
                $this->sentimentData = $service->formatAiSentiment($this->sentiment);
            }
        } catch (\Exception $exception) {
            Log::error('RingoverAISummaryWidget error', [
                'error' => $exception->getMessage(),
                'exception' => $exception,
            ]);
        }
    }
}
