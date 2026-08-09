<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDashboard extends Model
{
    protected $fillable = [
        'user_id',
        'nom',
        'par_defaut',
        'widgets_config',
        'layout_config',
    ];

    protected $casts = [
        'par_defaut' => 'boolean',
        'widgets_config' => 'array',
        'layout_config' => 'array',
    ];

    // ── Relations ────────────────────────────────────────────────────
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ──────────────────────────────────────────────────────
    public function scopeParDefaut($query)
    {
        return $query->where('par_defaut', true);
    }

    public function scopePourUtilisateur($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ── Méthodes métier ─────────────────────────────────────────────
    public function ajouterWidget(string $widgetClass, array $config = []): void
    {
        $widgets = $this->widgets_config ?? [];
        $widgets[] = [
            'class' => $widgetClass,
            'config' => $config,
            'visible' => true,
        ];
        $this->update(['widgets_config' => $widgets]);
    }

    public function retirerWidget(int $index): void
    {
        $widgets = $this->widgets_config ?? [];
        if (isset($widgets[$index])) {
            unset($widgets[$index]);
            $this->update(['widgets_config' => array_values($widgets)]);
        }
    }

    public function definirParDefaut(): void
    {
        // Retirer le défaut des autres dashboards de l'utilisateur
        UserDashboard::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['par_defaut' => false]);
        
        $this->update(['par_defaut' => true]);
    }
}
