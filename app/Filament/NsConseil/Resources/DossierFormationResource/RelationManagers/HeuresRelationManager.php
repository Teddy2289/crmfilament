<?php

namespace App\Filament\NsConseil\Resources\DossierFormationResource\RelationManagers;

use App\Models\HeuresFormation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class HeuresRelationManager extends RelationManager
{
    protected static string $relationship = 'heures';

    protected static ?string $recordTitleAttribute = 'dossier_id';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Détails des heures')
                ->schema([
                    Forms\Components\TextInput::make('heures_obligatoires')
                        ->label('Heures obligatoires')
                        ->numeric()
                        ->step(0.5)
                        ->minValue(0)
                        ->required()
                        ->prefix('h')
                        ->default(0),

                    Forms\Components\TextInput::make('heures_complementaires')
                        ->label('Heures complémentaires')
                        ->numeric()
                        ->step(0.5)
                        ->minValue(0)
                        ->required()
                        ->prefix('h')
                        ->default(0),

                    Forms\Components\TextInput::make('heures_elearning')
                        ->label('Heures e-learning')
                        ->numeric()
                        ->step(0.5)
                        ->minValue(0)
                        ->required()
                        ->prefix('h')
                        ->default(0),
                ])->columns(3),

            Forms\Components\Section::make('Récapitulatif')
                ->schema([
                    Forms\Components\TextInput::make('heures_realisees')
                        ->label('Heures réalisées')
                        ->numeric()
                        ->step(0.5)
                        ->minValue(0)
                        ->required()
                        ->prefix('h')
                        ->default(0),

                    Forms\Components\TextInput::make('total_heures')
                        ->label('Total heures')
                        ->numeric()
                        ->step(0.5)
                        ->minValue(0)
                        ->required()
                        ->prefix('h')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Total = obligatoires + complémentaires + e-learning'),

                    Forms\Components\TextInput::make('heures_restantes')
                        ->label('Heures restantes')
                        ->numeric()
                        ->step(0.5)
                        ->minValue(0)
                        ->required()
                        ->prefix('h')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Restantes = total_heures - heures_realisees'),
                ])->columns(3),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('heures_obligatoires')
                    ->label('Obligatoires')
                    ->numeric(2)
                    ->suffix(' h')
                    ->sortable(),

                Tables\Columns\TextColumn::make('heures_complementaires')
                    ->label('Complémentaires')
                    ->numeric(2)
                    ->suffix(' h')
                    ->sortable(),

                Tables\Columns\TextColumn::make('heures_elearning')
                    ->label('E-learning')
                    ->numeric(2)
                    ->suffix(' h')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_heures')
                    ->label('Total')
                    ->numeric(2)
                    ->suffix(' h')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('heures_realisees')
                    ->label('Réalisées')
                    ->numeric(2)
                    ->suffix(' h')
                    ->sortable()
                    ->color(fn ($state, HeuresFormation $record) => $state > $record->total_heures ? 'danger' : 'success'
                    ),

                Tables\Columns\TextColumn::make('heures_restantes')
                    ->label('Restantes')
                    ->numeric(2)
                    ->suffix(' h')
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->filters([
                Tables\Filters\Filter::make('heures_restantes')
                    ->label('Heures restantes > 0')
                    ->query(fn ($query) => $query->where('heures_restantes', '>', 0)),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Ajouter des heures')
                    ->mutateFormDataUsing(function (array $data): array {
                        $total = ($data['heures_obligatoires'] ?? 0)
                            + ($data['heures_complementaires'] ?? 0)
                            + ($data['heures_elearning'] ?? 0);
                        $restantes = $total - ($data['heures_realisees'] ?? 0);

                        return [
                            ...$data,
                            'dossier_id' => $this->getOwnerRecord()->id,
                            'total_heures' => $total,
                            'heures_restantes' => $restantes,
                        ];
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data, $record): array {
                        $total = ($data['heures_obligatoires'] ?? 0)
                            + ($data['heures_complementaires'] ?? 0)
                            + ($data['heures_elearning'] ?? 0);
                        $restantes = $total - ($data['heures_realisees'] ?? 0);

                        return [
                            ...$data,
                            'total_heures' => $total,
                            'heures_restantes' => $restantes,
                        ];
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Aucune heure de formation enregistrée')
            ->emptyStateDescription('Ajoutez les heures de formation pour ce dossier.');
    }
}
