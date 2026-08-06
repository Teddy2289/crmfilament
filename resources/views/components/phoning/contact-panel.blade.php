@props(['contact', 'contactType', 'queueCount', 'progress', 'isSupervisorMode'])

{{--
    Composant : contact-panel
    Contient :
      - Carte identité entreprise (nom, badge statut, alertes rappel/difficile, bouton appel, EN FILE)
      - Grille de données entreprise (SIRET, localisation, adresse, secteur, salariés)
      - Barre de contact (téléphone principal, email)
      - Indicateur de progression (contacts restants + pourcentage)

    Props :
      - contact          : array          — données formatées du contact courant (getContactInfo())
      - contactType      : string|null    — 'prospect'|'artisan'|'partenaire'|'client'|'particulier'
      - queueCount       : int            — nombre de contacts restants en file
      - progress         : int            — pourcentage de progression (0–100)
      - isSupervisorMode : bool           — true si l'utilisateur visualise un autre agent
--}}

@php
    $tel          = $contact['telephone'] ?? null;
    $statutCls    = 'pw-badge-' . strtolower($contact['statut_code'] ?? $contact['statut'] ?? 'ac');
    if (!isset($contact['statut']) && !isset($contact['statut_code'])) {
        $statutCls = 'pw-badge-gray';
    }
    $statutLabel      = $contact['statut_label'] ?? ($contact['statut'] ?? 'AC');
    $statutBadgeStyle = $contact['statut_badge_style'] ?? null;

    $localisation = collect([$contact['ville'] ?? null, $contact['code_postal'] ?? null])
        ->filter()
        ->implode(' · ');
    if (!empty($contact['departement'])) {
        $localisation .= $localisation
            ? ' — Dépt ' . $contact['departement']
            : 'Dépt ' . $contact['departement'];
    }
@endphp

