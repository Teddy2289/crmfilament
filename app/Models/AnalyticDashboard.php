<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticDashboard extends Model
{
    protected $fillable = [
        'nom',
        'description',
        'created_by',
        'widgets',
        'filters',
        'public',
        'default',
        'order',
    ];

    protected $casts = [
        'widgets' => 'array',
        'filters' => 'array',
        'public' => 'boolean',
        'default' => 'boolean',
        'order' => 'integer',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublic($query)
    {
        return $query->where('public', true);
    }

    public function scopePrivate($query)
    {
        return $query->where('public', false);
    }

    public function scopeDefault($query)
    {
        return $query->where('default', true);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    public function addWidget(array $widgetConfig): void
    {
        $widgets = $this->widgets ?? [];
        $widgets[] = $widgetConfig;
        $this->update(['widgets' => $widgets]);
    }

    public function removeWidget(string $widgetId): void
    {
        $widgets = $this->widgets ?? [];
        $widgets = array_filter($widgets, fn($w) => $w['id'] !== $widgetId);
        $this->update(['widgets' => array_values($widgets)]);
    }

    public function updateWidget(string $widgetId, array $widgetConfig): void
    {
        $widgets = $this->widgets ?? [];
        foreach ($widgets as &$widget) {
            if ($widget['id'] === $widgetId) {
                $widget = array_merge($widget, $widgetConfig);
                break;
            }
        }
        $this->update(['widgets' => $widgets]);
    }

    public function getWidget(string $widgetId): ?array
    {
        $widgets = $this->widgets ?? [];
        foreach ($widgets as $widget) {
            if ($widget['id'] === $widgetId) {
                return $widget;
            }
        }
        return null;
    }

    public function reorderWidgets(array $widgetIds): void
    {
        $widgets = $this->widgets ?? [];
        $orderedWidgets = [];
        
        foreach ($widgetIds as $id) {
            foreach ($widgets as $widget) {
                if ($widget['id'] === $id) {
                    $orderedWidgets[] = $widget;
                    break;
                }
            }
        }
        
        $this->update(['widgets' => $orderedWidgets]);
    }

    public function setAsDefault(User $user): void
    {
        // Remove default from other dashboards of the user
        static::where('created_by', $user->id)->update(['default' => false]);
        $this->update(['default' => true]);
    }
}
