<?php
namespace App\Filament\NsConseil\Resources\PartenaireResource\Pages;

use App\Filament\NsConseil\Resources\PartenaireResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPartenaire extends ViewRecord
{
    protected static string $resource = PartenaireResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('changer_statut')
                ->label('Changer le statut')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->form([
                    \Filament\Forms\Components\Select::make('statut')
                        ->label('Nouveau statut')
                        ->options(\App\Models\Partenaire::STATUTS)
                        ->required()
                        ->native(false),
                ])
                ->action(fn (array $data) => $this->record->update(['statut' => $data['statut']])),
        ];
    }
}
