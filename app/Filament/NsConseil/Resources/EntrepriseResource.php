<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\EntrepriseResource\Pages;
use App\Models\Entreprise;
use App\Support\UsesResourcePermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EntrepriseResource extends Resource
{
    use UsesResourcePermissions;

    protected static ?string $model = Entreprise::class;

    protected static string $permissionPrefix = 'entreprises';

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Entreprises';

    protected static ?string $navigationGroup = 'Pipeline';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema(static::applyFormFieldPermissions([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('raison_sociale')
                            ->label('Raison sociale')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('siret')
                                    ->label('SIRET')
                                    ->maxLength(14)
                                    ->length(14),

                                Forms\Components\TextInput::make('siren')
                                    ->label('SIREN')
                                    ->maxLength(9)
                                    ->length(9),

                                Forms\Components\TextInput::make('numero_tva')
                                    ->label('Numéro TVA')
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('forme_juridique')
                                    ->label('Forme juridique')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('capital')
                                    ->label('Capital')
                                    ->maxLength(255)
                                    ->suffix('€'),
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Adresse et contact')
                    ->schema([
                        Forms\Components\Textarea::make('adresse')
                            ->label('Adresse')
                            ->rows(2)
                            ->maxLength(255),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('code_postal')
                                    ->label('Code postal')
                                    ->maxLength(10),

                                Forms\Components\TextInput::make('ville')
                                    ->label('Ville')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('pays')
                                    ->label('Pays')
                                    ->maxLength(255)
                                    ->default('France'),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('telephone')
                                    ->label('Téléphone')
                                    ->tel()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('site_web')
                                    ->label('Site web')
                                    ->url()
                                    ->maxLength(255),
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Activité')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('secteur_activite')
                                    ->label('Secteur d\'activité')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('effectif')
                                    ->label('Effectif')
                                    ->numeric()
                                    ->suffix('salariés'),

                                Forms\Components\TextInput::make('code_naf')
                                    ->label('Code NAF/APE')
                                    ->maxLength(10),
                            ]),

                        Forms\Components\DatePicker::make('date_creation')
                            ->label('Date de création'),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(Entreprise::query()->withCount(['partenaires', 'clients']))
            ->columns(static::applyShowFieldPermissions([
                Tables\Columns\TextColumn::make('raison_sociale')
                    ->label('Raison sociale')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('siret')
                    ->label('SIRET')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ville')
                    ->label('Ville')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('secteur_activite')
                    ->label('Secteur')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('telephone')
                    ->label('Téléphone')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('partenaires_count')
                    ->label('Partenaires')
                    ->sortable(),

                Tables\Columns\TextColumn::make('clients_count')
                    ->label('Clients')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ]))
            ->filters([
                Tables\Filters\Filter::make('secteur')
                    ->form([
                        Forms\Components\TextInput::make('secteur')
                            ->label('Secteur d\'activité'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['secteur'],
                            fn (Builder $query, string $secteur): Builder => $query->where('secteur_activite', 'like', "%{$secteur}%")
                        );
                    }),

                Tables\Filters\Filter::make('ville')
                    ->form([
                        Forms\Components\TextInput::make('ville')
                            ->label('Ville'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['ville'],
                            fn (Builder $query, string $ville): Builder => $query->where('ville', 'like', "%{$ville}%")
                        );
                    }),
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
            ->defaultSort('raison_sociale');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema(static::applyShowFieldPermissions([
                Infolists\Components\Section::make('Informations generales')
                    ->schema([
                        Infolists\Components\TextEntry::make('raison_sociale')
                            ->label('Raison sociale')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('forme_juridique')
                            ->label('Forme juridique')
                            ->placeholder('Non renseigne'),
                        Infolists\Components\TextEntry::make('siret')
                            ->label('SIRET')
                            ->placeholder('Non renseigne'),
                        Infolists\Components\TextEntry::make('siren')
                            ->label('SIREN')
                            ->placeholder('Non renseigne'),
                        Infolists\Components\TextEntry::make('numero_tva')
                            ->label('Numero TVA')
                            ->placeholder('Non renseigne'),
                        Infolists\Components\TextEntry::make('capital')
                            ->label('Capital')
                            ->placeholder('Non renseigne'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Adresse et contact')
                    ->schema([
                        Infolists\Components\TextEntry::make('adresse')
                            ->label('Adresse')
                            ->placeholder('Non renseignee')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('code_postal')
                            ->label('Code postal')
                            ->placeholder('Non renseigne'),
                        Infolists\Components\TextEntry::make('ville')
                            ->label('Ville')
                            ->placeholder('Non renseignee'),
                        Infolists\Components\TextEntry::make('pays')
                            ->label('Pays')
                            ->placeholder('Non renseigne'),
                        Infolists\Components\TextEntry::make('telephone')
                            ->label('Telephone')
                            ->placeholder('Non renseigne'),
                        Infolists\Components\TextEntry::make('email')
                            ->label('Email')
                            ->placeholder('Non renseigne')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('site_web')
                            ->label('Site web')
                            ->placeholder('Non renseigne')
                            ->url(fn (?string $state): ?string => $state),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Activite')
                    ->schema([
                        Infolists\Components\TextEntry::make('secteur_activite')
                            ->label('Secteur')
                            ->placeholder('Non renseigne'),
                        Infolists\Components\TextEntry::make('effectif')
                            ->label('Effectif')
                            ->suffix(' salaries')
                            ->placeholder('Non renseigne'),
                        Infolists\Components\TextEntry::make('code_naf')
                            ->label('Code NAF/APE')
                            ->placeholder('Non renseigne'),
                        Infolists\Components\TextEntry::make('date_creation')
                            ->label('Date de creation')
                            ->date('d/m/Y')
                            ->placeholder('Non renseignee'),
                        Infolists\Components\TextEntry::make('description')
                            ->label('Description')
                            ->placeholder('Non renseignee')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Relations')
                    ->schema([
                        Infolists\Components\TextEntry::make('partenaires_count')
                            ->label('Partenaires')
                            ->state(fn (Entreprise $record): int => $record->partenaires()->count()),
                        Infolists\Components\TextEntry::make('clients_count')
                            ->label('Clients')
                            ->state(fn (Entreprise $record): int => $record->clients()->count()),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Cree le')
                            ->dateTime('d/m/Y H:i'),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Mis a jour le')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(4),
            ]));
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
            'index' => Pages\ListEntreprises::route('/'),
            'create' => Pages\CreateEntreprise::route('/create'),
            'edit' => Pages\EditEntreprise::route('/{record}/edit'),
            'view' => Pages\ViewEntreprise::route('/{record}'),
        ];
    }
}
