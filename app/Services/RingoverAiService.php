<?php

namespace App\Services;

use App\Models\Appel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class RingoverAiService
{
    /**
     * Récupère un résumé global IA des appels Ringover récents
     */
    public function getGlobalAiSummary(int $days = 30): ?string
    {
        return Cache::remember(
            'ringover_ai_summary_'.$days.'days',
            now()->addHours(6),
            fn () => $this->generateGlobalAiSummary($days)
        );
    }

    /**
     * Génère un résumé IA global basé sur les appels
     */
    private function generateGlobalAiSummary(int $days = 30): ?string
    {
        $appels = $this->getRecentCallsWithAiData($days);

        if ($appels->isEmpty()) {
            return null;
        }

        $summaries = $appels
            ->pluck('resume_ia')
            ->filter()
            ->values();

        if ($summaries->isEmpty()) {
            return $this->generateFallbackSummary($appels);
        }

        // Combiner les résumés disponibles
        return $this->combineSummaries($summaries);
    }

    /**
     * Génère un résumé de secours si pas de données IA
     */
    private function generateFallbackSummary(Collection $appels): string
    {
        $total = $appels->count();
        $answered = $appels->where('resultat', 'realise')->count();
        $missed = $appels->where('resultat', 'manque')->count();
        $avgDuration = (int) $appels->avg('duree_secondes');
        
        $directions = $appels->groupBy('direction')->map(fn ($calls) => $calls->count());
        $incomingCount = $directions->get('in', 0);
        $outgoingCount = $directions->get('out', 0);

        $rate = $total > 0 ? round(($answered / $total) * 100) : 0;
        $duration = $this->formatDuration($avgDuration);

        return "Résumé des appels Ringover : "
            ."$total appels ($incomingCount entrants, $outgoingCount sortants), "
            ."Taux de réponse : $rate% ($answered répondus, $missed manqués), "
            ."Durée moyenne : $duration.";
    }

    /**
     * Combine plusieurs résumés IA en un seul
     */
    private function combineSummaries(Collection $summaries): string
    {
        if ($summaries->count() === 1) {
            return $summaries->first();
        }

        // Limiter à 5 résumés pour ne pas être trop long
        $limited = $summaries->take(5);
        $combined = implode("\n\n", $limited->toArray());

        // Si plusieurs résumés, ajouter un intro
        if ($limited->count() > 1) {
            return "Synthèse IA des appels récents :\n\n" . $combined;
        }

        return $combined;
    }

    /**
     * Récupère la note IA moyenne des appels récents
     */
    public function getAverageAiNote(int $days = 30): ?float
    {
        return Cache::remember(
            'ringover_ai_note_avg_'.$days.'days',
            now()->addHours(6),
            fn () => $this->calculateAverageAiNote($days)
        );
    }

    /**
     * Calcule la note IA moyenne
     */
    private function calculateAverageAiNote(int $days = 30): ?float
    {
        $appels = $this->getRecentCallsWithAiData($days);

        if ($appels->isEmpty()) {
            return null;
        }

        $notesCount = $appels->whereNotNull('note_ia')->count();

        if ($notesCount === 0) {
            return null;
        }

        return round($appels->avg('note_ia'), 1);
    }

    /**
     * Récupère le sentiment IA global (majoritaire)
     */
    public function getGlobalAiSentiment(int $days = 30): ?string
    {
        return Cache::remember(
            'ringover_ai_sentiment_'.$days.'days',
            now()->addHours(6),
            fn () => $this->calculateGlobalAiSentiment($days)
        );
    }

    /**
     * Calcule le sentiment global des appels
     */
    private function calculateGlobalAiSentiment(int $days = 30): ?string
    {
        $appels = $this->getRecentCallsWithAiData($days);

        if ($appels->isEmpty()) {
            return null;
        }

        $sentiments = $appels
            ->whereNotNull('sentiment_ia')
            ->groupBy('sentiment_ia')
            ->map(fn ($calls) => $calls->count())
            ->all();

        if (empty($sentiments)) {
            return null;
        }

        return array_key_first($sentiments);
    }

    /**
     * Récupère les statistiques IA des appels récents
     */
    public function getAiStatistics(int $days = 30): array
    {
        $cacheKey = 'ringover_ai_stats_'.$days.'days';

        return Cache::remember(
            $cacheKey,
            now()->addHours(6),
            fn () => $this->calculateAiStatistics($days)
        );
    }

    /**
     * Calcule les statistiques IA complètes
     */
    private function calculateAiStatistics(int $days = 30): array
    {
        $appels = $this->getRecentCallsWithAiData($days);

        if ($appels->isEmpty()) {
            return [
                'total_calls' => 0,
                'calls_with_ai' => 0,
                'average_ai_note' => null,
                'ai_coverage' => 0,
                'global_sentiment' => null,
                'sentiment_distribution' => [],
            ];
        }

        $totalCalls = $appels->count();
        $callsWithAi = $appels->whereNotNull('note_ia')->count();
        $avgNote = $this->calculateAverageAiNote($days);
        $globalSentiment = $this->calculateGlobalAiSentiment($days);
        
        $sentiments = $appels
            ->whereNotNull('sentiment_ia')
            ->groupBy('sentiment_ia')
            ->map(fn ($calls) => $calls->count())
            ->all();

        return [
            'total_calls' => $totalCalls,
            'calls_with_ai' => $callsWithAi,
            'average_ai_note' => $avgNote,
            'ai_coverage' => $totalCalls > 0 ? round(($callsWithAi / $totalCalls) * 100, 1) : 0,
            'global_sentiment' => $globalSentiment,
            'sentiment_distribution' => $sentiments,
        ];
    }

    /**
     * Récupère les appels récents avec données IA
     */
    private function getRecentCallsWithAiData(int $days = 30): Collection
    {
        $fromDate = now()->subDays($days);

        return Appel::query()
            ->whereNotNull('ringover_call_id')
            ->where('date_heure', '>=', $fromDate)
            ->orderBy('date_heure', 'desc')
            ->get(['id', 'date_heure', 'direction', 'resultat', 'duree_secondes', 'resume_ia', 'note_ia', 'sentiment_ia', 'ia_generated_at']);
    }

    /**
     * Formate une durée en secondes en format lisible
     */
    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }

        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;

        if ($secs === 0) {
            return "{$minutes}min";
        }

        return "{$minutes}min {$secs}s";
    }

    /**
     * Formate la note IA avec une couleur/badge
     */
    public function formatAiNote(?float $note): array
    {
        if ($note === null) {
            return [
                'value' => 'N/A',
                'color' => 'gray',
                'icon' => 'heroicon-o-minus',
            ];
        }

        return match (true) {
            $note >= 80 => [
                'value' => (string) $note,
                'color' => 'success',
                'icon' => 'heroicon-o-check-circle',
            ],
            $note >= 60 => [
                'value' => (string) $note,
                'color' => 'warning',
                'icon' => 'heroicon-o-exclamation-circle',
            ],
            default => [
                'value' => (string) $note,
                'color' => 'danger',
                'icon' => 'heroicon-o-x-circle',
            ],
        };
    }

    /**
     * Formate le sentiment IA avec label et couleur
     */
    public function formatAiSentiment(?string $sentiment): array
    {
        return match ($sentiment) {
            'positive' => [
                'value' => 'Positif',
                'color' => 'success',
                'icon' => 'heroicon-o-face-smile',
            ],
            'negative' => [
                'value' => 'Négatif',
                'color' => 'danger',
                'icon' => 'heroicon-o-face-frown',
            ],
            'neutral' => [
                'value' => 'Neutre',
                'color' => 'info',
                'icon' => 'heroicon-o-minus-circle',
            ],
            default => [
                'value' => 'Inconnu',
                'color' => 'gray',
                'icon' => 'heroicon-o-question-mark-circle',
            ],
        };
    }

    /**
     * Invalide les caches IA pour forcer un recalcul
     */
    public function flushAiCache(): void
    {
        Cache::forget('ringover_ai_summary_30days');
        Cache::forget('ringover_ai_note_avg_30days');
        Cache::forget('ringover_ai_sentiment_30days');
        Cache::forget('ringover_ai_stats_30days');
    }
}
