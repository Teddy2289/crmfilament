<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport Dossier Formation #{{ $dossier->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #1e40af; }
        .info-section { margin-bottom: 20px; padding: 15px; background: #f3f4f6; border-radius: 5px; }
        .info-section h2 { color: #374151; margin-top: 0; }
        .info-row { display: flex; margin-bottom: 10px; }
        .info-label { font-weight: bold; width: 150px; color: #6b7280; }
        .info-value { flex: 1; }
        .footer { margin-top: 30px; text-align: center; color: #6b7280; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rapport Dossier de Formation</h1>
        <p>NS Conseil - Gestion Commerciale</p>
        <p>Date: {{ $date }}</p>
    </div>

    <div class="info-section">
        <h2>Informations Générales</h2>
        <div class="info-row">
            <span class="info-label">ID:</span>
            <span class="info-value">{{ $dossier->id }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Référence:</span>
            <span class="info-value">{{ $dossier->reference ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Intitulé:</span>
            <span class="info-value">{{ $dossier->intitule ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Statut:</span>
            <span class="info-value">{{ $dossier->statut ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Type Formation:</span>
            <span class="info-value">{{ $dossier->type_formation ?? 'N/A' }}</span>
        </div>
    </div>

    <div class="info-section">
        <h2>Détails Complémentaires</h2>
        <div class="info-row">
            <span class="info-label">Client:</span>
            <span class="info-value">{{ $dossier->client?->nom_tiers ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Consultant:</span>
            <span class="info-value">{{ $dossier->consultant?->name ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Date Début:</span>
            <span class="info-value">{{ $dossier->date_debut?->format('d/m/Y') ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Date Fin:</span>
            <span class="info-value">{{ $dossier->date_fin?->format('d/m/Y') ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Créé le:</span>
            <span class="info-value">{{ $dossier->created_at?->format('d/m/Y H:i') ?? 'N/A' }}</span>
        </div>
    </div>

    <div class="footer">
        <p>Document généré automatiquement par NS Conseil CRM</p>
        <p>{{ $date }}</p>
    </div>
</body>
</html>
