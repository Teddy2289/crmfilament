<?php

namespace App\Filament\NsConseil\Widgets;

use App\Services\RingoverAiService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Log;

class RingoverAINoteWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '120s';

    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        try {
            $service = app(RingoverAiService::class);
            $stats = $service->getAiStatistics(30);

            if (!isset($stats['total_calls']) || $stats['total_calls'] === 0) {
                return [
                    Stat::make('Données IA', 'Aucune donnée')
                        ->description('Aucun appel Ringover analysé')
                        ->color('gray')
                        ->icon('heroicon-o-information-circle'),
                ];
            }

            $avgNote = $stats['average_ai_note'];
            $noteFormatted = $service->formatAiNote($avgNote);
            $coverage = $stats['ai_coverage'];
            $sentiment = $stats['global_sentiment'];
            $sentimentFormatted = $service->formatAiSentiment($sentiment);

            return [
                // Note IA moyenne
                Stat::make('Note IA moyenne', $noteFormatted['value'] ?? 'N/A')
                    ->description("Analyse de {$stats['calls_with_ai']} appels")
                    ->color($noteFormatted['color'])
                    ->icon($noteFormatted['icon']),

                // Couverture IA
                Stat::make('Couverture IA', $coverage.'%')
                    ->description("{$stats['calls_with_ai']} sur {$stats['total_calls']} appels")
                    ->color($coverage >= 80 ? 'success' : ($coverage >= 50 ? 'warning' : 'danger'))
                    ->icon('heroicon-o-chart-pie'),

                // Sentiment global
                Stat::make('Sentiment global', $sentimentFormatted['value'] ?? 'Inconnu')
                    ->description($this->describeSentimentDistribution($stats['sentiment_distribution']))
                    ->color($sentimentFormatted['color'])
                    ->icon($sentimentFormatted['icon']),

                // Statistiques appels
                Stat::make('Appels analysés', $stats['total_calls'])
                    ->description("30 derniers jours")
                    ->color('info')
                    ->icon('heroicon-o-phone'),
            ];
        } catch (\Exception $exception) {
            Log::error('RingoverAINoteWidget error', [
                'error' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return [
                Stat::make('Erreur IA', 'Indisponible')
                    ->description('Impossible de récupérer les données IA')
                    ->color('danger')
                    ->icon('heroicon-o-exclamation-triangle'),
            ];
        }
    }

    /**
     * Décrit la distribution des sentiments
     */
    private function describeSentimentDistribution(array $distribution): string
    {
        if (empty($distribution)) {
            return 'Pas de données';
        }

        $parts = [];
        foreach (['positive' => '😊', 'neutral' => '😐', 'negative' => '😞'] as $sentiment => $emoji) {
            if (isset($distribution[$sentiment])) {
                $parts[] = $emoji . ' ' . $distribution[$sentiment];
            }
        }

        return implode(' • ', $parts);
    }
}
