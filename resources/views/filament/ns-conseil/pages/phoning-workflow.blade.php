{{-- resources/views/filament/ns-conseil/pages/phoning-workflow.blade.php --}}
<x-filament-panels::page>
    @if($currentContact)
        @php
            $info = $this->getContactInfo();
            $contactName = trim(($info['prenom'] ?? '') . ' ' . ($info['nom'] ?? ''));
            $phoneNumber = $info['telephone'] ?? '0240333090';
            $department = $info['adresse'] ?? 'Adresse non définie';
            $initials = strtoupper(substr($info['prenom'] ?? 'C', 0, 1) . substr($info['nom'] ?? '?', 0, 1));


            $optionClasses = [
                'gray'   => [
                    'active'   => 'fi-phoning-option-gray-active',
                    'inactive' => 'fi-phoning-option-inactive',
                ],
                'green'  => [
                    'active'   => 'fi-phoning-option-green-active',
                    'inactive' => 'fi-phoning-option-inactive',
                ],
                'orange' => [
                    'active'   => 'fi-phoning-option-orange-active',
                    'inactive' => 'fi-phoning-option-inactive',
                ],
                'red'    => [
                    'active'   => 'fi-phoning-option-red-active',
                    'inactive' => 'fi-phoning-option-inactive',
                ],
            ];
        @endphp

        {{--
            STYLE BLOCK : On définit ici toutes les classes custom nécessaires
            en utilisant les variables CSS de Filament (--fi-*) pour la cohérence
            avec le thème clair/sombre automatiquement.

            POURQUOI CE FICHIER STYLE ?
            Filament compile son propre CSS via un processus séparé de votre app.css.
            Les classes Tailwind dans vos blades custom ne sont scannées QUE par votre
            pipeline Vite/app.css — mais Filament injecte son propre stylesheet en premier.
            En injectant un <style> dans le blade, on garantit que les styles sont toujours
            présents peu importe quel CSS est chargé.
        --}}
        @push('styles')
        <style>
            /* ─── Layout général ─── */
            .fi-phoning-wrap {
                padding: 1.5rem;
            }

            /* ─── Cards ─── */
            .fi-phoning-card {
                background-color: var(--fi-bg-card, #ffffff);
                border: 1px solid var(--fi-color-gray-200, #e5e7eb);
                border-radius: 1rem;
                box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.05);
                overflow: hidden;
            }

            .dark .fi-phoning-card {
                background-color: var(--fi-bg-card, rgb(17 24 39));
                border-color: var(--fi-color-gray-800, rgb(31 41 55));
            }

            /* ─── En-tête de card ─── */
            .fi-phoning-card-header {
                padding: 1.25rem;
                border-bottom: 1px solid rgb(243 244 246);
            }

            .dark .fi-phoning-card-header {
                border-bottom-color: rgb(31 41 55);
            }

            /* ─── Badge avatar initiales ─── */
            .fi-phoning-avatar {
                width: 3.5rem;
                height: 3.5rem;
                border-radius: 0.75rem;
                background: linear-gradient(135deg, rgb(59 130 246), rgb(147 51 234));
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-weight: 700;
                font-size: 1.25rem;
                flex-shrink: 0;
            }

            /* ─── Barre de progression ─── */
            .fi-phoning-progress-bar {
                width: 8rem;
                height: 0.5rem;
                background-color: rgb(229 231 235);
                border-radius: 9999px;
                overflow: hidden;
            }

            .dark .fi-phoning-progress-bar {
                background-color: rgb(55 65 81);
            }

            .fi-phoning-progress-fill {
                height: 100%;
                background: linear-gradient(to right, rgb(34 197 94), rgb(59 130 246));
                border-radius: 9999px;
                transition: width 0.5s ease;
            }

            /* ─── Onglets du script ─── */
            .fi-phoning-tab {
                padding: 0.75rem 1.25rem;
                font-size: 0.875rem;
                font-weight: 600;
                transition: all 0.15s;
                white-space: nowrap;
                border-bottom: 2px solid transparent;
                color: rgb(107 114 128);
                background: none;
                border-top: none;
                border-left: none;
                border-right: none;
                cursor: pointer;
            }

            .dark .fi-phoning-tab {
                color: rgb(156 163 175);
            }

            .fi-phoning-tab:hover {
                color: rgb(55 65 81);
            }

            .dark .fi-phoning-tab:hover {
                color: rgb(209 213 219);
            }

            .fi-phoning-tab-active {
                color: rgb(37 99 235);
                border-bottom-color: rgb(37 99 235);
            }

            .dark .fi-phoning-tab-active {
                color: rgb(96 165 250);
                border-bottom-color: rgb(96 165 250);
            }

            /* ─── Boîtes de script colorées ─── */
            .fi-phoning-script-box {
                border-radius: 0.75rem;
                padding: 1.25rem;
            }

            .fi-phoning-script-blue {
                background: linear-gradient(to right, rgb(239 246 255), rgb(238 242 255));
            }

            .dark .fi-phoning-script-blue {
                background: linear-gradient(to right, rgb(23 37 84 / 0.3), rgb(30 27 75 / 0.3));
            }

            .fi-phoning-script-green {
                background: linear-gradient(to right, rgb(236 253 245), rgb(240 253 250));
            }

            .dark .fi-phoning-script-green {
                background: linear-gradient(to right, rgb(6 78 59 / 0.3), rgb(4 47 46 / 0.3));
            }

            .fi-phoning-script-purple {
                background: linear-gradient(to right, rgb(250 245 255), rgb(253 242 248));
            }

            .dark .fi-phoning-script-purple {
                background: linear-gradient(to right, rgb(59 7 100 / 0.3), rgb(74 4 78 / 0.3));
            }

            .fi-phoning-script-emerald {
                background: linear-gradient(to right, rgb(240 253 244), rgb(236 253 245));
            }

            .dark .fi-phoning-script-emerald {
                background: linear-gradient(to right, rgb(5 46 22 / 0.3), rgb(6 78 59 / 0.3));
            }

            /* ─── Conseil (tip) amber ─── */
            .fi-phoning-tip {
                background-color: rgb(255 251 235);
                border-left: 4px solid rgb(251 191 36);
                border-radius: 0.75rem;
                padding: 1rem;
            }

            .dark .fi-phoning-tip {
                background-color: rgb(120 53 15 / 0.2);
            }

            /* ─── Objection box ─── */
            .fi-phoning-objection {
                background-color: rgb(254 242 242);
                border-left: 4px solid rgb(248 113 113);
                border-radius: 0.75rem;
                padding: 1rem;
            }

            .dark .fi-phoning-objection {
                background-color: rgb(127 29 29 / 0.2);
            }

            /* ─── Options "Issue de l'appel" — Classes statiques complètes ─── */
            .fi-phoning-option-inactive {
                border: 2px solid rgb(229 231 235);
                background-color: transparent;
            }

            .dark .fi-phoning-option-inactive {
                border-color: rgb(55 65 81);
            }

            /* Gray */
            .fi-phoning-option-gray-active {
                border: 2px solid rgb(107 114 128);
                background-color: rgb(249 250 251);
            }

            .dark .fi-phoning-option-gray-active {
                background-color: rgb(17 24 39 / 0.3);
            }

            /* Green */
            .fi-phoning-option-green-active {
                border: 2px solid rgb(34 197 94);
                background-color: rgb(240 253 244);
            }

            .dark .fi-phoning-option-green-active {
                background-color: rgb(5 46 22 / 0.3);
            }

            /* Orange */
            .fi-phoning-option-orange-active {
                border: 2px solid rgb(249 115 22);
                background-color: rgb(255 247 237);
            }

            .dark .fi-phoning-option-orange-active {
                background-color: rgb(124 45 18 / 0.3);
            }

            /* Red */
            .fi-phoning-option-red-active {
                border: 2px solid rgb(239 68 68);
                background-color: rgb(254 242 242);
            }

            .dark .fi-phoning-option-red-active {
                background-color: rgb(127 29 29 / 0.3);
            }

            /* ─── Bouton appeler ─── */
            .fi-phoning-btn-call {
                padding: 0.5rem 1rem;
                background: linear-gradient(to right, rgb(34 197 94), rgb(16 185 129));
                color: white;
                border-radius: 0.75rem;
                font-weight: 700;
                font-size: 0.875rem;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                border: none;
                cursor: pointer;
                box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
                transition: all 0.15s;
            }

            .fi-phoning-btn-call:hover {
                background: linear-gradient(to right, rgb(22 163 74), rgb(5 150 105));
            }

            /* ─── Bouton principal (enregistrer) ─── */
            .fi-phoning-btn-primary {
                flex: 1;
                padding: 0.75rem;
                background: linear-gradient(to right, rgb(34 197 94), rgb(16 185 129));
                color: white;
                border-radius: 0.75rem;
                font-weight: 700;
                font-size: 0.875rem;
                border: none;
                cursor: pointer;
                box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
                transition: all 0.15s;
            }

            .fi-phoning-btn-primary:hover {
                background: linear-gradient(to right, rgb(22 163 74), rgb(5 150 105));
            }

            /* ─── Bouton secondaire (passer) ─── */
            .fi-phoning-btn-secondary {
                padding: 0.75rem 1.25rem;
                background-color: rgb(229 231 235);
                color: rgb(55 65 81);
                border-radius: 0.75rem;
                font-weight: 700;
                font-size: 0.875rem;
                border: none;
                cursor: pointer;
                transition: all 0.15s;
            }

            .fi-phoning-btn-secondary:hover {
                background-color: rgb(209 213 219);
            }

            .dark .fi-phoning-btn-secondary {
                background-color: rgb(55 65 81);
                color: rgb(209 213 219);
            }

            .dark .fi-phoning-btn-secondary:hover {
                background-color: rgb(75 85 99);
            }

            /* ─── Bouton tentative ─── */
            .fi-phoning-btn-outline {
                width: 100%;
                padding: 0.5rem;
                border: 1px solid rgb(59 130 246);
                color: rgb(37 99 235);
                border-radius: 0.75rem;
                font-size: 0.875rem;
                font-weight: 600;
                background: transparent;
                cursor: pointer;
                transition: background 0.15s;
            }

            .fi-phoning-btn-outline:hover {
                background-color: rgb(239 246 255);
            }

            .dark .fi-phoning-btn-outline {
                color: rgb(96 165 250);
            }

            .dark .fi-phoning-btn-outline:hover {
                background-color: rgb(23 37 84 / 0.3);
            }

            /* ─── Textarea ─── */
            .fi-phoning-textarea {
                width: 100%;
                padding: 0.5rem 0.75rem;
                border: 1px solid rgb(229 231 235);
                border-radius: 0.75rem;
                font-size: 0.875rem;
                background-color: rgb(249 250 251);
                color: inherit;
                resize: vertical;
                outline: none;
                transition: box-shadow 0.15s;
            }

            .fi-phoning-textarea:focus {
                box-shadow: 0 0 0 2px rgb(59 130 246 / 0.5);
                border-color: rgb(59 130 246);
            }

            .dark .fi-phoning-textarea {
                border-color: rgb(55 65 81);
                background-color: rgb(31 41 55 / 0.5);
            }

            /* ─── Input datetime ─── */
            .fi-phoning-input {
                width: 100%;
                padding: 0.5rem 0.75rem;
                border: 1px solid rgb(209 213 219);
                border-radius: 0.75rem;
                background-color: white;
                color: inherit;
                outline: none;
            }

            .dark .fi-phoning-input {
                border-color: rgb(55 65 81);
                background-color: rgb(31 41 55);
            }

            /* ─── Stat mini box ─── */
            .fi-phoning-stat-box {
                text-align: center;
                padding: 0.75rem;
                background-color: rgb(249 250 251);
                border-radius: 0.75rem;
            }

            .dark .fi-phoning-stat-box {
                background-color: rgb(31 41 55 / 0.5);
            }

            /* ─── Stat KPI (argumentaire) ─── */
            .fi-phoning-kpi-box {
                background-color: white;
                border-radius: 0.5rem;
                padding: 0.75rem;
                text-align: center;
            }

            .dark .fi-phoning-kpi-box {
                background-color: rgb(31 41 55);
            }

            /* ─── Icon badge ─── */
            .fi-phoning-icon-badge {
                width: 2rem;
                height: 2rem;
                border-radius: 0.5rem;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }

            .fi-phoning-icon-badge-orange {
                background-color: rgb(255 237 213);
            }

            .dark .fi-phoning-icon-badge-orange {
                background-color: rgb(124 45 18 / 0.5);
            }

            .fi-phoning-icon-badge-red {
                background-color: rgb(254 226 226);
            }

            .dark .fi-phoning-icon-badge-red {
                background-color: rgb(127 29 29 / 0.5);
            }
        </style>
        @endpush

        <div class="fi-phoning-wrap">
            {{-- EN-TÊTE --}}
            <div style="margin-bottom:1.5rem;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem;">
                    <div>
                        <h1 style="font-size:1.5rem; font-weight:700; margin:0;">File d'appel</h1>
                        <p style="font-size:0.875rem; color:rgb(107 114 128); margin:0;">Campagne de phoning NS Conseil</p>
                    </div>
                    <div style="display:flex; align-items:center; gap:0.75rem;">
                        <div style="text-align:right;">
                            <div style="font-size:0.75rem; color:rgb(107 114 128); text-transform:uppercase; font-weight:600;">Progression</div>
                            <div style="font-size:1.5rem; font-weight:700;">
                                {{ $completed }}
                                <span style="font-size:0.875rem; font-weight:400; color:rgb(107 114 128);">/ {{ $total }}</span>
                            </div>
                        </div>
                        <div class="fi-phoning-progress-bar">
                            <div class="fi-phoning-progress-fill" style="width: {{ $progress }}%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr; gap:1.5rem;">
                {{-- Sur écran large : 2/3 + 1/3 via style inline pour éviter lg:grid-cols-3 --}}
                <div style="display:grid; grid-template-columns:1fr; gap:1.5rem;" class="fi-phoning-grid-main">
                    <style>
                        @media (min-width: 1024px) {
                            .fi-phoning-grid-main { grid-template-columns: 2fr 1fr !important; }
                        }
                    </style>

                    {{-- COLONNE GAUCHE --}}
                    <div style="display:flex; flex-direction:column; gap:1.5rem;">

                        {{-- CARTE CONTACT --}}
                        <div class="fi-phoning-card">
                            <div style="padding:1.5rem;">
                                <div style="display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:1.5rem;">
                                    <div>
                                        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.5rem;">
                                            <span style="font-size:0.75rem; font-weight:600; color:rgb(239 68 68); background:rgb(254 242 242); padding:0.25rem 0.5rem; border-radius:0.25rem;">
                                                Appels manqués
                                            </span>
                                            <span style="font-size:0.75rem; color:rgb(107 114 128);">{{ $completed }} appels aujourd'hui</span>
                                        </div>
                                        <div style="display:flex; align-items:center; gap:0.75rem;">
                                            <div class="fi-phoning-avatar">{{ $initials }}</div>
                                            <div>
                                                <h2 style="font-size:1.25rem; font-weight:700; margin:0;">{{ $contactName ?: 'John Deere SAS' }}</h2>
                                                <p style="font-size:0.875rem; color:rgb(107 114 128); margin:0;">{{ Str::limit($department, 50) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="text-align:right;">
                                        <div style="font-size:1.5rem; font-family:monospace; font-weight:700;">{{ $phoneNumber }}</div>
                                        <div style="display:flex; align-items:center; gap:0.25rem; justify-content:flex-end; margin-top:0.25rem;">
                                            <span style="font-size:0.75rem; color:rgb(107 114 128);">Durée</span>
                                            <span style="font-size:0.75rem; font-family:monospace; font-weight:600;">00:00</span>
                                        </div>
                                    </div>
                                </div>

                                <div style="display:flex; align-items:center; gap:1rem; padding-top:1rem; border-top:1px solid rgb(243 244 246);">
                                    <div style="display:flex; align-items:center; gap:0.5rem;">
                                        <div class="fi-phoning-icon-badge fi-phoning-icon-badge-orange">
                                            <svg style="width:1rem;height:1rem;color:rgb(234 88 12);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <div style="font-size:0.75rem; color:rgb(107 114 128);">Tentatives</div>
                                            <div style="font-weight:700;">3</div>
                                        </div>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:0.5rem;">
                                        <div class="fi-phoning-icon-badge fi-phoning-icon-badge-red">
                                            <svg style="width:1rem;height:1rem;color:rgb(220 38 38);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <div style="font-size:0.75rem; color:rgb(107 114 128);">Sans réponse</div>
                                            <div style="font-weight:700;">3</div>
                                        </div>
                                    </div>
                                    <div style="margin-left:auto;">
                                        <button wire:click="callNow" class="fi-phoning-btn-call">
                                            <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                            </svg>
                                            Appeler
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- SCRIPT D'APPEL --}}
                        <div class="fi-phoning-card">
                            <div style="border-bottom:1px solid rgb(243 244 246);">
                                <div style="display:flex; overflow-x:auto;">
                                    @php
                                        $tabs = [
                                            ['id' => 'accroche',    'label' => 'Accroche'],
                                            ['id' => 'decouverte',  'label' => 'Découverte'],
                                            ['id' => 'argumentaire','label' => 'Argumentaire'],
                                            ['id' => 'objections',  'label' => 'Objections'],
                                            ['id' => 'closing',     'label' => 'Closing'],
                                        ];
                                    @endphp
                                    @foreach($tabs as $tab)
                                        <button
                                            wire:click="$set('activeScriptTab', '{{ $tab['id'] }}')"
                                            class="fi-phoning-tab {{ ($activeScriptTab ?? 'accroche') === $tab['id'] ? 'fi-phoning-tab-active' : '' }}">
                                            {{ $tab['label'] }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div style="padding:1.5rem;">

                                {{-- Accroche --}}
                                @if(($activeScriptTab ?? 'accroche') === 'accroche')
                                    <div style="display:flex; flex-direction:column; gap:1rem;">
                                        <div class="fi-phoning-script-box fi-phoning-script-blue">
                                            <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.75rem;">
                                                <svg style="width:1.25rem;height:1.25rem;color:rgb(37 99 235);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                                                </svg>
                                                <h3 style="font-weight:700; margin:0;">Script d'accroche</h3>
                                            </div>
                                            <p style="line-height:1.75; margin:0;">
                                                "Bonjour <strong>{{ $contactName ?: 'Madame/Monsieur' }}</strong>,
                                                je suis <strong>[VOTRE NOM]</strong> de <strong>NS CONSEIL</strong>.
                                                Est-ce que vous disposez de quelques minutes pour un échange ?
                                                Notre équipe accompagne les professionnels comme vous à optimiser leur développement commercial."
                                            </p>
                                        </div>
                                        <div class="fi-phoning-tip">
                                            <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.25rem;">
                                                <svg style="width:1rem;height:1rem;color:rgb(217 119 6);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                                </svg>
                                                <span style="font-size:0.75rem; font-weight:600; color:rgb(180 83 9);">💡 Conseil</span>
                                            </div>
                                            <p style="font-size:0.875rem; color:rgb(180 83 9); margin:0;">Sourire au téléphone, parler clairement, noter le prénom dès l'annonce</p>
                                        </div>
                                    </div>
                                @endif

                                {{-- Découverte --}}
                                @if(($activeScriptTab ?? 'accroche') === 'decouverte')
                                    <div class="fi-phoning-script-box fi-phoning-script-green">
                                        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.75rem;">
                                            <svg style="width:1.25rem;height:1.25rem;color:rgb(5 150 105);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <h3 style="font-weight:700; margin:0;">Questions de découverte</h3>
                                        </div>
                                        <div style="display:flex; flex-direction:column; gap:0.75rem;">
                                            <p style="margin:0;">• "Quels sont vos principaux objectifs pour cette année ?"</p>
                                            <p style="margin:0;">• "Quels sont les défis que vous rencontrez actuellement dans votre activité ?"</p>
                                            <p style="margin:0;">• "Comment gérez-vous actuellement votre prospection commerciale ?"</p>
                                            <p style="margin:0;">• "Qu'est-ce qui vous motive à chercher des solutions d'accompagnement ?"</p>
                                        </div>
                                    </div>
                                @endif

                                {{-- Argumentaire --}}
                                @if(($activeScriptTab ?? 'accroche') === 'argumentaire')
                                    <div class="fi-phoning-script-box fi-phoning-script-purple">
                                        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.75rem;">
                                            <svg style="width:1.25rem;height:1.25rem;color:rgb(147 51 234);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                            </svg>
                                            <h3 style="font-weight:700; margin:0;">Proposition de valeur</h3>
                                        </div>
                                        <p style="margin:0 0 1rem 0;">
                                            "NS CONSEIL c'est <strong>+40% de chiffre d'affaires</strong> en moyenne pour nos clients,
                                            une équipe dédiée de 15 experts, et une approche 100% personnalisée."
                                        </p>
                                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                                            <div class="fi-phoning-kpi-box">
                                                <div style="font-size:1.5rem; font-weight:700; color:rgb(147 51 234);">94%</div>
                                                <div style="font-size:0.75rem; color:rgb(107 114 128);">de clients satisfaits</div>
                                            </div>
                                            <div class="fi-phoning-kpi-box">
                                                <div style="font-size:1.5rem; font-weight:700; color:rgb(147 51 234);">15j</div>
                                                <div style="font-size:0.75rem; color:rgb(107 114 128);">de déploiement</div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{-- Objections --}}
                                @if(($activeScriptTab ?? 'accroche') === 'objections')
                                    <div style="display:flex; flex-direction:column; gap:0.75rem;">
                                        <div class="fi-phoning-objection">
                                            <p style="font-weight:600; font-size:0.875rem; color:rgb(185 28 28); margin:0 0 0.25rem 0;">"Je n'ai pas le temps"</p>
                                            <p style="font-size:0.875rem; margin:0;">→ "Je comprends, je vous propose 5 minutes chrono pour voir si notre offre peut vous apporter de la valeur."</p>
                                        </div>
                                        <div class="fi-phoning-objection">
                                            <p style="font-weight:600; font-size:0.875rem; color:rgb(185 28 28); margin:0 0 0.25rem 0;">"Je ne suis pas intéressé"</p>
                                            <p style="font-size:0.875rem; margin:0;">→ "Je comprends tout à fait. Puis-je vous envoyer une brochure pour que vous ayez toutes les informations si jamais votre situation change ?"</p>
                                        </div>
                                        <div class="fi-phoning-objection">
                                            <p style="font-weight:600; font-size:0.875rem; color:rgb(185 28 28); margin:0 0 0.25rem 0;">"C'est trop cher"</p>
                                            <p style="font-size:0.875rem; margin:0;">→ "Notre offre est modulable à partir de 499€/mois, et nos clients constatent un ROI dès le premier mois."</p>
                                        </div>
                                    </div>
                                @endif

                                {{-- Closing --}}
                                @if(($activeScriptTab ?? 'accroche') === 'closing')
                                    <div class="fi-phoning-script-box fi-phoning-script-emerald">
                                        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.75rem;">
                                            <svg style="width:1.25rem;height:1.25rem;color:rgb(16 185 129);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            <h3 style="font-weight:700; margin:0;">Prochaines étapes</h3>
                                        </div>
                                        <p style="margin:0 0 1rem 0;">
                                            "Seriez-vous disponible <strong>[jour de la semaine]</strong> pour un
                                            <strong>rendez-vous découverte de 15 minutes</strong> ?
                                            Nous pourrions échanger sur vos besoins et voir comment NS CONSEIL peut vous aider."
                                        </p>
                                        <div>
                                            <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.5rem;">Date de rappel proposée</label>
                                            <input type="datetime-local" wire:model="rappel_date" class="fi-phoning-input">
                                        </div>
                                    </div>
                                @endif

                            </div>
                        </div>
                    </div>{{-- /COLONNE GAUCHE --}}

                    {{-- COLONNE DROITE --}}
                    <div style="display:flex; flex-direction:column; gap:1.5rem;">

                        {{-- Compte rendu --}}
                        <div class="fi-phoning-card">
                            <div class="fi-phoning-card-header">
                                <div style="display:flex; align-items:center; gap:0.5rem;">
                                    <svg style="width:1.25rem;height:1.25rem;color:rgb(107 114 128);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <h3 style="font-weight:700; margin:0;">Compte rendu d'appel</h3>
                                </div>
                                <p style="font-size:0.75rem; color:rgb(107 114 128); margin:0.25rem 0 0 0;">Décrivez l'échange : interlocuteur joint, objections, prochaine étape...</p>
                            </div>
                            <div style="padding:1.25rem;">
                                <textarea
                                    wire:model="commentaires"
                                    rows="4"
                                    placeholder="Interlocuteur joint ? Objections rencontrées ? Prochaine étape ? Date de rappel ?"
                                    class="fi-phoning-textarea"></textarea>
                            </div>
                        </div>

                        {{-- Tentatives --}}
                        <div class="fi-phoning-card">
                            <div class="fi-phoning-card-header">
                                <div style="display:flex; align-items:center; justify-content:space-between;">
                                    <div style="display:flex; align-items:center; gap:0.5rem;">
                                        <svg style="width:1.25rem;height:1.25rem;color:rgb(107 114 128);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        <h3 style="font-weight:700; margin:0;">Tentatives d'appel</h3>
                                    </div>
                                    <span style="font-size:0.75rem; color:rgb(107 114 128);">Règle des 3 créneaux</span>
                                </div>
                            </div>
                            <div style="padding:1.25rem;">
                                <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:0.75rem; margin-bottom:1rem;">
                                    <div class="fi-phoning-stat-box">
                                        <div style="font-size:1.5rem; font-weight:700;">0</div>
                                        <div style="font-size:0.75rem; color:rgb(107 114 128);">Matin</div>
                                    </div>
                                    <div class="fi-phoning-stat-box">
                                        <div style="font-size:1.5rem; font-weight:700;">3</div>
                                        <div style="font-size:0.75rem; color:rgb(107 114 128);">Midi</div>
                                    </div>
                                    <div class="fi-phoning-stat-box">
                                        <div style="font-size:1.5rem; font-weight:700;">0</div>
                                        <div style="font-size:0.75rem; color:rgb(107 114 128);">Après-midi</div>
                                    </div>
                                </div>
                                <button class="fi-phoning-btn-outline">+ Enregistrer une tentative</button>
                            </div>
                        </div>

                        {{-- Issue de l'appel --}}
                        <div class="fi-phoning-card">
                            <div class="fi-phoning-card-header">
                                <h3 style="font-weight:700; margin:0;">Issue de l'appel</h3>
                            </div>
                            <div style="padding:1.25rem; display:flex; flex-direction:column; gap:0.75rem;">
                                @foreach([
                                    ['value' => 'std_nr',    'label' => 'STD-NR',    'desc' => 'Sans réponse',      'color' => 'gray'],
                                    ['value' => 'std_joint', 'label' => 'STD-Joint', 'desc' => 'Joint',             'color' => 'green'],
                                    ['value' => 'cse_nr',   'label' => 'CSE-NR',    'desc' => 'CSE sans réponse',  'color' => 'orange'],
                                    ['value' => 'ko',        'label' => 'KO',         'desc' => 'Refs / KO',         'color' => 'red'],
                                ] as $option)
                                    @php
                                        $isActive = $statut_resultat === $option['value'];
                                        $stateKey = $isActive ? 'active' : 'inactive';
                                        $labelClass = $optionClasses[$option['color']][$stateKey];
                                    @endphp
                                    <label style="display:flex; align-items:center; justify-content:space-between; padding:0.75rem; border-radius:0.75rem; cursor:pointer; transition:all 0.15s;"
                                           class="{{ $labelClass }}">
                                        <div style="display:flex; align-items:center; gap:0.75rem;">
                                            <input type="radio" wire:model="statut_resultat" value="{{ $option['value'] }}" style="width:1rem;height:1rem;">
                                            <div>
                                                <p style="font-weight:600; font-size:0.875rem; margin:0;">{{ $option['label'] }}</p>
                                                <p style="font-size:0.75rem; color:rgb(107 114 128); margin:0;">{{ $option['desc'] }}</p>
                                            </div>
                                        </div>
                                        <svg style="width:1rem;height:1rem;color:rgb(156 163 175);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Boutons d'action --}}
                        <div style="display:flex; gap:0.75rem;">
                            <button wire:click="submitResult" class="fi-phoning-btn-primary">
                                Enregistrer &amp; suivant
                            </button>
                            <button wire:click="skipCall" class="fi-phoning-btn-secondary">
                                Passer
                            </button>
                        </div>

                    </div>{{-- /COLONNE DROITE --}}
                </div>
            </div>
        </div>

    @else
        {{-- ÉTAT VIDE --}}
        <div style="display:flex; align-items:center; justify-content:center; min-height:60vh;">
            <div style="text-align:center;">
                <div style="width:6rem; height:6rem; border-radius:9999px; background:rgb(243 244 246); display:flex; align-items:center; justify-content:center; margin:0 auto 1rem auto;">
                    <svg style="width:2.5rem;height:2.5rem;color:rgb(156 163 175);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <h3 style="font-size:1.25rem; font-weight:700; margin:0 0 0.5rem 0;">Aucun contact à appeler</h3>
                <p style="color:rgb(107 114 128); margin:0 0 1.5rem 0;">Ajoutez des contacts depuis leur interface respective</p>
                <button wire:click="$refresh"
                        style="padding:0.5rem 1.5rem; background:rgb(37 99 235); color:white; border-radius:0.75rem; font-weight:600; border:none; cursor:pointer;">
                    Rafraîchir
                </button>
            </div>
        </div>
    @endif
</x-filament-panels::page>
