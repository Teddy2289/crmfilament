<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PipelineStatut extends Model
{
    protected $fillable = [
        'model_type',
        'code',
        'label',
        'description',
        'couleur',
        'icone',
        'transitions',
        'ordre',
        'is_terminal',
        'is_archive',
        'actif',
    ];

    protected $casts = [
        'transitions' => 'array',
        'is_terminal' => 'boolean',
        'is_archive' => 'boolean',
        'actif' => 'boolean',
        'ordre' => 'integer',
    ];

    public static function forModelType(string $modelType): Collection
    {
        return static::where('model_type', $modelType)
            ->where('actif', true)
            ->orderBy('ordre')
            ->get();
    }

    public static function optionsFor(string $modelType): array
    {
        return static::forModelType($modelType)
            ->mapWithKeys(fn (self $s) => [$s->code => $s->label])
            ->toArray();
    }

    public static function findFor(string $modelType, string $code): ?self
    {
        return static::where('model_type', $modelType)
            ->where('code', $code)
            ->first();
    }

    public function getBadgeStyleAttribute(): string
    {
        return match ($this->couleur) {
            'blue' => 'background:rgb(219 234 254); color:rgb(30 64 175);',
            'warning', 'orange' => 'background:rgb(255 237 213); color:rgb(154 52 18);',
            'green', 'emerald' => 'background:rgb(220 252 231); color:rgb(20 83 45);',
            'success', 'teal' => 'background:rgb(204 251 241); color:rgb(17 94 89);',
            'danger', 'red' => 'background:rgb(254 226 226); color:rgb(153 27 27);',
            'primary', 'purple' => 'background:rgb(237 233 254); color:rgb(91 33 182);',
            'info' => 'background:rgb(219 234 254); color:rgb(30 64 175);',
            default => 'background:rgb(243 244 246); color:rgb(55 65 81); border:1px solid rgb(229 231 235);',
        };
    }
}
