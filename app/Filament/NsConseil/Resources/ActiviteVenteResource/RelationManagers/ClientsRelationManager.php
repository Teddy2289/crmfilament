<?php

namespace App\Filament\NsConseil\Resources\ActiviteVenteResource\RelationManagers;

use App\Filament\NsConseil\Resources\ClientResource;
use App\Models\Client;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClientsRelationManager extends RelationManager
{
    protected static string $relationship = 'clients';

    protected static ?string $title = 'Clients liés';

    protected static ?string $modelLabel = 'client lié';

    protected static ?string $pluralModelLabel = 'clients liés';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nom_tiers')
            ->modifyQueryUsing(fn(Builder $query): Builder => $query
                ->withCount([
                    'dossierFormations as ventes_count' => fn(Builder $dossiers): Builder => $dossiers
                        ->whereNotNull('date_vente'),
                ]))
            ->columns([
                Tables\Columns\TextColumn::make('nom_tiers')
                    ->label('Client')
                    ->searchable()
                    ->sortable()
                    ->description(fn(Client $record): ?string => $record->email),

                Tables\Columns\TextColumn::make('ref_client')
                    ->label('Réf. client')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('telephone')
                    ->label('Téléphone')
                    ->badge()
                    ->color('green')
                    ->icon('heroicon-o-phone')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ventes_count')
                    ->label('Ventes')
                    ->numeric()
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('montant_cpf')
                    ->label('Montant CPF')
                    ->money('EUR')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('avec_ventes')
                    ->label('Avec ventes')
                    ->query(fn(Builder $query): Builder => $query
                        ->whereHas('dossierFormations', fn(Builder $dossiers): Builder => $dossiers
                            ->whereNotNull('date_vente')))
                    ->toggle(),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('ouvrir')
                    ->label('Ouvrir')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn(Client $record): string => ClientResource::getUrl('view', ['record' => $record])),
            ])
            ->bulkActions([]);
    }
}
