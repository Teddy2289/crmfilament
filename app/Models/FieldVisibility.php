<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldVisibility extends Model
{
    protected $table = 'field_visibility';

    protected $fillable = [
        'table_name',
        'column_name',
        'role_name',
        'visible',
    ];

    protected $casts = [
        'visible' => 'boolean',
    ];

    // Helper statique utilisé dans les Resources Filament
    public static function isVisible(
        string $table,
        string $column,
        string $role
    ): bool {
        return static::where('table_name', $table)
            ->where('column_name', $column)
            ->where('role_name', $role)
            ->value('visible') ?? true; // visible par défaut si pas de règle
    }

    public static function getRulesForTable(string $table): \Illuminate\Support\Collection
    {
        return static::where('table_name', $table)->get();
    }
}
