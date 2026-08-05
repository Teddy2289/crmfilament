<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\RendezVousAssociationResource\Pages\ListRendezVousAssociations;
use App\Filament\NsConseil\Resources\RendezVousResource;
use App\Models\RendezVousAssociation;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;

class RendezVousAssociationResource extends Resource
{
    protected static ?string $model = RendezVousAssociation::class;

    protected static ?string $navigationIcon = 'heroicon-o-link-vertical';
    protected static ?string $navigationGroup = 'Activités';
    protected static ?string $navigationLabel = 'Associations RDV';

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('rendez_vous_id')->label('RDV')->url(fn($record) => RendezVousResource::getUrl('view', ['record' => $record->rendez_vous_id]))->openUrlInNewTab(),
                TextColumn::make('rdvable_type')->label('Type')->formatStateUsing(fn($state) => class_basename($state)),
                TextColumn::make('rdvable_id')->label('ID'),
                TextColumn::make('method')->label('Méthode'),
                TextColumn::make('user.nom')->label('Utilisateur'),
                TextColumn::make('meta')->label('Meta')->limit(60),
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRendezVousAssociations::route('/'),
        ];
    }
}
