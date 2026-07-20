<?php

namespace App\Filament\Allopro\Resources;

use App\Enums\StatutOccupant;
use App\Enums\TypeLogement;
use App\Filament\Allopro\Resources\ContactParticulierResource\Pages;
use App\Models\ContactParticulier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContactParticulierResource extends Resource
{
    protected static ?string $model = ContactParticulier::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationGroup = 'AlloPro 24/24';

    protected static ?string $modelLabel = 'Contact Particulier';

    protected static ?string $pluralModelLabel = 'Contacts Particuliers';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        // Colonne Gauche : Identité & Coordonnées
                        Forms\Components\Section::make('Qualification CTI & Identité')
                            ->columnSpan(2)
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('nom')
                                            ->label('Nom')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('prenom')
                                            ->label('Prénom')
                                            ->maxLength(255),
                                    ]),
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('telephone')
                                            ->label('Téléphone (CTI)')
                                            ->tel()
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('email')
                                            ->label('Adresse Email')
                                            ->email()
                                            ->maxLength(255),
                                    ]),
                                Forms\Components\Textarea::make('adresse_complete')
                                    ->label('Adresse complète d\'intervention')
                                    ->required()
                                    ->rows(3),
                            ]),

                        // Colonne Droite : Qualification Logement
                        Forms\Components\Section::make('Typologie & Profil')
                            ->columnSpan(1)
                            ->schema([
                                Forms\Components\Select::make('type_logement')
                                    ->label('Type de Logement')
                                    ->options(collect(TypeLogement::cases())->mapWithKeys(fn($enum) => [$enum->value => $enum->label()]))
                                    ->required(),
                                Forms\Components\Select::make('statut_occupant')
                                    ->label('Statut de l\'occupant')
                                    ->options(collect(StatutOccupant::cases())->mapWithKeys(fn($enum) => [$enum->value => $enum->label()]))
                                    ->required(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nom')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('prenom')
                    ->label('Prénom')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('telephone')
                    ->label('Téléphone')
                    ->badge()
                    ->color('green')
                    ->icon('heroicon-o-phone')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('adresse_complete')
                    ->label('Adresse d\'intervention')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('type_logement')
                    ->label('Logement')
                    ->badge()
                    ->state(fn(ContactParticulier $record): string => $record->type_logement_label)
                    ->color(fn(ContactParticulier $record): string => $record->type_logement_color),
                Tables\Columns\TextColumn::make('statut_occupant')
                    ->label('Statut')
                    ->badge()
                    ->state(fn(ContactParticulier $record): string => $record->statut_occupant_label)
                    ->color(fn(ContactParticulier $record): string => $record->statut_occupant_color),
                Tables\Columns\TextColumn::make('nombre_tickets')
                    ->label('Tickets')
                    ->badge()
                    ->color('gray')
                    ->state(fn(ContactParticulier $record): int => $record->nombre_tickets),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type_logement')
                    ->label('Type de Logement')
                    ->options(collect(TypeLogement::cases())->mapWithKeys(fn($enum) => [$enum->value => $enum->label()])),
                Tables\Filters\SelectFilter::make('statut_occupant')
                    ->label('Statut Occupant')
                    ->options(collect(StatutOccupant::cases())->mapWithKeys(fn($enum) => [$enum->value => $enum->label()])),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactParticuliers::route('/'),
            'create' => Pages\CreateContactParticulier::route('/create'),
            'edit' => Pages\EditContactParticulier::route('/{record}/edit'),
        ];
    }
}
