<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroupeTelepro extends Model
{
    use SoftDeletes;

    protected $table = 'groupes_telepro';

    protected $fillable = [
        'nom',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    // ── Relations ────────────────────────────────────────────────────

    public function membres(): HasMany
    {
        return $this->hasMany(User::class, 'groupe_telepro_id');
    }

    public function campagnes(): HasMany
    {
        return $this->hasMany(CampagnePhoning::class, 'groupe_telepro_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeActifs(Builder $query): Builder
    {
        return $query->where('actif', true);
    }
}
