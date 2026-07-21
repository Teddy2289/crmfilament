<?php

namespace App\Filament\NsConseil\Widgets;

use App\Models\Appel;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class FichesWordRecentesWidget extends BaseWidget
{
    protected static ?string $heading = 'Fiches Word générées récemment';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 1;

    public function table(Table $table): Table
    {
        $user = Auth::user();

        $query = Appel::query()
            ->whereNotNull('fiche_word_path')
            ->with(['appelable', 'user'])
            ->latest('fiche_word_generated_at')
            ->limit(10);

        // Filtrer par utilisateur si pas admin/superviseur
        if (! $user->hasRoleCache('admin') && ! $user->hasRoleCache('superviseur') && ! $user->isSuperAdmin()) {
            $query->where('user_id', $user->id);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('fiche_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'bleue' => 'blue',
                        'jaune' => 'yellow',
                        'verte' => 'green',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('appelable.nom')
                    ->label('Contact')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phoning_status')
                    ->label('Statut appel')
                    ->badge(),
                Tables\Columns\TextColumn::make('fiche_word_generated_at')
                    ->label('Générée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Téléprospecteur')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('fiche_jaune_j7_envoye_at')
                    ->label('J+7 envoyé')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('fiche_word_path')
                    ->label('Télécharger')
                    ->formatStateUsing(fn ($state) => 'Télécharger')
                    ->url(fn ($record) => $record->fiche_word_path)
                    ->openUrlInNewTab()
                    ->color('primary'),
            ])
            ->defaultSort('fiche_word_generated_at', 'desc')
            ->paginated(false);
    }
}
