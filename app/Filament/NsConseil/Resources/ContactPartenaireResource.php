<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\ContactPartenaireResource\Pages\ListContactPartenaires;
use App\Filament\NsConseil\Resources\ContactPartenaireResource\Pages\CreateContactPartenaire;
use App\Filament\NsConseil\Resources\ContactPartenaireResource\Pages\EditContactPartenaire;
use App\Models\ContactPartenaire;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContactPartenaireResource extends Resource
{
    protected static ?string $model = ContactPartenaire::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Contacts Partenaires';

    protected static ?string $modelLabel = 'Contact Partenaire';

    protected static ?string $pluralModelLabel = 'Contacts Partenaires';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\Select::make('partenaire_id')
                            ->relationship('partenaire', 'nom_retenu')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('civilite')
                            ->options([
                                'M.' => 'M.',
                                'Mme' => 'Mme',
                                'Dr' => 'Dr',
                                'Pr' => 'Pr',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('nom')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('prenom')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('role')
                            ->options(ContactPartenaire::ROLES)
                            ->required()
                            ->label('Rôle'),
                        Forms\Components\TextInput::make('nom_syndicat')
                            ->label('Syndicat associé')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Fonction et contact')
                    ->schema([
                        Forms\Components\TextInput::make('fonction')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('service')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('telephone_direct')
                            ->tel()
                            ->label('Téléphone direct')
                            ->maxLength(20),
                        Forms\Components\TextInput::make('telephone_mobile')
                            ->tel()
                            ->label('Téléphone mobile')
                            ->maxLength(20),
                        Forms\Components\TextInput::make('telephone_perso')
                            ->tel()
                            ->label('Téléphone personnel')
                            ->maxLength(20),
                        Forms\Components\TextInput::make('email_perso')
                            ->email()
                            ->label('Email personnel')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Informations supplémentaires')
                    ->schema([
                        Forms\Components\DatePicker::make('date_naissance'),
                        Forms\Components\Select::make('preference_contact')
                            ->options([
                                'email' => 'Email',
                                'telephone' => 'Téléphone',
                                'mobile' => 'Mobile',
                            ])
                            ->label('Préférence de contact'),
                        Forms\Components\Select::make('niveau_influence')
                            ->options([
                                1 => 'Faible',
                                2 => 'Moyen',
                                3 => 'Fort',
                                4 => 'Très fort',
                                5 => 'Décisionnaire',
                            ])
                            ->label('Niveau d\'influence'),
                        Forms\Components\Toggle::make('est_principal')
                            ->label('Contact principal'),
                        Forms\Components\Toggle::make('est_decisionnaire')
                            ->label('Décisionnaire'),
                        Forms\Components\Textarea::make('notes')
                            ->rows(3),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('partenaire.nom_retenu')
                    ->label('Partenaire')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nom_complet')
                    ->label('Nom complet')
                    ->searchable(['nom', 'prenom'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('role_label')
                    ->label('Rôle')
                    ->sortable(),
                Tables\Columns\TextColumn::make('fonction')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('telephone_principal')
                    ->label('Téléphone')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('email_principal')
                    ->label('Email')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('est_principal')
                    ->label('Principal')
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('est_decisionnaire')
                    ->label('Décisionnaire')
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('niveau_influence_label')
                    ->label('Influence')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options(ContactPartenaire::ROLES)
                    ->label('Rôle'),
                Tables\Filters\TernaryFilter::make('est_principal')
                    ->label('Contact principal'),
                Tables\Filters\TernaryFilter::make('est_decisionnaire')
                    ->label('Décisionnaire'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => ListContactPartenaires::route('/'),
            'create' => CreateContactPartenaire::route('/create'),
            'edit' => EditContactPartenaire::route('/{record}/edit'),
        ];
    }
}
