<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $fillable = [
        'role_id',
        'resource',
        'action',
        'fields',
        'autorise',
    ];

    protected $casts = [
        'fields' => 'array',
        'autorise' => 'boolean',
    ];

    const ACTIONS = [
        'view' => 'Voir',
        'create' => 'Créer',
        'update' => 'Modifier',
        'delete' => 'Supprimer',
        'export' => 'Exporter',
        'import' => 'Importer',
    ];

    // ── Relations ────────────────────────────────────────────────────
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // ── Scopes ──────────────────────────────────────────────────────
    public function scopePourRole($query, int $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    public function scopePourRessource($query, string $resource)
    {
        return $query->where('resource', $resource);
    }

    public function scopeAutorises($query)
    {
        return $query->where('autorise', true);
    }

    // ── Méthodes métier ─────────────────────────────────────────────
    public function aAccesChamp(string $champ): bool
    {
        if (empty($this->fields)) {
            return true; // Tous les champs autorisés
        }

        return in_array($champ, $this->fields);
    }

    public static function verifierPermission(int $roleId, string $resource, string $action): bool
    {
        $permission = self::where('role_id', $roleId)
            ->where('resource', $resource)
            ->where('action', $action)
            ->first();

        if (!$permission) {
            return false; // Pas de permission = refusé
        }

        return $permission->autorise;
    }
}
