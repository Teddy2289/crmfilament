<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\RendezVousAssociationResource\Pages\ListRendezVousAssociations;
use App\Filament\NsConseil\Resources\RendezVousAssociationResource\Pages\ViewRendezVousAssociations;
use App\Filament\NsConseil\Resources\RendezVousResource;
use App\Filament\NsConseil\Resources\ProspectResource;
use App\Filament\NsConseil\Resources\PartenaireResource;
use App\Filament\NsConseil\Resources\ClientResource;
use App\Models\RendezVousAssociation;
use App\Support\UsesResourcePermissions;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use App\Models\User;

class RendezVousAssociationResource extends Resource
{
    use UsesResourcePermissions;

    protected static string $permissionPrefix = 'rdv_association';
    protected static ?string $model = RendezVousAssociation::class;

    protected static ?string $navigationIcon = 'heroicon-o-link-vertical';
    protected static ?string $navigationGroup = 'Activités';
    protected static ?string $navigationLabel = 'Associations RDV';

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('rendez_vous_id')->label('RDV')->url(fn($record) => RendezVousResource::getUrl('view', ['record' => $record->rendez_vous_id]))->openUrlInNewTab(),
                TextColumn::make('rdvable')->label('Fiche')->formatStateUsing(fn($state, $record) => $record->rdvable ? (
                    ($record->rdvable->prenom ?? null) ? ($record->rdvable->prenom . ' ' . ($record->rdvable->nom ?? '')) : ($record->rdvable->nom ?? class_basename($record->rdvable_type))
                ) : '—')
                    ->url(fn($record) => match ($record->rdvable_type) {
                        'App\\Models\\Prospect' => ProspectResource::getUrl('view', ['record' => $record->rdvable_id], panel: 'ns-conseil'),
                        'App\\Models\\Partenaire' => PartenaireResource::getUrl('view', ['record' => $record->rdvable_id], panel: 'ns-conseil'),
                        'App\\Models\\Client' => ClientResource::getUrl('view', ['record' => $record->rdvable_id], panel: 'ns-conseil'),
                        default => null,
                    })->openUrlInNewTab(),
                TextColumn::make('rdvable_type')->label('Type')->formatStateUsing(fn($state) => class_basename($state)),
                TextColumn::make('method')->label('Méthode'),
                TextColumn::make('user.nom')->label('Utilisateur'),
                TextColumn::make('meta')->label('Meta')->limit(60),
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Utilisateur')
                    ->options(fn() => User::orderBy('nom')->get()->mapWithKeys(fn($u) => [$u->id => "{$u->prenom} {$u->nom}"])->toArray()),
                Tables\Filters\SelectFilter::make('method')
                    ->label('Méthode')
                    ->options(fn() => RendezVousAssociation::query()->distinct()->pluck('method')->filter()->values()->mapWithKeys(fn($m) => [$m => $m])->toArray()),
                Tables\Filters\Filter::make('created_between')
                    ->label('Date')
                    ->form([
                        Tables\Filters\Form\Components\DatePicker::make('from')->label('De'),
                        Tables\Filters\Form\Components\DatePicker::make('until')->label('À'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (! empty($data['from'])) {
                            $from = $data['from']->startOfDay();
                            $query->where('created_at', '>=', $from);
                        }

                        if (! empty($data['until'])) {
                            $until = $data['until']->endOfDay();
                            $query->where('created_at', '<=', $until);
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRendezVousAssociations::route('/'),
            'view' => \App\Filament\NsConseil\Resources\RendezVousAssociationResource\Pages\ViewRendezVousAssociation::route('/{record}'),
        ];
    }
}
