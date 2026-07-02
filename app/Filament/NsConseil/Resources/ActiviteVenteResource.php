<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\ActiviteVenteResource\Pages;
use App\Filament\NsConseil\Resources\ActiviteVenteResource\RelationManagers;
use App\Models\ActiviteVente;
use App\Models\Client;
use App\Models\Partenaire;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class ActiviteVenteResource extends Resource
{
    protected static ?string $model = ActiviteVente::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-euro';

    protected static ?string $navigationLabel = 'Activités Vente';

    protected static ?string $modelLabel = 'Activité Vente';

    protected static ?string $pluralModelLabel = 'Activités Vente';

    protected static ?string $navigationGroup = 'Activités';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['partenaire', 'consultant'])
            ->withCount('clients');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Partenaire et clients')
                    ->schema([
                        Forms\Components\Select::make('partenaire_id')
                            ->label('Partenaire')
                            ->relationship('partenaire', 'nom')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required()
                            ->helperText('Les clients rattachés au partenaire alimentent les statistiques.')
                            ->afterStateUpdated(function (Set $set, ?int $state): void {
                                $set('consultant_id', $state
                                    ? Partenaire::query()->whereKey($state)->value('conseiller_id')
                                    : null);

                                static::renseignerTotauxVente($set, $state);
                            }),

                        Forms\Components\Select::make('consultant_id')
                            ->label('Consultant')
                            ->relationship('consultant', 'nom')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Renseigne automatiquement le conseiller du partenaire si disponible.'),

                        Forms\Components\Placeholder::make('clients_lies')
                            ->label('Clients liés')
                            ->content(fn (Get $get, ?ActiviteVente $record): HtmlString => static::apercuClientsLies(
                                $get('partenaire_id') ?: $record?->partenaire_id
                            ))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Statistiques calculées depuis les clients')
                    ->schema([
                        Forms\Components\TextInput::make('nombre_ventes_total')
                            ->label('Nombre total de ventes')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Nombre de dossiers de formation avec une date de vente.'),

                        Forms\Components\DatePicker::make('derniere_vente')
                            ->label('Dernière vente')
                            ->nullable()
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Dernière date de vente parmi les clients liés.'),

                        Forms\Components\TextInput::make('ventes_2025')
                            ->label('Ventes 2025')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('ventes_2026')
                            ->label('Ventes 2026')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false),
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

                Tables\Columns\TextColumn::make('clients_count')
                    ->label('Clients liés')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('consultant.nom')
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
                Tables\Filters\SelectFilter::make('partenaire_id')
                    ->label('Partenaire')
                    ->relationship('partenaire', 'nom')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('consultant')
                    ->label('Consultant')
                    ->relationship('consultant', 'nom'),

                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Client lié')
                    ->options(fn (): array => Client::query()
                        ->orderBy('nom_tiers')
                        ->limit(100)
                        ->pluck('nom_tiers', 'id')
                        ->all())
                    ->searchable()
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['value'] ?? null, fn (Builder $q, $clientId) => $q
                            ->whereHas('clients', fn (Builder $clientQuery) => $clientQuery->whereKey($clientId)))),

                Tables\Filters\Filter::make('avec_clients')
                    ->label('Avec clients liés')
                    ->query(fn (Builder $query): Builder => $query->whereHas('clients'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('recalculer')
                    ->label('Recalculer')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(fn (ActiviteVente $record) => $record->recalculerDepuisClients())
                    ->successNotificationTitle('Statistiques recalculées depuis les clients liés'),

                Tables\Actions\Action::make('clients')
                    ->label('Clients')
                    ->icon('heroicon-o-users')
                    ->color('info')
                    ->url(fn (ActiviteVente $record): string => ClientResource::getUrl('index', [
                        'tableFilters' => [
                            'partenaire_id' => ['value' => $record->partenaire_id],
                        ],
                    ])),

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
            RelationManagers\ClientsRelationManager::class,
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

    private static function renseignerTotauxVente(Set $set, ?int $partenaireId): void
    {
        foreach (ActiviteVente::calculerDepuisClients($partenaireId) as $champ => $valeur) {
            $set($champ, $valeur);
        }
    }

    private static function apercuClientsLies(?int $partenaireId): HtmlString
    {
        if (! $partenaireId) {
            return new HtmlString('<span class="text-sm text-gray-500">Sélectionnez un partenaire pour afficher ses clients liés.</span>');
        }

        $totalClients = Client::query()
            ->where('partenaire_id', $partenaireId)
            ->count();

        if ($totalClients === 0) {
            return new HtmlString('<span class="text-sm text-gray-500">Aucun client n&#039;est rattaché à ce partenaire.</span>');
        }

        $clients = Client::query()
            ->where('partenaire_id', $partenaireId)
            ->orderBy('nom_tiers')
            ->limit(6)
            ->get(['id', 'nom_tiers', 'email', 'ref_client']);

        $totaux = ActiviteVente::calculerDepuisClients($partenaireId);
        $derniereVente = $totaux['derniere_vente']
            ? date('d/m/Y', strtotime((string) $totaux['derniere_vente']))
            : 'aucune';

        $listeClients = $clients
            ->map(fn (Client $client): string => '<li>'.e(static::libelleClient($client)).'</li>')
            ->implode('');

        if ($totalClients > $clients->count()) {
            $listeClients .= '<li>'.e(($totalClients - $clients->count()).' client(s) supplémentaire(s)').'</li>';
        }

        return new HtmlString(
            '<div class="space-y-2 text-sm">'
            .'<div><strong>'.e((string) $totalClients).'</strong> client(s) lié(s), <strong>'.e((string) $totaux['nombre_ventes_total']).'</strong> vente(s) calculée(s), dernière vente : '.e($derniereVente).'</div>'
            .'<ul class="list-disc pl-5">'.$listeClients.'</ul>'
            .'</div>'
        );
    }

    private static function libelleClient(Client $client): string
    {
        return $client->nom_tiers
            ?: $client->email
            ?: $client->ref_client
            ?: 'Client #'.$client->id;
    }
}
