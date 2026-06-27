<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Theme extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'label',
        'panel',
        'is_default',
        'is_active',
        'primary_color',
        'success_color',
        'warning_color',
        'danger_color',
        'info_color',
        'gray_color',
        'primary_color_dark',
        'success_color_dark',
        'warning_color_dark',
        'danger_color_dark',
        'info_color_dark',
        'gray_color_dark',
        'brand_name',
        'brand_logo_path',
        'favicon_path',
        'custom_css',
        'metadata',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the default theme for a specific panel.
     */
    public static function getDefaultForPanel(string $panel): ?self
    {
        return static::where('panel', $panel)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get the active theme for a specific panel.
     */
    public static function getActiveForPanel(string $panel): ?self
    {
        return static::where('panel', $panel)
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->first();
    }

    /**
     * Get colors as array for Filament.
     */
    public function getColors(): array
    {
        return [
            'primary' => $this->primary_color,
            'success' => $this->success_color,
            'warning' => $this->warning_color,
            'danger' => $this->danger_color,
            'info' => $this->info_color,
            'gray' => $this->gray_color,
        ];
    }

    /**
     * Get dark mode colors as array for Filament.
     */
    public function getDarkModeColors(): array
    {
        return [
            'primary' => $this->primary_color_dark ?? $this->primary_color,
            'success' => $this->success_color_dark ?? $this->success_color,
            'warning' => $this->warning_color_dark ?? $this->warning_color,
            'danger' => $this->danger_color_dark ?? $this->danger_color,
            'info' => $this->info_color_dark ?? $this->info_color,
            'gray' => $this->gray_color_dark ?? $this->gray_color,
        ];
    }

    /**
     * Scope for panel.
     */
    public function scopeForPanel($query, string $panel)
    {
        return $query->where('panel', $panel);
    }

    /**
     * Scope for active themes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for default themes.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
