<?php

namespace App\Filament\NsConseil\Widgets\Crm;

use App\Models\HistoriqueInteractionUser;
use App\Models\Opportunite;
use App\Models\Prospect;
use App\Models\Client;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class CrmRecentActivityWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '60s';

    protected static bool $isLazy = true;

    public ?string $errorMessage = null;

    public function getHeading(): string
    {
        return '🕐 Activité Récente';
    }

    protected function getTableQuery(): Builder
    {
        try {
            $user = auth()->user();
            
            $query = HistoriqueInteractionUser::query()
                ->with(['user', 'interactable'])
                ->latest('date_interaction')
                ->limit(10);

            // Filter by user if not admin/supervisor
            if (!$user->hasRoleCache(['admin', 'superviseur']) && !$user->isSuperAdmin()) {
                // Show only user's activities
                $query->where('user_id', $user->id);
            }

            return $query;
            
        } catch (\Exception $exception) {
            Log::error('CrmRecentActivityWidget error', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);
            
            $this->errorMessage = 'Impossible de charger l\'activité récente';
            
            return \App\Models\User::query()->where('id', 0);
        }
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('date_interaction')
                ->label('Date')
                ->dateTime('d/m/Y H:i')
                ->sortable()
                ->weight('bold'),
            
            Tables\Columns\TextColumn::make('user.name')
                ->label('Utilisateur')
                ->sortable()
                ->searchable()
                ->badge()
                ->color('gray'),
            
            Tables\Columns\TextColumn::make('type_interaction_label')
                ->label('Type')
                ->badge()
                ->color(fn ($state) => match ($state) {
                    'Consultation' => 'info',
                    'Modification' => 'warning',
                    'Appel' => 'success',
                    'Rendez-vous' => 'primary',
                    'Email' => 'gray',
                    'Conversion' => 'danger',
                    'Création' => 'success',
                    default => 'gray',
                }),
            
            Tables\Columns\TextColumn::make('interactable_type')
                ->label('Entité')
                ->formatStateUsing(fn ($state) => match ($state) {
                    Prospect::class => 'Prospect',
                    Client::class => 'Client',
                    Opportunite::class => 'Opportunité',
                    default => class_basename($state),
                })
                ->badge()
                ->color('info'),
            
            Tables\Columns\TextColumn::make('description')
                ->label('Description')
                ->limit(50)
                ->wrap()
                ->tooltip(fn ($record) => $record->description),
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
