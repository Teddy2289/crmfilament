<?php

namespace App\Filament\Exports;

use App\Models\Prospect;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ProspectExporter extends Exporter
{
    protected static ?string $model = Prospect::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('nom')->label('Nom'),
            ExportColumn::make('type_pressenti')->label('Type pressenti'),
            ExportColumn::make('siret')->label('SIRET'),
            ExportColumn::make('departement')->label('Département'),
            ExportColumn::make('ville')->label('Ville'),
            ExportColumn::make('telephone')->label('Téléphone'),
            ExportColumn::make('email')->label('Email'),
            ExportColumn::make('statut')->label('Statut'),
            ExportColumn::make('interlocuteur_nom')->label('Interlocuteur'),
            ExportColumn::make('interlocuteur_telephone')->label('Téléphone interlocuteur'),
            ExportColumn::make('interlocuteur_email')->label('Email interlocuteur'),
            ExportColumn::make('teleprospecteur.name')->label('Téléprospecteur'),
            ExportColumn::make('commercial.name')->label('Commercial'),
            ExportColumn::make('validePar.name')->label('Validé par'),
            ExportColumn::make('date_premier_contact')->label('Date 1er contact'),
            ExportColumn::make('date_qualification')->label('Date qualification'),
            ExportColumn::make('rappel_planifie_at')->label('Rappel planifié'),
            ExportColumn::make('created_at')->label('Créé le'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Votre export de prospects est terminé. ' . number_format($export->successful_rows) . ' ' . str('ligne')->plural($export->successful_rows) . ' exportée(s).';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('ligne')->plural($failedRowsCount) . ' échouée(s).';
        }

        return $body;
    }

    public function getFileName(Export $export): string
    {
        return "prospects-{$export->created_at->format('Y-m-d-His')}.xlsx";
    }
}
