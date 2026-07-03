<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnvSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
        'label',
        'description',
        'type',
        'is_sensitive',
        'is_editable',
        'sort_order',
    ];

    protected $casts = [
        'is_sensitive' => 'boolean',
        'is_editable' => 'boolean',
        'sort_order' => 'integer',
    ];
}
