@props([
    'info',
    'callHistory',
    'noteLines',
    'contactType',
    'incomingCallPhone'   => null,
    'incomingCallMatches' => [],
])

@php
    $statutCls        = 'pw-badge-' . strtolower($info['statut_code'] ?? $info['statut'] ?? 'ac');
    $statutLabel      = $info['statut_label'] ?? ($info['statut'] ?? 'AC');
    $statutBadgeStyle = $info['statut_badge_style'] ?? null;
    $editActionRoute  = null;
    if (!empty($info['id'])) {
        match ($info['type'] ?? '') {
            'prospect'   => $editActionRoute = \App\Filament\NsConseil\Resources\ProspectResource::getUrl('edit', ['record' => $info['id']]),
            'partenaire' => $editActionRoute = \App\Filament\NsConseil\Resources\PartenaireResource::getUrl('edit', ['record' => $info['id']]),
            'client'     => $editActionRoute = \App\Filament\NsConseil\Resources\ClientResource::getUrl('edit', ['record' => $info['id']]),
            default      => null,
        };
    }
    $existingContacts = [];
    if (!empty($info['interlocuteur_nom']))          $existingContacts[] = ['label' => 'Principal',      'value' => $info['interlocuteur_nom']];
    if (!empty($info['interlocuteur_fonction']))      $existingContacts[] = ['label' => 'Fonction',       'value' => $info['interlocuteur_fonction']];
    if (!empty($info['interlocuteur_telephone']))     $existingContacts[] = ['label' => 'Téléphone',      'value' => $info['interlocuteur_telephone']];
    if (!empty($info['interlocuteur_email']))         $existingContacts[] = ['label' => 'Email',          'value' => $info['interlocuteur_email']];
    if (!empty($info['interlocuteur_add_nom']))       $existingContacts[] = ['label' => 'Suppl.',         'value' => $info['interlocuteur_add_nom']];
    if (!empty($info['interlocuteur_add_telephone'])) $existingContacts[] = ['label' => 'Tél. suppl.',    'value' => $info['interlocuteur_add_telephone']];
    if (!empty($info['nom_interlocuteur_standard']))  $existingContacts[] = ['label' => 'Standard',       'value' => $info['nom_interlocuteur_standard']];
    if (!empty($info['creneaux_permanence_cse']))     $existingContacts[] = ['label' => 'Créneaux',       'value' => $info['creneaux_permanence_cse']];
    if (!empty($info['email_general_standard']))      $existingContacts[] = ['label' => 'Email std.',     'value' => $info['email_general_standard']];
@endphp

