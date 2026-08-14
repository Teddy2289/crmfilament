<?php

namespace App\Filament\NsConseil\Widgets;

use App\Enums\ProspectStatut;
use App\Models\Prospect;
use App\Models\User;
use App\Traits\WithCommonEagerLoading;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TeamLeaderRappelsWidget extends BaseWidget
{
    use WithCommonEagerLoading;

    protected static ?string $heading = '📞 Rappels téléprospecteurs';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user
            && ($user->hasRoleCache(User::ROLE_SUPERVISEUR)
                || $user->hasRoleCache(User::ROLE_ADMIN)
                || $user->isSuperAdmin());
    }

    public static function queryForLeader(User $user)
    {
        return Prospect::query()
            ->whereNotNull('rappel_planifie_at')
            ->whereNotIn('statut', [
                ProspectStatut::KO->value,
                ProspectStatut::QF->value,
            ])
            ->when(
                $user->hasRoleCache(User::ROLE_SUPERVISEUR) || $user->hasRoleCache(User::ROLE_ADMIN) || $user->isSuperAdmin(),
                fn ($query) => $query
                    ->with(['teleprospecteur:id,nom,prenom,email'])
            )
            ->orderBy('rappel_planifie_at', 'asc');
    }

    public function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->query(
                $this->loadCommonProspectRelations(
                    self::queryForLeader($user)
                )
            )
            ->columns([
                Tables\Columns\TextColumn::make('teleprospecteur.nom')
                    ->label('Téléprospecteur')
                    ->formatStateUsing(fn ($state, $record) => $record->teleprospecteur
                        ? trim("{$record->teleprospecteur->prenom} {$record->teleprospecteur->nom}")
                        : '—')
                    ->searchable(),

                Tables\Columns\TextColumn::make('nom')
                    ->label('Entité')
                    ->searchable(),

                Tables\Columns\TextColumn::make('telephone')
                    ->label('Téléphone')
                    ->copyable(),

                Tables\Columns\TextColumn::make('rappel_planifie_at')
                    ->label('Rappel')
                    ->sortable()
                    ->dateTime('d/m/Y H:i')
                    ->color(fn (Prospect $record) => match (true) {
                        $record->rappel_planifie_at && $record->rappel_planifie_at->isPast() => 'danger',
                        $record->rappel_planifie_at && $record->rappel_planifie_at->isToday() => 'warning',
                        default => 'primary',
                    }),

                Tables\Columns\TextColumn::make('statut')
                    ->label('Statut')
                    ->formatStateUsing(fn (ProspectStatut $state) => $state->label())
                    ->color(fn (ProspectStatut $state) => $state->color())
                    ->icon(fn (ProspectStatut $state) => $state->icon()),

                Tables\Columns\TextColumn::make('rappel_type')
                    ->label('Type')
                    ->state(function (Prospect $record): string {
                        if (! $record->rappel_planifie_at) {
                            return '—';
                        }

                        if ($record->rappel_planifie_at->isPast()) {
                            return 'En retard';
                        }

                        if ($record->rappel_planifie_at->isToday()) {
                            return 'Aujourd\'hui';
                        }

                        return 'À venir';
                    })
                    ->badge()
                    ->color(fn (Prospect $record) => match (true) {
                        $record->rappel_planifie_at && $record->rappel_planifie_at->isPast() => 'danger',
                        $record->rappel_planifie_at && $record->rappel_planifie_at->isToday() => 'warning',
                        default => 'success',
                    }),
            ])
            ->filters([
                SelectFilter::make('teleprospecteur_id')
                    ->label('Téléprospecteur')
                    ->options(
                        User::query()
                            ->where('actif', true)
                            ->where('role_cache', User::ROLE_TELEPROSPECTEUR)
                            ->orderBy('nom')
                            ->get()
                            ->mapWithKeys(fn (User $user) => [
                                $user->id => trim("{$user->prenom} {$user->nom}"),
                            ])
                            ->all()
                    )
                    ->searchable()
                    ->placeholder('Tous'),

                SelectFilter::make('rappel_type')
                    ->label('Type de rappel')
                    ->options([
                        'jour' => 'Aujourd\'hui',
                        'retard' => 'En retard',
                        'avenir' => 'À venir',
                    ])
                    ->query(function ($query, $state) {
                        if ($state === 'retard') {
                            return $query->where('rappel_planifie_at', '<', now());
                        }

                        if ($state === 'jour') {
                            return $query->whereDate('rappel_planifie_at', today());
                        }

                        if ($state === 'avenir') {
                            return $query->where('rappel_planifie_at', '>', now());
                        }

                        return $query;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('appel_fait')
                    ->label('Appel effectué')
                    ->icon('heroicon-o-phone')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('resultat')
                            ->options([
                                'Réalisé' => 'Réalisé',
                                'Non abouti' => 'Non abouti',
                                'Rappel' => 'Rappel',
                            ])
                            ->required(),
                        Forms\Components\DateTimePicker::make('prochain_rappel')
                            ->label('Prochain rappel (si rappel)')
                            ->seconds(false),
                        Forms\Components\Textarea::make('commentaire')
                            ->rows(2),
                    ])
                    ->action(function (Prospect $record, array $data) {
                        $record->appels()->create([
                            'user_id' => auth()->id(),
                            'type' => 'Appel',
                            'resultat' => $data['resultat'],
                            'date_heure' => now(),
                            'commentaire' => $data['commentaire'] ?? null,
                        ]);

                        if (! empty($data['prochain_rappel'])) {
                            $record->update([
                                'rappel_planifie_at' => $data['prochain_rappel'],
                                'statut' => ProspectStatut::RP,
                            ]);
                        }
                    }),
            ])
            ->emptyStateHeading('Aucun rappel à traiter')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
