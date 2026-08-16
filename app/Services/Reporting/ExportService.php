<?php

namespace App\Services\Reporting;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ExportService
{
    /**
     * Exporte des données en CSV
     */
    public function exportCsv(array $data, string $filename): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // En-têtes
        $headers = array_keys($data[0] ?? []);
        $sheet->fromArray($headers, null, 'A1');

        // Données
        $sheet->fromArray($data, null, 'A2');

        // Écriture CSV
        $writer = new Csv($spreadsheet);
        $writer->setDelimiter(',');
        $writer->setEnclosure('"');
        $writer->setSheetIndex(0);

        $path = "exports/{$filename}.csv";
        $fullPath = storage_path("app/public/{$path}");

        $writer->save($fullPath);

        return Storage::disk('public')->url($path);
    }

    /**
     * Exporte des données en Excel (XLSX)
     */
    public function exportExcel(array $data, string $filename, string $title = 'Export'): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($title);

        // En-têtes
        $headers = array_keys($data[0] ?? []);
        $sheet->fromArray($headers, null, 'A1');

        // Style des en-têtes
        $headerStyle = $sheet->getStyle('A1:' . $this->getColumnLetter(count($headers)) . '1');
        $headerStyle->getFont()->setBold(true);
        $headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');

        // Données
        $sheet->fromArray($data, null, 'A2');

        // Auto-size des colonnes
        foreach (range('A', $this->getColumnLetter(count($headers))) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Écriture Excel
        $writer = new Xlsx($spreadsheet);
        $path = "exports/{$filename}.xlsx";
        $fullPath = storage_path("app/public/{$path}");

        $writer->save($fullPath);

        return Storage::disk('public')->url($path);
    }

    /**
     * Exporte les données de performance des téléprospecteurs
     */
    public function exportPerformanceData(Collection $users, string $startDate, string $endDate, string $format = 'excel'): string
    {
        $data = [];
        
        foreach ($users as $user) {
            $data[] = [
                'Utilisateur' => trim("{$user->prenom} {$user->nom}"),
                'Rôle' => $user->role_cache ?? 'Téléprospecteur',
                'Appels (période)' => $this->getAppelsCount($user, $startDate, $endDate),
                'CSE joints (période)' => $this->getCseJointsCount($user, $startDate, $endDate),
                'QF (période)' => $this->getQfCount($user, $startDate, $endDate),
                'Taux conversion' => $this->getConversionRate($user, $startDate, $endDate) . '%',
                'Base AC' => $this->getStatutCount($user, 'AC'),
                'STD NR' => $this->getStatutCount($user, 'STD_NR'),
                'STD Joint' => $this->getStatutCount($user, 'STD_Joint'),
                'CSE NR' => $this->getStatutCount($user, 'CSE_NR'),
                'RP' => $this->getStatutCount($user, 'RP'),
                'RPC' => $this->getStatutCount($user, 'RPC'),
                'KO' => $this->getStatutCount($user, 'KO'),
                'QF' => $this->getStatutCount($user, 'QF'),
                'Alerte' => $this->getAlertes($user),
            ];
        }

        $filename = "performance_{$startDate}_{$endDate}_" . now()->format('Ymd_His');

        return $format === 'csv' 
            ? $this->exportCsv($data, $filename)
            : $this->exportExcel($data, $filename, 'Performance Équipe');
    }

    /**
     * Exporte les KPIs de direction
     */
    public function exportDirectionKpis(array $kpis, string $format = 'excel'): string
    {
        $data = [];
        
        foreach ($kpis as $label => $value) {
            $data[] = [
                'Indicateur' => $label,
                'Valeur' => $value,
                'Date export' => now()->format('d/m/Y H:i'),
            ];
        }

        $filename = "direction_kpis_" . now()->format('Ymd_His');

        return $format === 'csv' 
            ? $this->exportCsv($data, $filename)
            : $this->exportExcel($data, $filename, 'KPIs Direction');
    }

    /**
     * Helper: Convertit un index numérique en lettre de colonne Excel
     */
    private function getColumnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = floor($index / 26);
        }
        return $letter;
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
     * Helper: Calcule le taux de conversion
     */
    private function getConversionRate($user, string $startDate, string $endDate): string
    {
        $appels = $this->getAppelsCount($user, $startDate, $endDate);
        if ($appels === 0) {
            return '0';
        }
        $joints = $this->getCseJointsCount($user, $startDate, $endDate);
        return round(($joints / $appels) * 100, 1);
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
     * Helper: Génère les alertes
     */
    private function getAlertes($user): string
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

        return $alertes ? implode(' · ', $alertes) : '—';
    }
}