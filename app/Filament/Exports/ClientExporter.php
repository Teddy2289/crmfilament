<?php

namespace App\Filament\Exports;

use App\Models\Client;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ClientExporter extends Exporter
{
    protected static ?string $model = Client::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('ref_client')->label('Réf. Client'),
            ExportColumn::make('civilite')->label('Civilité'),
            ExportColumn::make('nom_tiers')->label('Nom'),
            ExportColumn::make('prenom')->label('Prénom'),
            ExportColumn::make('email')->label('Email'),
            ExportColumn::make('telephone')->label('Téléphone'),
            ExportColumn::make('adresse')->label('Adresse'),
            ExportColumn::make('code_postal')->label('Code Postal'),
            ExportColumn::make('ville')->label('Ville'),
            ExportColumn::make('departement')->label('Département'),
            ExportColumn::make('date_naissance')->label('Date de naissance'),
            ExportColumn::make('entreprise')->label('Entreprise'),
            ExportColumn::make('type_tiers')->label('Type'),
            ExportColumn::make('partenaire.nom')->label('Partenaire'),
            ExportColumn::make('parrain.nom_prenom')->label('Parrain'),
            ExportColumn::make('commercial.name')->label('Commercial'),
            ExportColumn::make('etat')->label('Statut'),
            ExportColumn::make('montant_cpf')->label('Montant CPF'),
            ExportColumn::make('ne_plus_contacter')->label('Ne plus contacter'),
            ExportColumn::make('created_at')->label('Créé le'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Votre export de clients est terminé. ' . number_format($export->successful_rows) . ' ' . str('ligne')->plural($export->successful_rows) . ' exportée(s).';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('ligne')->plural($failedRowsCount) . ' échouée(s).';
        }

        return $body;
    }
}
