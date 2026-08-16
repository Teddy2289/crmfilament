<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; border-bottom: 2px solid #2563eb; padding-bottom: 15px; }
        .logo { font-size: 20px; font-weight: bold; color: #2563eb; }
        .fiche-type { font-size: 14px; font-weight: bold; color: #2563eb; background: #dbeafe; padding: 4px 12px; border-radius: 4px; }
        .section { margin-bottom: 20px; }
        .section-title { font-size: 12px; font-weight: bold; color: #475569; text-transform: uppercase; margin-bottom: 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #f1f5f9; text-align: left; padding: 6px; font-size: 10px; text-transform: uppercase; color: #64748b; }
        td { padding: 6px; border-bottom: 1px solid #e2e8f0; }
        .label { font-weight: bold; color: #475569; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .info-box { background: #f8fafc; padding: 10px; border-radius: 4px; }
        .footer { margin-top: 40px; font-size: 9px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 10px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 9px; font-weight: bold; }
        .badge-oui { background: #dcfce7; color: #166534; }
        .badge-non { background: #fee2e2; color: #991b1b; }
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
        <div class="fiche-type">FICHE BLEUE</div>
        <div style="margin-top:6px; font-size:12px; font-weight:bold;">Récapitulatif RDV Pris</div>
        <div style="margin-top:4px; font-size:10px; color:#64748b;">
            Générée le : {{ $data['date_generation'] ?? now()->format('d/m/Y H:i') }}
        </div>
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

{{-- Détails RDV --}}
<div class="section">
    <div class="section-title">Détails du Rendez-vous</div>
    <table>
        <tr>
            <td style="width:50%"><span class="label">Date et heure :</span> {{ $data['rdv_date_heure'] ?? '—' }}</td>
            <td><span class="label">Lieu :</span> {{ $data['rdv_lieu'] ?? '—' }}</td>
        </tr>
        <tr>
            <td><span class="label">Invitation agenda envoyée :</span> 
                @if(isset($data['invitation_agenda_envoyee']) && $data['invitation_agenda_envoyee'] === 'Oui')
                    <span class="badge badge-oui">OUI</span>
                @else
                    <span class="badge badge-non">NON</span>
                @endif
            </td>
            <td></td>
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

{{-- Besoins et objections --}}
<div class="section">
    <div class="section-title">Analyse de l'échange</div>
    <table>
        <tr>
            <td style="width:50%; vertical-align:top;">
                <span class="label">Besoins exprimés :</span>
                <div style="margin-top:4px; color:#475569;">{{ $data['besoins_exprimes'] ?? 'Non renseigné' }}</div>
            </td>
            <td style="vertical-align:top;">
                <span class="label">Objections soulevées :</span>
                <div style="margin-top:4px; color:#475569;">{{ $data['objections_soulevees'] ?? 'Non renseigné' }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <span class="label">Points d'attention pour le RDV :</span>
                <div style="margin-top:4px; color:#475569;">{{ $data['points_attention_rdv'] ?? 'Non renseigné' }}</div>
            </td>
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
        <tr>
            <td colspan="2"><span class="label">Date du premier contact :</span> {{ $data['date_appel'] ?? '—' }}</td>
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