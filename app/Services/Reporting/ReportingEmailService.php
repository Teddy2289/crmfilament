<?php

namespace App\Services\Reporting;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ReportingEmailService
{
    private ExportService $exportService;

    public function __construct(ExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * Envoie un rapport de performance par email
     */
    public function sendPerformanceReport(
        array $recipients,
        \Illuminate\Support\Collection $users,
        string $startDate,
        string $endDate,
        string $format = 'excel'
    ): void {
        // Générer l'export
        $exportUrl = $this->exportService->exportPerformanceData($users, $startDate, $endDate, $format);
        
        // Convertir l'URL en chemin local
        $localPath = str_replace('https://manage.ns-conseil.com/storage/', storage_path('app/public/'), $exportUrl);
        $filename = basename($localPath);

        // Envoyer l'email
        foreach ($recipients as $recipient) {
            $emailBody = $this->getPerformanceEmailBody($startDate, $endDate, $users);
            
            Mail::send([], [], function ($message) use ($recipient, $localPath, $filename, $format, $startDate, $endDate, $emailBody) {
                $message->to($recipient)
                    ->subject("Rapport Performance Équipe ({$startDate} - {$endDate})")
                    ->html($emailBody)
                    ->attach($localPath, [
                        'as' => $filename,
                        'mime' => $format === 'csv' ? 'text/csv' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ]);
            });
        }
    }

    /**
     * Envoie un rapport KPI direction par email
     */
    public function sendDirectionKpiReport(
        array $recipients,
        array $kpis,
        string $format = 'excel'
    ): void {
        // Générer l'export
        $exportUrl = $this->exportService->exportDirectionKpis($kpis, $format);
        
        // Convertir l'URL en chemin local
        $localPath = str_replace('https://manage.ns-conseil.com/storage/', storage_path('app/public/'), $exportUrl);
        $filename = basename($localPath);

        // Envoyer l'email
        foreach ($recipients as $recipient) {
            $emailBody = $this->getKpiEmailBody($kpis);
            
            Mail::send([], [], function ($message) use ($recipient, $localPath, $filename, $format, $emailBody) {
                $message->to($recipient)
                    ->subject("Rapport KPIs Direction - " . now()->format('d/m/Y'))
                    ->html($emailBody)
                    ->attach($localPath, [
                        'as' => $filename,
                        'mime' => $format === 'csv' ? 'text/csv' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ]);
            });
        }
    }

    /**
     * Corps de l'email pour le rapport de performance
     */
    private function getPerformanceEmailBody(string $startDate, string $endDate, \Illuminate\Support\Collection $users): string
    {
        $dateNow = now()->format('d/m/Y H:i');
        $startDateFr = \Carbon\Carbon::parse($startDate)->locale('fr')->translatedFormat('d F Y');
        $endDateFr = \Carbon\Carbon::parse($endDate)->locale('fr')->translatedFormat('d F Y');
        
        // Calculer les statistiques globales
        $totalAppels = 0;
        $totalCseJoints = 0;
        $totalQf = 0;
        $totalAlertes = 0;
        $topPerformers = [];
        $alertesActives = [];
        
        foreach ($users as $user) {
            $appels = $this->getAppelsCount($user, $startDate, $endDate);
            $cseJoints = $this->getCseJointsCount($user, $startDate, $endDate);
            $qf = $this->getQfCount($user, $startDate, $endDate);
            $taux = $appels > 0 ? round(($cseJoints / $appels) * 100, 1) : 0;
            $alertes = $this->getAlertesDetails($user);
            
            $totalAppels += $appels;
            $totalCseJoints += $cseJoints;
            $totalQf += $qf;
            
            if ($alertes) {
                $totalAlertes++;
                $alertesActives[] = [
                    'user' => trim("{$user->prenom} {$user->nom}"),
                    'alertes' => $alertes
                ];
            }
            
            $topPerformers[] = [
                'user' => trim("{$user->prenom} {$user->nom}"),
                'appels' => $appels,
                'cse' => $cseJoints,
                'qf' => $qf,
                'taux' => $taux
            ];
        }
        
        // Trier par taux de conversion
        usort($topPerformers, function($a, $b) {
            return $b['taux'] <=> $a['taux'];
        });
        
        $tauxGlobal = $totalAppels > 0 ? round(($totalCseJoints / $totalAppels) * 100, 1) : 0;
        
        // Construire le tableau HTML des meilleurs performeurs
        $topPerformersHtml = '';
        $top5 = array_slice($topPerformers, 0, 5);
        foreach ($top5 as $perf) {
            $color = $perf['taux'] >= 50 ? 'green' : ($perf['taux'] >= 30 ? 'orange' : 'red');
            $topPerformersHtml .= "<tr style='border-bottom: 1px solid #e0e0e0;'>
                <td style='padding: 8px;'>{$perf['user']}</td>
                <td style='padding: 8px; text-align: center;'>{$perf['appels']}</td>
                <td style='padding: 8px; text-align: center;'>{$perf['cse']}</td>
                <td style='padding: 8px; text-align: center;'>{$perf['qf']}</td>
                <td style='padding: 8px; text-align: center; color: {$color}; font-weight: bold;'>{$perf['taux']}%</td>
            </tr>";
        }
        
        // Construire le tableau des alertes
        $alertesHtml = '';
        if (!empty($alertesActives)) {
            foreach ($alertesActives as $alerte) {
                $alertesHtml .= "<tr style='border-bottom: 1px solid #e0e0e0;'>
                    <td style='padding: 8px;'>{$alerte['user']}</td>
                    <td style='padding: 8px; color: red;'>{$alerte['alertes']}</td>
                </tr>";
            }
        } else {
            $alertesHtml = "<tr><td colspan='2' style='padding: 8px; text-align: center; color: green;'>Aucune alerte active</td></tr>";
        }
        
        return "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0; }
        .kpi-card { background: white; padding: 15px; border-radius: 8px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .kpi-value { font-size: 24px; font-weight: bold; color: #667eea; }
        .kpi-label { font-size: 12px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; background: white; }
        th { background: #667eea; color: white; padding: 10px; text-align: left; }
        td { padding: 8px; }
        .section-title { color: #667eea; font-size: 18px; font-weight: bold; margin: 25px 0 15px 0; border-bottom: 2px solid #667eea; padding-bottom: 5px; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0; text-align: center; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1 style='margin: 0;'>📊 Rapport Performance Équipe</h1>
            <p style='margin: 5px 0 0 0; opacity: 0.9;'>Période: {$startDateFr} - {$endDateFr}</p>
        </div>
        
        <div class='content'>
            <h2 class='section-title'>🎯 Synthèse Globale</h2>
            <div class='kpi-grid'>
                <div class='kpi-card'>
                    <div class='kpi-value'>{$totalAppels}</div>
                    <div class='kpi-label'>Appels Totaux</div>
                </div>
                <div class='kpi-card'>
                    <div class='kpi-value'>{$totalCseJoints}</div>
                    <div class='kpi-label'>CSE Joints</div>
                </div>
                <div class='kpi-card'>
                    <div class='kpi-value'>{$totalQf}</div>
                    <div class='kpi-label'>QF Validés</div>
                </div>
                <div class='kpi-card'>
                    <div class='kpi-value'>{$tauxGlobal}%</div>
                    <div class='kpi-label'>Taux Conversion</div>
                </div>
            </div>
            
            <h2 class='section-title'>🏆 Top 5 Performeurs</h2>
            <table>
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th style='text-align: center;'>Appels</th>
                        <th style='text-align: center;'>CSE</th>
                        <th style='text-align: center;'>QF</th>
                        <th style='text-align: center;'>Taux</th>
                    </tr>
                </thead>
                <tbody>
                    {$topPerformersHtml}
                </tbody>
            </table>
            
            <h2 class='section-title'>⚠️ Alertes Actives ({$totalAlertes})</h2>
            <table>
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Alertes</th>
                    </tr>
                </thead>
                <tbody>
                    {$alertesHtml}
                </tbody>
            </table>
            
            <h2 class='section-title'>📋 Détails Utilisateurs</h2>
            <table>
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th style='text-align: center;'>Appels</th>
                        <th style='text-align: center;'>CSE</th>
                        <th style='text-align: center;'>QF</th>
                        <th style='text-align: center;'>Taux</th>
                        <th style='text-align: center;'>AC</th>
                        <th style='text-align: center;'>RPC</th>
                    </tr>
                </thead>
                <tbody>";
        
        // Ajouter les détails de tous les utilisateurs
        foreach ($users as $user) {
            $appels = $this->getAppelsCount($user, $startDate, $endDate);
            $cseJoints = $this->getCseJointsCount($user, $startDate, $endDate);
            $qf = $this->getQfCount($user, $startDate, $endDate);
            $taux = $appels > 0 ? round(($cseJoints / $appels) * 100, 1) : 0;
            $ac = $this->getStatutCount($user, 'AC');
            $rpc = $this->getStatutCount($user, 'RPC');
            
            $tauxColor = $taux >= 50 ? 'green' : ($taux >= 30 ? 'orange' : 'red');
            
            $emailBody .= "<tr style='border-bottom: 1px solid #e0e0e0;'>
                <td style='padding: 8px; font-weight: bold;'>".trim("{$user->prenom} {$user->nom}")."</td>
                <td style='padding: 8px; text-align: center;'>{$appels}</td>
                <td style='padding: 8px; text-align: center;'>{$cseJoints}</td>
                <td style='padding: 8px; text-align: center;'>{$qf}</td>
                <td style='padding: 8px; text-align: center; color: {$tauxColor}; font-weight: bold;'>{$taux}%</td>
                <td style='padding: 8px; text-align: center;'>{$ac}</td>
                <td style='padding: 8px; text-align: center;'>{$rpc}</td>
            </tr>";
        }
        
        $emailBody .= "
                </tbody>
            </table>
            
            <div class='footer'>
                <p>📎 Fichier détaillé Excel/CSV joint à cet email</p>
                <p>Équipe NS CONSEIL CRM | {$dateNow}</p>
            </div>
        </div>
    </div>
</body>
</html>";
    }

    /**
     * Corps de l'email pour le rapport KPI
     */
    private function getKpiEmailBody(array $kpis): string
    {
        $dateNow = now()->format('d/m/Y H:i');
        $dateFr = now()->locale('fr')->translatedFormat('d F Y');
        
        // Construire le tableau des KPIs
        $kpiRows = '';
        $colorIndex = 0;
        $colors = ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe'];
        
        foreach ($kpis as $label => $value) {
            $color = $colors[$colorIndex % count($colors)];
            $kpiRows .= "<tr style='border-bottom: 1px solid #e0e0e0;'>
                <td style='padding: 12px; font-weight: bold; color: {$color};'>{$label}</td>
                <td style='padding: 12px; font-size: 18px; font-weight: bold; color: #333;'>{$value}</td>
            </tr>";
            $colorIndex++;
        }
        
        return "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
        .kpi-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin: 20px 0; }
        .kpi-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; background: white; }
        th { background: #667eea; color: white; padding: 12px; text-align: left; }
        td { padding: 12px; }
        .section-title { color: #667eea; font-size: 18px; font-weight: bold; margin: 25px 0 15px 0; border-bottom: 2px solid #667eea; padding-bottom: 5px; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0; text-align: center; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1 style='margin: 0;'>📈 Rapport KPIs Direction</h1>
            <p style='margin: 5px 0 0 0; opacity: 0.9;'>Date: {$dateFr}</p>
        </div>
        
        <div class='content'>
            <h2 class='section-title'>🎯 Indicateurs Clés de Performance</h2>
            <table>
                <thead>
                    <tr>
                        <th>Indicateur</th>
                        <th>Valeur</th>
                    </tr>
                </thead>
                <tbody>
                    {$kpiRows}
                </tbody>
            </table>
            
            <div class='footer'>
                <p>📎 Fichier détaillé Excel/CSV joint à cet email</p>
                <p>Équipe NS CONSEIL CRM | {$dateNow}</p>
            </div>
        </div>
    </div>
</body>
</html>";
    }

    /**
     * Helper: Compte les appels pour une période
     */
    private function getAppelsCount($user, string $startDate, string $endDate): int
    {
        return \App\Models\Appel::where('user_id', $user->id)
            ->where('appelable_type', \App\Models\Prospect::class)
            ->whereBetween('date_heure', [$startDate, $endDate])
            ->count();
    }

    /**
     * Helper: Compte les CSE joints pour une période
     */
    private function getCseJointsCount($user, string $startDate, string $endDate): int
    {
        return \App\Models\Appel::where('user_id', $user->id)
            ->where('appelable_type', \App\Models\Prospect::class)
            ->whereBetween('date_heure', [$startDate, $endDate])
            ->whereIn('phoning_status', ['std_joint', 'cse_ni', 'rdv', 'rapl_elu', 'rp', 'rpc'])
            ->count();
    }

    /**
     * Helper: Compte les QF pour une période
     */
    private function getQfCount($user, string $startDate, string $endDate): int
    {
        return \App\Models\Prospect::where('teleprospecteur_id', $user->id)
            ->where('statut', \App\Enums\ProspectStatut::QF->value)
            ->whereBetween('qf_valide_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * Helper: Compte les prospects par statut
     */
    private function getStatutCount($user, string $statut): int
    {
        return \App\Models\Prospect::where('teleprospecteur_id', $user->id)
            ->where('statut', \App\Enums\ProspectStatut::{$statut}->value)
            ->count();
    }

    /**
     * Helper: Génère les alertes détaillées
     */
    private function getAlertesDetails($user): string
    {
        $alertes = [];

        $dernierAppel = \App\Models\Appel::where('user_id', $user->id)
            ->where('appelable_type', \App\Models\Prospect::class)
            ->latest('date_heure')
            ->first();

        if (!$dernierAppel || $dernierAppel->date_heure->diffInDays(now()) >= 2) {
            $alertes[] = 'Sans appel 2j+';
        }

        $rpcAncien = \App\Models\Prospect::where('teleprospecteur_id', $user->id)
            ->where('statut', \App\Enums\ProspectStatut::RPC->value)
            ->where('updated_at', '<', now()->subDays(5))
            ->count();

        if ($rpcAncien > 0) {
            $alertes[] = "{$rpcAncien} RPC > 5j";
        }

        return $alertes ? implode(' · ', $alertes) : '';
    }
}