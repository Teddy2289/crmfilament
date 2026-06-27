<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Récapitulatif hebdomadaire</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background: #f4f4f4; padding: 20px; border-radius: 5px;">
            <h1 style="color: #2c3e50; margin-top: 0;">Récapitulatif hebdomadaire - Commercial</h1>
            <p>Bonjour {{ $user->prenom }} {{ $user->nom }},</p>
            <p>Voici votre récapitulatif d'activité pour la semaine du {{ $startDate->format('d/m/Y') }} au {{ $endDate->format('d/m/Y') }} :</p>
        </div>

        <div style="background: #fff; padding: 20px; border-radius: 5px; margin-top: 20px; border: 1px solid #ddd;">
            <h2 style="color: #27ae60; border-bottom: 2px solid #27ae60; padding-bottom: 10px;">Statistiques</h2>
            
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>RDV réalisés :</strong></td>
                    <td style="padding: 10px; border-bottom: 1px solid #eee; text-align: right;">{{ $stats['rdv_realises'] }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Prospects QF :</strong></td>
                    <td style="padding: 10px; border-bottom: 1px solid #eee; text-align: right;">{{ $stats['prospects_qf'] }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Conversions Partenaire :</strong></td>
                    <td style="padding: 10px; border-bottom: 1px solid #eee; text-align: right;">{{ $stats['conversions_partenaire'] }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Partenaires actifs :</strong></td>
                    <td style="padding: 10px; border-bottom: 1px solid #eee; text-align: right;">{{ $stats['partenaires_actifs'] }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px;"><strong>Opportunités en cours :</strong></td>
                    <td style="padding: 10px; text-align: right;">{{ $stats['opportunites_en_cours'] }}</td>
                </tr>
            </table>
        </div>

        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 20px; border-left: 4px solid #27ae60;">
            <p style="margin: 0;"><strong>Excellente semaine !</strong></p>
            <p style="margin: 5px 0 0 0;">L'équipe CRM AOPIA / LIKE Formation</p>
        </div>
    </div>
</body>
</html>
