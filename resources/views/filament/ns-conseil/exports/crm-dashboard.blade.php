<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24px; }
        body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 10px; }
        h1 { margin: 0 0 4px; font-size: 20px; }
        h2 { margin: 18px 0 7px; font-size: 13px; color: #334155; }
        .muted { color: #64748b; }
        .grid { width: 100%; border-collapse: separate; border-spacing: 7px; margin-left: -7px; }
        .kpi { border: 1px solid #dbe3ec; background: #f8fafc; padding: 10px; }
        .kpi-label { color: #64748b; font-size: 9px; }
        .kpi-value { font-size: 18px; font-weight: bold; margin-top: 5px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { border-bottom: 1px solid #e2e8f0; padding: 6px 7px; text-align: left; }
        table.data th { background: #eef2f7; color: #475569; font-size: 9px; }
        .positive { color: #047857; } .negative { color: #b91c1c; }
    </style>
</head>
<body>
    <h1>Dashboard CRM</h1>
    <div class="muted">{{ $data['period']['label'] ?? 'Toutes les périodes' }} — généré le {{ now()->format('d/m/Y H:i') }}</div>

    <h2>Indicateurs clés</h2>
    <table class="grid"><tr>
        @foreach ($data['kpis'] ?? [] as $kpi)
            <td class="kpi" style="width:25%">
                <div class="kpi-label">{{ $kpi['label'] ?? $kpi['key'] }}</div>
                <div class="kpi-value">{{ number_format((int) ($kpi['value'] ?? 0), 0, ',', ' ') }}</div>
                @if (!empty($kpi['comparison']))
                    <div class="{{ ($kpi['comparison']['delta'] ?? 0) >= 0 ? 'positive' : 'negative' }}">{{ $kpi['comparison']['percent'] === null ? 'Nouveau volume' : (($kpi['comparison']['percent'] >= 0 ? '+' : '').$kpi['comparison']['percent'].' %') }} vs période précédente</div>
                @endif
            </td>
        @endforeach
    </tr></table>

    <h2>Pipeline actuel</h2>
    <table class="data"><thead><tr><th>Statut</th><th>Total</th></tr></thead><tbody>
        @foreach ($data['pipeline'] ?? [] as $item)
            <tr><td>{{ $item['label'] ?? '' }}</td><td>{{ number_format((int) ($item['total'] ?? 0), 0, ',', ' ') }}</td></tr>
        @endforeach
    </tbody></table>

    <h2>Évolution temporelle du pipeline</h2>
    <table class="data"><thead><tr><th>Période</th><th>Statut</th><th>Total</th></tr></thead><tbody>
        @foreach ($data['pipeline_trend'] ?? [] as $item)
            <tr><td>{{ $item['bucket'] ?? '' }}</td><td>{{ $item['label'] ?? '' }}</td><td>{{ number_format((int) ($item['total'] ?? 0), 0, ',', ' ') }}</td></tr>
        @endforeach
    </tbody></table>
</body>
</html>
