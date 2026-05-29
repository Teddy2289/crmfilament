<?php

namespace App\Filament\NsConseil\Resources\ClientResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PropositionsRelationManager extends RelationManager
{
    protected static string $relationship = 'propositions';
    protected static ?string $title = 'Propositions';
    protected static ?string $icon = 'heroicon-o-document-text';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Détails de la proposition')
                ->schema([
                    Forms\Components\TextInput::make('tiers')
                        ->label('Tiers')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Select::make('etat')
                        ->label('État')
                        ->options([
                            'Lancée' => 'Lancée',
                            'En cours' => 'En cours',
                            'Planifiée' => 'Planifiée',
                            'Terminée' => 'Terminée',
                            'Annulée' => 'Annulée',
                        ])
                        ->required(),

                    Forms\Components\DatePicker::make('date_lancement')
                        ->label('Date lancement')
                        ->displayFormat('d/m/Y'),

                    Forms\Components\DatePicker::make('date_vente')
                        ->label('Date vente')
                        ->displayFormat('d/m/Y'),
                ])->columns(2),

            Forms\Components\Section::make('Formation')
                ->schema([
                    Forms\Components\TextInput::make('nb_heures_formation')
                        ->label('Heures totales')
                        ->numeric()
                        ->minValue(0),

                    Forms\Components\TextInput::make('heures_realisees')
                        ->label('Heures réalisées')
                        ->numeric()
                        ->minValue(0),

                    Forms\Components\TextInput::make('heures_restantes')
                        ->label('Heures restantes')
                        ->numeric()
                        ->minValue(0),

                    Forms\Components\TextInput::make('consultant_formateur')
                        ->label('Consultant / Formateur'),

                    Forms\Components\DatePicker::make('date_debut_formation')
                        ->label('Début formation')
                        ->displayFormat('d/m/Y'),

                    Forms\Components\DatePicker::make('date_fin_formation')
                        ->label('Fin formation')
                        ->displayFormat('d/m/Y'),

                    Forms\Components\DatePicker::make('date_certification')
                        ->label('Date certification')
                        ->displayFormat('d/m/Y'),
                ])->columns(3),

            Forms\Components\Section::make('Données supplémentaires')
                ->schema([
                    Forms\Components\KeyValue::make('extra_data')
                        ->label('Données extra')
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->collapsed(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('date_lancement', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tiers')
                    ->label('Tiers')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('etat')
                    ->label('État')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'Lancée' => 'info',
                        'En cours' => 'primary',
                        'Planifiée' => 'warning',
                        'Terminée' => 'success',
                        'Annulée' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('nb_heures_formation')
                    ->label('Heures totales')
                    ->numeric()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('heures_realisees')
                    ->label('Réalisées')
                    ->numeric()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('heures_restantes')
                    ->label('Restantes')
                    ->numeric()
                    ->alignCenter()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'success'),

                Tables\Columns\TextColumn::make('consultant_formateur')
                    ->label('Formateur')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('date_lancement')
                    ->label('Lancée le')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('date_vente')
                    ->label('Vendue le')
                    ->date('d/m/Y')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('etat')
                    ->label('État')
                    ->options([
                        'Lancée' => 'Lancée',
                        'En cours' => 'En cours',
                        'Planifiée' => 'Planifiée',
                        'Terminée' => 'Terminée',
                        'Annulée' => 'Annulée',
                    ]),

                Tables\Filters\SelectFilter::make('consultant_formateur')
                    ->label('Formateur'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Nouvelle proposition'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
