<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Reporting\ExportService;
use App\Services\Reporting\ReportingEmailService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TestReportingExportCommand extends Command
{
    protected $signature = 'test:reporting-export {email}';
    protected $description = 'Teste les fonctionnalités d\'export et d\'envoi par email des reportings';

    public function handle(ExportService $exportService, ReportingEmailService $emailService): int
    {
        $email = $this->argument('email');
        
        $this->info('Test des fonctionnalités de reporting...');
        
        // Récupérer les utilisateurs actifs
        $users = User::where('actif', true)
            ->where(function ($q) {
                $q->where('role_cache', 'teleprospecteur')
                    ->orWhere('role_cache', 'commercial');
            })
            ->orderBy('nom')
            ->get();

        $this->info('Utilisateurs trouvés: ' . $users->count());

        if ($users->count() === 0) {
            $this->warn('Aucun utilisateur trouvé pour le test');
            return self::FAILURE;
        }

        // Test export CSV
        $this->info('Test export CSV...');
        try {
            $startDate = now()->startOfMonth()->format('Y-m-d');
            $endDate = now()->endOfMonth()->format('Y-m-d');
            
            $csvUrl = $exportService->exportPerformanceData($users, $startDate, $endDate, 'csv');
            $this->info('✅ Export CSV réussi: ' . $csvUrl);
        } catch (\Exception $e) {
            $this->error('❌ Export CSV échoué: ' . $e->getMessage());
            return self::FAILURE;
        }

        // Test export Excel
        $this->info('Test export Excel...');
        try {
            $excelUrl = $exportService->exportPerformanceData($users, $startDate, $endDate, 'excel');
            $this->info('✅ Export Excel réussi: ' . $excelUrl);
        } catch (\Exception $e) {
            $this->error('❌ Export Excel échoué: ' . $e->getMessage());
            return self::FAILURE;
        }

        // Test envoi par email
        $this->info('Test envoi par email...');
        try {
            $emailService->sendPerformanceReport(
                [$email],
                $users,
                $startDate,
                $endDate,
                'excel'
            );
            $this->info('✅ Email envoyé avec succès à ' . $email);
        } catch (\Exception $e) {
            $this->error('❌ Envoi email échoué: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('');
        $this->info('=== Résumé des tests ===');
        $this->info('✅ Export CSV: OK');
        $this->info('✅ Export Excel: OK');
        $this->info('✅ Envoi email: OK');
        $this->info('');
        $this->info('Période testée: ' . $startDate . ' - ' . $endDate);
        $this->info('Utilisateurs inclus: ' . $users->count());

        return self::SUCCESS;
    }
}