<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport d'Activités</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #1e40af; }
        .info-section { margin-bottom: 20px; padding: 15px; background: #f3f4f6; border-radius: 5px; }
        .info-section h2 { color: #374151; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #1e40af; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .footer { margin-top: 30px; text-align: center; color: #6b7280; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rapport d'Activités de Vente</h1>
        <p>NS Conseil - Gestion Commerciale</p>
        <p>Période: {{ $startDate }} au {{ $endDate }}</p>
        <p>Date: {{ $date }}</p>
    </div>

    <div class="info-section">
        <h2>Statistiques Générales</h2>
        <div class="info-row">
            <span class="info-label">Total Activités:</span>
            <span class="info-value">{{ $activities->count() }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Période:</span>
            <span class="info-value">{{ $startDate }} - {{ $endDate }}</span>
        </div>
    </div>

    <div class="info-section">
        <h2>Détail des Activités</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Prospect</th>
                    <th>Dernière Vente</th>
                    <th>Montant</th>
                    <th>Consultant</th>
                </tr>
            </thead>
            <tbody>
                @foreach($activities as $activity)
                <tr>
                    <td>{{ $activity->id }}</td>
                    <td>{{ $activity->prospect?->nom ?? 'N/A' }}</td>
                    <td>{{ $activity->derniere_vente?->format('d/m/Y') ?? 'N/A' }}</td>
                    <td>{{ number_format($activity->montant ?? 0, 2, ',', ' ') }} €</td>
                    <td>{{ $activity->consultant?->name ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Document généré automatiquement par NS Conseil CRM</p>
        <p>{{ $date }}</p>
    </div>
</body>
</html>
