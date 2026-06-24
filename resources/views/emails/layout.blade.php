<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $sujet ?? 'Email AOPIA Formation' }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { background: #1e3a5f; padding: 24px 32px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 20px; font-weight: 600; letter-spacing: .5px; }
        .body { padding: 32px; white-space: pre-line; line-height: 1.7; }
        .footer { background: #f8f8f8; padding: 16px 32px; text-align: center; font-size: 12px; color: #888; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>AOPIA Formation</h1>
        </div>
        <div class="body">
            @yield('content')
        </div>
        <div class="footer">
            AOPIA Formation — NS Conseil &bull; assistante-commerciale@ns-conseil.com
        </div>
    </div>
</body>
</html>
