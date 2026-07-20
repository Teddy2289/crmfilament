<?php

namespace App\Filament\NsConseil\Resources;

use App\Enums\OrganizationType;
use App\Enums\ProspectStatut;
use App\Events\Mail2EnvoyeEvent;
use App\Filament\NsConseil\Resources\ProspectResource\Pages;
use App\Filament\NsConseil\Resources\ProspectResource\RelationManagers;
use App\Filament\Shared\Actions\LancerAppelsAction;
use App\Filament\Shared\Components\DuplicateWarning;
use App\Filament\Shared\Components\PhoneNumberInput;
use App\Filament\Shared\Concerns\HasCustomFieldsForm;
use App\Filament\Shared\RelationManagers\SentEmailsRelationManager;
use App\Mail\ConfirmationRdvCseMail;
use App\Mail\InvitationAgendaResponsableMail;
use App\Models\Prospect;
use App\Models\User;
use App\Support\UsesResourcePermissions;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;

class ProspectResource extends Resource
{
    use HasCustomFieldsForm;
    use UsesResourcePermissions;

    protected static ?string $model = Prospect::class;

    protected static string $permissionPrefix = 'prospects';

    protected static ?string $navigationIcon = 'heroicon-o-funnel';

    protected static ?string $navigationGroup = 'Suivi des dossiers';

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
        return $form->schema(static::applyFormFieldPermissions([
            Forms\Components\Section::make('Identification')
                ->icon('heroicon-o-building-office')
                ->schema([
                    Forms\Components\TextInput::make('nom')
                        ->label("Nom de l'entité")
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2)
                        ->live(onBlur: true),

                    Forms\Components\Select::make('type_pressenti')
                        ->label('Type pressenti')
                        ->options(OrganizationType::class)
                        ->live(),

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
                    PhoneNumberInput::make('telephone')
                        ->label('Téléphone principal')
                        ->required()
                        ->live(onBlur: true),

                    PhoneNumberInput::make('telephone_alt')
                        ->label('Téléphone alternatif')
                        ->live(onBlur: true),

                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->live(onBlur: true),

                    DuplicateWarning::make(
                        key: 'prospect_duplicate_warning',
                        modelClass: Prospect::class,
                        fields: [
                            'nom' => 'nom',
                            'telephone' => 'telephone',
                            'telephone_alt' => 'telephone_alt',
                            'email' => 'email',
                        ],
                        labelAttribute: 'nom',
                        resourceClass: self::class,
                        entityLabel: 'prospect',
                    ),

                    Forms\Components\TextInput::make('interlocuteur_nom')
                        ->label('Interlocuteur — Nom'),

                    Forms\Components\TextInput::make('interlocuteur_prenom')
                        ->label('Interlocuteur — Prénom'),

                    Forms\Components\TextInput::make('interlocuteur_fonction')
                        ->label('Fonction'),

                    PhoneNumberInput::make('interlocuteur_telephone')
                        ->label('Téléphone interlocuteur'),

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
                        ->relationship('teleprospecteur', 'nom')
                        ->getOptionLabelFromRecordUsing(fn(User $r) => "{$r->prenom} {$r->nom}")
                        ->searchable()
                        ->preload()
                        ->default(fn() => auth()->user()?->hasRoleCache('teleprospecteur') ? auth()->id() : null),

                    Forms\Components\Select::make('commercial_id')
                        ->label('Commercial (si QF)')
                        ->relationship('commercial', 'nom')
                        ->getOptionLabelFromRecordUsing(fn(User $r) => "{$r->prenom} {$r->nom}")
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->default(fn() => auth()->user()?->hasRoleCache('commercial') ? auth()->id() : null),

                    Forms\Components\DatePicker::make('date_premier_contact')
                        ->label('1er contact le')
                        ->displayFormat('d/m/Y')
                        ->default(now()),

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
                        ->visible(fn(Get $get) => $get('statut') === ProspectStatut::KO->value),
                ]),
            Forms\Components\Section::make('Dirigeant')
                ->icon('heroicon-o-user-circle')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('dirigeant_nom')
                        ->label('Nom'),
                    Forms\Components\TextInput::make('dirigeant_prenom')
                        ->label('Prénom'),
                    Forms\Components\TextInput::make('dirigeant_fonction')
                        ->label('Fonction'),
                    PhoneNumberInput::make('dirigeant_telephone')
                        ->label('Téléphone'),
                    Forms\Components\TextInput::make('dirigeant_email')
                        ->label('Email')
                        ->email(),
                ])->columns(3),

