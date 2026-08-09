<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentVersion extends Model
{
    protected $fillable = [
        'document_id',
        'fichier',
        'chemin',
        'version',
        'commentaire',
        'uploaded_by',
    ];

    protected $casts = [
        'version' => 'integer',
    ];

    // ── Relations ────────────────────────────────────────────────────
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ── Scopes ──────────────────────────────────────────────────────
    public function scopeVersion($query, int $version)
    {
        return $query->where('version', $version);
    }

    // ── Méthodes métier ─────────────────────────────────────────────
    public static function creer(Document $document, string $fichier, string $chemin, ?string $commentaire = null): self
    {
        $derniereVersion = $document->versions()->max('version') ?? 0;
        
        return self::create([
            'document_id' => $document->id,
            'fichier' => $fichier,
            'chemin' => $chemin,
            'version' => $derniereVersion + 1,
            'commentaire' => $commentaire,
            'uploaded_by' => auth()->id(),
        ]);
    }
}
