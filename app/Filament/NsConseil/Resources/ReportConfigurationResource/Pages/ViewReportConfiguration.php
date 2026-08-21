<?php

declare(strict_types=1);

namespace App\Filament\NsConseil\Resources\ReportConfigurationResource\Pages;

use App\Filament\NsConseil\Resources\ReportConfigurationResource;
use App\Models\CrmReportConfiguration;
use Filament\Actions;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewReportConfiguration extends ViewRecord
{
    protected static string $resource = ReportConfigurationResource::class;

    protected static ?string $title = 'Détail de la configuration';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->label('Modifier'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            TextEntry::make('name')->label('Nom'),
            TextEntry::make('slug')->label('Identifiant'),
            TextEntry::make('report_type')->label('Type'),
            TextEntry::make('description')->label('Description')->placeholder('Aucune description'),
            TextEntry::make('active')->label('Actif')->boolean(),
            TextEntry::make('schedule_description')
                ->label('Planification')
                ->state(fn (CrmReportConfiguration $record): string => $record->scheduleDescription()),
            TextEntry::make('recipient_mode')->label('Mode destinataires'),
            TextEntry::make('recipient_count')
                ->label('Nombre de destinataires')
                ->state(fn (CrmReportConfiguration $record): string => (string) $record->recipientCount()),
            TextEntry::make('sections')
                ->label('Blocs inclus')
                ->formatStateUsing(fn (?array $state): string => collect($state ?? [])->implode(', ') ?: 'Aucun'),
            TextEntry::make('period_type')->label('Période'),
            TextEntry::make('last_status')->label('Dernier statut')->placeholder('Jamais exécuté'),
            TextEntry::make('last_run_at')->label('Dernière exécution')->dateTime('d/m/Y H:i:s')->placeholder('Jamais'),
            TextEntry::make('next_run_at')->label('Prochaine exécution')->dateTime('d/m/Y H:i:s')->placeholder('Non calculée'),
            TextEntry::make('last_error')->label('Dernière erreur')->placeholder('Aucune erreur')->columnSpanFull(),
        ])->columns(3);
    }
}
