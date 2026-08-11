<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; }
        .header { margin-bottom: 30px; }
        .logo { font-size: 22px; font-weight: bold; color: #2563eb; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .badge-brouillon  { background: #f1f5f9; color: #475569; }
        .badge-envoye     { background: #dbeafe; color: #1d4ed8; }
        .badge-accepte    { background: #dcfce7; color: #166534; }
        .badge-refuse     { background: #fee2e2; color: #991b1b; }
        .badge-expire     { background: #fef9c3; color: #854d0e; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #f1f5f9; text-align: left; padding: 8px; font-size: 11px; text-transform: uppercase; }
        td { padding: 8px; border-bottom: 1px solid #e2e8f0; }
        .total-box { float: right; width: 280px; margin-top: 20px; }
        .total-box table td { border: none; padding: 4px 8px; }
        .total-ttc { font-size: 15px; font-weight: bold; border-top: 2px solid #1a1a1a; }
        .footer { margin-top: 60px; font-size: 10px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 10px; }
        .info-row { width: 100%; margin-bottom: 30px; }
        .info-cell { width: 33%; vertical-align: top; padding-right: 16px; background: #f8fafc; padding: 12px; }
        .info-cell h4 { margin: 0 0 6px; font-size: 10px; text-transform: uppercase; color: #64748b; }
        .validity-banner { background: #eff6ff; border-left: 4px solid #2563eb; padding: 10px 14px; margin-bottom: 24px; font-size: 11px; }
    </style>
</head>
<body>

{{-- En-tête --}}
<table style="width:100%; border:none; margin-bottom:30px;">
    <tr>
        <td style="border:none; padding:0; vertical-align:top;">
            <div class="logo">AllioPro</div>
            <div style="margin-top:4px; color:#64748b;">Plateforme de mise en relation artisans / particuliers</div>
        </td>
        <td style="border:none; padding:0; text-align:right; vertical-align:top;">
            <div style="font-size:18px; font-weight:bold;">DEVIS</div>
            <div style="font-size:14px; color:#2563eb;">{{ $devis->numero }}</div>
            <div style="margin-top:6px;">
                @php
                    $badgeClass = match($devis->statut->value ?? '') {
                        'envoye'   => 'badge-envoye',
                        'accepte'  => 'badge-accepte',
                        'refuse'   => 'badge-refuse',
                        'expire'   => 'badge-expire',
                        default    => 'badge-brouillon',
                    };
                @endphp
                <span class="badge {{ $badgeClass }}">{{ $devis->statut->label() }}</span>
            </div>
            <div style="margin-top:6px; font-size:11px; color:#64748b;">
                Émis le : {{ $devis->created_at->format('d/m/Y') }}
            </div>
        </td>
    </tr>
</table>

{{-- Bandeau validité --}}
<div class="validity-banner">
    @if($devis->est_expire)
        ⚠️ Ce devis a expiré le {{ $devis->date_validite?->format('d/m/Y') }}.
    @else
        ✅ Devis valable jusqu'au <strong>{{ $devis->date_validite?->format('d/m/Y') ?? '—' }}</strong>
        — encore <strong>{{ $devis->jours_avant_expiration }} jour(s)</strong>.
    @endif
</div>

{{-- Parties --}}
<table style="width:100%; border:none; margin-bottom:30px;">
    <tr>
        <td class="info-cell" style="border:none;">
            <h4>Prestataire</h4>
            <strong>{{ $devis->artisan?->nom_complet ?? '—' }}</strong><br>
            SIRET : {{ $devis->artisan?->siret ?? '⚠️ manquant' }}<br>
            {{ $devis->artisan?->email ?? '' }}<br>
            {{ $devis->artisan?->telephone ?? '' }}
        </td>
        <td style="width:4%; border:none;"></td>
        <td class="info-cell" style="border:none;">
            <h4>Client</h4>
            <strong>{{ trim(($devis->contactParticulier?->prenom ?? '') . ' ' . ($devis->contactParticulier?->nom ?? '')) ?: '—' }}</strong><br>
            {{ $devis->contactParticulier?->email ?? '' }}<br>
            {{ $devis->contactParticulier?->telephone ?? '' }}
        </td>
        <td style="width:4%; border:none;"></td>
        <td class="info-cell" style="border:none;">
            <h4>Référence</h4>
            Ticket : <strong>{{ $devis->ticket?->reference ?? '—' }}</strong><br>
            @if($devis->date_acceptation_refus)
                {{ $devis->statut->value === 'accepte' ? 'Accepté' : 'Refusé' }} le :
                {{ $devis->date_acceptation_refus->format('d/m/Y') }}<br>
            @endif
        </td>
    </tr>
</table>

{{-- Lignes --}}
<table>
    <thead>
        <tr>
            <th style="width:45%">Désignation</th>
            <th style="width:10%; text-align:right;">Qté</th>
            <th style="width:15%; text-align:right;">PU HT</th>
            <th style="width:10%; text-align:center;">TVA</th>
            <th style="width:20%; text-align:right;">Total HT</th>
        </tr>
    </thead>
    <tbody>
        @foreach($devis->lignes ?? [] as $ligne)
        <tr>
            <td>{{ $ligne['libelle'] ?? '' }}</td>
            <td style="text-align:right;">{{ $ligne['quantite'] ?? 1 }}</td>
            <td style="text-align:right;">{{ number_format((float)($ligne['prix_unitaire_ht'] ?? 0), 2, ',', ' ') }} €</td>
            <td style="text-align:center;">{{ $ligne['taux_tva'] ?? 10 }} %</td>
            <td style="text-align:right;">{{ number_format((float)($ligne['quantite'] ?? 1) * (float)($ligne['prix_unitaire_ht'] ?? 0), 2, ',', ' ') }} €</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- Totaux --}}
<div class="total-box">
    <table>
        <tr>
            <td>Total HT</td>
            <td style="text-align:right;">{{ number_format((float)$devis->total_ht, 2, ',', ' ') }} €</td>
        </tr>
        <tr>
            <td>TVA</td>
            <td style="text-align:right;">{{ number_format((float)$devis->montant_tva, 2, ',', ' ') }} €</td>
        </tr>
        @if($devis->acompte_montant)
        <tr>
            <td>Acompte demandé</td>
            <td style="text-align:right;">{{ number_format((float)$devis->acompte_montant, 2, ',', ' ') }} €</td>
        </tr>
        @endif
        <tr class="total-ttc">
            <td><strong>Total TTC</strong></td>
            <td style="text-align:right;"><strong>{{ number_format((float)$devis->total_ttc, 2, ',', ' ') }} €</strong></td>
        </tr>
    </table>
</div>

<div style="clear:both;"></div>

{{-- Notes --}}
@if($devis->notes ?? false)
<div style="margin-top:30px; background:#f8fafc; padding:12px; border-radius:4px; font-size:11px;">
    <strong>Notes :</strong> {{ $devis->notes }}
</div>
@endif

{{-- Mention légale acceptation --}}
@if(in_array($devis->statut->value ?? '', ['brouillon', 'envoye']))
<div style="margin-top:30px; border: 1px solid #e2e8f0; padding: 14px; font-size:11px; color:#475569;">
    <strong>Bon pour accord</strong> — En acceptant ce devis, le client reconnaît avoir pris connaissance des conditions
    de réalisation de la prestation et s'engage à régler le montant indiqué selon les modalités convenues.<br><br>
    Signature du client : _____________________________ &nbsp;&nbsp; Date : _______________
</div>
@endif

<div class="footer">
    Devis généré automatiquement — AllioPro &bull; Document non contractuel avant acceptation &bull; {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
