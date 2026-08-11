<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport Partenaire #{{ $partenaire->id }}</title>
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
        <h1>Rapport Partenaire</h1>
        <p>NS Conseil - Gestion Commerciale</p>
        <p>Date: {{ $date }}</p>
    </div>

    <div class="info-section">
        <h2>Informations Générales</h2>
        <div class="info-row">
            <span class="info-label">ID:</span>
            <span class="info-value">{{ $partenaire->id }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Nom:</span>
            <span class="info-value">{{ $partenaire->nom ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Email:</span>
            <span class="info-value">{{ $partenaire->email ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Téléphone:</span>
            <span class="info-value">{{ $partenaire->telephone ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Type Partenaire:</span>
            <span class="info-value">{{ $partenaire->type_partenaire ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Statut:</span>
            <span class="info-value">{{ $partenaire->statut ?? 'N/A' }}</span>
        </div>
    </div>

    <div class="info-section">
        <h2>Détails Complémentaires</h2>
        <div class="info-row">
            <span class="info-label">Consultant:</span>
            <span class="info-value">{{ $partenaire->consultant?->name ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Créé le:</span>
            <span class="info-value">{{ $partenaire->created_at?->format('d/m/Y H:i') ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Mis à jour le:</span>
            <span class="info-value">{{ $partenaire->updated_at?->format('d/m/Y H:i') ?? 'N/A' }}</span>
        </div>
    </div>

    <div class="footer">
        <p>Document généré automatiquement par NS Conseil CRM</p>
        <p>{{ $date }}</p>
    </div>
</body>
</html>
