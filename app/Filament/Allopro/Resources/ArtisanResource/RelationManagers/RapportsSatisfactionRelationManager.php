<?php

namespace App\Filament\Allopro\Resources\ArtisanResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RapportsSatisfactionRelationManager extends RelationManager
{
    protected static string $relationship = 'rapportsSatisfaction';

    protected static ?string $title = 'Rapports de satisfaction (P6)';

    protected static ?string $icon = 'heroicon-o-star';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('date_appel_j1', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('ticket.reference')
                    ->label('Ticket')
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('note_nps')
                    ->label('NPS')
                    ->formatStateUsing(fn ($state) => $state.' / 10')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 8 => 'success',
                        $state >= 6 => 'warning',
                        default => 'danger',
                    })
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('statut_cloture')
                    ->label('Statut')
                    ->color(fn ($state) => match ($state) {
                        'satisfait' => 'success',
                        'suivi_qualite_requis' => 'warning',
                        'reclamation_ouverte' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('feedback_artisan')
                    ->label('Feedback transmis')
                    ->boolean(),

                Tables\Columns\TextColumn::make('verbatim_client')
                    ->label('Verbatim')
                    ->limit(60)
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('date_appel_j1')
                    ->label('Date appel J+1')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }
}
