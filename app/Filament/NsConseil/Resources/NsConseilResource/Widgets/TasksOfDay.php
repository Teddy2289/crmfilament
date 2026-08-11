<?php

namespace App\Filament\NsConseil\Resources\NsConseilResource\Widgets;

use App\Models\Task;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class TasksOfDay extends BaseWidget
{
    protected static ?string $heading = 'Tâches du jour';

    protected int | string | array $columnSpan = 'full';

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Task::query()
            ->where('assigne_a', Auth::id())
            ->whereIn('statut', ['a_faire', 'en_cours'])
            ->where(function ($query) {
                $query->whereDate('date_echeance', today())
                    ->orWhere('priorite', 'critique');
            })
            ->orderBy('priorite')
            ->orderBy('date_echeance');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('titre')
                ->label('Tâche')
                ->searchable()
                ->wrap(),

            Tables\Columns\BadgeColumn::make('priorite_label')
                ->label('Priorité')
                ->color(fn (Task $record) => $record->priorite_color),

            Tables\Columns\BadgeColumn::make('statut_label')
                ->label('Statut')
                ->color(fn (Task $record) => $record->statut_color),

            Tables\Columns\TextColumn::make('date_echeance')
                ->label('Échéance')
                ->dateTime('d/m/Y H:i')
                ->description(fn (Task $record): string => $record->est_en_retard ? 'En retard' : ''),

            Tables\Columns\TextColumn::make('prospect.nom')
                ->label('Prospect')
                ->toggleable(),

            Tables\Columns\TextColumn::make('client.nom_tiers')
                ->label('Client')
                ->toggleable(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('marquer_en_cours')
                ->label('En cours')
                ->icon('heroicon-o-play')
                ->color('warning')
                ->visible(fn (Task $record) => $record->statut === 'a_faire')
                ->action(fn (Task $record) => $record->marquerEnCours()),

            Tables\Actions\Action::make('marquer_terminee')
                ->label('Terminer')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(fn (Task $record) => in_array($record->statut, ['a_faire', 'en_cours']))
                ->action(fn (Task $record) => $record->marquerTerminee()),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns($this->getTableColumns())
            ->actions($this->getTableActions())
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 25]);
    }
}
