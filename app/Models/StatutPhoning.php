<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class StatutPhoning extends Model
{
    protected $fillable = [
        'model_type',
        'groupe',
        'groupe_label',
        'code',
        'label',
        'description',
        'action_immediate',
        'note_obligatoire',
        'message_note_obligatoire',
        'delai_rappel_jours',
        'prioritaire',
        'fiche_type',
        'retire_de_file',
        'pipeline_statut',
        'compte_comme_tentative',
        'couleur',
        'icone',
        'ordre',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'ordre' => 'integer',
        'note_obligatoire' => 'boolean',
        'prioritaire' => 'boolean',
        'retire_de_file' => 'boolean',
        'compte_comme_tentative' => 'boolean',
        'delai_rappel_jours' => 'integer',
    ];

    const MODEL_TYPES = [
        'prospect' => 'Prospect',
        'partenaire' => 'Partenaire',
        'opportunite' => 'Opportunité',
        'client' => 'Client',
    ];

    const COULEURS = [
        'gray' => 'Gris',
        'blue' => 'Bleu',
        'orange' => 'Orange',
        'green' => 'Vert',
        'teal' => 'Turquoise',
        'mint' => 'Menthe',
        'red' => 'Rouge',
        'yellow' => 'Jaune',
        'purple' => 'Violet',
        'pink' => 'Rose',
    ];

    public static function forModelType(string $modelType): Collection
    {
        return static::where('model_type', $modelType)
            ->where('actif', true)
            ->orderBy('ordre')
            ->get();
    }

    /**
     * Trouve un statut phoning par type de modèle et code.
     * Utilisé par PhoningContactResolver, PhoningResultService, etc.
     */
    public static function findFor(string $modelType, string $code): ?static
    {
        return static::where('model_type', $modelType)
            ->where('code', $code)
            ->first();
    }

    /**
     * Accessor "libelle" : alias de "label" pour la compatibilité avec le design
     * (le design cible utilise "libelle" dans getStatutsPhoning()).
     */
    public function getLibelleAttribute(): string
    {
        return $this->label ?? $this->code;
    }

    /**
     * Badge style inline CSS pour l'affichage dans les composants Blade.
     * Retourne un style CSS inline combinant couleur de fond et texte blanc.
     */
    public function getBadgeStyleAttribute(): string
    {
        return match ($this->couleur) {
            'blue'   => 'background:rgb(59 130 246); color:#fff; border:none;',
            'orange' => 'background:rgb(249 115 22); color:#fff; border:none;',
            'green'  => 'background:rgb(34 197 94); color:#fff; border:none;',
            'teal'   => 'background:rgb(20 184 166); color:#fff; border:none;',
            'mint'   => 'background:rgb(0 206 201); color:#fff; border:none;',
            'red'    => 'background:rgb(239 68 68); color:#fff; border:none;',
            'yellow' => 'background:rgb(234 179 8); color:#fff; border:none;',
            'purple' => 'background:rgb(168 85 247); color:#fff; border:none;',
            'pink'   => 'background:rgb(236 72 153); color:#fff; border:none;',
            default  => 'background:rgb(156 163 175); color:#fff; border:none;',
        };
    }

    public function getCouleurCssAttribute(): string
    {
        return match ($this->couleur) {
            'blue' => 'background:rgb(59 130 246)',
            'orange' => 'background:rgb(249 115 22)',
            'green' => 'background:rgb(34 197 94)',
            'teal' => 'background:rgb(20 184 166)',
            'mint' => 'background:rgb(0 206 201)',
            'red' => 'background:rgb(239 68 68)',
            'yellow' => 'background:rgb(234 179 8)',
            'purple' => 'background:rgb(168 85 247)',
            'pink' => 'background:rgb(236 72 153)',
            default => 'background:rgb(156 163 175)',
        };
    }

    public function getCouleurFilamentAttribute(): string
    {
        return match ($this->couleur) {
            'blue' => 'info',
            'orange' => 'warning',
            'green' => 'success',
            'teal' => 'success',
            'mint' => 'success',
            'red' => 'danger',
            'yellow' => 'warning',
            'purple' => 'primary',
            'pink' => 'danger',
            default => 'gray',
        };
    }
}
