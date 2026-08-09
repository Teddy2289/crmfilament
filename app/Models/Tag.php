<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tag extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nom',
        'slug',
        'couleur',
        'description',
        'created_by',
    ];

    protected $casts = [
        'couleur' => 'string',
    ];

    const COULEURS = [
        'gray' => 'Gris',
        'blue' => 'Bleu',
        'green' => 'Vert',
        'yellow' => 'Jaune',
        'red' => 'Rouge',
        'purple' => 'Violet',
        'pink' => 'Rose',
        'indigo' => 'Indigo',
    ];

    // ── Relations ────────────────────────────────────────────────────
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function prospects()
    {
        return $this->morphedByMany(Prospect::class, 'taggable');
    }

    public function partenaires()
    {
        return $this->morphedByMany(Partenaire::class, 'taggable');
    }

    public function clients()
    {
        return $this->morphedByMany(Client::class, 'taggable');
    }

    public function rendezVous()
    {
        return $this->morphedByMany(RendezVous::class, 'taggable');
    }

    public function opportunites()
    {
        return $this->morphedByMany(Opportunite::class, 'taggable');
    }

    // ── Scopes ──────────────────────────────────────────────────────
    public function scopeParCouleur($query, string $couleur)
    {
        return $query->where('couleur', $couleur);
    }

    // ── Méthodes métier ─────────────────────────────────────────────
    public static function creer(string $nom, string $couleur = 'gray', ?string $description = null): self
    {
        $slug = \Illuminate\Support\Str::slug($nom);
        
        return self::create([
            'nom' => $nom,
            'slug' => $slug,
            'couleur' => $couleur,
            'description' => $description,
            'created_by' => auth()->id(),
        ]);
    }
}
