<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    protected $table = 'Import_logs'; // ⚠️ Respect de la casse de la migration

    protected $casts = [
        'model_type' => 'string',
        'rows_imported' => 'integer',
        'rows_skipped' => 'integer',
        'rows_failed' => 'integer',
        'errors' => 'array',
        'column_mapping' => 'array',
    ];

    protected $fillable = [
        'filename',
        'sheet_name',
        'model_type',
        'rows_imported',
        'rows_skipped',
        'rows_failed',
        'errors',
        'column_mapping',
    ];

    // ── Accesseurs ──────────────────────────────────────────────────
    public function getTauxReussiteAttribute(): float
    {
        $total = $this->rows_imported + $this->rows_skipped + $this->rows_failed;
        if ($total === 0) {
            return 0;
        }

        return round(($this->rows_imported / $total) * 100, 1);
    }

    public function getTotalRowsAttribute(): int
    {
        return $this->rows_imported + $this->rows_skipped + $this->rows_failed;
    }

    public function getModelLabelAttribute(): string
    {
        return match ($this->model_type) {
            'client' => 'Clients',
            'proposition' => 'Propositions',
            default => $this->model_type,
        };
    }

    public function getImportStatusAttribute(): string
    {
        if ($this->rows_failed > 0) {
            return 'danger';
        }
        if ($this->rows_skipped > 0) {
            return 'warning';
        }

        return 'success';
    }

    // ── Méthodes statiques ──────────────────────────────────────────
    public static function logImport(
        string $filename,
        string $sheetName,
        string $modelType,
        int $imported,
        int $skipped = 0,
        int $failed = 0,
        ?array $errors = null,
        ?array $mapping = null
    ): self {
        return static::create([
            'filename' => $filename,
            'sheet_name' => $sheetName,
            'model_type' => $modelType,
            'rows_imported' => $imported,
            'rows_skipped' => $skipped,
            'rows_failed' => $failed,
            'errors' => $errors,
            'column_mapping' => $mapping,
        ]);
    }

    public static function getDernierImport(string $modelType): ?self
    {
        return static::where('model_type', $modelType)
            ->latest()
            ->first();
    }

    public static function getKpis(): array
    {
        return [
            'total_imports' => static::count(),
            'total_rows_imported' => static::sum('rows_imported'),
            'total_rows_failed' => static::sum('rows_failed'),
            'derniers_imports' => static::latest()->take(5)->get(),
        ];
    }

    // ── Relations ────────────────────────────────────────────────────
    // Pas de relations directes, c'est un modèle de log
}
