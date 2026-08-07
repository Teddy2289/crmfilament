@props([
    'info',           {{-- array — données du contact (getContactInfo()) --}}
    'callHistory',    {{-- array — historique des appels (getCallHistory()) --}}
    'noteLines',      {{-- array — notes parsées [{date, text}] --}}
    'contactType',    {{-- string — type de contact --}}
    'incomingCallPhone'   => null,
    'incomingCallMatches' => [],
])

@php
    $statutCls        = 'pw-badge-' . strtolower($info['statut_code'] ?? $info['statut'] ?? 'ac');
    $statutLabel      = $info['statut_label'] ?? ($info['statut'] ?? 'AC');
    $statutBadgeStyle = $info['statut_badge_style'] ?? null;

    $editActionRoute = null;
    if (!empty($info['id'])) {
        match ($info['type'] ?? '') {
            'prospect'  => $editActionRoute = \App\Filament\NsConseil\Resources\ProspectResource::getUrl('edit', ['record' => $info['id']]),
            'partenaire'=> $editActionRoute = \App\Filament\NsConseil\Resources\PartenaireResource::getUrl('edit', ['record' => $info['id']]),
            'client'    => $editActionRoute = \App\Filament\NsConseil\Resources\ClientResource::getUrl('edit', ['record' => $info['id']]),
            default     => null,
        };
    }
@endphp
