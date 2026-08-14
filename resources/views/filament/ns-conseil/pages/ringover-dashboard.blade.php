<x-filament-panels::page>

    <div class="ringover-dashboard-wrapper">

        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- SECTION 1: Statut de Connexion & Alerte --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        <div class="ringover-status-alert">
            <div @class([ 'ringover-status-icon' , 'success'=> $this->connexionOk, 'danger' => ! $this->connexionOk ])>
                @if ($this->connexionOk)
                    <x-heroicon-o-check-circle class="h-6 w-6" />
                @else
                    <x-heroicon-o-exclamation-triangle class="h-6 w-6" />
                @endif
            </div>
            <div class="ringover-status-content">
                <h3 class="ringover-status-title">
                    {{ $this->connexionOk ? '✓ Connexion active' : '⚠ Connexion impossible' }}
                </h3>
                <p class="ringover-status-message">
                    {{ $this->connexionOk
                        ? 'L\'API Ringover répond correctement et les données sont en cours de synchronisation.'
                        : 'Impossible de se connecter à l\'API Ringover. Vérifiez votre configuration.' }}
                </p>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- SECTION 2: Configuration (Collapsible) --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        <x-filament::section class="ringover-config-section">
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-cog-6-tooth class="h-5 w-5" />
                    <span>Configuration</span>
                </div>
            </x-slot>
            <x-slot name="description">Endpoints et identifiants API Ringover</x-slot>
            <x-slot name="headerEnd">
                <x-filament::icon-button
                    icon="heroicon-o-ellipsis-horizontal"
                    color="gray"
                    size="sm"
                />
            </x-slot>

            <div class="grid gap-4 sm:grid-cols-3">
                {{-- Webhook URL --}}
                <div class="ringover-config-card">
                    <div class="ringover-config-label">
                        <x-heroicon-o-link class="h-4 w-4" />
                        Webhook URL
                    </div>
                    <code class="ringover-config-code">{{ url('/api/ringover/webhook') }}</code>
                    <button
                        type="button"
                        x-data
                        x-on:click="
                            navigator.clipboard.writeText(@js(url('/api/ringover/webhook')));
                            $tooltip('Copié !', { timeout: 1500 })
                        "
                        class="ringover-copy-button">
                        <x-heroicon-o-clipboard-document class="h-4 w-4" />
                    </button>
                </div>

                {{-- Token API --}}
                <div class="ringover-config-card">
                    <div class="ringover-config-label">
                        <x-heroicon-o-key class="h-4 w-4" />
                        Token API
                    </div>
                    <x-filament::badge :color="config('ringover.api_token') ? 'success' : 'danger'" class="ringover-badge">
                        {{ config('ringover.api_token') ? '✓ Configuré' : '✗ Non configuré' }}
                    </x-filament::badge>
                </div>

                {{-- Secret Webhook --}}
                <div class="ringover-config-card">
                    <div class="ringover-config-label">
                        <x-heroicon-o-shield-check class="h-4 w-4" />
                        Secret Webhook
                    </div>
                    <x-filament::badge :color="config('ringover.webhook_secret') ? 'success' : 'danger'" class="ringover-badge">
                        {{ config('ringover.webhook_secret') ? '✓ Configuré' : '✗ Non configuré' }}
                    </x-filament::badge>
                </div>
            </div>
        </x-filament::section>

        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- SECTION 3: Statistiques de Synchronisation --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        <x-filament::section class="ringover-sync-section">
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-arrow-path class="h-5 w-5" />
                    <span>Synchronisation</span>
                </div>
            </x-slot>
            <x-slot name="description">État de synchronisation des appels Ringover</x-slot>

            @php
            $stats = [
                ['label' => 'Appels', 'value' => $this->diagnostic['total_calls'] ?? 0, 'icon' => 'heroicon-o-phone', 'tone' => 'info'],
                ['label' => 'Tags complets', 'value' => $this->diagnostic['complete_tags'] ?? 0, 'icon' => 'heroicon-o-tag', 'tone' => 'success'],
                ['label' => 'Sans département', 'value' => $this->diagnostic['missing_department'] ?? 0, 'icon' => 'heroicon-o-map-pin', 'tone' => 'warn'],
                ['label' => 'Sans statut', 'value' => $this->diagnostic['missing_status'] ?? 0, 'icon' => 'heroicon-o-exclamation-circle', 'tone' => 'warn'],
                ['label' => 'Utilisateurs mappés', 'value' => $this->diagnostic['mapped_users'] ?? 0, 'icon' => 'heroicon-o-user-group', 'tone' => 'success'],
                ['label' => 'Non mappés', 'value' => $this->diagnostic['unmapped_users'] ?? 0, 'icon' => 'heroicon-o-user-minus', 'tone' => 'warn'],
            ];
            @endphp

            <div class="ringover-stats-grid">
                @foreach ($stats as $stat)
                    @php
                    $isFlagged = $stat['tone'] === 'warn' && ($stat['value'] ?? 0) > 0;
                    $colorClass = match($stat['tone']) {
                        'success' => 'success',
                        'danger' => 'danger',
                        'warn' => $isFlagged ? 'danger' : 'success',
                        default => 'info',
                    };
                    @endphp
                    <div class="ringover-stat-box">
                        <div class="ringover-stat-icon" style="color: var(--c-{{ $colorClass }}-500);">
                            <x-dynamic-component :component="$stat['icon']" class="h-5 w-5" />
                        </div>
                        <div class="ringover-stat-content">
                            <div class="ringover-stat-value">{{ $stat['value'] }}</div>
                            <div class="ringover-stat-label">{{ $stat['label'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if (! ($this->diagnostic['schema_ready'] ?? false))
                <div class="ringover-warning-banner">
                    <x-heroicon-o-exclamation-triangle class="h-5 w-5" />
                    <p>
                        <strong>Migration requise :</strong> Lancez <code>php artisan migrate</code> pour activer toutes les métriques.
                    </p>
                </div>
            @endif
        </x-filament::section>

    </div>

    <style>
        .ringover-dashboard-wrapper {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        /* ─── Status Alert ─── */
        .ringover-status-alert {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.5rem;
            border-radius: 1rem;
            border: 2px solid;
            background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(248,250,252,0.95));
        }

        .ringover-status-alert.success {
            border-color: rgb(34 197 94 / 0.3);
            background: linear-gradient(135deg, rgba(240,253,250,0.95), rgba(236,253,245,0.95));
        }

        .ringover-status-alert.danger {
            border-color: rgb(239 68 68 / 0.3);
            background: linear-gradient(135deg, rgba(254,242,242,0.95), rgba(254,237,237,0.95));
        }

        .ringover-status-icon {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            border-radius: 0.75rem;
            background: rgba(255,255,255,0.8);
            border: 2px solid currentColor;
        }

        .ringover-status-icon.success {
            color: rgb(34 197 94);
        }

        .ringover-status-icon.danger {
            color: rgb(239 68 68);
        }

        .ringover-status-content {
            flex: 1;
            min-width: 0;
        }

        .ringover-status-title {
            margin: 0;
            font-size: 1.125rem;
            font-weight: 700;
            color: rgb(15 23 42);
        }

        .ringover-status-message {
            margin: 0.5rem 0 0 0;
            font-size: 0.95rem;
            color: rgb(75 85 99);
            line-height: 1.5;
        }

        /* ─── Config Section ─── */
        .ringover-config-section {
            border-radius: 1rem !important;
            border: 1px solid rgb(226 232 240) !important;
        }

        .ringover-config-section ::-webkit-scrollbar {
            display: none;
        }

        .ringover-config-card {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            padding: 1rem;
            background: rgb(248 250 252 / 0.5);
            border: 1px solid rgb(226 232 240);
            border-radius: 0.75rem;
            transition: all 0.2s ease;
        }

        .ringover-config-card:hover {
            background: rgb(241 245 249);
            border-color: rgb(191 219 254);
        }

        .ringover-config-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgb(100 116 139);
        }

        .ringover-config-code {
            font-size: 0.8rem;
            word-break: break-all;
            color: rgb(51 65 81);
            font-family: 'Monaco', 'Menlo', monospace;
            background: white;
            padding: 0.5rem;
            border-radius: 0.375rem;
            border: 1px solid rgb(226 232 240);
        }

        .ringover-copy-button {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            padding: 0.5rem;
            background: white;
            border: 1px solid rgb(226 232 240);
            border-radius: 0.375rem;
            color: rgb(100 116 139);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .ringover-copy-button:hover {
            background: rgb(59 130 246);
            border-color: rgb(59 130 246);
            color: white;
        }

        .ringover-badge {
            display: inline-block;
            width: fit-content;
            margin-top: 0.5rem;
        }

        /* ─── Sync Section ─── */
        .ringover-sync-section {
            border-radius: 1rem !important;
            border: 1px solid rgb(226 232 240) !important;
        }

        .ringover-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .ringover-stat-box {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: white;
            border: 1px solid rgb(226 232 240);
            border-radius: 0.75rem;
            transition: all 0.2s ease;
        }

        .ringover-stat-box:hover {
            border-color: rgb(191 219 254);
            background: rgb(248 250 252);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
        }

        .ringover-stat-icon {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.75rem;
            background: currentColor;
            opacity: 0.1;
            color: inherit;
        }

        .ringover-stat-content {
            flex: 1;
            min-width: 0;
        }

        .ringover-stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: rgb(15 23 42);
        }

        .ringover-stat-label {
            font-size: 0.875rem;
            color: rgb(100 116 139);
            margin-top: 0.25rem;
        }

        /* ─── Warning Banner ─── */
        .ringover-warning-banner {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem;
            background: linear-gradient(135deg, rgba(254, 243, 199, 0.5), rgba(253, 230, 138, 0.5));
            border-left: 4px solid rgb(202 138 4);
            border-radius: 0.5rem;
            color: rgb(113 63 18);
        }

        .ringover-warning-banner svg {
            flex-shrink: 0;
            width: 1.25rem;
            height: 1.25rem;
        }

        .ringover-warning-banner p {
            margin: 0;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .ringover-warning-banner code {
            background: rgba(0,0,0,0.05);
            padding: 0.2rem 0.4rem;
            border-radius: 0.25rem;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 0.85rem;
        }

        @media (max-width: 768px) {
            .ringover-status-alert {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .ringover-stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }

            .ringover-config-card {
                min-height: 5rem;
            }
        }
    </style>

</x-filament-panels::page>