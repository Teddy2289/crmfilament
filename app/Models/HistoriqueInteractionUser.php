<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoriqueInteractionUser extends Model
{
    protected $table = 'historique_interactions_users';

    protected $fillable = [
        'interactable_type',
        'interactable_id',
        'user_id',
        'type_interaction',
        'description',
        'metadata',
        'date_interaction',
    ];

    protected $casts = [
        'metadata' => 'array',
        'date_interaction' => 'datetime',
    ];

    const TYPES_INTERACTION = [
        'consultation' => 'Consultation',
        'modification' => 'Modification',
        'appel' => 'Appel',
        'rdv' => 'Rendez-vous',
        'email' => 'Email',
        'conversion' => 'Conversion',
        'creation' => 'Création',
    ];

    public function interactable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeInteractionLabelAttribute(): string
    {
        return self::TYPES_INTERACTION[$this->type_interaction] ?? $this->type_interaction;
    }

    public function scopePourEntite($query, string $type, int $id)
    {
        return $query->where('interactable_type', $type)
            ->where('interactable_id', $id);
    }

    public function scopePourUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeParType($query, string $type)
    {
        return $query->where('type_interaction', $type);
    }

    public static function enregistrerInteraction(
        Model $entite,
        int $userId,
        string $type = 'consultation',
        ?string $description = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'interactable_type' => get_class($entite),
            'interactable_id' => $entite->id,
            'user_id' => $userId,
            'type_interaction' => $type,
            'description' => $description,
            'metadata' => $metadata,
            'date_interaction' => now(),
        ]);
    }
}
