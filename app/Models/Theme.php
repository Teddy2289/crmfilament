<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Schema;

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
        if (! static::tableExists()) {
            return null;
        }

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
        if (! static::tableExists()) {
            return null;
        }

        return static::where('panel', $panel)
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->first();
    }

    protected static function tableExists(): bool
    {
        try {
            return Schema::hasTable((new static())->getTable());
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Get colors as array for Filament.
     */
    public function getColors(): array
    {
        return [
            'primary' => static::filamentColor($this->primary_color, Color::Blue),
            'success' => static::filamentColor($this->success_color, Color::Emerald),
            'warning' => static::filamentColor($this->warning_color, Color::Amber),
            'danger' => static::filamentColor($this->danger_color, Color::Rose),
            'info' => static::filamentColor($this->info_color, Color::Sky),
            'gray' => static::filamentColor($this->gray_color, Color::Slate),
        ];
    }

    /**
     * Get dark mode colors as array for Filament.
     */
    public function getDarkModeColors(): array
    {
        return [
            'primary' => static::filamentColor($this->primary_color_dark ?? $this->primary_color, Color::Blue),
            'success' => static::filamentColor($this->success_color_dark ?? $this->success_color, Color::Emerald),
            'warning' => static::filamentColor($this->warning_color_dark ?? $this->warning_color, Color::Amber),
            'danger' => static::filamentColor($this->danger_color_dark ?? $this->danger_color, Color::Rose),
            'info' => static::filamentColor($this->info_color_dark ?? $this->info_color, Color::Sky),
            'gray' => static::filamentColor($this->gray_color_dark ?? $this->gray_color, Color::Slate),
        ];
    }

    protected static function filamentColor(?string $name, array $fallback): array
    {
        return match ($name) {
            'slate' => Color::Slate,
            'gray' => Color::Gray,
            'zinc' => Color::Zinc,
            'neutral' => Color::Neutral,
            'stone' => Color::Stone,
            'red' => Color::Red,
            'orange' => Color::Orange,
            'amber' => Color::Amber,
            'yellow' => Color::Yellow,
            'lime' => Color::Lime,
            'green' => Color::Green,
            'emerald' => Color::Emerald,
            'teal' => Color::Teal,
            'cyan' => Color::Cyan,
            'sky' => Color::Sky,
            'blue' => Color::Blue,
            'indigo' => Color::Indigo,
            'violet', 'purple' => Color::Purple,
            'fuchsia' => Color::Fuchsia,
            'pink' => Color::Pink,
            'rose' => Color::Rose,
            default => $fallback,
        };
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
