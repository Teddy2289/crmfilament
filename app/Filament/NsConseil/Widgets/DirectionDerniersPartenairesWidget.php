<?php

namespace App\Filament\NsConseil\Widgets;

use App\Enums\OrganizationStatus;
use App\Models\Partenaire;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class DirectionDerniersPartenairesWidget extends BaseWidget
{
    protected static ?string $heading = '🤝 10 derniers partenaires signés';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user
            && ($user->hasRoleCache('admin')
                || $user->isSuperAdmin());
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Partenaire::query()
                    ->whereIn('statut', [
                        OrganizationStatus::SigneAccordCadre->value,
                        OrganizationStatus::ConventionEngagement->value,
                    ])
                    ->latest('date_modification_statut')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('nom')
                    ->label('Partenaire')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge(),

                Tables\Columns\TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof OrganizationStatus
                        ? $state->label()
                        : (OrganizationStatus::tryFrom($state)?->label() ?? $state))
                    ->color(fn ($state) => $state instanceof OrganizationStatus
                        ? $state->color()
                        : (OrganizationStatus::tryFrom($state)?->color() ?? 'gray')),

                Tables\Columns\TextColumn::make('commercial.nom')
                    ->label('Commercial')
                    ->formatStateUsing(fn ($record) => $record->commercial
                        ? "{$record->commercial->prenom} {$record->commercial->nom}"
                        : '—'),

                Tables\Columns\TextColumn::make('departement')
                    ->label('Dép.'),

                Tables\Columns\TextColumn::make('date_modification_statut')
                    ->label('Signé le')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->emptyStateHeading('Aucun partenaire signé');
    }
}
