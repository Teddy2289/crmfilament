<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\ActivitePermanenceResource\Pages;
use App\Models\ActivitePermanence;
use App\Support\UsesResourcePermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ActivitePermanenceResource extends Resource
{
    use UsesResourcePermissions;

    protected static ?string $model = ActivitePermanence::class;

    protected static string $permissionPrefix = 'activite_permanences';

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Activités Permanence';

    protected static ?string $modelLabel = 'Activité Permanence';

    protected static ?string $pluralModelLabel = 'Activités Permanence';

    protected static ?string $navigationGroup = 'Activités';

    protected static ?int $navigationSort = 1;

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
                            ->relationship('consultant', 'nom')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Consultant associé (optionnel)'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Statistiques')
                    ->schema([
                        Forms\Components\DatePicker::make('derniere_permanence')
                            ->label('Dernière permanence')
                            ->nullable()
                            ->helperText('Date de la dernière permanence'),

                        Forms\Components\TextInput::make('nbre_2025')
                            ->label('Nombre 2025')
                            ->numeric()
                            ->default(0)
                            ->helperText('Nombre de permanences en 2025'),

                        Forms\Components\TextInput::make('nbre_2026')
                            ->label('Nombre 2026')
                            ->numeric()
                            ->default(0)
                            ->helperText('Nombre de permanences en 2026'),

                        Forms\Components\TextInput::make('prc_2026')
                            ->label('Pourcentage 2026')
                            ->numeric()
                            ->default(0)
                            ->suffix('%')
                            ->helperText('Pourcentage de permanences en 2026'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('RDV')
                    ->schema([
                        Forms\Components\TextInput::make('rdv_physique')
                            ->label('RDV physiques')
                            ->numeric()
                            ->default(0)
                            ->helperText('Nombre de rendez-vous physiques'),

                        Forms\Components\TextInput::make('rdv_telephonique')
                            ->label('RDV téléphoniques')
                            ->numeric()
                            ->default(0)
                            ->helperText('Nombre de rendez-vous téléphoniques'),
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

                Tables\Columns\TextColumn::make('consultant.nom')
                    ->label('Consultant')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('derniere_permanence')
                    ->label('Dernière permanence')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('nbre_2025')
                    ->label('2025')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('nbre_2026')
                    ->label('2026')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('prc_2026')
                    ->label('% 2026')
                    ->numeric()
                    ->suffix('%')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('rdv_physique')
                    ->label('RDV physiques')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('rdv_telephonique')
                    ->label('RDV téléphoniques')
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
                    ->relationship('consultant', 'nom'),
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
            ->defaultSort('derniere_permanence', 'desc');
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
            'index' => Pages\ListActivitePermanences::route('/'),
            'create' => Pages\CreateActivitePermanence::route('/create'),
            'edit' => Pages\EditActivitePermanence::route('/{record}/edit'),
        ];
    }
}
