<?php
namespace App\Filament\Allopro\Resources\ArtisanResource\RelationManagers;

use App\Enums\StatutCampagneProspection;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ProspectionRelationManager extends RelationManager
{
    protected static string $relationship = 'prospection';
    protected static ?string $title = 'Fiche de prospection';
    protected static ?string $icon  = 'heroicon-o-megaphone';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('statut_campagne')
                    ->label('Statut campagne')
                    ->formatStateUsing(fn($state) => $state->label())
                    ->color(fn($state) => $state->color()),

                Tables\Columns\TextColumn::make('priorite_segment')
                    ->label('Priorité')
                    ->formatStateUsing(fn($state) => $state->label())
                    ->color(fn($state) => $state->color()),

                Tables\Columns\IconColumn::make('accord_verbal')
                    ->label('Accord verbal')
                    ->boolean(),

                Tables\Columns\TextColumn::make('date_dernier_contact')
                    ->label('Dernier contact')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Jamais'),

                Tables\Columns\TextColumn::make('date_envoi_document')
                    ->label('Document envoyé')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('teleprospecteur.nom')
                    ->label('Téléprospecteur')
                    ->formatStateUsing(fn($state, $record) =>
                        trim($record->teleprospecteur?->prenom . ' ' . $record->teleprospecteur?->nom) ?: '—'
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }
}
