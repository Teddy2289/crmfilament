<?php

namespace App\Filament\NsConseil\Widgets\Crm;

use App\Models\Prospect;
use App\Filament\NsConseil\Resources\ProspectResource;
use App\Models\Opportunite;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CrmActionRequiredWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';



    public ?string $errorMessage = null;

    public function getHeading(): string
    {
        return '⚡ Actions Requises';
    }

    public function getTableQuery(): Builder
    {
        try {
            $user = auth()->user();

            // Simplified query focusing on prospects needing action
            return Prospect::query()
                ->with(['teleprospecteur', 'commercial'])
                ->where(function ($query) {
                    $query->where('statut', 'AC')
                        ->orWhere('statut', 'RP');
                })
                ->when(!$user->hasRoleCache(['admin', 'superviseur']) && !$user->isSuperAdmin(), function ($query) use ($user) {
                    if ($user->hasRoleCache('teleprospecteur')) {
                        $query->where('teleprospecteur_id', $user->id);
                    }
                    if ($user->hasRoleCache('commercial')) {
                        $query->where('commercial_id', $user->id);
                    }
                })
                ->latest('updated_at')
                ->limit(10);
                
        } catch (\Exception $exception) {
            Log::error('CrmActionRequiredWidget error', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);
            
            $this->errorMessage = 'Impossible de charger les actions requises';
            
            return \App\Models\User::query()->where('id', 0);
        }
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('nom')
                ->label('Nom')
                ->searchable()
                ->sortable()
                ->weight('bold'),
            
            Tables\Columns\TextColumn::make('statut')
                ->label('Statut')
                ->badge()
                ->color('warning'),
            
            Tables\Columns\TextColumn::make('teleprospecteur.name')
                ->label('Téléprospecteur')
                ->default('—')
                ->badge()
                ->color('gray'),
            
            Tables\Columns\TextColumn::make('commercial.name')
                ->label('Commercial')
                ->default('—')
                ->badge()
                ->color('gray'),
            
            Tables\Columns\TextColumn::make('updated_at')
                ->label('Dernière activité')
                ->dateTime('d/m/Y H:i')
                ->sortable()
                ->since()
                ->color(fn ($record) => $record->updated_at->diffInDays(now()) > 7 ? 'danger' : 'warning'),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('voir')
                ->label('Voir')
                ->url(fn ($record) => ProspectResource::getUrl('view', ['record' => $record], panel: 'ns-conseil'))
                ->openUrlInNewTab()
                ->icon('heroicon-o-eye')
                ->color('primary'),
        ];
    }

    protected function isTablePaginationEnabled(): bool
    {
        return false;
    }

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRoleCache('admin') || $user->hasRoleCache('superviseur') || $user->hasRoleCache('teleprospecteur') || $user->hasRoleCache('commercial'));
    }
}
