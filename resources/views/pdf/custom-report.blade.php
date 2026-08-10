<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Rapport' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #333;
        }
        .header .date {
            color: #666;
            margin-top: 10px;
        }
        .content {
            margin: 20px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        table th {
            background-color: #f4f4f4;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
        .summary {
            background-color: #f9f9f9;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title ?? 'Rapport CRM' }}</h1>
        <div class="date">Généré le {{ now()->format('d/m/Y H:i') }}</div>
    </div>

    @if(isset($summary))
    <div class="summary">
        <h3>Résumé</h3>
        @foreach($summary as $key => $value)
            <p><strong>{{ $key }}:</strong> {{ $value }}</p>
        @endforeach
    </div>
    @endif

    <div class="content">
        {{ $slot ?? '' }}
    </div>

    <div class="footer">
        <p>Document généré automatiquement par le CRM NsConseil</p>
    </div>
</body>
</html>
