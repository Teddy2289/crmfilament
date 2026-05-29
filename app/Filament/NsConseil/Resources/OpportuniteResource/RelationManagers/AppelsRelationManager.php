<?php

namespace App\Filament\NsConseil\Resources\OpportuniteResource\RelationManagers;

use App\Enums\EventType;
use App\Enums\EventResult;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AppelsRelationManager extends RelationManager
{
    protected static string $relationship = 'appels';
    protected static ?string $title = 'Appels';
    protected static ?string $icon = 'heroicon-o-phone';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('type')
                ->label('Type')
                ->options(EventType::class)
                ->required(),

            Forms\Components\Select::make('resultat')
                ->label('Résultat')
                ->options(EventResult::class)
                ->required(),

            Forms\Components\DateTimePicker::make('date_heure')
                ->label('Date et heure')
                ->required()
                ->default(now()),

            Forms\Components\TextInput::make('duree_secondes')
                ->label('Durée (secondes)')
                ->numeric(),

            Forms\Components\Textarea::make('commentaire')
                ->label('Commentaire')
                ->rows(3)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('date_heure', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('date_heure')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof EventType
                        ? $state->label()
                        : $state
                    )
                    ->color(fn ($state) => match (true) {
                        $state instanceof EventType && $state === EventType::Appel => 'primary',
                        $state instanceof EventType && $state === EventType::Permanence => 'info',
                        $state instanceof EventType && $state === EventType::Presentation => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('resultat')
                    ->label('Résultat')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof EventResult
                        ? $state->label()
                        : $state
                    )
                    ->color(fn ($state) => match (true) {
                        $state instanceof EventResult && $state === EventResult::Realise => 'success',
                        $state instanceof EventResult && in_array($state, [EventResult::Annule, EventResult::NonAbouti]) => 'danger',
                        $state instanceof EventResult && in_array($state, [EventResult::Decale, EventResult::Rappel]) => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('duree_secondes')
                    ->label('Durée')
                    ->formatStateUsing(fn ($state) => $state
                        ? floor($state / 60) . 'min ' . ($state % 60) . 's'
                        : '—')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('user.nom')
                    ->label('Par')
                    ->formatStateUsing(fn ($record) => $record->user
                        ? "{$record->user->prenom} {$record->user->nom}"
                        : '—'),

                Tables\Columns\TextColumn::make('commentaire')
                    ->limit(60)
                    ->tooltip(fn ($state) => $state),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Enregistrer un appel')
                    ->mutateFormDataUsing(fn (array $data) => array_merge($data, [
                        'user_id' => auth()->id(),
                    ])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
