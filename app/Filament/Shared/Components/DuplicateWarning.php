<?php

namespace App\Filament\Shared\Components;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

/**
 * Avertissement (non bloquant) affiché sur un formulaire de création/édition
 * lorsque des enregistrements existants partagent un des champs surveillés
 * (téléphone, email, nom...). Ne remplace pas une contrainte d'unicité :
 * sert juste à alerter l'utilisateur avant qu'il ne crée un doublon.
 */
class DuplicateWarning
{
    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, string>  $fields  champ du formulaire => colonne en base
     * @param  class-string  $resourceClass  Resource Filament utilisée pour construire le lien "voir"
     */
    public static function make(
        string $key,
        string $modelClass,
        array $fields,
        string $labelAttribute,
        string $resourceClass,
        string $entityLabel,
    ): Placeholder {
        return Placeholder::make($key)
            ->hiddenLabel()
            ->live()
            ->visible(fn (Get $get, $livewire) => static::findMatches($modelClass, $fields, $get, $livewire)->isNotEmpty())
            ->content(function (Get $get, $livewire) use ($modelClass, $fields, $labelAttribute, $resourceClass, $entityLabel) {
                $matches = static::findMatches($modelClass, $fields, $get, $livewire);

                $links = $matches->map(function (Model $match) use ($labelAttribute, $resourceClass) {
                    $label = e($match->{$labelAttribute} ?? ('#'.$match->getKey()));
                    $url = e($resourceClass::getUrl('view', ['record' => $match]));

                    return "<a href=\"{$url}\" target=\"_blank\" class=\"underline font-medium hover:no-underline\">{$label}</a>";
                })->implode(', ');

                $count = $matches->count();
                $entite = $count > 1 ? "{$entityLabel}s" : $entityLabel;
                $accord = $count > 1 ? 's' : '';

                return new HtmlString(
                    "<div class=\"flex items-start gap-2 rounded-lg bg-warning-50 dark:bg-warning-500/10 p-3 text-sm text-warning-700 dark:text-warning-400\">"
                    ."<span>⚠️</span>"
                    ."<span>{$count} {$entite} existant{$accord} avec des coordonnées proches : {$links}</span>"
                    .'</div>'
                );
            })
            ->columnSpanFull();
    }

    /**
     * @param  array<string, string>  $fields
     */
    protected static function findMatches(string $modelClass, array $fields, Get $get, $livewire): Collection
    {
        $criteria = [];

        foreach ($fields as $formField => $column) {
            $value = trim((string) $get($formField));

            if ($value !== '') {
                $criteria[$column] = $value;
            }
        }

        if (empty($criteria)) {
            return collect();
        }

        $query = $modelClass::query()->where(function (Builder $query) use ($criteria) {
            foreach ($criteria as $column => $value) {
                $query->orWhere($column, $value);
            }
        });

        $record = method_exists($livewire, 'getRecord') ? $livewire->getRecord() : null;

        if ($record) {
            $query->whereKeyNot($record->getKey());
        }

        return $query->limit(5)->get();
    }
}
