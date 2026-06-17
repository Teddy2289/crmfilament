<?php

namespace App\Filament\Allopro\Resources\ArtisanResource\RelationManagers;

use App\Enums\NiveauPriorite;
use App\Enums\TicketStatut;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TicketsRelationManager extends RelationManager
{
    protected static string $relationship = 'tickets';

    protected static ?string $title = 'Tickets d\'intervention';

    protected static ?string $icon = 'heroicon-o-ticket';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('date_creation', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->weight('semibold')
                    ->url(fn ($record) => route('filament.allopro.resources.tickets.view', $record)),

                Tables\Columns\TextColumn::make('statut')
                    ->label('Statut')
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn ($state) => $state->color()),

                Tables\Columns\TextColumn::make('niveau_priorite')
                    ->label('Priorité')
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '—')
                    ->color(fn ($state) => $state?->color() ?? 'gray'),

                Tables\Columns\TextColumn::make('contactParticulier.nom')
                    ->label('Client')
                    ->formatStateUsing(fn ($state, $record) => trim($record->contactParticulier?->prenom.' '.$record->contactParticulier?->nom) ?: '—'
                    ),

                Tables\Columns\TextColumn::make('date_creation')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('date_cloture')
                    ->label('Clôturé le')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('En cours')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('statut')
                    ->options(TicketStatut::class)
                    ->native(false),

                Tables\Filters\SelectFilter::make('niveau_priorite')
                    ->options(NiveauPriorite::class)
                    ->native(false),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => route('filament.allopro.resources.tickets.view', $record)),
            ]);
    }
}
