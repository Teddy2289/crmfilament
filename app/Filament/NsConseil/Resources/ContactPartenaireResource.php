<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\ContactPartenaireResource\Pages\ListContactPartenaires;
use App\Filament\NsConseil\Resources\ContactPartenaireResource\Pages\CreateContactPartenaire;
use App\Filament\NsConseil\Resources\ContactPartenaireResource\Pages\EditContactPartenaire;
use App\Filament\Shared\Components\PhoneNumberInput;
use App\Models\ContactPartenaire;
use App\Support\UsesResourcePermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContactPartenaireResource extends Resource
{
    use UsesResourcePermissions;

    protected static ?string $model = ContactPartenaire::class;

    protected static string $permissionPrefix = 'contact_partenaires';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Contacts Partenaires';

    protected static ?string $modelLabel = 'Contact Partenaire';

    protected static ?string $pluralModelLabel = 'Contacts Partenaires';

    protected static ?string $navigationGroup = 'Carnet d\'adresses';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\Select::make('partenaire_id')
                            ->relationship('partenaire', 'nom')
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
                            ->label('Email professionnel')
                            ->maxLength(255),
                        PhoneNumberInput::make('telephone_direct')
                            ->label('Téléphone professionnel'),
                           
                        Forms\Components\TextInput::make('email_perso')
                            ->email()
                            ->label('Email personnel')
                            ->maxLength(255),
                        PhoneNumberInput::make('telephone_perso')
                            ->label('Téléphone personnel')
                           


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
                Tables\Columns\TextColumn::make('partenaire.nom')
                    ->label('Partenaire')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nom_complet')
                    ->label('Nom complet')
                    ->searchable(['nom', 'prenom'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('fonction')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('telephone_principal')
                    ->label('Téléphone')
                    ->badge()
                    ->icon('heroicon-o-phone')
                    ->color('green')
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
                Tables\Filters\SelectFilter::make('partenaire')
                    ->relationship('partenaire', 'nom')
                    ->label('Partenaire')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('niveau_influence')
                    ->label('Niveau d\'influence')
                    ->options([
                        1 => 'Faible',
                        2 => 'Moyen',
                        3 => 'Fort',
                        4 => 'Très fort',
                        5 => 'Décisionnaire',
                    ]),
                Tables\Filters\SelectFilter::make('preference_contact')
                    ->label('Préférence de contact')
                    ->options([
                        'email' => 'Email',
                        'telephone' => 'Téléphone',
                        'mobile' => 'Mobile',
                    ]),
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
