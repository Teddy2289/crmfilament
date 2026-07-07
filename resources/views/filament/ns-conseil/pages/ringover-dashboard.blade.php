<x-filament-panels::page>

    {{-- ── Bandeau de statut de connexion ──────────────────────────── --}}
    <div @class([ 'flex items-center gap-3 rounded-xl px-4 py-3 ring-1' , 'bg-success-50 ring-success-600/10 dark:bg-success-400/10 dark:ring-success-400/20'=> $this->connexionOk,
        'bg-danger-50 ring-danger-600/10 dark:bg-danger-400/10 dark:ring-danger-400/20' => ! $this->connexionOk,
        ])>
        <span @class([ 'flex h-9 w-9 shrink-0 items-center justify-center rounded-lg' , 'bg-success-600/10 text-success-600 dark:text-success-400'=> $this->connexionOk,
            'bg-danger-600/10 text-danger-600 dark:text-danger-400' => ! $this->connexionOk,
            ])>
            @if ($this->connexionOk)
            <x-heroicon-o-check-circle class="h-5 w-5" />
            @else
            <x-heroicon-o-exclamation-triangle class="h-5 w-5" />
            @endif
        </span>
        <div class="min-w-0 flex-1">
            <p @class([ 'text-sm font-semibold' , 'text-success-700 dark:text-success-400'=> $this->connexionOk,
                'text-danger-700 dark:text-danger-400' => ! $this->connexionOk,
                ])>
                {{ $this->connexionOk ? 'Connexion Ringover active' : 'Connexion Ringover impossible' }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ $this->connexionOk
                    ? 'L\'API Ringover répond correctement.'
                    : 'Vérifiez le token API et le réseau, puis relancez le diagnostic.' }}
            </p>
        </div>
    </div>

    {{-- ── Configuration ────────────────────────────────────────────── --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">Configuration</x-slot>
        <x-slot name="description">Endpoints et identifiants utilisés par l'intégration Ringover.</x-slot>

        <div class="grid gap-4 md:grid-cols-3">

            {{-- Webhook URL --}}
            <div class="rounded-lg bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <div class="flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-link class="h-4 w-4" />
                    Webhook
                </div>
                <div class="mt-2 flex items-center gap-2">
                    <code class="flex-1 truncate text-xs text-gray-700 dark:text-gray-300">
                        {{ url('/api/ringover/webhook') }}
                    </code>
                    <button
                        type="button"
                        x-data
                        x-on:click="
                            navigator.clipboard.writeText(@js(url('/api/ringover/webhook')));
                            $tooltip('Copié !', { timeout: 1500 })
                        "
                        class="shrink-0 rounded-md p-1 text-gray-400 transition hover:bg-gray-200 hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-gray-300">
                        <x-heroicon-o-clipboard-document class="h-4 w-4" />
                    </button>
                </div>
            </div>

            {{-- Token API --}}
            <div class="rounded-lg bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <div class="flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-key class="h-4 w-4" />
                    Token API
                </div>
                <div class="mt-2">
                    <x-filament::badge :color="config('ringover.api_token') ? 'success' : 'danger'">
                        {{ config('ringover.api_token') ? 'Configuré' : 'Non configuré' }}
                    </x-filament::badge>
                </div>
            </div>

            {{-- Secret webhook --}}
            <div class="rounded-lg bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <div class="flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-shield-check class="h-4 w-4" />
                    Secret webhook
                </div>
                <div class="mt-2">
                    <x-filament::badge :color="config('ringover.webhook_secret') ? 'success' : 'danger'">
                        {{ config('ringover.webhook_secret') ? 'Configuré' : 'Non configuré' }}
                    </x-filament::badge>
                </div>
            </div>
        </div>
    </x-filament::section>

    {{-- ── Statistiques ─────────────────────────────────────────────── --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">Statistiques de synchronisation</x-slot>

        @php
        $stats = [
        ['label' => 'Appels', 'value' => $this->diagnostic['total_calls'] ?? 0, 'icon' => 'heroicon-o-phone', 'tone' => 'neutral'],
        ['label' => 'Tags complets', 'value' => $this->diagnostic['complete_tags'] ?? 0, 'icon' => 'heroicon-o-tag', 'tone' => 'good'],
        ['label' => 'Sans département', 'value' => $this->diagnostic['missing_department'] ?? 0, 'icon' => 'heroicon-o-map-pin', 'tone' => 'warn'],
        ['label' => 'Sans statut', 'value' => $this->diagnostic['missing_status'] ?? 0, 'icon' => 'heroicon-o-exclamation-circle', 'tone' => 'warn'],
        ['label' => 'Utilisateurs mappés', 'value' => $this->diagnostic['mapped_users'] ?? 0, 'icon' => 'heroicon-o-user-group', 'tone' => 'good'],
        ['label' => 'Utilisateurs non mappés', 'value' => $this->diagnostic['unmapped_users'] ?? 0, 'icon' => 'heroicon-o-user-minus', 'tone' => 'warn'],
        ];
        @endphp

        <div class="flex flex-wrap rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10">
            @foreach ($stats as $i => $stat)
            @php
            // "warn" ne s'allume que si la valeur est > 0 (sinon tout va bien)
            $isFlagged = $stat['tone'] === 'warn' && ($stat['value'] ?? 0) > 0;
            $isGood = $stat['tone'] === 'good';
            @endphp
            <div @class([ 'flex flex-1 basis-1/2 sm:basis-1/3 items-center gap-3 px-4 py-4 sm:border-l first:border-l-0 border-gray-950/5 dark:border-white/10' ,
                ])>
                <span @class([ 'flex h-9 w-9 shrink-0 items-center justify-center rounded-lg' , 'bg-danger-600/10 text-danger-600 dark:text-danger-400'=> $isFlagged,
                    'bg-success-600/10 text-success-600 dark:text-success-400' => $isGood && ! $isFlagged,
                    'bg-gray-600/10 text-gray-500 dark:text-gray-400' => ! $isFlagged && ! $isGood,
                    ])>
                    <x-dynamic-component :component="$stat['icon']" class="h-5 w-5" />
                </span>
                <div class="min-w-0">
                    <div @class([ 'text-xl font-semibold' , 'text-danger-600 dark:text-danger-400'=> $isFlagged,
                        'text-gray-950 dark:text-white' => ! $isFlagged,
                        ])>
                        {{ $stat['value'] }}
                    </div>
                    <div class="truncate text-xs text-gray-500 dark:text-gray-400">
                        {{ $stat['label'] }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        @if (! ($this->diagnostic['schema_ready'] ?? false))
        <div class="mt-6 flex items-center gap-3 rounded-lg bg-warning-50 px-4 py-3 ring-1 ring-warning-600/10 dark:bg-warning-400/10 dark:ring-warning-400/20">
            <x-heroicon-o-exclamation-triangle class="h-5 w-5 shrink-0 text-warning-600 dark:text-warning-400" />
            <p class="text-sm text-warning-700 dark:text-warning-400">
                Migration Ringover avancée non appliquée. Lancez <code class="font-mono">php artisan migrate</code> pour activer toutes les métriques.
            </p>
        </div>
        @endif
    </x-filament::section>

</x-filament-panels::page>