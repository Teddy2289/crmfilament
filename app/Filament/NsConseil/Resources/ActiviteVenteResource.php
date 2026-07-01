<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\ActiviteVenteResource\Pages;
use App\Models\ActiviteVente;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ActiviteVenteResource extends Resource
{
    protected static ?string $model = ActiviteVente::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-euro';

    protected static ?string $navigationLabel = 'Activités Vente';

    protected static ?string $modelLabel = 'Activité Vente';

    protected static ?string $pluralModelLabel = 'Activités Vente';

    protected static ?string $navigationGroup = 'Activités';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\Select::make('partenaire_id')
                            ->label('Partenaire')
                            ->relationship('partenaire', 'nom')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Partenaire associé à cette activité'),

                        Forms\Components\Select::make('consultant_id')
                            ->label('Consultant')
                            ->relationship('consultant', 'nom_complet')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Consultant associé (optionnel)'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Statistiques de ventes')
                    ->schema([
                        Forms\Components\TextInput::make('nombre_ventes_total')
                            ->label('Nombre total de ventes')
                            ->numeric()
                            ->default(0)
                            ->helperText('Nombre total de ventes réalisées'),

                        Forms\Components\DatePicker::make('derniere_vente')
                            ->label('Dernière vente')
                            ->nullable()
                            ->helperText('Date de la dernière vente'),

                        Forms\Components\TextInput::make('ventes_2025')
                            ->label('Ventes 2025')
                            ->numeric()
                            ->default(0)
                            ->helperText('Nombre de ventes en 2025'),

                        Forms\Components\TextInput::make('ventes_2026')
                            ->label('Ventes 2026')
                            ->numeric()
                            ->default(0)
                            ->helperText('Nombre de ventes en 2026'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('partenaire.nom')
                    ->label('Partenaire')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('consultant.nom_complet')
                    ->label('Consultant')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('nombre_ventes_total')
                    ->label('Total ventes')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('derniere_vente')
                    ->label('Dernière vente')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ventes_2025')
                    ->label('2025')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ventes_2026')
                    ->label('2026')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Mis à jour le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('consultant')
                    ->label('Consultant')
                    ->relationship('consultant', 'nom_complet'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('derniere_vente', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActiviteVentes::route('/'),
            'create' => Pages\CreateActiviteVente::route('/create'),
            'edit' => Pages\EditActiviteVente::route('/{record}/edit'),
        ];
    }
}
