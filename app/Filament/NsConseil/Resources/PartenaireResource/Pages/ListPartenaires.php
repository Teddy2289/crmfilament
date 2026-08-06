<?php

namespace App\Filament\NsConseil\Resources\PartenaireResource\Pages;

use App\Enums\OrganizationStatus;
use App\Filament\NsConseil\Resources\PartenaireResource;
use App\Filament\NsConseil\Resources\PartenaireResource\Actions\ImportPartenairesAction;
use App\Models\Partenaire;
use App\Traits\HasResponsiveTable;
use App\Traits\WithCommonEagerLoading;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListPartenaires extends ListRecords
{
    use HasResponsiveTable, WithCommonEagerLoading;

    protected static string $resource = PartenaireResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->check() && auth()->user()?->actif;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    protected function getHeaderActions(): array
    {
        return [
            ImportPartenairesAction::make(),
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'tous' => Tab::make('Tous')
                ->badge(Partenaire::withoutTrashed()->count()),

            'actifs' => Tab::make('Actifs')
                ->badge(Partenaire::withoutTrashed()->whereNotIn('statut', [OrganizationStatus::Refus->value])->count())
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->whereNotIn('statut', [OrganizationStatus::Refus->value]);
                }),

            'a_prospecter' => Tab::make('À prospecter')
                ->badge(Partenaire::withoutTrashed()->where('statut', OrganizationStatus::AProspecter->value)->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->where('statut', OrganizationStatus::AProspecter->value);
                }),

            'en_cours' => Tab::make('En cours')
                ->badge(Partenaire::withoutTrashed()->whereIn('statut', [
                    OrganizationStatus::EnCoursProspection->value,
                    OrganizationStatus::RdvEnCours->value,
                ])->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->whereIn('statut', [
                        OrganizationStatus::EnCoursProspection->value,
                        OrganizationStatus::RdvEnCours->value,
                    ]);
                }),

            'conventionnes' => Tab::make('Conventionnés')
                ->badge(Partenaire::withoutTrashed()->whereIn('statut', [
                    OrganizationStatus::SigneAccordCadre->value,
                    OrganizationStatus::ConventionEngagement->value,
                ])->count())
                ->badgeColor('success')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->whereIn('statut', [
                        OrganizationStatus::SigneAccordCadre->value,
                        OrganizationStatus::ConventionEngagement->value,
                    ]);
                }),

            'refus' => Tab::make('Refus')
                ->badge(Partenaire::withoutTrashed()->where('statut', OrganizationStatus::Refus->value)->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->where('statut', OrganizationStatus::Refus->value);
                }),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return $this->loadCommonPartenaireRelations(
            Partenaire::query()->withoutTrashed()
        );
    }
}
