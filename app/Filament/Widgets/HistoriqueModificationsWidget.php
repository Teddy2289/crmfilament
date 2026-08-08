<?php

namespace App\Filament\Widgets;

use App\Models\HistoriqueModification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class HistoriqueModificationsWidget extends BaseWidget
{
    public ?string $modelType = null;
    public ?int $modelId = null;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Historique des modifications';

    public function table(Table $table): Table
    {
        $query = HistoriqueModification::query();

        if ($this->modelType && $this->modelId) {
            $query->pourModel($this->modelType, $this->modelId);
        }

        return $table
            ->query($query->recent()->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('date_modification')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('type_modification')
                    ->label('Type')
                    ->colors([
                        'success' => 'creation',
                        'warning' => 'modification',
                        'danger' => 'suppression',
                        'info' => 'restauration',
                    ])
                    ->formatStateUsing(fn ($state) => HistoriqueModification::TYPES_MODIFICATION[$state] ?? $state),

                Tables\Columns\TextColumn::make('user.nom_complet')
                    ->label('Utilisateur')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('champ')
                    ->label('Champ')
                    ->formatStateUsing(function ($state, $record) {
                        if (blank($state)) {
                            return 'Enregistrement';
                        }

                        return $record?->champ_label ?: (string) $state;
                    }),

                Tables\Columns\TextColumn::make('ancienne_valeur')
                    ->label('Ancienne valeur')
                    ->formatStateUsing(function ($state, $record) {
                        if (blank($state)) {
                            return '—';
                        }

                        if (is_array($state)) {
                            return json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                        }

                        return (string) $state;
                    })
                    ->wrap()
                    ->limit(80)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('nouvelle_valeur')
                    ->label('Nouvelle valeur')
                    ->formatStateUsing(function ($state, $record) {
                        if (blank($state)) {
                            return '—';
                        }

                        if (is_array($state)) {
                            return json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                        }

                        return (string) $state;
                    })
                    ->wrap()
                    ->limit(80)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date_modification', 'desc')
            ->paginated(false);
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('view_historique_modifications') ?? false;
    }
}
