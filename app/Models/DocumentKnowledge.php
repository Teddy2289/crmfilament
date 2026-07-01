<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentKnowledge extends Model
{
    use SoftDeletes;

    protected $table = 'document_knowledges';

    protected $fillable = [
        'titre',
        'description',
        'type',
        'categorie',
        'fichier_path',
        'fichier_nom',
        'fichier_type',
        'taille_octets',
        'created_by',
        'updated_by',
        'est_publique',
        'ordre',
    ];

    protected $casts = [
        'est_publique' => 'boolean',
        'taille_octets' => 'integer',
        'ordre' => 'integer',
    ];

    public const TYPES = [
        'procedure' => 'Procédure',
        'script' => 'Script',
        'checklist' => 'Liste de contrôle',
        'politique' => 'Politique',
        'template' => 'Modèle',
        'guide' => 'Guide',
        'faq' => 'FAQ / Objections',
        'modele_mail' => 'Modèle d\'e-mail',
        'modele_fiche' => 'Modèle de fiche récapitulative',
        'cdc' => 'Cahier des charges',
        'autre' => 'Autre',
    ];

    public const CATEGORIES = [
        'cdc' => 'CDC',
        'commercial' => 'Commercial',
        'operationnel' => 'Opérationnel',
        'technique' => 'Technique',
        'it' => 'IT',
        'rh' => 'RH',
        'juridique' => 'Juridique',
        'autre' => 'Autre',
    ];

    // ── Accesseurs ──────────────────────────────────────────────────
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getCategorieLabelAttribute(): string
    {
        return self::CATEGORIES[$this->categorie] ?? $this->categorie;
    }

    public function getTailleFormateeAttribute(): string
    {
        if (!$this->taille_octets) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->taille_octets;
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }

    public function getUrlAttribute(): string
    {
        if (!$this->fichier_path) {
            return '';
        }

        return asset('storage/' . $this->fichier_path);
    }

    // ── Scopes ──────────────────────────────────────────────────────
    public function scopePublics($query)
    {
        return $query->where('est_publique', true);
    }

    public function scopePrives($query)
    {
        return $query->where('est_publique', false);
    }

    public function scopeParType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeParCategorie($query, string $categorie)
    {
        return $query->where('categorie', $categorie);
    }

    public function scopeRecherche($query, string $terme)
    {
        return $query->where(function ($q) use ($terme) {
            $q->where('titre', 'like', "%{$terme}%")
                ->orWhere('description', 'like', "%{$terme}%");
        });
    }

    // ── Relations ────────────────────────────────────────────────────
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
