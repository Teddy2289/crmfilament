<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; border-bottom: 2px solid #ca8a04; padding-bottom: 15px; }
        .logo { font-size: 20px; font-weight: bold; color: #ca8a04; }
        .fiche-type { font-size: 14px; font-weight: bold; color: #ca8a04; background: #fef9c3; padding: 4px 12px; border-radius: 4px; }
        .section { margin-bottom: 20px; }
        .section-title { font-size: 12px; font-weight: bold; color: #475569; text-transform: uppercase; margin-bottom: 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #f1f5f9; text-align: left; padding: 6px; font-size: 10px; text-transform: uppercase; color: #64748b; }
        td { padding: 6px; border-bottom: 1px solid #e2e8f0; }
        .label { font-weight: bold; color: #475569; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .info-box { background: #f8fafc; padding: 10px; border-radius: 4px; }
        .footer { margin-top: 40px; font-size: 9px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 10px; }
        .alert-box { background: #fef9c3; border-left: 4px solid #ca8a04; padding: 12px; margin-bottom: 15px; }
        .alert-title { font-weight: bold; color: #854d0e; margin-bottom: 4px; }
    </style>
</head>
<body>

{{-- En-tête --}}
<div class="header">
    <div>
        <div class="logo">AOPIA CRM</div>
        <div style="margin-top:4px; color:#64748b;">Fiche de prospection CSE</div>
    </div>
    <div style="text-align:right;">
        <div class="fiche-type">FICHE JAUNE</div>
        <div style="margin-top:6px; font-size:12px; font-weight:bold;">CSE Pas Intéressé — Rappel J+7</div>
        <div style="margin-top:4px; font-size:10px; color:#64748b;">
            Générée le : {{ $data['date_generation'] ?? now()->format('d/m/Y H:i') }}
        </div>
    </div>
</div>

{{-- Alerte rappel --}}
<div class="alert-box">
    <div class="alert-title">⚠️ RAPPEL COMMERCIAL À J+7</div>
    <div style="font-size:10px; color:#854d0e;">
        Ce prospect nécessite un relance commercial dans 7 jours. Le CSE a été joint mais n'est pas intéressé pour le moment.
    </div>
</div>

{{-- Informations entreprise --}}
<div class="section">
    <div class="section-title">Informations Entreprise</div>
    <table>
        <tr>
            <td style="width:50%"><span class="label">Raison sociale :</span> {{ $data['raison_sociale'] ?? '—' }}</td>
            <td><span class="label">Secteur d'activité :</span> {{ $data['secteur_activite'] ?? '—' }}</td>
        </tr>
        <tr>
            <td><span class="label">Effectif total :</span> {{ $data['nb_salaries'] ?? '—' }}</td>
            <td><span class="label">SIRET :</span> {{ $data['siret'] ?? '—' }}</td>
        </tr>
        <tr>
            <td colspan="2"><span class="label">Adresse complète :</span> {{ $data['adresse_complete'] ?? '—' }}</td>
        </tr>
    </table>
</div>

{{-- Interlocuteur CSE --}}
<div class="section">
    <div class="section-title">Interlocuteur CSE</div>
    <table>
        <tr>
            <td style="width:50%"><span class="label">Nom complet :</span> {{ $data['interlocuteur_nom'] ?? '—' }}</td>
            <td><span class="label">Fonction :</span> {{ $data['interlocuteur_fonction'] ?? '—' }}</td>
        </tr>
        <tr>
            <td><span class="label">Téléphone :</span> {{ $data['interlocuteur_telephone'] ?? '—' }}</td>
            <td><span class="label">Email :</span> {{ $data['interlocuteur_email'] ?? '—' }}</td>
        </tr>
    </table>
</div>

{{-- Informations CSE --}}
<div class="section">
    <div class="section-title">Informations CSE</div>
    <div class="info-grid">
        <div class="info-box">
            <div style="font-size:10px; color:#64748b; margin-bottom:4px;">Secrétaire CSE</div>
            <div>{{ $data['cse_secretaire'] ?? '—' }}</div>
        </div>
        <div class="info-box">
            <div style="font-size:10px; color:#64748b; margin-bottom:4px;">Trésorier CSE</div>
            <div>{{ $data['cse_tresorier'] ?? '—' }}</div>
        </div>
    </div>
    <table style="margin-top:8px;">
        <tr>
            <td><span class="label">Nombre d'élus CSE :</span> {{ $data['cse_nb_elus'] ?? '—' }}</td>
        </tr>
    </table>
</div>

{{-- Motif de refus --}}
<div class="section">
    <div class="section-title">Motif de Non-Intérêt</div>
    <div style="background:#fef9c3; padding:10px; border-radius:4px; font-size:10px; border-left:4px solid #ca8a04;">
        <span class="label">Motif du refus :</span>
        <div style="margin-top:4px; color:#854d0e;">{{ $data['motif_refus'] ?? 'Non renseigné' }}</div>
    </div>
</div>

{{-- Planning rappel --}}
<div class="section">
    <div class="section-title">Planning de Relance</div>
    <table>
        <tr>
            <td style="width:50%"><span class="label">Date de l'appel initial :</span> {{ $data['date_appel'] ?? '—' }}</td>
            <td><span class="label">Date de rappel J+7 :</span> {{ $data['date_rappel_j7'] ?? '—' }}</td>
        </tr>
    </table>
</div>

{{-- Responsables --}}
<div class="section">
    <div class="section-title">Responsables</div>
    <table>
        <tr>
            <td style="width:50%"><span class="label">Téléprospecteur :</span> {{ $data['teleprospecteur_nom'] ?? '—' }}</td>
            <td><span class="label">Responsable de Secteur :</span> {{ $data['commercial_nom'] ?? '—' }}</td>
        </tr>
    </table>
</div>

{{-- Notes --}}
@if(isset($data['notes']) && $data['notes'])
<div class="section">
    <div class="section-title">Notes supplémentaires</div>
    <div style="background:#f8fafc; padding:10px; border-radius:4px; font-size:10px;">
        {{ $data['notes'] }}
    </div>
</div>
@endif

<div class="footer">
    Fiche générée automatiquement par AOPIA CRM — Document confidentiel — {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>