<x-filament-widgets::widget>
    <div class="ai-summary-shell">
        {{-- Header --}}
        <div class="ai-summary-header">
            <div class="ai-summary-header-content">
                <div class="ai-summary-icon">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5a4 4 0 100-8 4 4 0 000 8z"></path>
                    </svg>
                </div>
                <div class="ai-summary-title-group">
                    <h3 class="ai-summary-kicker">Intelligence Artificielle</h3>
                    <h2 class="ai-summary-title">Analyse et résumé des appels</h2>
                </div>
            </div>
            @if($avgNote)
                <div class="ai-summary-badge">
                    <div class="ai-summary-badge-score">{{ $avgNote }}</div>
                    <div class="ai-summary-badge-label">Note moyenne</div>
                </div>
            @endif
        </div>

        {{-- Sentiment Bar --}}
        @if($sentimentData)
            <div class="ai-summary-sentiment-bar">
                <div class="sentiment-label">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm0-2a6 6 0 100-12 6 6 0 000 12zM9 9a1 1 0 11-2 0 1 1 0 012 0zm0 4a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="sentiment-text">Sentiment global : <strong>{{ $sentimentData['value'] }}</strong></span>
                </div>
                <div class="sentiment-indicator" style="background-color: var(--c-{{ $sentimentData['color'] }}-500);">
                    <svg class="{{ $sentimentData['icon'] }} w-5 h-5" fill="currentColor"></svg>
                </div>
            </div>
        @endif

        {{-- Main Summary Section --}}
        @if($summary)
            <div class="ai-summary-content">
                <div class="ai-summary-box">
                    <div class="ai-summary-box-header">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 6a1 1 0 011-1h12a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zm0 8a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2z"></path>
                        </svg>
                        <span>Résumé IA (30 derniers jours)</span>
                    </div>
                    <div class="ai-summary-box-content">
                        {{ $summary }}
                    </div>
                </div>
            </div>
        @else
            <div class="ai-summary-empty">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="ai-summary-empty-text">Aucune donnée IA disponible</p>
                <p class="ai-summary-empty-subtext">Les données apparaîtront dès que des appels Ringover seront analysés</p>
            </div>
        @endif

        {{-- Statistics Grid --}}
        @if($stats && $stats['total_calls'] > 0)
            <div class="ai-summary-stats-grid">
                <div class="ai-stat-card">
                    <div class="ai-stat-value">{{ $stats['total_calls'] }}</div>
                    <div class="ai-stat-label">Appels analysés</div>
                </div>
                <div class="ai-stat-card">
                    <div class="ai-stat-value">{{ $stats['calls_with_ai'] }}</div>
                    <div class="ai-stat-label">Avec données IA</div>
                </div>
                <div class="ai-stat-card">
                    <div class="ai-stat-value">{{ $stats['ai_coverage'] }}%</div>
                    <div class="ai-stat-label">Couverture IA</div>
                </div>
            </div>
        @endif
    </div>

    <style>
        .ai-summary-shell {
            border: 1px solid rgb(226 232 240);
            border-radius: 1rem;
            background: linear-gradient(180deg, rgba(249,250,251,0.96), rgba(243,244,246,0.96));
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.08);
            overflow: hidden;
        }

        .ai-summary-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            padding: 1.5rem;
            border-bottom: 1px solid rgb(226 232 240);
            background: rgba(249, 250, 251, 0.7);
        }

        .ai-summary-header-content {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
            min-width: 0;
        }

        .ai-summary-icon {
            flex-shrink: 0;
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.75rem;
            background: linear-gradient(135deg, rgb(59 130 246 / 0.1), rgb(99 102 241 / 0.1));
            color: rgb(59 130 246);
        }

        .ai-summary-title-group {
            flex: 1;
            min-width: 0;
        }

        .ai-summary-kicker {
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: rgb(100 116 139);
            margin: 0;
        }

        .ai-summary-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: rgb(15 23 42);
            margin: 0.25rem 0 0 0;
        }

        .ai-summary-badge {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            background: linear-gradient(135deg, rgb(59 130 246 / 0.05), rgb(99 102 241 / 0.05));
            border-radius: 0.75rem;
            border: 1px solid rgb(59 130 246 / 0.2);
            flex-shrink: 0;
        }

        .ai-summary-badge-score {
            font-size: 1.875rem;
            font-weight: 800;
            color: rgb(59 130 246);
        }

        .ai-summary-badge-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: rgb(100 116 139);
            letter-spacing: 0.03em;
        }

        .ai-summary-sentiment-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgb(226 232 240);
            background: rgba(255, 255, 255, 0.5);
        }

        .sentiment-label {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: rgb(51 65 81);
            font-size: 0.95rem;
            flex: 1;
            min-width: 0;
        }

        .sentiment-label svg {
            flex-shrink: 0;
            color: rgb(100 116 139);
        }

        .sentiment-text {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sentiment-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 9999px;
            flex-shrink: 0;
        }

        .ai-summary-content {
            padding: 1.5rem;
        }

        .ai-summary-box {
            border-radius: 0.75rem;
            background: rgb(255 255 255 / 0.7);
            border: 1px solid rgb(226 232 240);
            overflow: hidden;
        }

        .ai-summary-box-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            background: linear-gradient(to bottom, rgb(248 250 252), rgb(241 245 249));
            border-bottom: 1px solid rgb(226 232 240);
            color: rgb(51 65 81);
            font-weight: 600;
            font-size: 0.95rem;
        }

        .ai-summary-box-header svg {
            flex-shrink: 0;
            color: rgb(59 130 246);
        }

        .ai-summary-box-content {
            padding: 1.25rem;
            color: rgb(55 65 81);
            line-height: 1.6;
            font-size: 0.95rem;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .ai-summary-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 1.5rem;
            text-align: center;
        }

        .ai-summary-empty svg {
            color: rgb(203 213 225);
            margin-bottom: 1rem;
        }

        .ai-summary-empty-text {
            font-weight: 600;
            color: rgb(100 116 139);
            margin: 0 0 0.5rem 0;
        }

        .ai-summary-empty-subtext {
            font-size: 0.875rem;
            color: rgb(148 163 184);
            margin: 0;
        }

        .ai-summary-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            padding: 1.5rem;
            border-top: 1px solid rgb(226 232 240);
            background: rgba(249, 250, 251, 0.5);
        }

        .ai-stat-card {
            background: white;
            border: 1px solid rgb(226 232 240);
            border-radius: 0.75rem;
            padding: 1rem;
            text-align: center;
        }

        .ai-stat-value {
            font-size: 1.875rem;
            font-weight: 700;
            color: rgb(59 130 246);
            margin-bottom: 0.5rem;
        }

        .ai-stat-label {
            font-size: 0.85rem;
            color: rgb(100 116 139);
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .ai-summary-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .ai-summary-badge {
                align-self: flex-end;
            }

            .ai-summary-stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            }
        }
    </style>
</x-filament-widgets::widget>
