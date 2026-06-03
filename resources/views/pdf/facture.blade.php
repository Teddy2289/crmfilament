<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .logo { font-size: 22px; font-weight: bold; color: #2563eb; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .badge-attente { background: #fef9c3; color: #854d0e; }
        .badge-paye   { background: #dcfce7; color: #166534; }
        .badge-retard { background: #fee2e2; color: #991b1b; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #f1f5f9; text-align: left; padding: 8px; font-size: 11px; text-transform: uppercase; }
        td { padding: 8px; border-bottom: 1px solid #e2e8f0; }
        .total-box { float: right; width: 280px; margin-top: 20px; }
        .total-box table td { border: none; padding: 4px 8px; }
        .total-ttc { font-size: 15px; font-weight: bold; border-top: 2px solid #1a1a1a; }
        .footer { margin-top: 60px; font-size: 10px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 10px; }
        .parties { display: flex; gap: 40px; margin-bottom: 30px; }
        .partie-box { flex: 1; background: #f8fafc; padding: 12px; border-radius: 4px; }
        .partie-box h4 { margin: 0 0 6px; font-size: 10px; text-transform: uppercase; color: #64748b; }
    </style>
</head>
<body>

{{-- En-tête --}}
<div class="header">
    <div>
        <div class="logo">AllioPro</div>
        <div style="margin-top:4px; color:#64748b;">Plateforme de mise en relation artisans / particuliers</div>
    </div>
    <div style="text-align:right;">
        <div style="font-size:18px; font-weight:bold;">FACTURE</div>
        <div style="font-size:14px; color:#2563eb;">{{ $facture->numero }}</div>
        <div style="margin-top:6px;">
            @php
                $badgeClass = match($facture->statut_paiement->value ?? '') {
                    'paye'    => 'badge-paye',
                    'retard'  => 'badge-retard',
                    default   => 'badge-attente',
                };
            @endphp
            <span class="badge {{ $badgeClass }}">{{ $facture->statut_paiement->label() }}</span>
        </div>
        <div style="margin-top:6px; font-size:11px; color:#64748b;">
            Échéance : {{ $facture->date_echeance?->format('d/m/Y') ?? '—' }}
        </div>
    </div>
</div>

{{-- Parties --}}
<div class="parties">
    <div class="partie-box">
        <h4>Prestataire</h4>
        <strong>{{ $facture->artisan?->nom_complet ?? '—' }}</strong><br>
        SIRET : {{ $facture->artisan?->siret ?? '⚠️ manquant' }}<br>
        {{ $facture->artisan?->email ?? '' }}<br>
        {{ $facture->artisan?->telephone ?? '' }}
    </div>
    <div class="partie-box">
        <h4>Client facturé</h4>
        <strong>{{ trim(($facture->contactParticulier?->prenom ?? '') . ' ' . ($facture->contactParticulier?->nom ?? '')) ?: '—' }}</strong><br>
        {{ $facture->contactParticulier?->email ?? '' }}<br>
        {{ $facture->contactParticulier?->telephone ?? '' }}
    </div>
    <div class="partie-box">
        <h4>Référence</h4>
        Ticket : <strong>{{ $facture->ticket?->reference ?? '—' }}</strong><br>
        BC : {{ $facture->bonDeCommande?->numero ?? '—' }}<br>
        Émise le : {{ $facture->created_at->format('d/m/Y') }}
    </div>
</div>

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
        @foreach($facture->lignes ?? [] as $ligne)
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
        <tr><td>Total HT</td><td style="text-align:right;">{{ number_format((float)$facture->total_ht, 2, ',', ' ') }} €</td></tr>
        <tr><td>TVA</td><td style="text-align:right;">{{ number_format((float)$facture->montant_tva, 2, ',', ' ') }} €</td></tr>
        @if($facture->acompte_deja_verse)
        <tr><td>Acompte versé</td><td style="text-align:right;">- {{ number_format((float)$facture->acompte_deja_verse, 2, ',', ' ') }} €</td></tr>
        @endif
        <tr class="total-ttc">
            <td><strong>Total TTC</strong></td>
            <td style="text-align:right;"><strong>{{ number_format((float)$facture->total_ttc, 2, ',', ' ') }} €</strong></td>
        </tr>
        @if((float)$facture->solde_restant_du > 0)
        <tr><td style="color:#dc2626;">Solde dû</td><td style="text-align:right; color:#dc2626;"><strong>{{ number_format((float)$facture->solde_restant_du, 2, ',', ' ') }} €</strong></td></tr>
        @endif
    </table>
</div>

<div style="clear:both;"></div>

@if($facture->notes)
<div style="margin-top:30px; background:#f8fafc; padding:12px; border-radius:4px; font-size:11px;">
    <strong>Notes :</strong> {{ $facture->notes }}
</div>
@endif

<div class="footer">
    Facture générée automatiquement — AllioPro &bull; Document à conserver &bull; {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