            Forms\Components\Section::make('Informations CSE')
                ->icon('heroicon-o-building-office')
                ->collapsible()
                ->collapsed()
                ->visible(fn(Get $get) => $get('type_pressenti') === OrganizationType::CSE->value)
                ->schema([
                    Forms\Components\TextInput::make('cse_secretaire_nom')->label('Secrétaire — Nom'),
                    Forms\Components\TextInput::make('cse_secretaire_prenom')->label('Secrétaire — Prénom'),
                    PhoneNumberInput::make('cse_secretaire_tel_direct')->label('Téléphone professionnel'),
                    PhoneNumberInput::make('cse_secretaire_tel_perso')->label('Téléphone personnel'),
                    Forms\Components\TextInput::make('cse_secretaire_email_pro')->label('Email professionnel')->email(),
                    Forms\Components\TextInput::make('cse_secretaire_email_perso')->label('Email personnel')->email(),

                    Forms\Components\TextInput::make('cse_tresorier_nom')->label('Trésorier — Nom'),
                    Forms\Components\TextInput::make('cse_tresorier_prenom')->label('Trésorier — Prénom'),
                    PhoneNumberInput::make('cse_tresorier_tel_direct')->label('Téléphone professionnel'),
                    PhoneNumberInput::make('cse_tresorier_tel_perso')->label('Téléphone personnel'),
                    Forms\Components\TextInput::make('cse_tresorier_email_pro')->label('Email professionnel')->email(),
                    Forms\Components\TextInput::make('cse_tresorier_email_perso')->label('Email personnel')->email(),

                    Forms\Components\TextInput::make('cse_nb_elus')->label('Nombre d\'élus')->numeric(),
                    Forms\Components\DatePicker::make('cse_date_fin_mandat')
                        ->label('Fin de mandat')
                        ->displayFormat('d/m/Y'),
                    Forms\Components\Toggle::make('cse_existence_juridique')
                        ->label('Existence juridique propre'),
                    Forms\Components\Textarea::make('cse_notes')
                        ->label('Notes CSE')
                        ->rows(2)
                        ->columnSpanFull(),
                ])->columns(3),

            Forms\Components\Section::make('Informations Syndicat')
                ->icon('heroicon-o-user-group')
                ->collapsible()
                ->collapsed()
                ->visible(fn(Get $get) => $get('type_pressenti') === OrganizationType::Syndicat->value)
                ->schema([
                    Forms\Components\TextInput::make('syndicat_appartenance')->label('Appartenance syndicale'),
                    Forms\Components\TextInput::make('syndicat_nom_organisation')->label('Nom organisation'),
                    Forms\Components\TextInput::make('syndicat_responsable_nom')->label('Responsable — Nom'),
                    Forms\Components\TextInput::make('syndicat_responsable_prenom')->label('Responsable — Prénom'),
                    Forms\Components\TextInput::make('syndicat_responsable_fonction')->label('Fonction'),
                    PhoneNumberInput::make('syndicat_tel_direct')->label('Téléphone professionnel'),
                    PhoneNumberInput::make('syndicat_tel_perso')->label('Téléphone personnel'),
                    Forms\Components\TextInput::make('syndicat_email_pro')->label('Email professionnel')->email(),
                    Forms\Components\TextInput::make('syndicat_email_perso')->label('Email personnel')->email(),
                    Forms\Components\Textarea::make('syndicat_perimetre')
                        ->label('Périmètre')
                        ->rows(2)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('syndicat_notes')
                        ->label('Notes')
                        ->rows(2)
                        ->columnSpanFull(),
                ])->columns(3),

