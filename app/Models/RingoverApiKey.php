<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RingoverApiKey extends Model
{
    use SoftDeletes;

    protected $table = 'ringover_api_keys';

    protected $fillable = [
        'name',
        'api_key',
        'type',
        'is_active',
        'user_id',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
