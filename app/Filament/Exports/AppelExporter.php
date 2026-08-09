<?php

namespace App\Filament\Exports;

use App\Models\Appel;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class AppelExporter extends Exporter
{
    protected static ?string $model = Appel::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('date_heure')->label('Date et heure'),
            ExportColumn::make('direction')->label('Direction'),
            ExportColumn::make('type')->label('Type'),
            ExportColumn::make('resultat')->label('Résultat'),
            ExportColumn::make('duree_secondes')->label('Durée (s)'),
            ExportColumn::make('appelable_type')->label('Entité'),
            ExportColumn::make('appelable_id')->label('Entité ID'),
            ExportColumn::make('appelable.nom')->label('Nom entité'),
            ExportColumn::make('ringover_call_id')->label('ID Ringover'),
            ExportColumn::make('ringover_agent_nom')->label('Agent Ringover'),
            ExportColumn::make('ringover_tags')->label('Tags Ringover'),
            ExportColumn::make('ringover_status_tag')->label('Statut Ringover'),
            ExportColumn::make('ringover_department_tag')->label('Département Ringover'),
            ExportColumn::make('phoning_status')->label('Statut phoning'),
            ExportColumn::make('phoning_result')->label('Résultat phoning'),
            ExportColumn::make('commentaire')->label('Commentaire'),
            ExportColumn::make('enregistrement_audio')->label('Enregistrement audio'),
            ExportColumn::make('user.email')->label('Utilisateur'),
            ExportColumn::make('created_at')->label('Créé le'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Votre export d\'appels Ringover est terminé. ' . number_format($export->successful_rows) . ' ' . str('ligne')->plural($export->successful_rows) . ' exportée(s).';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('ligne')->plural($failedRowsCount) . ' échouée(s).';
        }

        return $body;
    }

    public function getFileName(Export $export): string
    {
        return "appels-{$export->created_at->format('Y-m-d-His')}.xlsx";
    }
}
