<?php

namespace App\Filament\NsConseil\Resources\ClientResource\Pages;

use App\Filament\NsConseil\Resources\ClientResource;
use App\Filament\NsConseil\Resources\ClientResource\Actions\ImportClientsAction;
use App\Models\Client;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportClientsAction::make(),
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'tous' => Tab::make('Tous')
                ->badge(Client::withoutTrashed()->count()),

            'contactables' => Tab::make('Contactables')
                ->badge(
                    Client::withoutTrashed()
                        ->where('ne_plus_contacter', false)
                        ->whereNotNull('email')
                        ->orWhere(function (Builder $q) {
                            $q->where('ne_plus_contacter', false)
                              ->whereNotNull('telephone');
                        })
                        ->count()
                )
                ->modifyQueryUsing(function (Builder $query) {
                    return $query
                        ->where('ne_plus_contacter', false)
                        ->where(function (Builder $sub) {
                            $sub->whereNotNull('email')
                                ->orWhereNotNull('telephone');
                        });
                }),

            'non_contactables' => Tab::make('Non contactables')
                ->badge(Client::withoutTrashed()->where('ne_plus_contacter', true)->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->where('ne_plus_contacter', true);
                }),

            'avec_cpf' => Tab::make('Avec CPF')
                ->badge(
                    Client::withoutTrashed()
                        ->whereNotNull('montant_cpf')
                        ->where('montant_cpf', '>', 0)
                        ->count()
                )
                ->modifyQueryUsing(function (Builder $query) {
                    return $query
                        ->whereNotNull('montant_cpf')
                        ->where('montant_cpf', '>', 0);
                }),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return Client::query()->withoutTrashed();
    }
}