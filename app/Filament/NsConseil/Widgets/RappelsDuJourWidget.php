<?php

namespace App\Filament\NsConseil\Widgets;

use App\Enums\ProspectStatut;
use App\Models\Prospect;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RappelsDuJourWidget extends BaseWidget
{
    protected static ?string $heading = '📞 Rappels du jour';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->query(
                Prospect::query()
                    ->whereDate('rappel_planifie_at', today())
                    ->whereNotIn('statut', [
                        ProspectStatut::KO->value,
                        ProspectStatut::QF->value,
                    ])
                    ->when(
                        $user->hasRole('teleprospecteur'),
                        fn ($q) => $q->where('teleprospecteur_id', $user->id)
                    )
                    ->orderBy('rappel_planifie_at', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('rappel_planifie_at')
                    ->label('Heure')
                    ->time('H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('nom')
                    ->label('Entité')
                    ->searchable(),

                Tables\Columns\TextColumn::make('telephone')
                    ->label('Téléphone')
                    ->copyable(),

                Tables\Columns\TextColumn::make('statut')
                    ->formatStateUsing(fn (ProspectStatut $state) => $state->label())
                    ->color(fn (ProspectStatut $state) => $state->color())
                    ->icon(fn (ProspectStatut $state) => $state->icon()),

                Tables\Columns\TextColumn::make('teleprospecteur.nom')
                    ->label('Téléprospecteur')
                    ->formatStateUsing(fn ($r) => $r->teleprospecteur
                        ? "{$r->teleprospecteur->prenom} {$r->teleprospecteur->nom}"
                        : '—'),
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
                        // Enregistrer l'appel
                        $record->appels()->create([
                            'user_id' => auth()->id(),
                            'type' => 'Appel',
                            'resultat' => $data['resultat'],
                            'date_heure' => now(),
                            'commentaire' => $data['commentaire'] ?? null,
                        ]);

                        // Mettre à jour le rappel si besoin
                        if (! empty($data['prochain_rappel'])) {
                            $record->update([
                                'rappel_planifie_at' => $data['prochain_rappel'],
                                'statut' => ProspectStatut::RP,
                            ]);
                        }
                    }),
            ])
            ->emptyStateHeading('Aucun rappel pour aujourd\'hui')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
