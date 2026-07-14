@php
    $progress = $this->progress;
    $status = $progress['status'] ?? null;
@endphp

<div
    @if($this->shouldPoll())
        wire:poll.1200ms="refreshProgress"
    @endif
>
    @if($this->importId && $status)
        <div class="ns-import-progress ns-import-progress--{{ $status }}">
            <div class="ns-import-progress-icon">
                @if($status === 'done')
                    <x-heroicon-o-check-circle />
                @elseif($status === 'failed')
                    <x-heroicon-o-exclamation-circle />
                @else
                    <x-heroicon-o-arrow-path class="ns-import-progress-icon-spin" />
                @endif
            </div>

            <div class="ns-import-progress-body">
                <div class="ns-import-progress-header">
                    <span class="ns-import-progress-title">
                        @if($status === 'queued')
                            Import en attente…
                        @elseif($status === 'processing')
                            Import en cours{{ ($progress['sheet'] ?? null) ? " — {$progress['sheet']}" : '' }}
                        @elseif($status === 'done')
                            Import terminé
                        @else
                            Échec de l'import
                        @endif
                    </span>

                    @if($status === 'processing' && ($progress['total'] ?? 0) > 0)
                        <span class="ns-import-progress-percent">{{ $this->percent() }}%</span>
                    @endif
                </div>

                @if($status === 'processing')
                    <div class="ns-import-progress-track">
                        <div
                            class="ns-import-progress-fill{{ ($progress['total'] ?? 0) ? '' : ' ns-import-progress-fill--indeterminate' }}"
                            style="width: {{ ($progress['total'] ?? 0) ? $this->percent() : 30 }}%"
                        ></div>
                    </div>
                    <div class="ns-import-progress-meta">
                        @if(($progress['total'] ?? 0) > 0)
                            {{ $progress['processed'] ?? 0 }} / {{ $progress['total'] }} lignes traitées
                        @else
                            Analyse du fichier…
                        @endif
                    </div>
                @endif

                @if($status === 'done')
                    <div class="ns-import-progress-meta">
                        Créés : {{ $progress['created'] ?? 0 }} · Mis à jour : {{ $progress['updated'] ?? 0 }} · Ignorés : {{ $progress['skipped'] ?? 0 }}
                        @if(($progress['errors_count'] ?? 0) > 0)
                            · {{ $progress['errors_count'] }} ligne(s) en erreur
                        @endif
                    </div>
                @endif

                @if($status === 'failed')
                    <div class="ns-import-progress-meta">
                        {{ $progress['message'] ?? 'Une erreur inattendue est survenue.' }}
                    </div>
                @endif
            </div>

            <button
                type="button"
                wire:click="dismiss"
                class="ns-import-progress-dismiss"
                aria-label="Fermer"
            >
                <x-heroicon-o-x-mark />
            </button>
        </div>
    @endif
</div>
