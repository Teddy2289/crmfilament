<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomFieldValue extends Model
{
    protected $fillable = [
        'custom_field_id',
        'model_type',
        'model_id',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    public function customField(): BelongsTo
    {
        return $this->belongsTo(CustomField::class);
    }

    public function model()
    {
        return $this->morphTo();
    }

    public function scopeForModel($query, $model)
    {
        return $query->where('model_type', get_class($model))
                     ->where('model_id', $model->id);
    }
}
