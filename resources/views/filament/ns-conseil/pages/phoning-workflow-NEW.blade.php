@php
$rappelCodes = $this->getRappelStatusCodes();
$maxTentatives = app(\App\Services\Crm\CrmSettingsService::class)->get('prospection.max_standard_attempts', 3);
$tentativesActuelles = $this->getTentativesAppel();
@endphp

<x-filament-panels::page>
    @push('styles')
    {{-- TODO task 11.1: Extract these ~1870 lines of CSS to resources/css/phoning-workflow.css --}}
    @include('filament.ns-conseil.pages.partials.phoning-workflow-styles')
    @endpush

    @push('scripts')
    <script>
        // Fonction d'appel unifiée qui s'appuie uniquement sur l'instance globale du Panel Provider
        function appelerAvecRingover(phoneNumber) {
            if (!phoneNumber) return;

            const cleanedPhone = phoneNumber.replace(/[^0-9+]/g, '');
            if (!cleanedPhone) {
                return;
            }

            Livewire.dispatch('ringover-call', { phone: cleanedPhone });

            if (window.ringoverPhone && typeof window.ringoverPhone.dial === 'function') {
                window.ringoverPhone.show();
                window.ringoverPhone.dial(cleanedPhone);
            } else {
                // Fallback si le SDK n'est pas encore prêt
                window.location.href = 'tel:' + cleanedPhone;
            }
        }

        function rechercherAppelEntrant(numero) {
            if (!numero) return;
            const normalized = String(numero).replace(/[^0-9+]/g, '');
            if (!normalized) return;
            Livewire.dispatch('search-incoming-call', { phone: normalized });
        }

        window.appelerAvecRingover = appelerAvecRingover;
        window.rechercherAppelEntrant = rechercherAppelEntrant;
    </script>
    @endpush

    @php
    $info = $this->getContactInfo();
    $tel = $info['telephone'] ?? null;
    $teleprospecteurs = $this->getTeleprospecteurs();
    $nbEnFile = $this->getContactsRestantsCount();
    $progress = $this->progress;
    $statutsGroupes = $this->getStatutsPhoningGroupes();
    $options = $this->getStatutsPhoning();
    $callHistory = $this->getCallHistory();
    $statutCls = 'pw-badge-' . strtolower($info['statut_code'] ?? $info['statut'] ?? 'ac');
    if (!isset($info['statut']) && !isset($info['statut_code'])) {
        $statutCls = 'pw-badge-gray';
    }
    $statutLabel = $info['statut_label'] ?? ($info['statut'] ?? 'AC');
    $statutBadgeStyle = $info['statut_badge_style'] ?? null;
    $pipelinePreview = $this->getPipelineTransitionPreview();
    @endphp

    <div class="pw-wrap">

        {{-- Search bar --}}
        <div style="display:flex; align-items:center; gap:1rem; padding:0.5rem 1rem; background:rgb(249 250 251); border-bottom:1px solid rgb(229 231 235); flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:0.5rem; flex:1; min-width:200px; position:relative;">
                <svg style="width:1.125rem;height:1.125rem;color:rgb(156 163 175);flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text"
                    wire:model.live.debounce.300ms="searchQuery"
                    wire:keydown.escape="clearSearch"
                    placeholder="Rechercher un contact (nom, téléphone, SIRET...)"
                    class="pw-field-input"
                    style="flex:1; padding:0.375rem 0.75rem; font-size:0.8125rem; border:1px solid rgb(209 213 219); border-radius:0.5rem; outline:none;">
                @if ($searchQuery)
                <button wire:click="clearSearch"
                    style="position:absolute; right:0.5rem; background:none; border:none; color:rgb(156 163 175); cursor:pointer; font-size:1rem;">
                    ✕
                </button>
                @endif
            </div>
        </div>

        {{-- Search results --}}
        @include('filament.ns-conseil.pages.partials.phoning-search-results')

        @if ($currentContact)

            {{-- Contact panel (card + barre contact + progression) --}}
            <x-phoning::contact-panel
                :contact="$info"
                :contact-type="$contactType"
                :queue-count="$nbEnFile"
                :progress="$progress"
                :is-supervisor-mode="$isSupervisorMode"
            />

            <div class="pw-body">
                <div class="pw-left">

                    {{-- Dossier prospect (onglets Contact, Interlocuteurs, Notes, Journal, RDV) --}}
                    @include('filament.ns-conseil.pages.partials.phoning-dossier-prospect')

                    {{-- Status panel (onglets cas + chips statuts + rappel + commentaires + actions) --}}
                    <x-phoning::status-panel
                        :statuts="$options"
                        :selected-statut="$statut_resultat"
                        :commentaires="$commentaires"
                        :rappel-date="$rappel_date"
                        :rappel-heure="$rappel_heure"
                        :statuts-groupes="$statutsGroupes"
                        :rappel-codes="$rappelCodes"
                        :pipeline-preview="$pipelinePreview"
                    />
                </div>

                <div class="pw-right">
                    {{-- Ringover widget (NR box + iframe + appel entrant) --}}
                    <x-phoning::ringover-widget
                        :phone="$tel"
                        :nr-count="$tentativesActuelles"
                        :max-nr="$maxTentatives"
                        :call-id="$ringoverCallId"
                    />
                </div>
            </div>

        @else
            {{-- File vide --}}
            <div style="display:flex; align-items:center; justify-content:center; min-height:60vh;">
                <div style="text-align:center;">
                    <div style="width:5rem; height:5rem; border-radius:9999px; background:rgb(243 244 246); display:flex; align-items:center; justify-content:center; margin:0 auto 1rem auto;">
                        <svg style="width:2.25rem;height:2.25rem;color:rgb(156 163 175);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                    </div>
                    <h3 style="font-size:1.125rem; font-weight:700; margin:0 0 0.5rem;">File vide</h3>
                    <p style="color:rgb(107 114 128); margin:0 0 1.5rem;">
                        Tous les contacts ont été traités ou aucun prospect n'est assigné à ce téléprospecteur.
                    </p>
                    <div style="display:flex; gap:0.75rem; justify-content:center; flex-wrap:wrap;">
                        <button wire:click="refreshQueue"
                            style="padding:0.5rem 1.5rem; background:rgb(37 99 235); color:white; border-radius:0.625rem; font-weight:600; border:none; cursor:pointer;">
                            Rafraîchir
                        </button>
                        @if ($isSupervisorMode)
                        <a href="{{ route('filament.ns-conseil.pages.phoning-back-office') }}"
                            style="display:inline-flex; align-items:center; gap:0.4375rem; padding:0.5rem 1.5rem; background:rgb(249 250 251); color:rgb(55 65 81); border-radius:0.625rem; font-weight:600; border:1px solid rgb(229 231 235); text-decoration:none;">
                            <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            Gérer la file
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Email preview modal --}}
        @if ($showEmailPreview)
            <x-phoning::email-preview
                :email-preview-subject="$emailPreviewSubject"
                :email-preview-body="$emailPreviewBody"
                :email-preview-recipient="$emailPreviewRecipient"
                :email-preview-original-subject="$emailPreviewOriginalSubject ?? $emailPreviewSubject"
                :email-preview-original-body="$emailPreviewOriginalBody ?? $emailPreviewBody"
            />
        @endif

    </div>

    @include('filament.ns-conseil.pages.partials.phoning-scripts')

</x-filament-panels::page>
