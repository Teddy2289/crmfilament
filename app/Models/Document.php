<?php

namespace App\Models;

use App\Enums\OrganizationCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Document extends Model
{
    protected $table = 'documents';

    protected $casts = [
        'categorie' => OrganizationCategory::class,
        'taille' => 'integer',
    ];

    protected $fillable = [
        'nom_fichier',
        'categorie',
        'path',
        'mime_type',
        'taille',
        'documentable_type',
        'documentable_id',
        'uploaded_by',
    ];

    // ── Accesseurs ──────────────────────────────────────────────────
    public function getUrlAttribute(): string
    {
        return Storage::url($this->path);
    }

    public function getTailleFormateeAttribute(): string
    {
        if (! $this->taille) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $this->taille;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2).' '.$units[$unit];
    }

    public function getExtensionAttribute(): string
    {
        return pathinfo($this->nom_fichier, PATHINFO_EXTENSION);
    }

    public function getCategorieLabelAttribute(): string
    {
        return $this->categorie->label();
    }

    public function getCategorieColorAttribute(): string
    {
        return $this->categorie->color();
    }

    public function getCategorieIconAttribute(): string
    {
        return $this->categorie->icon();
    }

    public function getIconAttribute(): string
    {
        return match ($this->mime_type) {
            'application/pdf' => 'heroicon-o-document-text',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'heroicon-o-document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'heroicon-o-table-cells',
            'image/jpeg', 'image/png', 'image/gif', 'image/webp' => 'heroicon-o-photo',
            'text/csv' => 'heroicon-o-arrows-right-left',
            'application/zip', 'application/x-rar-compressed' => 'heroicon-o-archive-box',
            default => 'heroicon-o-document',
        };
    }

    public function getEstImageAttribute(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    public function getEstPDFAttribute(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    // ── Méthodes métier ─────────────────────────────────────────────
    public function telecharger(): StreamedResponse
    {
        return Storage::download($this->path, $this->nom_fichier);
    }

    public function supprimerFichier(): bool
    {
        if (Storage::exists($this->path)) {
            return Storage::delete($this->path);
        }

        return true;
    }

    public function renommer(string $nouveauNom): void
    {
        $ancienPath = $this->path;
        $nouveauPath = dirname($this->path).'/'.$nouveauNom;

        if (Storage::exists($ancienPath)) {
            Storage::move($ancienPath, $nouveauPath);
        }

        $this->update([
            'nom_fichier' => $nouveauNom,
            'path' => $nouveauPath,
        ]);
    }

    public function deplacerVersCategorie(OrganizationCategory $nouvelleCategorie): void
    {
        $nouveauDossier = strtolower($nouvelleCategorie->value);
        $ancienPath = $this->path;
        $nouveauPath = $nouveauDossier.'/'.$this->nom_fichier;

        if (Storage::exists($ancienPath)) {
            Storage::move($ancienPath, $nouveauPath);
        }

        $this->update([
            'categorie' => $nouvelleCategorie,
            'path' => $nouveauPath,
        ]);
    }

    // ── Scopes ──────────────────────────────────────────────────────
    public function scopeParCategorie($query, OrganizationCategory $categorie): Builder
    {
        return $query->where('categorie', $categorie);
    }

    public function scopePartenaires($query): Builder
    {
        return $query->where('categorie', OrganizationCategory::Partenaires);
    }

    public function scopeArtisans($query): Builder
    {
        return $query->where('categorie', OrganizationCategory::Artisans);
    }

    public function scopeContrats($query): Builder
    {
        return $query->where('categorie', OrganizationCategory::Contrats);
    }

    public function scopeImages($query): Builder
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    public function scopePDFs($query): Builder
    {
        return $query->where('mime_type', 'application/pdf');
    }

    public function scopeUploadesPar($query, int $userId): Builder
    {
        return $query->where('uploaded_by', $userId);
    }

    public function scopeRecents($query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeVolumineux($query, int $tailleMin = 1048576): Builder // 1MB
    {
        return $query->where('taille', '>=', $tailleMin);
    }

    public function scopePourEntite($query, Model $entity): Builder
    {
        return $query->where('documentable_type', get_class($entity))
            ->where('documentable_id', $entity->id);
    }

    // ── Méthodes statiques ──────────────────────────────────────────
    public static function uploadFichier(
        $fichier,
        Model $entite,
        OrganizationCategory $categorie,
        ?int $userId = null
    ): self {
        $nomFichier = time().'_'.preg_replace('/[^a-zA-Z0-9._-]/', '_', $fichier->getClientOriginalName());
        $dossier = strtolower($categorie->value);
        $path = $fichier->storeAs($dossier, $nomFichier, 'public');

        return static::create([
            'nom_fichier' => $fichier->getClientOriginalName(),
            'categorie' => $categorie,
            'path' => $path,
            'mime_type' => $fichier->getMimeType(),
            'taille' => $fichier->getSize(),
            'documentable_type' => get_class($entite),
            'documentable_id' => $entite->id,
            'uploaded_by' => $userId ?? auth()->id(),
        ]);
    }

    public static function getKpis(): array
    {
        return [
            'total' => static::count(),
            'taille_totale' => static::sum('taille'),
            'par_categorie' => static::getRepartitionParCategorie(),
            'par_type_mime' => static::getRepartitionParTypeMime(),
            'recemment_uploades' => static::recents()->take(10)->get(),
        ];
    }

    public static function getRepartitionParCategorie(): array
    {
        return collect(OrganizationCategory::cases())
            ->mapWithKeys(function ($cat) {
                return [$cat->value => static::where('categorie', $cat)->count()];
            })
            ->toArray();
    }

    public static function getRepartitionParTypeMime(): array
    {
        return static::selectRaw("
            CASE
                WHEN mime_type LIKE 'image/%' THEN 'Images'
                WHEN mime_type = 'application/pdf' THEN 'PDF'
                WHEN mime_type LIKE 'application/vnd.openxmlformats-officedocument.word%' THEN 'Word'
                WHEN mime_type LIKE 'application/vnd.openxmlformats-officedocument.spreadsheet%' THEN 'Excel'
                WHEN mime_type LIKE 'text/%' THEN 'Texte'
                ELSE 'Autres'
            END as type,
            COUNT(*) as total
        ")
            ->groupBy('type')
            ->orderByDesc('total')
            ->get()
            ->pluck('total', 'type')
            ->toArray();
    }

    // ── Boot ────────────────────────────────────────────────────────
    protected static function booted(): void
    {
        static::deleting(function (Document $document) {
            // Supprimer le fichier physique
            if (Storage::exists($document->path)) {
                Storage::delete($document->path);
            }
        });
    }

    // ── Relations ────────────────────────────────────────────────────
    public function documentable()
    {
        return $this->morphTo();
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
