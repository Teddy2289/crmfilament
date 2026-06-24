<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateFiche extends Model
{
    protected $table = 'templates_fiches';

    protected $fillable = [
        'code',
        'nom',
        'type',
        'description',
        'fichier_path',
        'variables',
        'actif',
    ];

    protected $casts = [
        'variables' => 'array',
        'actif' => 'boolean',
    ];

    public function scopeActifs($query)
    {
        return $query->where('actif', true);
    }

    public function scopeParType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeParCode($query, string $code)
    {
        return $query->where('code', $code);
    }
}
