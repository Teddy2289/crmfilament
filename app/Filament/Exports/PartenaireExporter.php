<?php

namespace App\Filament\Exports;

use App\Models\Partenaire;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PartenaireExporter extends Exporter
{
    protected static ?string $model = Partenaire::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('nom')->label('Nom légal'),
            ExportColumn::make('entreprise')->label('Entreprise'),
            ExportColumn::make('nom_retenu')->label('Nom retenu'),
            ExportColumn::make('siret')->label('SIRET'),
            ExportColumn::make('type')->label('Type'),
            ExportColumn::make('statut')->label('Statut'),
            ExportColumn::make('adresse')->label('Adresse'),
            ExportColumn::make('code_postal')->label('Code Postal'),
            ExportColumn::make('ville')->label('Ville'),
            ExportColumn::make('departement')->label('Département'),
            ExportColumn::make('telephone')->label('Téléphone'),
            ExportColumn::make('email')->label('Email'),
            ExportColumn::make('secteur_activite')->label("Secteur d'activité"),
            ExportColumn::make('nb_salaries')->label('Nombre de salariés'),
            ExportColumn::make('chiffre_affaires')->label("Chiffre d'affaires"),
            ExportColumn::make('commercial.name')->label('Commercial'),
            ExportColumn::make('conseiller.nom')->label('Conseiller'),
            ExportColumn::make('entite.nom')->label('Entité commerciale'),
            ExportColumn::make('date_signature')->label('Date de signature'),
            ExportColumn::make('possibilite_permanence')->label('Possibilité permanence'),
            ExportColumn::make('replicable')->label('Réplicable'),
            ExportColumn::make('created_at')->label('Créé le'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Votre export de partenaires est terminé. ' . number_format($export->successful_rows) . ' ' . str('ligne')->plural($export->successful_rows) . ' exportée(s).';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('ligne')->plural($failedRowsCount) . ' échouée(s).';
        }

        return $body;
    }

    public function getFileName(Export $export): string
    {
        return "partenaires-{$export->created_at->format('Y-m-d-His')}.xlsx";
    }
}
