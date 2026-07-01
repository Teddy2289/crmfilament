<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Webhook extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'url',
        'event',
        'is_active',
        'description',
        'headers',
        'secret',
        'user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'headers' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByEvent($query, string $event)
    {
        return $query->where('event', $event);
    }
}