{{-- ── CARTE IDENTITÉ ENTREPRISE ─────────────────────────────────────── --}}
<div class="pw-summary-card">
    <div class="pw-summary-top">
        <div class="pw-summary-identity">
            <div class="pw-summary-avatar">
                <svg class="pw-icon-xl" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5M4.5 3h6.75v18M4.5 3v18M11.25 3h8.25v18M11.25 3v18M7.5 6.75h.008v.008H7.5V6.75Zm0 3h.008v.008H7.5V9.75Zm0 3h.008v.008H7.5v-.008Zm7.5-6h.008v.008h-.008V6.75Zm0 3h.008v.008h-.008V9.75Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                </svg>
            </div>
            <div>
                <div class="pw-summary-name-row">
                    <h2 class="pw-summary-name">
                        {{ Str::upper(trim(($contact['prenom'] ?? '') . ' ' . ($contact['nom'] ?? ''))) ?: 'CONTACT SANS NOM' }}
                    </h2>
                    <span class="pw-badge {{ $statutCls }}"
                          @if ($statutBadgeStyle) style="{{ $statutBadgeStyle }}" @endif>
                        {{ $statutLabel }}
                    </span>
                    @if (!empty($contact['rappel_en_retard']) && $contact['rappel_en_retard'])
                    <span class="pw-alert-badge pw-alert-badge-red">
                        <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                        Rappel en retard
                    </span>
                    @endif
                    @if (!empty($contact['difficile']))
                    <span class="pw-alert-badge pw-alert-badge-amber">
                        <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0l2.77-.693a9 9 0 016.208.682l.108.054a9 9 0 006.086.71l3.114-.732a48.524 48.524 0 01-.005-10.499l-3.11.732a9 9 0 01-6.085-.711l-.108-.054a9 9 0 00-6.208-.682L3 4.5M3 15V4.5" />
                        </svg>
                        Fiche difficile
                    </span>
                    @endif
                    @if ($isSupervisorMode)
                    <span class="pw-alert-badge" style="background:rgb(219 234 254);color:rgb(29 78 216);border-color:rgb(191 219 254);">
                        <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Mode supervision
                    </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── Bouton appel + compteur EN FILE ─────────────────────────── --}}
        <div class="pw-summary-actions">
            <button wire:click="callNow"
                    onclick="startTimer(); appelerAvecRingover('{{ $tel }}')"
                    class="pw-btn-call">
                <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                {{ $tel ?? 'Appeler' }}
            </button>

            <div class="pw-en-file">
                <span class="pw-en-file-num">{{ $queueCount }}</span>
                <span class="pw-en-file-label">EN FILE</span>
            </div>
        </div>
    </div>

    {{-- ── Grille de données entreprise ─────────────────────────────────── --}}
    <div class="pw-summary-grid">
        <div class="pw-summary-field">
            <span class="pw-summary-field-icon">
                <svg class="pw-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" />
                </svg>
            </span>
            <span class="pw-summary-field-text">
                <span class="pw-summary-field-label">SIRET</span>
                <span class="pw-summary-field-value" title="{{ $contact['siret'] ?? '' }}">{{ $contact['siret'] ?? '—' }}</span>
            </span>
        </div>

        <div class="pw-summary-field">
            <span class="pw-summary-field-icon">
                <svg class="pw-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                </svg>
            </span>
            <span class="pw-summary-field-text">
                <span class="pw-summary-field-label">Localisation</span>
                <span class="pw-summary-field-value" title="{{ $localisation }}">{{ $localisation ?: '—' }}</span>
            </span>
        </div>

        <div class="pw-summary-field">
            <span class="pw-summary-field-icon">
                <svg class="pw-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
            </span>
            <span class="pw-summary-field-text">
                <span class="pw-summary-field-label">Adresse</span>
                <span class="pw-summary-field-value" title="{{ $contact['adresse_complete'] ?? '' }}">{{ $contact['adresse_complete'] ?? '—' }}</span>
            </span>
        </div>

        <div class="pw-summary-field">
            <span class="pw-summary-field-icon">
                <svg class="pw-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
            </span>
            <span class="pw-summary-field-text">
                <span class="pw-summary-field-label">Secteur d'activité</span>
                <span class="pw-summary-field-value" title="{{ $contact['secteur_activite'] ?? '' }}">{{ $contact['secteur_activite'] ?? '—' }}</span>
            </span>
        </div>

        <div class="pw-summary-field">
            <span class="pw-summary-field-icon">
                <svg class="pw-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
            </span>
            <span class="pw-summary-field-text">
                <span class="pw-summary-field-label">Nombre de salariés</span>
                <span class="pw-summary-field-value">{{ $contact['nb_salaries'] ?? '—' }}</span>
            </span>
        </div>
    </div>

    {{-- ── BARRE DE CONTACT : téléphone & email ─────────────────────────── --}}
    <div style="display:flex; align-items:center; gap:1.25rem; padding:0.625rem 1rem; border-top:1px solid rgb(243 244 246); flex-wrap:wrap;">
        {{-- Téléphone --}}
        <div style="display:flex; align-items:center; gap:0.5rem; min-width:0;">
            <svg class="pw-icon-sm" style="color:rgb(34 197 94);flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
            </svg>
            @if ($tel)
            <a href="tel:{{ $tel }}"
               onclick="event.preventDefault(); appelerAvecRingover('{{ $tel }}')"
               style="font-size:0.875rem; font-weight:600; color:rgb(17 24 39); text-decoration:none; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                {{ $tel }}
            </a>
            @else
            <span style="font-size:0.875rem; color:rgb(156 163 175);">—</span>
            @endif
        </div>

        <div style="width:1px;height:1rem;background:rgb(229 231 235);flex-shrink:0;"></div>

        {{-- Email --}}
        <div style="display:flex; align-items:center; gap:0.5rem; min-width:0; flex:1;">
            <svg class="pw-icon-sm" style="color:rgb(99 102 241);flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
            </svg>
            @if (!empty($contact['email']))
            <a href="mailto:{{ $contact['email'] }}"
               style="font-size:0.8125rem; color:rgb(37 99 235); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                {{ $contact['email'] }}
            </a>
            @else
            <span style="font-size:0.8125rem; color:rgb(156 163 175);">—</span>
            @endif
        </div>

        {{-- ── Barre de progression ────────────────────────────────────── --}}
        <div style="display:flex; align-items:center; gap:0.5rem; margin-left:auto; flex-shrink:0;">
            <div style="width:5rem; height:0.375rem; border-radius:9999px; background:rgb(229 231 235); overflow:hidden;">
                <div style="height:100%; border-radius:9999px; background:rgb(34 197 94); width:{{ $progress }}%;"></div>
            </div>
            <span style="font-size:0.75rem; color:rgb(107 114 128); white-space:nowrap;">
                {{ $progress }}%
                @if ($queueCount > 0)
                · {{ $queueCount }} restant{{ $queueCount > 1 ? 's' : '' }}
                @endif
            </span>
        </div>
    </div>
</div>
