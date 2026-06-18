<?php

namespace App\Filament\NsConseil\Widgets;

use App\Enums\ProspectStatut;
use App\Enums\RendezVousStatut;
use App\Models\Prospect;
use App\Models\RendezVous;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class CommercialAgendaWidget extends BaseWidget
{
    protected static ?string $heading = '📅 RDV à venir & Prospects en attente';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public string $activeTab = 'rdv';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user
            && ($user->hasRoleCache('commercial')
                || $user->hasRoleCache('superviseur')
                || $user->hasRoleCache('admin')
                || $user->isSuperAdmin());
    }

    public function table(Table $table): Table
    {
        $user = auth()->user();
        $isCommercial = $user->hasRoleCache('commercial');

        if ($this->activeTab === 'prospects') {
            return $this->tableProspects($table, $user, $isCommercial);
        }

        return $table
            ->query(
                RendezVous::query()
                    ->when($isCommercial, fn ($q) => $q->where('commercial_id', $user->id))
                    ->whereIn('statut', [
                        RendezVousStatut::Planifie->value,
                        RendezVousStatut::Decale->value,
                    ])
                    ->where('date_heure', '>=', now())
                    ->where('date_heure', '<=', now()->endOfWeek()->addWeek())
                    ->orderBy('date_heure')
            )
            ->columns([
                Tables\Columns\TextColumn::make('date_heure')
                    ->label('Date/Heure')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('rdvable.nom')
                    ->label('Entité')
                    ->default('—'),

                Tables\Columns\TextColumn::make('interlocuteur_nom')
                    ->label('Interlocuteur'),

                Tables\Columns\TextColumn::make('lieu')
                    ->label('Lieu')
                    ->limit(30),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '—')
                    ->color(fn ($state) => match ($state?->value ?? '') {
                        'appel' => 'info',
                        'permanence' => 'success',
                        'presentation' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '—')
                    ->color(fn ($state) => match ($state) {
                        RendezVousStatut::Planifie => 'success',
                        RendezVousStatut::Decale => 'warning',
                        default => 'gray',
                    }),
            ])
            ->emptyStateHeading('Aucun RDV à venir')
            ->emptyStateIcon('heroicon-o-calendar');
    }

    private function tableProspects(Table $table, $user, bool $isCommercial): Table
    {
        return $table
            ->query(
                Prospect::query()
                    ->when($isCommercial, fn ($q) => $q->where('commercial_id', $user->id))
                    ->whereIn('statut', [ProspectStatut::RP->value, ProspectStatut::RPC->value])
                    ->orderByRaw('CASE WHEN rappel_planifie_at IS NOT NULL THEN 0 ELSE 1 END, rappel_planifie_at ASC')
                    ->limit(20)
            )
            ->columns([
                Tables\Columns\TextColumn::make('nom')
                    ->label('Prospect')
                    ->searchable(),

                Tables\Columns\TextColumn::make('telephone')
                    ->label('Téléphone')
                    ->copyable(),

                Tables\Columns\TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (ProspectStatut $state) => $state->label())
                    ->color(fn (ProspectStatut $state) => $state->color()),

                Tables\Columns\TextColumn::make('rappel_planifie_at')
                    ->label('Rappel')
                    ->dateTime('d/m/Y H:i')
                    ->color(fn ($state) => $state && $state < now() ? 'danger' : null),

                Tables\Columns\TextColumn::make('teleprospecteur.nom')
                    ->label('TP')
                    ->formatStateUsing(fn ($record) => $record->teleprospecteur
                        ? "{$record->teleprospecteur->prenom} {$record->teleprospecteur->nom}"
                        : '—'),
            ])
            ->emptyStateHeading('Aucun prospect en attente')
            ->emptyStateIcon('heroicon-o-funnel');
    }
}