            static::customFieldsFormSection(),
        ]));
    }

    // ─────────────────────────────────────────────────────────────────
    // TABLE
    // ─────────────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return static::listTable($table);
    }

    protected static function listTable(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns(static::applyShowFieldPermissions([
                Tables\Columns\TextColumn::make('nom')
                    ->label("Nom de l'entité")
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(
                        fn($state) => $state instanceof ProspectStatut
                            ? $state->label()
                            : ProspectStatut::tryFrom($state)?->label() ?? $state
                    )
                    ->color(
                        fn($state) => $state instanceof ProspectStatut
                            ? $state->color()
                            : ProspectStatut::tryFrom($state)?->color() ?? 'gray'
                    )
                    ->tooltip(
                        fn($state) => ($state instanceof ProspectStatut ? $state : ProspectStatut::tryFrom($state))?->description()
                    ),

                Tables\Columns\TextColumn::make('teleprospecteur.nom')
                    ->label('Commercial')
                    ->icon('heroicon-m-user')
                    ->formatStateUsing(fn($record) => $record->teleprospecteur
                        ? "{$record->teleprospecteur->prenom} {$record->teleprospecteur->nom}"
                        : '—')
                    ->searchable(query: fn(Builder $q, string $search) => $q->whereHas(
                        'teleprospecteur',
                        fn(Builder $q2) => $q2->where('nom', 'like', "%{$search}%")
                            ->orWhere('prenom', 'like', "%{$search}%")
                    ))
                    ->sortable(),

                Tables\Columns\TextColumn::make('departement')
                    ->label('Département')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('ville')
                    ->label('Ville')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('telephone')
                    ->label('Téléphone')
                    ->icon('heroicon-m-phone')
                    ->badge()
                    ->color('green')
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('rappel_planifie_at')
                    ->label('Rappel le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—')
                    ->color(fn($state) => $state && $state instanceof Carbon && $state->isPast() ? 'danger' : null),

                Tables\Columns\IconColumn::make('qf_valide')
                    ->label('QF')
                    ->tooltip('Prospect qualifié QF')
                    ->boolean()
                    ->toggleable(),
            ], [
                'teleprospecteur.nom' => 'teleprospecteur_id',
            ]))
            ->filters([
                Tables\Filters\SelectFilter::make('statut')
                    ->options(ProspectStatut::class)
                    ->label('Statut'),

                Tables\Filters\SelectFilter::make('type_pressenti')
                    ->options(OrganizationType::class)
                    ->label('Type'),

                Tables\Filters\SelectFilter::make('teleprospecteur_id')
                    ->relationship('teleprospecteur', 'nom')
                    ->label('Commercial'),

                Tables\Filters\SelectFilter::make('commercial_id')
                    ->relationship('commercial', 'nom')
                    ->label('Commercial (QF)'),

                Tables\Filters\Filter::make('a_relancer')
                    ->label('À relancer')
                    ->query(fn(Builder $q) => $q->whereIn('statut', [
                        ProspectStatut::AC->value,
                        ProspectStatut::STD_NR->value,
                        ProspectStatut::CSE_NR->value,
                    ]))
                    ->toggle(),

                Tables\Filters\Filter::make('rappels_en_retard')
                    ->label('Rappels en retard')
                    ->query(
                        fn(Builder $q) => $q
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

                Tables\Actions\Action::make('envoyer_mail1')
                    ->label('Mail 1 — CSE')
                    ->icon('heroicon-o-envelope')
                    ->color('primary')
                    ->visible(
                        fn(Prospect $record) =>
                        $record->statut === ProspectStatut::RPC
                            && $record->rendezVous()->exists()
                            && !$record->mail1_envoye
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Envoyer la confirmation de RDV au CSE ?')
                    ->modalDescription(fn(Prospect $record) => 'Destinataire : ' . ($record->interlocuteur_email ?: 'email manquant'))
                    ->action(function (Prospect $record) {
                        if (!$record->interlocuteur_email) {
                            Notification::make()->title('Email interlocuteur manquant')->danger()->send();
                            return;
                        }

                        $rdv = $record->rendezVous()->latest()->first();
                        $mailable = new ConfirmationRdvCseMail($record, $rdv);

                        Mail::to($record->interlocuteur_email)->send($mailable);
                        $mailable->logEnvoi($record, $record->interlocuteur_email);

                        $record->update([
                            'mail1_envoye'    => true,
                            'mail1_envoye_at' => now(),
                        ]);

                        Notification::make()->title('Mail 1 envoyé au CSE')->success()->send();
                    }),

                Tables\Actions\Action::make('envoyer_mail2')
                    ->label('Mail 2 — Invitation agenda')
                    ->icon('heroicon-o-calendar')
                    ->color('success')
                    ->visible(fn(Prospect $record) => $record->mail1_envoye && !$record->mail2_envoye)
                    ->disabled(fn(Prospect $record) => !$record->mail1_envoye)
                    ->requiresConfirmation()
                    ->modalHeading('Envoyer l\'invitation agenda au Responsable de Secteur ?')
                    ->action(function (Prospect $record) {
                        $rdv = $record->rendezVous()->latest()->first();

                        if (!$rdv?->commercial?->email) {
                            Notification::make()->title('Email du Responsable de Secteur manquant')->danger()->send();
                            return;
                        }

                        $mailable = new InvitationAgendaResponsableMail(
                            $record,
                            $rdv,
                            $record->fiche_recap_pdf_path ?? null,
                            $record->enregistrement_audio_path ?? null,
                        );

                        $cc = implode(', ', InvitationAgendaResponsableMail::CC_FIXES);
                        Mail::to($rdv->commercial->email)->send($mailable);
                        $mailable->logEnvoi($record, $rdv->commercial->email, $cc);

                        $record->update([
                            'mail2_envoye'    => true,
                            'mail2_envoye_at' => now(),
                        ]);

                        event(new Mail2EnvoyeEvent($record));

                        Notification::make()->title('Invitation agenda envoyée au Responsable de Secteur')->success()->send();
                    }),

                Tables\Actions\Action::make('qualifier_qf')
                    ->label('Qualifier QF')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(
                        fn(Prospect $record) => ! in_array($record->statut, [ProspectStatut::KO, ProspectStatut::QF])
                    )
                    ->action(function (Prospect $record) {
                        $manquants = self::champsManquantsQF($record);
                        if (! empty($manquants)) {
                            Notification::make()
                                ->title('Passage QF bloqué — champs manquants')
                                ->body(implode(', ', $manquants))
                                ->danger()
                                ->persistent()
                                ->send();

                            return;
                        }
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
                    ->visible(function (Prospect $record) {
                        if (! $record->est_convertible_en_partenaire) {
                            return false;
                        }
                        // CDC §6 — Seul le TL (superviseur) peut convertir
                        $user = auth()->user();

                        return $user && ($user->isSuperAdmin() || $user->isAdmin() || $user->isSuperviseur());
                    })
                    ->action(function (Prospect $record) {
                        $record->convertirEnPartenaire();
                        Notification::make()
                            ->title('Converti en partenaire')
                            ->body('Le prospect a ete archive et reste tracable depuis le partenaire.')
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
            ->headerActions([
                Tables\Actions\Action::make('switch_view')
                    ->label(session()->get('view_prospects', 'list') === 'kanban' ? 'Vue liste' : 'Vue Kanban')
                    ->icon(session()->get('view_prospects', 'list') === 'kanban' ? 'heroicon-o-list' : 'heroicon-o-squares-2x2')
                    ->color('gray')
                    ->action(function () {
                        $currentView = session()->get('view_prospects', 'list');
                        session()->put('view_prospects', $currentView === 'kanban' ? 'list' : 'kanban');
                        return redirect()->back();
                    }),
                LancerAppelsAction::make('prospects'),
            ])
            ->emptyStateHeading('Aucun prospect')
            ->emptyStateDescription('Créez votre premier prospect.');
    }

    protected static function kanbanTable(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('nom')
                    ->label("Nom de l'entité")
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(
                        fn($state) => $state instanceof ProspectStatut
                            ? $state->label()
                            : ProspectStatut::tryFrom($state)?->label() ?? $state
                    )
                    ->color(
                        fn($state) => $state instanceof ProspectStatut
                            ? $state->color()
                            : ProspectStatut::tryFrom($state)?->color() ?? 'gray'
                    )
                    ->tooltip(
                        fn($state) => ($state instanceof ProspectStatut ? $state : ProspectStatut::tryFrom($state))?->description()
                    ),

                Tables\Columns\TextColumn::make('teleprospecteur.nom')
                    ->label('Commercial')
                    ->formatStateUsing(fn($record) => $record->teleprospecteur
                        ? "{$record->teleprospecteur->prenom} {$record->teleprospecteur->nom}"
                        : '—'),

                Tables\Columns\TextColumn::make('departement')
                    ->label('Dép.')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('telephone')
                    ->label('Téléphone')
                    ->copyable()
                    ->toggleable(),
            ])
            ->groups([
                'statut',
            ])
            ->reorderable('statut')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // INFOLIST COMPLET
    // ─────────────────────────────────────────────────────────────────
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema(static::applyShowFieldPermissions([

            // ── Ligne 1 : Statut + KPIs engagement ──
            Grid::make(2)
                ->schema([
                    // ── Carte Statut / Engagement ──
                    Section::make()
                        ->schema([
                            Grid::make(1)->schema([
                                TextEntry::make('statut')
                                    ->label('Statut pipeline')
                                    ->badge()
                                    ->icon('heroicon-m-flag')
                                    ->formatStateUsing(
                                        fn($state) => $state instanceof ProspectStatut
                                            ? $state->label()
                                            : ProspectStatut::tryFrom($state)?->label() ?? $state
                                    )
                                    ->color(
                                        fn($state) => $state instanceof ProspectStatut
                                            ? $state->color()
                                            : ProspectStatut::tryFrom($state)?->color() ?? 'gray'
                                    )
                                    ->size(TextEntry\TextEntrySize::Large),

                                TextEntry::make('taux_engagement')
                                    ->label('Engagement')
                                    ->state(fn(Prospect $r) => $r->taux_engagement)
                                    ->formatStateUsing(function ($state) {
                                        $niveau = (int) $state;
                                        return str_repeat('⭐', max(0, min(5, $niveau)))
                                            . str_repeat('☆', 5 - max(0, min(5, $niveau)));
                                    })
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->extraAttributes(['class' => 'tracking-wide mt-1']),

                                TextEntry::make('statut_description')
                                    ->label('')
                                    ->state(fn(Prospect $r) => $r->statut_description)
                                    ->color('gray')
                                    ->extraAttributes(['class' => 'italic text-sm mt-2 opacity-75']),
                            ]),
                        ])
                        ->extraAttributes(['class' => 'rounded-xl shadow-sm h-full']),

                    // ── Cartes KPI (jours / rappel) ──
                    Section::make()
                        ->schema([
                            Grid::make(2)->schema([
                                TextEntry::make('jours_depuis_premier_contact')
                                    ->label('Jours depuis 1er contact')
                                    ->icon('heroicon-o-calendar-days')
                                    ->iconColor(fn(Prospect $r) => ($r->jours_depuis_premier_contact ?? 0) > 30 ? 'warning' : 'success')
                                    ->state(
                                        fn(Prospect $r) => $r->jours_depuis_premier_contact
                                            ? $r->jours_depuis_premier_contact . ' j'
                                            : '—'
                                    )
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight('bold')
                                    ->color(fn(Prospect $r) => ($r->jours_depuis_premier_contact ?? 0) > 30 ? 'warning' : 'success')
                                    ->extraAttributes(['class' => 'p-3 rounded-lg bg-gray-50 dark:bg-white/5 text-center']),

                                TextEntry::make('jours_avant_rappel')
                                    ->label('Rappel dans')
                                    ->icon('heroicon-o-bell-alert')
                                    ->iconColor(fn(Prospect $r) => $r->rappel_est_en_retard ? 'danger' : 'success')
                                    ->state(fn(Prospect $r) => match (true) {
                                        $r->rappel_planifie_at === null => '—',
                                        $r->rappel_est_en_retard => 'Retard ' . abs($r->jours_avant_rappel) . ' j',
                                        default => $r->jours_avant_rappel . ' j',
                                    })
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight('bold')
                                    ->color(fn(Prospect $r) => $r->rappel_est_en_retard ? 'danger' : 'success')
                                    ->extraAttributes(['class' => 'p-3 rounded-lg bg-gray-50 dark:bg-white/5 text-center']),
                            ])->extraAttributes(['class' => 'gap-3']),
                        ])
                        ->extraAttributes(['class' => 'rounded-xl shadow-sm h-full']),
                ])
                ->columnSpanFull(), // ← occupe toute la largeur du parent

            // ── Section 2 : Identification entreprise ──
            Section::make('Identification')
                ->icon('heroicon-o-building-office-2')
                ->collapsible()
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('nom')
                            ->label("Nom de l'entité")
                            ->weight(FontWeight::Bold)
                            ->size(TextEntry\TextEntrySize::Large)
                            ->columnSpan(2),

                        TextEntry::make('type_pressenti_label')
                            ->label('Type pressenti')
                            ->state(fn(Prospect $r) => $r->type_pressenti_label)
                            ->badge()
                            ->color(fn(Prospect $r) => OrganizationType::tryFrom($r->type_pressenti)?->color() ?? 'gray'),

                        TextEntry::make('siret')
                            ->label('SIRET')
                            ->icon('heroicon-m-document-text')
                            ->badge()
                            ->color('primary')
                            ->copyable()
                            ->copyMessage('SIRET copié !')
                            ->placeholder('—'),

                        TextEntry::make('secteur_activite')
                            ->label("Secteur d'activité")
                            ->placeholder('—'),

                        TextEntry::make('departement')
                            ->label('Département')
                            ->placeholder('—'),

                        TextEntry::make('nb_salaries')
                            ->label('Nombre de salariés')
                            ->numeric()
                            ->placeholder('—')
                            ->suffix(' salariés'),

                        TextEntry::make('chiffre_affaires')
                            ->label("Chiffre d'affaires")
                            ->money('EUR')
                            ->placeholder('—'),

                        TextEntry::make('adresse')
                            ->label('Adresse')
                            ->placeholder('—')
                            ->copyable(),

                        TextEntry::make('code_postal')
                            ->label('Code postal')
                            ->placeholder('—'),

                        TextEntry::make('ville')
                            ->label('Ville')
                            ->placeholder('—'),
                    ]),
                ]),

            // ── Section 3 : Contacts ──
            Section::make('Coordonnées & Interlocuteur')
                ->icon('heroicon-o-phone')
                ->collapsible()
                ->schema([
                    Grid::make(2)->schema([
                        // Coordonnées entité
                        Group::make([
                            TextEntry::make('telephone')
                                ->label('Téléphone principal')
                                ->copyable()
                                ->copyMessage('Numéro copié !')
                                ->placeholder('—')
                                ->icon('heroicon-m-phone')
                                ->badge()
                                ->color('green'),

                            TextEntry::make('telephone_alt')
                                ->label('Téléphone alternatif')
                                ->copyable()
                                ->placeholder('—')
                                ->icon('heroicon-m-phone')
                                ->badge()
                                ->color('green'),

                            TextEntry::make('email')
                                ->label('Email')
                                ->copyable()
                                ->copyMessage('Email copié !')
                                ->placeholder('—')
                                ->icon('heroicon-m-envelope'),
                        ])->label('Entité'),

                        // Interlocuteur
                        Group::make([
                            TextEntry::make('interlocuteur_complet')
                                ->label('Interlocuteur')
                                ->state(fn(Prospect $r) => $r->interlocuteur_complet)
                                ->weight(FontWeight::SemiBold)
                                ->placeholder('—'),

                            TextEntry::make('interlocuteur_prenom')
                                ->label('Prénom interlocuteur')
                                ->placeholder('—'),

                            TextEntry::make('interlocuteur_telephone')
                                ->label('Téléphone interlocuteur')
                                ->copyable()
                                ->placeholder('—')
                                ->icon('heroicon-m-phone')
                                ->badge()
                                ->color('success'),

                            TextEntry::make('interlocuteur_email')
                                ->label('Email interlocuteur')
                                ->copyable()
                                ->placeholder('—')
                                ->icon('heroicon-m-envelope'),
                        ])->label('Interlocuteur'),

                    ]),
                ]),

            // ── Section 4 : Pipeline & Assignation ──
            Section::make('Suivi & Attribution')
                ->icon('heroicon-o-chart-bar-square')
                ->collapsible()
                ->schema([
                    Grid::make(2)->schema([
                        Group::make([
                            TextEntry::make('commercial.nom')
                                ->label('Commercial responsable')
                                ->formatStateUsing(
                                    fn($record) => $record->commercial
                                        ? "{$record->commercial->prenom} {$record->commercial->nom}"
                                        : '—'
                                )
                                ->icon('heroicon-m-briefcase')
                                ->placeholder('—'),
                        ])->label('Qui s\'en occupe ?'),

                        Group::make([
                            TextEntry::make('date_premier_contact')
                                ->label('1er contact')
                                ->date('d/m/Y')
                                ->placeholder('Jamais contacté')
                                ->icon('heroicon-m-calendar'),

                            TextEntry::make('rappel_planifie_at')
                                ->label('Rappel prévu')
                                ->dateTime('d/m/Y à H:i')
                                ->placeholder('Aucun rappel')
                                ->icon('heroicon-m-clock')
                                ->color(fn(Prospect $r) => $r->rappel_est_en_retard ? 'danger' : null),

                            TextEntry::make('dernier_contact')
                                ->label('Dernier échange')
                                ->state(fn(Prospect $r) => $r->dernier_contact ?? 'Jamais')
                                ->icon('heroicon-m-arrow-path'),

                            TextEntry::make('created_at')
                                ->label('Date de création')
                                ->dateTime('d/m/Y à H:i')
                                ->icon('heroicon-m-plus-circle'),
                        ])->label('Historique des contacts'),
                    ]),
                ]),

            // ── Section : Emails AOPIA (Mail 1 + Mail 2) ──
            Section::make('Envois emails AOPIA')
                ->icon('heroicon-o-envelope')
                ->collapsible()
                ->schema([
                    Grid::make(4)->schema([
                        IconEntry::make('mail1_envoye')
                            ->label('Mail 1 — CSE')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),

                        TextEntry::make('mail1_envoye_at')
                            ->label('Envoyé le')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('—')
                            ->visible(fn($record) => $record->mail1_envoye),

                        IconEntry::make('mail2_envoye')
                            ->label('Mail 2 — Invitation agenda')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),

                        TextEntry::make('mail2_envoye_at')
                            ->label('Envoyé le')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('—')
                            ->visible(fn($record) => $record->mail2_envoye),
                    ]),
                ]),

            // ── Section 5 : Qualification QF ──
            Section::make('Validation QF')
                ->icon('heroicon-o-clipboard-document-check')
                ->collapsible()
                ->visible(fn(Prospect $r) => $r->statut === ProspectStatut::QF || $r->qf_valide)
                ->schema([
                    Grid::make(3)->schema([
                        IconEntry::make('qf_valide')
                            ->label('QF Validé')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-badge')
                            ->falseIcon('heroicon-o-clock')
                            ->trueColor('success')
                            ->falseColor('warning'),

                        TextEntry::make('validePar.nom')
                            ->label('Validé par')
                            ->formatStateUsing(
                                fn($record) => $record->validePar
                                    ? "{$record->validePar->prenom} {$record->validePar->nom}"
                                    : '—'
                            )
                            ->placeholder('—'),

                        TextEntry::make('qf_valide_at')
                            ->label('Validé le')
                            ->dateTime('d/m/Y à H:i')
                            ->placeholder('—'),
                    ]),
                ]),

            // ── Section 6 : Motif KO ──
            Section::make('Motif KO')
                ->icon('heroicon-o-x-circle')
                ->collapsible()
                ->visible(fn(Prospect $r) => $r->statut === ProspectStatut::KO)
                ->schema([
                    TextEntry::make('motif_ko')
                        ->label('')
                        ->columnSpanFull()
                        ->placeholder('Aucun motif enregistré')
                        ->prose(),
                ]),
            // ── Section : Dirigeant ──
            Section::make('Dirigeant')
                ->icon('heroicon-o-user-circle')
                ->collapsible()
                ->collapsed()
                ->visible(fn(Prospect $r) => $r->dirigeant_nom || $r->dirigeant_email || $r->dirigeant_telephone)
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('dirigeant_nom')->label('Nom')->placeholder('—'),
                        TextEntry::make('dirigeant_prenom')->label('Prénom')->placeholder('—'),
                        TextEntry::make('dirigeant_fonction')->label('Fonction')->placeholder('—'),
                        TextEntry::make('dirigeant_telephone')
                            ->label('Téléphone')
                            ->copyable()
                            ->placeholder('—')
                            ->icon('heroicon-m-phone'),
                        TextEntry::make('dirigeant_email')
                            ->label('Email')
                            ->copyable()
                            ->placeholder('—')
                            ->icon('heroicon-m-envelope'),
                    ]),
                ]),

            // ── Section : CSE ──
            Section::make('Informations CSE')
                ->icon('heroicon-o-building-office')
                ->collapsible()
                ->collapsed()
                ->visible(fn(Prospect $r) => $r->type_pressenti === OrganizationType::CSE->value)
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('cse_secretaire_nom')->label('Secrétaire — Nom')->placeholder('—'),
                        TextEntry::make('cse_secretaire_prenom')->label('Secrétaire — Prénom')->placeholder('—'),
                        TextEntry::make('cse_secretaire_tel_direct')
                            ->label('Téléphone professionnel')->copyable()->placeholder('—')->icon('heroicon-m-phone'),
                        TextEntry::make('cse_secretaire_tel_perso')
                            ->label('Téléphone personnel')->copyable()->placeholder('—')->icon('heroicon-m-phone'),
                        TextEntry::make('cse_secretaire_email_pro')
                            ->label('Email professionnel')->copyable()->placeholder('—')->icon('heroicon-m-envelope'),
                        TextEntry::make('cse_secretaire_email_perso')
                            ->label('Email personnel')->copyable()->placeholder('—')->icon('heroicon-m-envelope'),

                        TextEntry::make('cse_tresorier_nom')->label('Trésorier — Nom')->placeholder('—'),
                        TextEntry::make('cse_tresorier_prenom')->label('Trésorier — Prénom')->placeholder('—'),
                        TextEntry::make('cse_tresorier_tel_direct')
                            ->label('Téléphone professionnel')->copyable()->placeholder('—')->icon('heroicon-m-phone'),
                        TextEntry::make('cse_tresorier_tel_perso')
                            ->label('Téléphone personnel')->copyable()->placeholder('—')->icon('heroicon-m-phone'),
                        TextEntry::make('cse_tresorier_email_pro')
                            ->label('Email professionnel')->copyable()->placeholder('—')->icon('heroicon-m-envelope'),
                        TextEntry::make('cse_tresorier_email_perso')
                            ->label('Email personnel')->copyable()->placeholder('—')->icon('heroicon-m-envelope'),

                        TextEntry::make('cse_nb_elus')->label('Nombre d\'élus')->placeholder('—')->suffix(' élus'),
                        TextEntry::make('cse_date_fin_mandat')
                            ->label('Fin de mandat')->date('d/m/Y')->placeholder('—'),
                        IconEntry::make('cse_existence_juridique')
                            ->label('Existence juridique')
                            ->boolean(),
                        TextEntry::make('cse_notes')
                            ->label('Notes CSE')
                            ->placeholder('—')
                            ->columnSpanFull()
                            ->prose(),
                    ]),
                ]),

            // ── Section : Syndicat ──
            Section::make('Informations Syndicat')
                ->icon('heroicon-o-user-group')
                ->collapsible()
                ->collapsed()
                ->visible(fn(Prospect $r) => $r->type_pressenti === OrganizationType::Syndicat->value)
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('syndicat_appartenance')->label('Appartenance')->placeholder('—'),
                        TextEntry::make('syndicat_nom_organisation')->label('Organisation')->placeholder('—'),
                        TextEntry::make('syndicat_responsable_nom')->label('Responsable — Nom')->placeholder('—'),
                        TextEntry::make('syndicat_responsable_prenom')->label('Responsable — Prénom')->placeholder('—'),
                        TextEntry::make('syndicat_responsable_fonction')->label('Fonction')->placeholder('—'),
                        TextEntry::make('syndicat_tel_direct')
                            ->label('Téléphone professionnel')->copyable()->placeholder('—')->icon('heroicon-m-phone'),
                        TextEntry::make('syndicat_tel_perso')
                            ->label('Téléphone personnel')->copyable()->placeholder('—')->icon('heroicon-m-phone'),
                        TextEntry::make('syndicat_email_pro')
                            ->label('Email professionnel')->copyable()->placeholder('—')->icon('heroicon-m-envelope'),
                        TextEntry::make('syndicat_email_perso')
                            ->label('Email personnel')->copyable()->placeholder('—')->icon('heroicon-m-envelope'),
                        TextEntry::make('syndicat_perimetre')
                            ->label('Périmètre')->placeholder('—')->columnSpanFull()->prose(),
                        TextEntry::make('syndicat_notes')
                            ->label('Notes')->placeholder('—')->columnSpanFull()->prose(),
                    ]),
                ]),
            // ── Section 7 : Notes / Description ──
            Section::make('Notes & Historique')
                ->icon('heroicon-o-document-text')
                ->collapsible()
                ->schema([
                    TextEntry::make('description')
                        ->label('')
                        ->columnSpanFull()
                        ->placeholder('Aucune note')
                        ->prose()
                        ->html(),
                ]),

            // ── Section : Historique Interactions ──
            Section::make('Historique des interactions')
                ->icon('heroicon-o-clock')
                ->collapsible()
                ->collapsed()
                ->schema([
                    \Filament\Infolists\Components\RepeatableEntry::make('historiqueInteractions')
                        ->label('')
                        ->schema([
                            Grid::make(4)->schema([
                                TextEntry::make('date_interaction')
                                    ->label('Date')
                                    ->dateTime('d/m/Y H:i')
                                    ->weight(FontWeight::Bold),
                                TextEntry::make('type_interaction_label')
                                    ->label('Type')
                                    ->badge()
                                    ->color(fn($state) => match ($state) {
                                        'Consultation' => 'info',
                                        'Modification' => 'warning',
                                        'Appel' => 'success',
                                        'Rendez-vous' => 'primary',
                                        'Email' => 'gray',
                                        'Conversion' => 'danger',
                                        'Création' => 'success',
                                        default => 'gray',
                                    }),
                                TextEntry::make('user.name')
                                    ->label('Utilisateur')
                                    ->icon('heroicon-m-user'),
                                TextEntry::make('description')
                                    ->label('Description')
                                    ->columnSpan(4)
                                    ->placeholder('—'),
                            ]),
                        ])
                        ->hiddenLabel()
                        ->columnSpanFull()
                        ->default([]),
                ]),

            // ── Section 8 : Métadonnées ──
            Section::make('Métadonnées')
                ->icon('heroicon-o-information-circle')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('id')
                            ->label('ID')
                            ->prefix('#'),

                        TextEntry::make('created_at')
                            ->label('Créé le')
                            ->dateTime('d/m/Y à H:i'),

                        TextEntry::make('updated_at')
                            ->label('Mis à jour le')
                            ->dateTime('d/m/Y à H:i'),

                        TextEntry::make('deleted_at')
                            ->label('Supprimé le')
                            ->dateTime('d/m/Y à H:i')
                            ->placeholder('—')
                            ->visible(fn(Prospect $r) => $r->trashed()),
                    ]),
                ]),
        ], [
            'type_pressenti_label' => 'type_pressenti',
            'statut_description' => 'statut',
            'jours_depuis_premier_contact' => 'date_premier_contact',
            'jours_avant_rappel' => 'rappel_planifie_at',
            'interlocuteur_complet' => 'interlocuteur_nom',
            'dernier_contact' => 'date_premier_contact',
            'taux_engagement' => 'statut',
            'validePar.nom' => 'valide_par',
        ]));
    }
    /**
     * WF4 — Champs obligatoires pour le passage en QF (CDC §9).
     *
     * @return list<string> labels des champs manquants
     */
    public static function champsManquantsQF(Prospect $prospect): array
    {
        $regles = [
            'interlocuteur_nom' => 'Nom interlocuteur CSE',
            'interlocuteur_telephone' => 'Téléphone interlocuteur CSE',
            'interlocuteur_email' => 'Email interlocuteur CSE',
            'telephone' => 'Téléphone entreprise',
            'commercial_id' => 'Commercial assigné',
        ];

        $manquants = [];
        foreach ($regles as $champ => $label) {
            if (empty($prospect->{$champ})) {
                $manquants[] = $label;
            }
        }

        // Vérifier qu'un RDV planifié existe
        $rdvExiste = $prospect->rendezVous()
            ->whereIn('statut', [
                \App\Enums\RendezVousStatut::Planifie->value,
                \App\Enums\RendezVousStatut::Decale->value,
            ])
            ->exists();

        if (! $rdvExiste) {
            $manquants[] = 'RDV planifié (date, heure, lieu)';
        }

        return $manquants;
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AppelsRelationManager::class,
            RelationManagers\RendezVousRelationManager::class,
            RelationManagers\DocumentsRelationManager::class,
            SentEmailsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProspects::route('/'),
            'create' => Pages\CreateProspect::route('/create'),
            'edit' => Pages\EditProspect::route('/{record}/edit'),
            'view' => Pages\ViewProspect::route('/{record}'),
            'kanban' => Pages\ProspectKanban::route('/kanban'),
        ];
    }
}
