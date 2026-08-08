@php
$rappelCodes = $this->getRappelStatusCodes();
$maxTentatives = app(\App\Services\Crm\CrmSettingsService::class)->get('prospection.max_standard_attempts', 3);
$tentativesActuelles = $this->getTentativesAppel();
@endphp

<x-filament-panels::page>
    <div wire:poll.keep-alive.300000ms="renewCurrentContactLock" class="pw-wrap">
    @vite('resources/css/phoning-workflow.css')

    {{-- ── Initialisation Ringover incrusté dans la colonne droite ────────────
         Le widget global (ringover-dialer.blade.php) détecte l'URL et ne se
         monte PAS sur cette page. Ici on initialise le SDK en mode 'relative'
         dans le conteneur dédié #ringover-embed-phoning.

         Stratégie :
         1. On détruit le widget flottant global s'il existe déjà (ex: navigation
            Livewire depuis une autre page).
         2. On remplace ringoverPhone par le widget incrusté dans le conteneur.
    --}}
    <script>
        function _destroyGlobalRingover() {
            if (window.ringoverPhone && !window.ringoverPhone.__placeholder) {
                if (typeof window.ringoverPhone.destroy === 'function') {
                    try { window.ringoverPhone.destroy(); } catch (e) {}
                }
                // Masquer tous les éléments Ringover flottants résiduels
                document.querySelectorAll('[id^="ringover"]').forEach(function(el) {
                    if (el.id !== 'ringover-embed-phoning') {
                        el.style.display = 'none';
                    }
                });
                window.ringoverPhone = null;
            }
        }

        function _initRingoverInContainer() {
            _destroyGlobalRingover();

            var container = document.getElementById('ringover-embed-phoning');
            if (!container) { return; }

            if (typeof window.RingoverSDK !== 'function') {
                setTimeout(_initRingoverInContainer, 150);
                return;
            }

            // Ne pas ré-instancier si déjà généré et fonctionnel
            if (window.ringoverPhone && !window.ringoverPhone.__placeholder && typeof window.ringoverPhone.dial === 'function') {
                return;
            }

            try {
                // Vider le conteneur au cas où une ancienne iframe résiduelle s'y trouve
                container.innerHTML = '';

                window.ringoverPhone = new window.RingoverSDK({
                    container: 'ringover-embed-phoning',
                    type: 'relative',
                    size: 'auto',
                    animation: true,
                });
                window.ringoverPhone.generate();
            } catch (err) {
                console.warn('Erreur initialisation Ringover SDK:', err);
            }

            window.appelerAvecRingover = function (numero) {
                if (!numero) { return; }
                if (window.ringoverPhone && typeof window.ringoverPhone.dial === 'function') {
                    window.ringoverPhone.show();
                    window.ringoverPhone.dial(numero);
                } else {
                    window.location.href = 'tel:' + numero;
                }
            };
        }

        // Lancement immédiat (si SDK déjà chargé) ou au DOMContentLoaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', _initRingoverInContainer);
        } else {
            _initRingoverInContainer();
        }

        // Marquer le body pour que le CSS cache les éléments Ringover flottants
        document.body.classList.add('pw-phoning-active');

        // Après navigation Livewire vers cette page
        document.addEventListener('livewire:navigated', function () {
            if (!window.location.pathname.includes('phoning-workflow')) {
                document.body.classList.remove('pw-phoning-active');
                return;
            }
            document.body.classList.add('pw-phoning-active');
            window.ringoverPhone = null;
            setTimeout(_initRingoverInContainer, 200);
        });
    </script>

    @push('scripts')
    <script>
        let timerInterval = null;
        let timerSeconds = 0;

        function startTimer() {
            if (timerInterval) {
                clearInterval(timerInterval);
                timerSeconds = 0;
            }
            timerInterval = setInterval(() => {
                timerSeconds++;
                const m = String(Math.floor(timerSeconds / 60)).padStart(2, '0');
                const s = String(timerSeconds % 60).padStart(2, '0');
                const el = document.querySelector('.pw-timer-value');
                if (el) el.textContent = m + ' : ' + s;
            }, 1000);
        }

        const pwRappelCodes = @json($rappelCodes);

        function phoningEmailPreview(initial) {
            return {
                activeTab: 'edit',
                subject: initial.subject || '',
                recipient: initial.recipient || '',
                body: initial.body || '',
                originalSubject: initial.originalSubject || initial.subject || '',
                originalBody: initial.originalBody || initial.body || '',
                isDirty: false,

                init() {
                    this.$nextTick(() => this.syncEditorFromState());
                },

                get plainTextLength() {
                    const tmp = document.createElement('div');
                    tmp.innerHTML = this.body || '';
                    return (tmp.textContent || tmp.innerText || '').trim().length;
                },

                markDirty() {
                    this.isDirty = this.subject !== this.originalSubject
                        || this.body !== this.originalBody
                        || this.recipient !== initial.recipient;
                },

                syncEditorFromState() {
                    const editor = this.$refs.editor;
                    if (editor && editor.innerHTML !== this.body) {
                        editor.innerHTML = this.body || '';
                    }
                    this.markDirty();
                },

                syncBodyFromEditor() {
                    const editor = this.$refs.editor;
                    if (!editor) { return; }
                    this.body = editor.innerHTML;
                    this.markDirty();
                },

                format(command, value = null) {
                    const editor = this.$refs.editor;
                    if (!editor) { return; }
                    editor.focus();
                    if (command === 'createLink') {
                        const url = window.prompt('URL du lien', 'https://');
                        if (!url) { return; }
                        document.execCommand(command, false, url);
                    } else {
                        document.execCommand(command, false, value);
                    }
                    this.syncBodyFromEditor();
                },

                resetTemplate() {
                    if (this.isDirty && !window.confirm("Réinitialiser le message au modèle d'origine ?")) {
                        return;
                    }
                    this.subject = this.originalSubject;
                    this.body = this.originalBody;
                    this.recipient = initial.recipient || '';
                    this.syncEditorFromState();
                },

                switchTab(tab) {
                    this.activeTab = tab;
                    if (tab === 'edit') {
                        this.$nextTick(() => this.syncEditorFromState());
                    }
                },

                async confirmSend() {
                    this.syncBodyFromEditor();
                    if (!this.subject.trim() || this.plainTextLength === 0) {
                        window.alert('Le sujet et le corps du message sont obligatoires.');
                        return;
                    }
                    await this.$wire.syncEmailPreviewContent(this.subject, this.body, this.recipient);
                    await this.$wire.confirmEmailPreview();
                },

                handleEditorInput() { this.syncBodyFromEditor(); },

                handleEditorPaste(event) {
                    event.preventDefault();
                    const text = (event.clipboardData || window.clipboardData).getData('text/html')
                        || (event.clipboardData || window.clipboardData).getData('text/plain');
                    if (text) { document.execCommand('insertHTML', false, text); }
                    this.syncBodyFromEditor();
                },

                handleEditorKeydown(event) {
                    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'b') {
                        event.preventDefault(); this.format('bold');
                    }
                    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'i') {
                        event.preventDefault(); this.format('italic');
                    }
                    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'u') {
                        event.preventDefault(); this.format('underline');
                    }
                },
            };
        }

        document.addEventListener('livewire:init', () => { switchInfoTab('contact'); });
        document.addEventListener('livewire:navigated', () => { switchInfoTab('contact'); });
        document.addEventListener('DOMContentLoaded', () => { switchInfoTab('contact'); });
        setTimeout(() => { switchInfoTab('contact'); }, 0);

        function toggleRappel(val) {
            const box = document.getElementById('pw-rappel-box');
            if (box) { box.classList.toggle('visible', pwRappelCodes.includes(val)); }
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                Livewire.dispatch('notify', { message: 'Copié !' });
            });
        }

        function switchInfoTab(tab) {
            document.querySelectorAll('.pw-info-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.pw-info-panel[data-tab]').forEach(p => p.style.display = 'none');
            document.querySelector(`.pw-info-tab[data-tab="${tab}"]`).classList.add('active');
            document.querySelector(`.pw-info-panel[data-tab="${tab}"]`).style.display = 'block';
        }

        function switchCaseTab(tab) {
            document.querySelectorAll('.pw-case-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.pw-case-panel').forEach(p => p.classList.remove('active'));
            const btn = document.querySelector(`.pw-case-tab[data-case-tab="${tab}"]`);
            const panel = document.querySelector(`.pw-case-panel[data-case-panel="${tab}"]`);
            if (btn) btn.classList.add('active');
            if (panel) panel.classList.add('active');
        }

        document.addEventListener('livewire:navigated', () => { switchInfoTab('contact'); });
        document.addEventListener('DOMContentLoaded', () => { switchInfoTab('contact'); });

        function appelerAvecRingover(phoneNumber) {
            if (!phoneNumber) return;
            const cleanedPhone = phoneNumber.replace(/[^0-9+]/g, '');
            if (!cleanedPhone) return;
            Livewire.dispatch('ringover-call', { phone: cleanedPhone });
            if (window.ringoverPhone && typeof window.ringoverPhone.dial === 'function') {
                window.ringoverPhone.show();
                window.ringoverPhone.dial(cleanedPhone);
            } else {
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
    $info         = $this->getContactInfo();
    $callHistory  = $this->getCallHistory();
    $noteLines    = [];
    if (!empty($info['notes'])) {
        foreach (explode("\n", $info['notes']) as $line) {
            $line = trim($line);
            if (!$line) continue;
            if (preg_match('/^\[(\d{2}\/\d{2}\/\d{4}[^\]]*)\]\s*(.+)$/', $line, $m)) {
                $noteLines[] = ['date' => $m[1], 'text' => $m[2]];
            } else {
                $noteLines[] = ['date' => null, 'text' => $line];
            }
        }
    }
    @endphp

    {{-- ══ BARRE DE RECHERCHE ══ --}}
    <div class="pw-search-bar">
        <input wire:model.live.debounce.300ms="searchQuery"
               type="search"
               placeholder="Rechercher un contact (nom, téléphone, SIRET...)"
               class="pw-search-input"
               autocomplete="off">
        @if ($showSearchResults && count($searchResults) > 0)
        <div class="pw-search-dropdown">
            @foreach ($searchResults as $result)
            <button type="button"
                    wire:click="selectSearchResult({{ $result['id'] }}, '{{ $result['type'] }}')"
                    class="pw-search-result">
                <span class="pw-search-result-nom">{{ $result['nom'] }}</span>
                <span class="pw-search-result-meta">{{ $result['type_entite'] ?? $result['type'] }} · {{ $result['telephone'] ?? '—' }}</span>
            </button>
            @endforeach
        </div>
        @endif
    </div>

    {{-- ══ CONTENU PRINCIPAL ══ --}}
    @if ($currentContact)

        {{-- Panneau identité contact (Toute la largeur du haut) --}}
        <x-phoning::contact-panel
            :contact="$info"
            :contact-type="$contactType"
            :queue-count="count($contactQueue)"
            :progress="$progress"
            :is-supervisor-mode="$isSupervisorMode"
        />

        <div class="pw-layout">

            {{-- ── Colonne gauche : dossier prospect + résultat appel ──────── --}}
            <div class="pw-col-main">

                {{-- Dossier prospect : onglets Contact/Interlocuteurs/Journal/RDV --}}
                <x-phoning::dossier-prospect
                    :info="$info"
                    :call-history="$callHistory"
                    :note-lines="$noteLines"
                    :contact-type="$contactType"
                    :incoming-call-phone="$incomingCallPhone"
                    :incoming-call-matches="$incomingCallMatches"
                />

                {{-- Panneau statut / résultat --}}
                <x-phoning::status-panel
                    :statuts="$this->getStatutsPhoning()"
                    :selected-statut="$statut_resultat"
                    :commentaires="$commentaires"
                    :rappel-date="$rappel_date"
                    :rappel-heure="$rappel_heure"
                    :statuts-groupes="$this->getStatutsPhoningGroupes()"
                    :rappel-codes="$rappelCodes"
                    :pipeline-preview="$this->getPipelineTransitionPreview()"
                />

            </div>{{-- /pw-col-main --}}

            {{-- ── Colonne droite : Ringover + appel entrant ────────────── --}}
            <div class="pw-col-side">

                <x-phoning::ringover-widget
                    :phone="$info['telephone'] ?? null"
                    :nr-count="$tentativesActuelles"
                    :max-nr="$maxTentatives"
                    :call-id="$ringoverCallId"
                    :incoming-call-phone="$incomingCallPhone"
                    :incoming-call-matches="$incomingCallMatches"
                />

            </div>{{-- /pw-col-side --}}

        </div>{{-- /pw-layout --}}

        {{-- ── Aperçu email (modal, conditionnel) ──────────────────────── --}}
        @if ($showEmailPreview)
        <x-phoning::email-preview
            :email-preview-subject="$emailPreviewSubject"
            :email-preview-body="$emailPreviewBody"
            :email-preview-recipient="$emailPreviewRecipient"
            :email-preview-original-subject="$emailPreviewOriginalSubject"
            :email-preview-original-body="$emailPreviewOriginalBody"
        />
        @endif

    @else

        {{-- ══ ÉTAT VIDE : file épuisée ══ --}}
        <div class="pw-empty-state">
            <div class="pw-empty-icon">✅</div>
            <div class="pw-empty-title">File d'appels vide</div>
            <div class="pw-empty-sub">Tous les contacts ont été traités pour cette session.</div>
            <button wire:click="refreshQueue" class="pw-btn-secondary pw-empty-action">
                Rafraîchir la file
            </button>
        </div>

    @endif

    </div>{{-- /wire:poll.keep-alive.300000ms="renewCurrentContactLock" --}}
</x-filament-panels::page>

