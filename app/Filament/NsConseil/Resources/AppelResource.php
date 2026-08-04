<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\Exports\AppelExporter;
use App\Filament\NsConseil\Resources\AppelResource\Pages\ListAppels;
use App\Filament\NsConseil\Resources\AppelResource\Pages\ViewAppel;
use App\Models\Appel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AppelResource extends Resource
{
    protected static ?string $model = Appel::class;

    protected static ?string $navigationIcon = 'heroicon-o-phone';

    protected static ?string $navigationLabel = 'Historique des appels';

    protected static ?string $modelLabel = 'Appel';

    protected static ?string $pluralModelLabel = 'Appels';

    protected static ?string $navigationGroup = 'AOPIA';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de l\'appel')
                    ->schema([
                        Forms\Components\DateTimePicker::make('date_heure')
                            ->label('Date et heure')
                            ->required()
                            ->seconds(false),

                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options([
                                'Appel' => 'Appel',
                                'Permanence' => 'Permanence',
                                'Presentation' => 'Présentation',
                            ])
                            ->required(),

                        Forms\Components\Select::make('resultat')
                            ->label('Résultat')
                            ->options([
                                'Realise' => 'Réalisé',
                                'NonAbouti' => 'Non abouti',
                                'Rappel' => 'Rappel',
                                'Annule' => 'Annulé',
                                'Decale' => 'Décalé',
                            ])
                            ->nullable(),

                        Forms\Components\Select::make('direction')
                            ->label('Direction')
                            ->options([
                                'inbound' => 'Entrant',
                                'outbound' => 'Sortant',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('numero_appelant')
                            ->label('Numéro appelé / entrant')
                            ->disabled(),

                        Forms\Components\TextInput::make('duree_secondes')
                            ->label('Durée (secondes)')
                            ->numeric()
                            ->nullable(),

                        Forms\Components\TextInput::make('user.nom')
                            ->label('Téléprospecteur')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Informations Ringover')
                    ->schema([
                        Forms\Components\TextInput::make('ringover_call_id')
                            ->label('ID Ringover')
                            ->disabled(),

                        Forms\Components\TextInput::make('ringover_agent_nom')
                            ->label('Agent Ringover')
                            ->disabled(),

                        Forms\Components\TextInput::make('ringover_department_tag')
                            ->label('Tag département')
                            ->disabled(),

                        Forms\Components\TextInput::make('ringover_status_tag')
                            ->label('Tag statut')
                            ->disabled(),

                        Forms\Components\TagsInput::make('ringover_tags')
                            ->label('Tags Ringover')
                            ->disabled(),

                        Forms\Components\Textarea::make('ringover_payload')
                            ->label('Payload Ringover')
                            ->rows(5)
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Statuts de phoning')
                    ->schema([
                        Forms\Components\Select::make('phoning_status')
                            ->label('Statut Phoning')
                            ->options([
                                'ok' => 'OK',
                                'ko' => 'KO',
                                'rdv' => 'RDV',
                                'supp' => 'Supprimé',
                                'cse_hz' => 'Hors zone CSE',
                            ])
                            ->nullable()
                            ->disabled(),

                        Forms\Components\Textarea::make('phoning_result')
                            ->label('Résultat Phoning')
                            ->rows(3)
                            ->nullable()
                            ->disabled(),

                        Forms\Components\Textarea::make('phoning_notes')
                            ->label('Notes phoning')
                            ->rows(4)
                            ->nullable()
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('phoning_completed_at')
                            ->label('Complété le')
                            ->seconds(false)
                            ->nullable()
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Fiche récap')
                    ->schema([
                        Forms\Components\TextInput::make('fiche_type')
                            ->label('Type de fiche')
                            ->disabled(),

                        Forms\Components\Textarea::make('fiche_data')
                            ->label('Données fiche récap')
                            ->rows(5)
                            ->disabled(),

                        Forms\Components\TextInput::make('fiche_word_path')
                            ->label('Fichier Word généré')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('fiche_word_generated_at')
                            ->label('Généré le')
                            ->seconds(false)
                            ->nullable()
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('fiche_jaune_j7_envoye_at')
                            ->label('Fiche jaune J+7 envoyée le')
                            ->seconds(false)
                            ->nullable()
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('fiche_verte_envoyee_at')
                            ->label('Fiche verte envoyée le')
                            ->seconds(false)
                            ->nullable()
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Détails')
                    ->schema([
                        Forms\Components\Textarea::make('commentaire')
                            ->label('Commentaire')
                            ->rows(4)
                            ->nullable(),

                        Forms\Components\Toggle::make('enregistrement_audio')
                            ->label('Enregistrement audio disponible')
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date_heure')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->description(fn ($record): string => $record->duree_formatee),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'Appel',
                        'info' => 'Permanence',
                        'warning' => 'Presentation',
                    ])
                    ->formatStateUsing(fn ($state) => $state->label()),

                Tables\Columns\BadgeColumn::make('resultat')
                    ->label('Résultat')
                    ->colors([
                        'success' => 'Realise',
                        'danger' => 'NonAbouti',
                        'warning' => 'Rappel',
                        'gray' => 'Annule',
                        'info' => 'Decale',
                    ])
                    ->formatStateUsing(fn ($state) => $state?->label() ?? 'N/A'),

                Tables\Columns\TextColumn::make('appelable_type')
                    ->label('Entité')
                    ->formatStateUsing(fn ($state): string => match ($state) {
                        'App\Models\Prospect' => 'Prospect',
                        'App\Models\Partenaire' => 'Partenaire',
                        'App\Models\Client' => 'Client',
                        'App\Models\Opportunite' => 'Opportunité',
                        default => $state,
                    })
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('appelable.nom')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record): string => $record->ringover_agent_nom ?? '-'),

                Tables\Columns\BadgeColumn::make('direction')
                    ->label('Direction')
                    ->formatStateUsing(fn ($state): string => match ($state) {
                        'inbound' => 'Entrant',
                        'outbound' => 'Sortant',
                        default => $state ?? '-',
                    })
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'inbound' => 'info',
                        'outbound' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\BadgeColumn::make('phoning_status')
                    ->label('Statut Phoning')
                    ->formatStateUsing(fn ($state): string => $state ?? '-')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'ok' => 'success',
                        'ko' => 'danger',
                        'rdv' => 'info',
                        'supp' => 'warning',
                        'cse_hz' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('enregistrement_audio')
                    ->label('Enregistrement')
                    ->boolean()
                    ->trueIcon('heroicon-o-microphone')
                    ->falseIcon('heroicon-o-x-mark')
                    ->tooltip(fn ($record): ?string => $record->enregistrement_audio ? 'Enregistrement disponible' : null),
            ])
            ->defaultSort('date_heure', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'Appel' => 'Appel',
                        'Permanence' => 'Permanence',
                        'Presentation' => 'Présentation',
                    ]),

                Tables\Filters\SelectFilter::make('resultat')
                    ->label('Résultat')
                    ->options([
                        'Realise' => 'Réalisé',
                        'NonAbouti' => 'Non abouti',
                        'Rappel' => 'Rappel',
                        'Annule' => 'Annulé',
                        'Decale' => 'Décalé',
                    ]),

                Tables\Filters\SelectFilter::make('direction')
                    ->label('Direction')
                    ->options([
                        'inbound' => 'Entrant',
                        'outbound' => 'Sortant',
                    ]),

                Tables\Filters\SelectFilter::make('phoning_status')
                    ->label('Statut Phoning')
                    ->options([
                        'ok' => 'OK',
                        'ko' => 'KO',
                        'rdv' => 'RDV',
                        'supp' => 'Supprimé',
                        'cse_hz' => 'Hors zone CSE',
                    ]),

                Tables\Filters\SelectFilter::make('appelable_type')
                    ->label('Entité')
                    ->options([
                        'App\Models\Prospect' => 'Prospect',
                        'App\Models\Partenaire' => 'Partenaire',
                        'App\Models\Client' => 'Client',
                        'App\Models\Opportunite' => 'Opportunité',
                    ]),

                Tables\Filters\Filter::make('has_recording')
                    ->label('Avec enregistrement')
                    ->query(fn ($query) => $query->where('enregistrement_audio', true)),
            ])
            ->headerActions([
                Tables\Actions\ExportAction::make()
                    ->exporter(AppelExporter::class)
                    ->label('Exporter mon historique Ringover')
                    ->icon('heroicon-o-arrow-down-tray'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Pas d'actions bulk pour l'instant
                ]),
            ]);
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
            'index' => ListAppels::route('/'),
            'view' => ViewAppel::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view_historique_appels') ?? false;
    }
}
