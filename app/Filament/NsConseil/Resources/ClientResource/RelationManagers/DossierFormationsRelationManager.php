<?php

namespace App\Filament\NsConseil\Resources\ClientResource\RelationManagers;

use App\Models\DossierFormation;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DossierFormationsRelationManager extends RelationManager
{
    protected static string $relationship = 'dossierFormations';

    protected static ?string $recordTitleAttribute = 'intitule_programme';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('intitule_programme')
                    ->label('Programme')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('montant_ht')
                    ->label('Montant HT')
                    ->money('EUR')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('montant_cpf')
                    ->label('Montant CPF')
                    ->money('EUR')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('date_vente')
                    ->label('Date vente')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('statut_formation')
                    ->label('Statut')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'En attente' => 'gray',
                        'Validé' => 'primary',
                        'En cours' => 'warning',
                        'Terminé' => 'success',
                        'Annulé' => 'danger',
                        'Reporté' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('consultantFormateur.nom_complet')
                    ->label('Formateur')
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (DossierFormation $record) => route('filament.ns-conseil.resources.dossier-formations.view', ['record' => $record])
                    ),
                Tables\Actions\EditAction::make()
                    ->url(fn (DossierFormation $record) => route('filament.ns-conseil.resources.dossier-formations.edit', ['record' => $record])
                    ),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->url(fn () => route('filament.ns-conseil.resources.dossier-formations.create')
                    ),
            ]);
    }
}
