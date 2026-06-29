<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomField extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'type',
        'options',
        'required',
        'target_model',
        'placeholder',
        'helper_text',
        'order',
        'active',
    ];

    protected $casts = [
        'options' => 'array',
        'required' => 'boolean',
        'active' => 'boolean',
        'order' => 'integer',
    ];

    const TYPES = [
        'text' => 'Texte',
        'textarea' => 'Zone de texte',
        'number' => 'Nombre',
        'select' => 'Liste déroulante',
        'checkbox' => 'Case à cocher',
        'date' => 'Date',
        'email' => 'Email',
        'tel' => 'Téléphone',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function scopeForModel($query, $modelClass)
    {
        return $query->where('target_model', $modelClass);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
