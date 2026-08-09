<?php

namespace App\Filament\NsConseil\Resources\RendezVousResource\RelationManagers;

use App\Models\HistoriqueModification;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HistoriqueModificationsRelationManager extends RelationManager
{
    protected static string $relationship = 'historiqueModifications';

    protected static ?string $title = 'Historique des modifications';

    protected static ?string $modelLabel = 'Modification';

    protected static ?string $pluralModelLabel = 'Modifications';

    public static function canViewForRecord(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Lecture seule - pas de formulaire d'édition
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('type_modification')
            ->columns([
                Tables\Columns\TextColumn::make('date_modification')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('type_modification_label')
                    ->label('Type')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Création' => 'success',
                        'Modification' => 'warning',
                        'Suppression' => 'danger',
                        'Restauration' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('champ_label')
                    ->label('Champ')
                    ->toggleable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('ancienne_valeur_formatee')
                    ->label('Ancienne valeur')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(50),

                Tables\Columns\TextColumn::make('nouvelle_valeur_formatee')
                    ->label('Nouvelle valeur')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(50),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->sortable()
                    ->icon('heroicon-m-user'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type_modification')
                    ->label('Type de modification')
                    ->options(HistoriqueModification::TYPES_MODIFICATION),

                Tables\Filters\Filter::make('recent')
                    ->label('30 derniers jours')
                    ->query(fn (Builder $query): Builder => $query->where('date_modification', '>=', now()->subDays(30))),
            ])
            ->defaultSort('date_modification', 'desc')
            ->paginated([10, 25, 50])
            ->headerActions([
                // Pas de création manuelle
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Détail de la modification')
                    ->modalContent(fn (HistoriqueModification $record) => view('filament.resources.rendezvous.historique-detail', ['record' => $record])),
            ])
            ->bulkActions([
                // Pas d'actions groupées
            ]);
    }

    protected function getTableQuery(): Builder
    {
        return $this->getRelationship()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
