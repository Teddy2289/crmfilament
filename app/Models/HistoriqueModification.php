<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoriqueModification extends Model
{
    protected $table = 'historique_modifications';

    protected $fillable = [
        'model_type',
        'model_id',
        'user_id',
        'champ',
        'ancienne_valeur',
        'nouvelle_valeur',
        'type_modification',
        'date_modification',
    ];

    protected $casts = [
        'ancienne_valeur' => 'json',
        'nouvelle_valeur' => 'json',
        'date_modification' => 'datetime',
    ];

    const TYPES_MODIFICATION = [
        'creation' => 'Création',
        'modification' => 'Modification',
        'suppression' => 'Suppression',
        'restauration' => 'Restauration',
    ];

    // ── Relations ────────────────────────────────────────────────────

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Accesseurs ──────────────────────────────────────────────────

    public function getTypeModificationLabelAttribute(): string
    {
        return self::TYPES_MODIFICATION[$this->type_modification] ?? $this->type_modification;
    }

    public function getAncienneValeurFormateeAttribute(): string
    {
        return $this->formaterValeur($this->ancienne_valeur);
    }

    public function getNouvelleValeurFormateeAttribute(): string
    {
        return $this->formaterValeur($this->nouvelle_valeur);
    }

    protected function formaterValeur($valeur): string
    {
        if (is_null($valeur)) {
            return '-';
        }

        if (is_array($valeur)) {
            return json_encode($valeur, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        if (is_bool($valeur)) {
            return $valeur ? 'Oui' : 'Non';
        }

        return (string) $valeur;
    }

    public function getChampLabelAttribute(): string
    {
        return match ($this->champ) {
            'nom' => 'Nom',
            'prenom' => 'Prénom',
            'email' => 'Email',
            'telephone' => 'Téléphone',
            'statut' => 'Statut',
            'commercial_id' => 'Commercial',
            'secteur_activite' => 'Secteur d\'activité',
            'ville' => 'Ville',
            'code_postal' => 'Code postal',
            'notes' => 'Notes',
            default => $this->champ,
        };
    }

    // ── Scopes ──────────────────────────────────────────────────────

    public function scopePourModel($query, string $type, int $id)
    {
        return $query->where('model_type', $type)
            ->where('model_id', $id);
    }

    public function scopePourUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeParType($query, string $type)
    {
        return $query->where('type_modification', $type);
    }

    public function scopeParChamp($query, string $champ)
    {
        return $query->where('champ', $champ);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('date_modification', 'desc');
    }

    // ── Méthodes statiques ───────────────────────────────────────────

    protected static function resolveUserId(): ?int
    {
        $currentUserId = auth()->id();

        if ($currentUserId !== null) {
            return $currentUserId;
        }

        $userId = User::query()->value('id');

        if ($userId !== null) {
            return (int) $userId;
        }

        $user = User::query()->firstOrCreate(
            ['email' => 'system@local.invalid'],
            [
                'nom' => 'System',
                'prenom' => 'User',
                'password' => bcrypt('system-password'),
                'actif' => true,
            ]
        );

        return $user->id;
    }

    public static function enregistrerModification(
        Model $model,
        string $champ,
        $ancienneValeur,
        $nouvelleValeur,
        string $type = 'modification'
    ): self {
        return self::create([
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'user_id' => self::resolveUserId(),
            'champ' => $champ,
            'ancienne_valeur' => $ancienneValeur,
            'nouvelle_valeur' => $nouvelleValeur,
            'type_modification' => $type,
            'date_modification' => now(),
        ]);
    }

    public static function enregistrerCreation(Model $model): self
    {
        return self::create([
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'user_id' => self::resolveUserId(),
            'champ' => null,
            'ancienne_valeur' => null,
            'nouvelle_valeur' => $model->toArray(),
            'type_modification' => 'creation',
            'date_modification' => now(),
        ]);
    }

    public static function enregistrerSuppression(Model $model): self
    {
        return self::create([
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'user_id' => self::resolveUserId(),
            'champ' => null,
            'ancienne_valeur' => $model->toArray(),
            'nouvelle_valeur' => null,
            'type_modification' => 'suppression',
            'date_modification' => now(),
        ]);
    }
}
