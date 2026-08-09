<?php

namespace App\Filament\NsConseil\Resources\ClientResource\RelationManagers;

use App\Filament\NsConseil\Resources\PartenaireResource;
use App\Models\Partenaire;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PartenaireRelationManager extends RelationManager
{
    protected static string $relationship = 'partenaire';

    protected static ?string $title = 'Partenaire lié';

    protected static ?string $modelLabel = 'Partenaire';

    protected static ?string $pluralModelLabel = 'Partenaires';

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
            ->recordTitleAttribute('nom')
            ->columns([
                Tables\Columns\TextColumn::make('nom')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('entreprise')
                    ->label('Entreprise')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ville')
                    ->label('Ville')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('statut_label')
                    ->label('Statut')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'À prospecter' => 'gray',
                        'En cours de prospection' => 'info',
                        'RDV en cours' => 'warning',
                        'Signé accord cadre' => 'primary',
                        'Convention d\'engagement' => 'success',
                        'Refus' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('commercial.nom')
                    ->label('Commercial')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->headerActions([
                // Pas de création manuelle - le partenaire est sélectionné via le champ partenaire_id
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (Partenaire $record): string => PartenaireResource::getUrl('view', ['record' => $record])),
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