<div class="pw-infos pw-card">

    {{-- En-tête --}}
    <div class="pw-infos-header">
        <span class="pw-infos-title">
            <span class="pw-infos-title-icon">
                <svg class="pw-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-19.5 0v6a2.25 2.25 0 002.25 2.25h15a2.25 2.25 0 002.25-2.25v-6m-19.5 0h19.5" /></svg>
            </span>
            Dossier Prospect
        </span>
        @if ($editActionRoute)
        <a href="{{ $editActionRoute }}" target="_blank" class="pw-info-edit-link">
            Accéder à la fiche →
        </a>
        @endif
    </div>

    {{-- Onglets --}}
    <div class="pw-info-tabs">
        <button class="pw-info-tab active" data-tab="contact" onclick="switchInfoTab('contact')">
            <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
            Contact
        </button>
        @if (($info['type'] ?? '') === 'prospect')
        <button class="pw-info-tab" data-tab="interlocuteur" onclick="switchInfoTab('interlocuteur')">
            <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            Interlocuteurs
        </button>
        @endif
        @if (($info['type'] ?? '') === 'prospect' && !empty($info['notes']))
        <button class="pw-info-tab" data-tab="notes" onclick="switchInfoTab('notes')">
            <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
            Notes
        </button>
        @endif
        <button class="pw-info-tab" data-tab="journal" onclick="switchInfoTab('journal')">
            <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>
            Journal
            @if (count($callHistory) > 0)
            <span style="display:inline-flex;align-items:center;justify-content:center;min-width:1.25rem;height:1.25rem;padding:0 0.25rem;border-radius:9999px;background:rgb(99 102 241);color:white;font-size:0.65rem;font-weight:700;">{{ count($callHistory) }}</span>
            @endif
        </button>
        @if (($info['type'] ?? '') !== 'client')
        <button class="pw-info-tab" data-tab="rdv" onclick="switchInfoTab('rdv')">
            <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
            RDV
        </button>
        @endif
    </div>

    {{-- Panneau Contact --}}
    <div class="pw-info-panel" data-tab="contact">
        <div class="pw-info-grid">
            <div class="pw-field-full">
                <div class="pw-field-label">Téléphone principal</div>
                <div class="pw-contact-row">
                    <span class="pw-contact-prefix">🇫🇷 +33</span>
                    <input type="text" value="{{ $info['telephone'] ?? '' }}" readonly class="pw-field-input pw-copy-input" onclick="this.select();document.execCommand('copy');" title="Cliquer pour copier">
                    @if (!empty($info['telephone']))
                    <a href="tel:{{ $info['telephone'] }}" onclick="event.preventDefault();appelerAvecRingover('{{ $info['telephone'] }}')" class="pw-contact-call-button">Appeler</a>
                    @endif
                </div>
            </div>
            @if (!empty($info['telephone_alt']))
            <div class="pw-field-full">
                <div class="pw-field-label">Téléphone secondaire</div>
                <input type="text" value="{{ $info['telephone_alt'] }}" readonly class="pw-field-input" onclick="this.select();document.execCommand('copy');" title="Cliquer pour copier">
            </div>
            @endif
            <div>
                <div class="pw-field-label">Email</div>
                @if (!empty($info['email']))<a href="mailto:{{ $info['email'] }}" style="font-size:0.8125rem;color:rgb(37 99 235);">{{ $info['email'] }}</a>
                @else<span style="font-size:0.8125rem;color:rgb(156 163 175);">—</span>@endif
            </div>
            <div>
                <div class="pw-field-label">Localisation</div>
                <div class="pw-field-value">{{ collect([$info['ville'] ?? null,$info['code_postal'] ?? null,$info['departement'] ?? null])->filter()->implode(' · ') ?: '—' }}</div>
            </div>
            @if (!empty($info['adresse_complete']))
            <div class="pw-field-full">
                <div class="pw-field-label">Adresse complète</div>
                <div class="pw-field-value pw-field-value-small">{{ $info['adresse_complete'] }}</div>
            </div>
            @endif
        </div>
        @if (($info['type'] ?? '') !== 'client')
        <div class="pw-section-divider">
            <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
            Suivi commercial
        </div>
        <div class="pw-info-grid">
            <div>
                <div class="pw-field-label">Statut pipeline actuel</div>
                <span class="pw-badge {{ $statutCls }}" style="font-size:0.8125rem;padding:0.25rem 0.625rem;{{ $statutBadgeStyle ?? '' }}">{{ $statutLabel }}</span>
            </div>
            <div><div class="pw-field-label">Téléprospecteur</div><div class="pw-field-value">{{ $info['teleprospecteur'] ?? '—' }}</div></div>
            <div><div class="pw-field-label">Commercial</div><div class="pw-field-value">{{ $info['commercial'] ?? '—' }}</div></div>
            <div><div class="pw-field-label">1er contact</div><div class="pw-field-value">{{ $info['date_premier_contact'] ?? 'Jamais contacté' }}</div></div>
            @if (!empty($info['rappel_planifie_at']))
            <div class="pw-field-full">
                <div class="pw-field-label">Rappel planifié</div>
                <div class="pw-field-value pw-field-value-inline {{ $info['rappel_en_retard'] ? 'pw-rappel-late' : '' }}">
                    <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ $info['rappel_planifie_at'] }}@if ($info['rappel_en_retard']) — EN RETARD @endif
                </div>
            </div>
            @endif
            @if (!empty($info['statut_description']))
            <div class="pw-field-full">
                <div class="pw-field-label">Description statut</div>
                <div class="pw-field-value" style="font-size:0.8125rem;color:rgb(107 114 128);">{{ $info['statut_description'] }}</div>
            </div>
            @endif
        </div>
        @endif
    </div>

    {{-- Panneau Interlocuteurs --}}
    @if (($info['type'] ?? '') === 'prospect')
    <div class="pw-info-panel" data-tab="interlocuteur" style="display:none;">
        <div class="pw-field-label" style="margin-bottom:0.75rem;">Nouveau contact identifié à l'appel</div>
        <div class="pw-interlocuteur-card">
            <div class="pw-interlocuteur-card-title">Interlocuteur principal</div>
            <div class="pw-info-grid">
                <div class="pw-field-full"><div class="pw-field-label">Prénom / Nom</div><input type="text" wire:model="interlocuteur_nom" class="pw-field-input" placeholder="Prénom Nom du responsable CSE"></div>
                <div class="pw-field-full"><div class="pw-field-label">Fonction</div><input type="text" wire:model="interlocuteur_fonction" class="pw-field-input" placeholder="Fonction du contact"></div>
                <div class="pw-field-full"><div class="pw-field-label">Téléphone</div><input type="tel" wire:model="interlocuteur_telephone" class="pw-field-input" placeholder="06 XX XX XX XX"></div>
                <div class="pw-field-full"><div class="pw-field-label">Email</div><input type="email" wire:model="interlocuteur_email" class="pw-field-input" placeholder="cse@entreprise.fr"></div>
            </div>
        </div>
        <div class="pw-interlocuteur-card" style="background:rgb(249 250 251);">
            <div class="pw-interlocuteur-card-title">Interlocuteur supplémentaire</div>
            <div class="pw-info-grid">
                <div class="pw-field-full"><div class="pw-field-label">Prénom / Nom</div><input type="text" wire:model="interlocuteur_add_nom" class="pw-field-input" placeholder="Prénom Nom"></div>
                <div class="pw-field-full"><div class="pw-field-label">Fonction</div><input type="text" wire:model="interlocuteur_add_fonction" class="pw-field-input" placeholder="Fonction complémentaire"></div>
                <div class="pw-field-full"><div class="pw-field-label">Téléphone</div><input type="tel" wire:model="interlocuteur_add_telephone" class="pw-field-input" placeholder="06 XX XX XX XX"></div>
                <div class="pw-field-full"><div class="pw-field-label">Email</div><input type="email" wire:model="interlocuteur_add_email" class="pw-field-input" placeholder="contact@entreprise.fr"></div>
            </div>
        </div>
        @if (!empty($existingContacts))
        <div class="pw-interlocuteur-card" style="background:rgb(249 250 251);">
            <div class="pw-interlocuteur-card-title">Données déjà enregistrées</div>
            <div style="display:grid;gap:0.35rem;font-size:0.875rem;">
                @foreach ($existingContacts as $entry)
                <div style="display:flex;gap:0.5rem;align-items:flex-start;">
                    <span style="font-weight:700;color:rgb(71 85 105);min-width:5rem;">{{ $entry['label'] }} :</span>
                    <span>{{ $entry['value'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        <div style="display:flex;justify-content:flex-end;margin-top:1rem;">
            <button type="button" wire:click="saveInterlocuteur" class="pw-btn-secondary" style="padding:0.75rem 1rem;">
                Enregistrer contact/interlocuteur
            </button>
        </div>
    </div>
    @endif

    {{-- Panneau Notes --}}
    @if (!empty($noteLines))
    <div class="pw-info-panel" data-tab="notes" style="display:none;">
        <div class="pw-notes-list">
            @foreach (array_reverse($noteLines) as $note)
            <div class="pw-note-item">
                @if ($note['date'])<div class="pw-note-date">{{ $note['date'] }}</div>@endif
                <div style="font-size:0.8125rem;">{{ $note['text'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Panneau Journal --}}
    <div class="pw-info-panel" data-tab="journal" style="display:none;">
        @if (count($callHistory) === 0)
        <div style="text-align:center;padding:2rem 1rem;color:rgb(156 163 175);font-size:0.875rem;">
            Aucun appel enregistré pour ce contact.
        </div>
        @else
        <div style="display:flex;flex-direction:column;gap:0.625rem;">
            @foreach ($callHistory as $appel)
            <div style="border-radius:0.625rem;border:1px solid rgb(226 232 240);background:rgb(248 250 252);padding:0.625rem 0.75rem;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0.375rem;">
                    <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                        <span style="font-size:0.7rem;font-weight:700;padding:0.125rem 0.5rem;border-radius:9999px;color:white;text-transform:uppercase;letter-spacing:0.05em;{{ data_get($appel, 'statut_bar', 'background:rgb(107 114 128)') }}">{{ data_get($appel, 'statut_label', '—') }}</span>
                        @if (filled(data_get($appel, 'pipeline_label')))
                        <span style="font-size:0.65rem;font-weight:600;padding:0.125rem 0.45rem;border-radius:9999px;{{ data_get($appel, 'pipeline_badge_style', 'background:rgb(243 244 246);color:rgb(55 65 81);') }}">Pipeline · {{ data_get($appel, 'pipeline_label') }}</span>
                        @endif
                        @if (filled(data_get($appel, 'campagne')))
                        <span style="font-size:0.7rem;padding:0.125rem 0.375rem;border-radius:9999px;background:rgb(238 242 255);color:rgb(79 70 229);border:1px solid rgb(199 210 254);">{{ data_get($appel, 'campagne') }}</span>
                        @endif
                    </div>
                    <span style="font-size:0.7rem;color:rgb(107 114 128);white-space:nowrap;margin-left:0.5rem;">{{ data_get($appel, 'date', '') }}</span>
                </div>
                <div style="font-size:0.75rem;color:rgb(55 65 81);"><span style="font-weight:600;">{{ data_get($appel, 'agent', '—') }}</span></div>
                @if (filled(data_get($appel, 'notes')))
                <div style="font-size:0.75rem;color:rgb(75 85 99);background:rgba(255,255,255,0.6);border-radius:0.375rem;padding:0.25rem 0.5rem;margin-top:0.25rem;border-left:2px solid rgb(148 163 184);">{{ data_get($appel, 'notes') }}</div>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Panneau RDV --}}
    @if (($info['type'] ?? '') !== 'client')
    <div class="pw-info-panel" data-tab="rdv" style="display:none;">
        <div class="pw-info-grid">
            <div><div class="pw-field-label">Date RDV</div><input type="date" wire:model="rappel_date" class="pw-field-input"></div>
            <div><div class="pw-field-label">Heure RDV</div><input type="time" wire:model="rappel_heure" class="pw-field-input"></div>
            <div class="pw-field-full"><div class="pw-field-label">Lieu / Adresse du RDV</div><input type="text" wire:model="lieu_rdv" class="pw-field-input" placeholder="Adresse ou visioconférence"></div>
        </div>
    </div>
    @endif

</div>{{-- /pw-infos --}}
