<!doctype html>
<html lang="fr">
<head><meta charset="utf-8"><title>Performances par agent</title><style>
body{font-family:DejaVu Sans,Arial,sans-serif;font-size:10px;color:#1f2937}h1{font-size:18px;margin:0 0 5px}p{color:#6b7280;margin:0 0 15px}.table{width:100%;border-collapse:collapse}.table th,.table td{border:1px solid #d1d5db;padding:6px;text-align:left;vertical-align:top}.table th{background:#f3f4f6;font-weight:bold}.statut{display:inline-block;margin-right:5px;margin-bottom:3px}.empty{text-align:center;color:#6b7280;padding:20px}</style></head>
<body>
<h1>Performances par agent</h1>
<p>Période : {{ $dateDebut ?: 'début' }} — {{ $dateFin ?: 'aujourd’hui' }}</p>
<table class="table"><thead><tr><th>Agent</th><th>Appels passés</th><th>Aboutis</th><th>Taux</th><th>Durée moyenne</th><th>Résultats par statut</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td>{{ $row['agent'] }}</td><td>{{ $row['appels'] }}</td><td>{{ $row['aboutis'] }}</td><td>{{ number_format($row['taux'], 1, ',', ' ') }} %</td><td>{{ $row['duree'] !== null ? round($row['duree']) . ' s' : '—' }}</td><td>@foreach($row['statuts'] as $statut)<span class="statut">{{ $statut['label'] }} : {{ $statut['total'] }}</span>@endforeach</td></tr>@empty<tr><td colspan="6" class="empty">Aucune donnée disponible.</td></tr>@endforelse
</tbody></table></body></html>
