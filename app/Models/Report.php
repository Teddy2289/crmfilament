<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nom',
        'description',
        'type',
        'config',
        'created_by',
        'public',
        'actif',
    ];

    protected $casts = [
        'config' => 'array',
        'public' => 'boolean',
        'actif' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('actif', true);
    }

    public function scopePublic($query)
    {
        return $query->where('public', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('public', true)
              ->orWhere('created_by', $userId);
        });
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function getConfigAttribute($value)
    {
        return json_decode($value, true) ?? [];
    }

    public function setConfigAttribute($value)
    {
        $this->attributes['config'] = json_encode($value);
    }
}
