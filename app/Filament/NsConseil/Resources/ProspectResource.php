<?php

namespace App\Filament\NsConseil\Resources;

use App\Enums\ProspectStatut;
use App\Enums\OrganizationType;
use App\Filament\NsConseil\Resources\ProspectResource\Pages;
use App\Filament\NsConseil\Resources\ProspectResource\RelationManagers;
use App\Models\Prospect;
use App\Models\Partenaire;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProspectResource extends Resource
{
    protected static ?string $model = Prospect::class;
    protected static ?string $navigationIcon = 'heroicon-o-funnel';
    protected static ?string $navigationGroup = 'Pipeline';
    protected static ?string $navigationLabel = 'Prospects';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return (string) Prospect::whereNotIn('statut', [
            ProspectStatut::KO->value,
            ProspectStatut::QF->value,
        ])->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    // ─────────────────────────────────────────────────────────────────
    // FORMULAIRE
    // ─────────────────────────────────────────────────────────────────
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identification')
                ->icon('heroicon-o-building-office')
                ->schema([
                    Forms\Components\TextInput::make('nom')
                        ->label("Nom de l'entité")
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),

                    Forms\Components\Select::make('type_pressenti')
                        ->label('Type pressenti')
                        ->options(OrganizationType::class),

                    Forms\Components\TextInput::make('siret')
                        ->label('SIRET')
                        ->maxLength(14)
                        ->minLength(14),

                    Forms\Components\TextInput::make('departement')
                        ->label('Département')
                        ->maxLength(3),

                    Forms\Components\TextInput::make('code_postal')
                        ->label('Code postal')
                        ->maxLength(5),

                    Forms\Components\TextInput::make('ville')->label('Ville'),

                    Forms\Components\TextInput::make('secteur_activite')
                        ->label("Secteur d'activité"),

                    Forms\Components\TextInput::make('nb_salaries')
                        ->label('Nombre de salariés')
                        ->numeric(),

                    Forms\Components\TextInput::make('chiffre_affaires')
                        ->label("Chiffre d'affaires (€)")
                        ->numeric()
                        ->prefix('€'),
                ])->columns(3),

            Forms\Components\Section::make('Contact')
                ->icon('heroicon-o-phone')
                ->schema([
                    Forms\Components\TextInput::make('telephone')
                        ->label('Téléphone principal')
                        ->tel()
                        ->required(),

                    Forms\Components\TextInput::make('telephone_alt')
                        ->label('Téléphone alt.')
                        ->tel(),

                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email(),

                    Forms\Components\TextInput::make('interlocuteur_nom')
                        ->label('Interlocuteur — Nom'),

                    Forms\Components\TextInput::make('interlocuteur_fonction')
                        ->label('Fonction'),

                    Forms\Components\TextInput::make('interlocuteur_telephone')
                        ->label('Tél. interlocuteur')
                        ->tel(),

                    Forms\Components\TextInput::make('interlocuteur_email')
                        ->label('Email interlocuteur')
                        ->email(),
                ])->columns(3),

            Forms\Components\Section::make('Pipeline et assignation')
                ->icon('heroicon-o-chart-bar')
                ->schema([
                    Forms\Components\Select::make('statut')
                        ->label('Statut')
                        ->options(ProspectStatut::class)
                        ->default(ProspectStatut::AC)
                        ->required()
                        ->native(false),

                    Forms\Components\Select::make('teleprospecteur_id')
                        ->label('Téléprospecteur')
                        ->relationship(
                            'teleprospecteur',
                            'nom'
                        )
                        ->getOptionLabelFromRecordUsing(fn (User $r) => "{$r->prenom} {$r->nom}")
                        ->searchable()
                        ->preload(),

                    Forms\Components\Select::make('commercial_id')
                        ->label('Commercial (si QF)')
                        ->relationship(
                            'commercial',
                            'nom'
                        )
                        ->getOptionLabelFromRecordUsing(fn (User $r) => "{$r->prenom} {$r->nom}")
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    Forms\Components\DatePicker::make('date_premier_contact')
                        ->label('1er contact le')
                        ->displayFormat('d/m/Y'),

                    Forms\Components\DateTimePicker::make('rappel_planifie_at')
                        ->label('Rappel planifié le')
                        ->seconds(false),
                ])->columns(3),

            Forms\Components\Section::make('Qualification')
                ->icon('heroicon-o-clipboard-document-check')
                ->schema([
                    Forms\Components\Textarea::make('description')
                        ->label('Notes de qualification')
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('motif_ko')
                        ->label('Motif KO')
                        ->rows(2)
                        ->visible(fn (Get $get) => $get('statut') === ProspectStatut::KO->value),
                ]),
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
                Tables\Columns\TextColumn::make('nom')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('departement')
                    ->label('Dép.')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('telephone')
                    ->label('Téléphone')
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof ProspectStatut
                        ? $state->label()
                        : ProspectStatut::tryFrom($state)?->label() ?? $state
                    )
                    ->color(fn ($state) => $state instanceof ProspectStatut
                        ? $state->color()
                        : ProspectStatut::tryFrom($state)?->color() ?? 'gray'
                    ),

                Tables\Columns\TextColumn::make('teleprospecteur.nom')
                    ->label('Téléprospecteur')
                    ->formatStateUsing(fn ($record) => $record->teleprospecteur
                        ? "{$record->teleprospecteur->prenom} {$record->teleprospecteur->nom}"
                        : '—'),

                Tables\Columns\TextColumn::make('rappel_planifie_at')
                    ->label('Rappel le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->color(fn ($state) => $state && $state instanceof \Carbon\Carbon && $state->isPast() ? 'danger' : null),

                Tables\Columns\IconColumn::make('qf_valide')
                    ->label('QF')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('statut')
                    ->options(ProspectStatut::class)
                    ->label('Statut'),

                Tables\Filters\SelectFilter::make('type_pressenti')
                    ->options(OrganizationType::class)
                    ->label('Type'),

                Tables\Filters\SelectFilter::make('teleprospecteur_id')
                    ->relationship('teleprospecteur', 'nom')
                    ->label('Téléprospecteur'),

                Tables\Filters\Filter::make('a_relancer')
                    ->label('À relancer')
                    ->query(fn (Builder $q) => $q->whereIn('statut', [
                        ProspectStatut::AC->value,
                        ProspectStatut::STD_NR->value,
                        ProspectStatut::CSE_NR->value,
                    ]))
                    ->toggle(),

                Tables\Filters\Filter::make('rappels_en_retard')
                    ->label('Rappels en retard')
                    ->query(fn (Builder $q) => $q
                        ->whereNotNull('rappel_planifie_at')
                        ->where('rappel_planifie_at', '<', now())
                        ->whereNotIn('statut', [
                            ProspectStatut::KO->value,
                            ProspectStatut::QF->value,
                        ])
                    )
                    ->toggle(),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('qualifier_qf')
                    ->label('Qualifier QF')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Prospect $record) =>
                        !in_array($record->statut, [ProspectStatut::KO, ProspectStatut::QF])
                    )
                    ->action(function (Prospect $record) {
                        $record->qualifier();
                        Notification::make()
                            ->title('Prospect qualifié QF')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('convertir_partenaire')
                    ->label('→ Partenaire')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('success')
                    ->visible(fn (Prospect $record) => $record->statut === ProspectStatut::QF)
                    ->action(function (Prospect $record) {
                        $record->convertirEnPartenaire();
                        Notification::make()
                            ->title('Converti en partenaire ✓')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Aucun prospect')
            ->emptyStateDescription('Créez votre premier prospect.');
    }

    // ─────────────────────────────────────────────────────────────────
    // INFOLIST
    // ─────────────────────────────────────────────────────────────────
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Identification')
                ->schema([
                    Infolists\Components\TextEntry::make('nom')->weight('bold'),
                    Infolists\Components\TextEntry::make('type_pressenti')->label('Type pressenti')->badge(),
                    Infolists\Components\TextEntry::make('statut')
                        ->label('Statut')
                        ->badge()
                        ->formatStateUsing(fn ($state) => $state instanceof ProspectStatut
                            ? $state->label()
                            : $state
                        ),
                    Infolists\Components\TextEntry::make('siret')->label('SIRET')->copyable(),
                    Infolists\Components\TextEntry::make('departement')->label('Département'),
                    Infolists\Components\TextEntry::make('secteur_activite')->label('Secteur'),
                    Infolists\Components\TextEntry::make('nb_salaries')->label('Salariés'),
                ])->columns(3),

            Infolists\Components\Section::make('Contacts')
                ->schema([
                    Infolists\Components\TextEntry::make('telephone')->copyable(),
                    Infolists\Components\TextEntry::make('email')->copyable(),
                    Infolists\Components\TextEntry::make('interlocuteur_nom')->label('Interlocuteur'),
                    Infolists\Components\TextEntry::make('interlocuteur_fonction')->label('Fonction'),
                ])->columns(2),

            Infolists\Components\Section::make('Assignation')
                ->schema([
                    Infolists\Components\TextEntry::make('teleprospecteur.nom')
                        ->label('Téléprospecteur')
                        ->formatStateUsing(fn ($r) => $r->teleprospecteur
                            ? "{$r->teleprospecteur->prenom} {$r->teleprospecteur->nom}"
                            : '—'),
                    Infolists\Components\TextEntry::make('commercial.nom')
                        ->label('Commercial')
                        ->formatStateUsing(fn ($r) => $r->commercial
                            ? "{$r->commercial->prenom} {$r->commercial->nom}"
                            : '—'),
                    Infolists\Components\TextEntry::make('rappel_planifie_at')
                        ->label('Rappel le')
                        ->dateTime('d/m/Y H:i'),
                    Infolists\Components\IconEntry::make('qf_valide')->label('QF Validé')->boolean(),
                ])->columns(2),

            Infolists\Components\Section::make('Notes')
                ->schema([
                    Infolists\Components\TextEntry::make('description')
                        ->label('')
                        ->columnSpanFull()
                        ->html(),
                ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AppelsRelationManager::class,
            RelationManagers\RendezVousRelationManager::class,
            RelationManagers\DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProspects::route('/'),
            'create' => Pages\CreateProspect::route('/create'),
            'edit' => Pages\EditProspect::route('/{record}/edit'),
            'view' => Pages\ViewProspect::route('/{record}'),
        ];
    }
}
