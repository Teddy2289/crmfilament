<?php

namespace App\Filament\NsConseil\Widgets;

use App\Enums\OrganizationStatus;
use App\Filament\NsConseil\Resources\PartenaireResource;
use App\Models\Partenaire;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class MesPartenairesRecentWidget extends BaseWidget
{
    protected static ?string $heading = '🏢 Derniers partenaires modifiés';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->query(
                Partenaire::query()
                    ->when(
                        $user->hasRole('commercial'),
                        fn ($q) => $q->where('commercial_id', $user->id)
                    )
                    ->orderBy('date_modification_statut', 'desc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('nom')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('statut')
                    ->formatStateUsing(fn (OrganizationStatus $state) => $state->label())
                    ->color(fn (OrganizationStatus $state) => $state->color())
                    ->icon(fn (OrganizationStatus $state) => $state->icon()),

                Tables\Columns\TextColumn::make('departement')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('date_modification_statut')
                    ->label('Modifié le')
                    ->dateTime('d/m/Y H:i'),

                Tables\Columns\TextColumn::make('commercial.nom')
                    ->formatStateUsing(fn ($r) => $r->commercial
                        ? "{$r->commercial->prenom} {$r->commercial->nom}"
                        : '—'),
            ])
            ->actions([
                Tables\Actions\Action::make('voir')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => PartenaireResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated(false);
    }
}
