<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\ClientResource\Actions\ImportClientsAction;
use App\Filament\NsConseil\Resources\ClientResource\Pages;
use App\Filament\NsConseil\Resources\ClientResource\RelationManagers\DocumentsRelationManager;
use App\Filament\NsConseil\Resources\ClientResource\RelationManagers\PropositionsRelationManager;
use App\Filament\NsConseil\Resources\ClientResource\RelationManagers\RendezVousRelationManager;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Clients & Formations';
    protected static ?string $navigationLabel = 'Clients';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) Client::count();
    }

    // ─────────────────────────────────────────────────────────────────
    // FORMULAIRE
    // ─────────────────────────────────────────────────────────────────
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identité')
                ->icon('heroicon-o-user')
                ->schema([
                    Forms\Components\Select::make('civilite')
                        ->label('Civilité')
                        ->options([
                            'M.'  => 'M.',
                            'Mme' => 'Mme',
                            'Mlle' => 'Mlle',
                            'Dr'  => 'Dr',
                        ]),

                    Forms\Components\TextInput::make('nom_tiers')
                        ->label('Nom')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email(),

                    Forms\Components\TextInput::make('telephone')
                        ->label('Téléphone')
                        ->tel(),

                    Forms\Components\DatePicker::make('date_naissance')
                        ->label('Date de naissance')
                        ->displayFormat('d/m/Y'),

                    Forms\Components\TextInput::make('entreprise')
                        ->label('Entreprise'),

                    Forms\Components\TextInput::make('ref_client')
                        ->label('Réf. Client')
                        ->disabled()
                        ->helperText('Généré automatiquement'),
                ])->columns(3),

            Forms\Components\Section::make('Adresse')
                ->icon('heroicon-o-map-pin')
                ->schema([
                    Forms\Components\Textarea::make('adresse')
                        ->label('Adresse')
                        ->rows(2)
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('code_postal')
                        ->label('Code postal')
                        ->maxLength(5),

                    Forms\Components\TextInput::make('ville')
                        ->label('Ville'),

                    Forms\Components\TextInput::make('departement')
                        ->label('Département')
                        ->maxLength(3),

                    Forms\Components\TextInput::make('region')
                        ->label('Région'),
                ])->columns(3),

            Forms\Components\Section::make('Formation & CPF')
                ->icon('heroicon-o-academic-cap')
                ->schema([
                    Forms\Components\Select::make('etat')
                        ->label('État')
                        ->options([
                            'prospect'  => 'Prospect',
                            'en_cours'  => 'En cours',
                            'termine'   => 'Terminé',
                            'certifie'  => 'Certifié',
                            'abandonne' => 'Abandonné',
                        ]),

                    Forms\Components\TextInput::make('montant_cpf')
                        ->label('Montant CPF (€)')
                        ->numeric()
                        ->prefix('€'),

                    Forms\Components\Toggle::make('ne_plus_contacter')
                        ->label('Ne plus contacter')
                        ->inline(false),

                    Forms\Components\TextInput::make('source_sheet')
                        ->label('Source (fichier)')
                        ->disabled(),
                ])->columns(2),

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

    // ─────────────────────────────────────────────────────────────────
    // TABLE
    // ─────────────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                // ✅ Colonnes réelles DB uniquement — pas d'accesseurs virtuels en searchable/sortable
                Tables\Columns\TextColumn::make('nom_tiers')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->formatStateUsing(fn($state, Client $record) => trim(($record->civilite ? $record->civilite . ' ' : '') . $state)),

                Tables\Columns\TextColumn::make('telephone')
                    ->label('Téléphone')
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ville')
                    ->label('Ville')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('departement')
                    ->label('Dép.')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('etat')
                    ->label('État')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'prospect'  => 'Prospect',
                        'en_cours'  => 'En cours',
                        'termine'   => 'Terminé',
                        'certifie'  => 'Certifié',
                        'abandonne' => 'Abandonné',
                        default     => $state ?? '—',
                    })
                    ->color(fn($state) => match ($state) {
                        'prospect'  => 'gray',
                        'en_cours'  => 'primary',
                        'termine'   => 'success',
                        'certifie'  => 'success',
                        'abandonne' => 'danger',
                        default     => 'gray',
                    }),

                Tables\Columns\TextColumn::make('montant_cpf')
                    ->label('CPF')
                    ->money('EUR')
                    ->sortable()
                    ->alignRight(),

                Tables\Columns\IconColumn::make('ne_plus_contacter')
                    ->label('NPC')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('etat')
                    ->label('État')
                    ->options([
                        'prospect'  => 'Prospect',
                        'en_cours'  => 'En cours',
                        'termine'   => 'Terminé',
                        'certifie'  => 'Certifié',
                        'abandonne' => 'Abandonné',
                    ]),

                Tables\Filters\SelectFilter::make('region')
                    ->label('Région')
                    ->options(
                        fn() => Client::whereNotNull('region')
                            ->where('region', '!=', '')
                            ->distinct()
                            ->orderBy('region')
                            ->pluck('region', 'region')
                            ->filter()
                            ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('departement')
                    ->label('Département')
                    ->options(
                        fn() => Client::whereNotNull('departement')
                            ->where('departement', '!=', '')
                            ->distinct()
                            ->orderBy('departement')
                            ->pluck('departement', 'departement')
                            ->filter()
                            ->toArray()
                    ),

                Tables\Filters\Filter::make('contactables')
                    ->label('Contactables')
                    ->query(
                        fn(Builder $q) => $q->where('ne_plus_contacter', false)
                            ->where(function ($q) {
                                $q->whereNotNull('email')->orWhereNotNull('telephone');
                            })
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('avec_cpf')
                    ->label('Avec CPF')
                    ->query(fn(Builder $q) => $q->whereNotNull('montant_cpf')->where('montant_cpf', '>', 0))
                    ->toggle(),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('toggle_contact')
                    ->label(fn(Client $record) => $record->ne_plus_contacter ? 'Réactiver' : 'Bloquer')
                    ->icon(fn(Client $record) => $record->ne_plus_contacter ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->color(fn(Client $record) => $record->ne_plus_contacter ? 'success' : 'danger')
                    ->action(function (Client $record) {
                        if ($record->ne_plus_contacter) {
                            $record->reactiver();
                        } else {
                            $record->marquerNePlusContacter('Bloqué manuellement');
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Aucun client')
            ->emptyStateDescription('Importez des clients depuis un fichier CSV.');
    }

    // ─────────────────────────────────────────────────────────────────
    // INFOLIST
    // ─────────────────────────────────────────────────────────────────
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Identité')
                ->schema([
                    Infolists\Components\TextEntry::make('nom_tiers')
                        ->label('Nom')
                        ->weight('bold')
                        ->formatStateUsing(fn($state, Client $record) => $record->nom_complet),
                    Infolists\Components\TextEntry::make('ref_client')
                        ->label('Référence'),
                    Infolists\Components\TextEntry::make('civilite')
                        ->label('Civilité'),
                    Infolists\Components\TextEntry::make('date_naissance')
                        ->label('Né(e) le')
                        ->date('d/m/Y'),
                    Infolists\Components\TextEntry::make('age')
                        ->label('Âge')
                        ->suffix(' ans'),
                    Infolists\Components\TextEntry::make('entreprise')
                        ->label('Entreprise'),
                ])->columns(3),

            Infolists\Components\Section::make('Coordonnées')
                ->schema([
                    Infolists\Components\TextEntry::make('telephone')
                        ->label('Téléphone')
                        ->copyable(),
                    Infolists\Components\TextEntry::make('email')
                        ->label('Email')
                        ->copyable(),
                    Infolists\Components\TextEntry::make('adresse_complete')
                        ->label('Adresse'),
                    Infolists\Components\TextEntry::make('localisation')
                        ->label('Localisation'),
                ])->columns(2),

            Infolists\Components\Section::make('Formation')
                ->schema([
                    Infolists\Components\TextEntry::make('etat')
                        ->label('État')
                        ->badge(),
                    Infolists\Components\TextEntry::make('montant_cpf')
                        ->label('Montant CPF')
                        ->money('EUR'),
                    Infolists\Components\IconEntry::make('ne_plus_contacter')
                        ->label('Ne plus contacter')
                        ->boolean(),
                    Infolists\Components\TextEntry::make('source_sheet')
                        ->label('Fichier source'),
                ])->columns(2),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            PropositionsRelationManager::class,
            DocumentsRelationManager::class,
            RendezVousRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit'   => Pages\EditClient::route('/{record}/edit'),
            'view'   => Pages\ViewClient::route('/{record}'),
        ];
    }
}