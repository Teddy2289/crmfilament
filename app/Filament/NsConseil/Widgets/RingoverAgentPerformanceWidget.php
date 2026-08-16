<?php

namespace App\Filament\NsConseil\Widgets;

use App\Services\RingoverService;
use App\Services\Ringover\RingoverExportService;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class RingoverAgentPerformanceWidget extends BaseWidget
{
    protected static ?string $heading = '👥 Performance des Agents Ringover';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '300s';

    protected static bool $isLazy = true;

    public ?string $errorMessage = null;

    public function table(Table $table): Table
    {
        try {
            $ringoverService = app(RingoverService::class);
            
            $endDate = now();
            $startDate = now()->subDays(7);
            
            $calls = $ringoverService->getCalls([
                'limit_count' => 1000,
                'date_from' => $startDate->timestamp,
                'date_to' => $endDate->timestamp,
            ]);
            
            $users = $ringoverService->getUsers();
            
            if (empty($calls) || empty($users)) {
                return $table
                    ->query(\App\Models\User::query()->where('id', 0))
                    ->columns([
                        Tables\Columns\TextColumn::make('message')
                            ->label('Message')
                            ->default('Aucune donnée disponible'),
                    ])
                    ->emptyStateHeading('Aucune donnée Ringover disponible');
            }
            
            $agentStats = [];
            
            foreach ($users as $user) {
                $userId = $user['id'] ?? $user['user_id'] ?? '';
                $userName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($user['name'] ?? ($user['email'] ?? 'Inconnu'));
                
                $agentStats[$userId] = [
                    'id' => $userId,
                    'name' => $userName,
                    'total_calls' => 0,
                    'answered' => 0,
                    'missed' => 0,
                    'incoming' => 0,
                    'outgoing' => 0,
                    'avg_duration' => 0,
                    'total_duration' => 0,
                ];
            }
            
            foreach ($calls as $call) {
                $callUserId = $call['user']['id'] ?? '';
                
                if (isset($agentStats[$callUserId])) {
                    $agentStats[$callUserId]['total_calls']++;
                    
                    if ($call['is_answered'] ?? false) {
                        $agentStats[$callUserId]['answered']++;
                    } else {
                        $agentStats[$callUserId]['missed']++;
                    }
                    
                    if (($call['direction'] ?? '') === 'inbound') {
                        $agentStats[$callUserId]['incoming']++;
                    } else {
                        $agentStats[$callUserId]['outgoing']++;
                    }
                    
                    $duration = $call['duration'] ?? 0;
                    $agentStats[$callUserId]['total_duration'] += $duration;
                }
            }
            
            foreach ($agentStats as &$stats) {
                if ($stats['total_calls'] > 0) {
                    $stats['answer_rate'] = round(($stats['answered'] / $stats['total_calls']) * 100, 1);
                    $stats['avg_duration'] = round($stats['total_duration'] / $stats['total_calls']);
                } else {
                    $stats['answer_rate'] = 0;
                    $stats['avg_duration'] = 0;
                }
            }
            
            $collection = collect(array_values($agentStats));
            
            return $table
                ->query($collection)
                ->columns([
                    Tables\Columns\TextColumn::make('name')
                        ->label('Agent')
                        ->searchable()
                        ->weight('bold'),
                    
                    Tables\Columns\TextColumn::make('total_calls')
                        ->label('Total Appels')
                        ->alignCenter()
                        ->badge()
                        ->color('primary'),
                    
                    Tables\Columns\TextColumn::make('answered')
                        ->label('Répondus')
                        ->alignCenter()
                        ->badge()
                        ->color('success'),
                    
                    Tables\Columns\TextColumn::make('missed')
                        ->label('Manqués')
                        ->alignCenter()
                        ->badge()
                        ->color('danger'),
                    
                    Tables\Columns\TextColumn::make('answer_rate')
                        ->label('Taux Réponse')
                        ->alignCenter()
                        ->state(fn ($record) => $record['answer_rate'] . '%')
                        ->badge()
                        ->color(fn ($record) => $record['answer_rate'] >= 80 ? 'success' : ($record['answer_rate'] >= 50 ? 'warning' : 'danger')),
                    
                    Tables\Columns\TextColumn::make('avg_duration')
                        ->label('Durée Moy.')
                        ->alignCenter()
                        ->state(fn ($record) => $this->formatDuration($record['avg_duration']))
                        ->badge()
                        ->color('info'),
                    
                    Tables\Columns\TextColumn::make('incoming')
                        ->label('Entrants')
                        ->alignCenter()
                        ->badge()
                        ->color('blue'),
                    
                    Tables\Columns\TextColumn::make('outgoing')
                        ->label('Sortants')
                        ->alignCenter()
                        ->badge()
                        ->color('purple'),
                ])
                ->defaultSort('total_calls', 'desc')
                ->emptyStateHeading('Aucune donnée de performance disponible')
                ->headerActions([
                    Tables\Actions\Action::make('export_csv')
                        ->label('Exporter CSV')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('gray')
                        ->action(function () {
                            $startDate = now()->subDays(7)->format('Y-m-d');
                            $endDate = now()->format('Y-m-d');
                            
                            $exportService = app(RingoverExportService::class);
                            $url = $exportService->exportAgentPerformance($startDate, $endDate, 'csv');
                            
                            Notification::make()
                                ->title('Export CSV généré')
                                ->success()
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('download')
                                        ->label('Télécharger')
                                        ->url($url)
                                        ->openUrlInNewTab(),
                                ])
                                ->send();
                        }),

                    Tables\Actions\Action::make('export_excel')
                        ->label('Exporter Excel')
                        ->icon('heroicon-o-table-cells')
                        ->color('success')
                        ->action(function () {
                            $startDate = now()->subDays(7)->format('Y-m-d');
                            $endDate = now()->format('Y-m-d');
                            
                            $exportService = app(RingoverExportService::class);
                            $url = $exportService->exportAgentPerformance($startDate, $endDate, 'excel');
                            
                            Notification::make()
                                ->title('Export Excel généré')
                                ->success()
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('download')
                                        ->label('Télécharger')
                                        ->url($url)
                                        ->openUrlInNewTab(),
                                ])
                                ->send();
                        }),
                ]);
            
        } catch (\Exception $exception) {
            Log::error('RingoverAgentPerformanceWidget error', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);
            
            $this->errorMessage = 'Impossible de charger les performances des agents';
            
            return $table
                ->query(\App\Models\User::query()->where('id', 0))
                ->columns([
                    Tables\Columns\TextColumn::make('error')
                        ->label('Erreur')
                        ->default($this->errorMessage),
                ])
                ->emptyStateHeading($this->errorMessage);
        }
    }
    
    protected function formatDuration(int $seconds): string
    {
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        
        if ($minutes > 0) {
            return "{$minutes}min {$secs}s";
        }
        
        return "{$secs}s";
    }

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRoleCache('admin') || $user->hasRoleCache('superviseur'));
    }
}
