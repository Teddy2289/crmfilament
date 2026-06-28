<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldPermission extends Model
{
    protected $fillable = [
        'role',
        'resource',
        'field_name',
        'visible_list',
        'visible_view',
        'visible_edit',
        'read_only',
    ];

    protected $casts = [
        'visible_list' => 'boolean',
        'visible_view' => 'boolean',
        'visible_edit' => 'boolean',
        'read_only' => 'boolean',
    ];

    public function scopeForResource($query, string $resource)
    {
        return $query->where('resource', $resource);
    }

    public function scopeForRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public static function canViewField(string $role, string $resource, string $field, string $context = 'list'): bool
    {
        $permission = self::where('role', $role)
            ->where('resource', $resource)
            ->where('field_name', $field)
            ->first();

        if (! $permission) {
            return true; // Default to visible if no permission set
        }

        return match ($context) {
            'list' => $permission->visible_list,
            'view' => $permission->visible_view,
            'edit' => $permission->visible_edit,
            default => true,
        };
    }

    public static function isFieldReadOnly(string $role, string $resource, string $field): bool
    {
        $permission = self::where('role', $role)
            ->where('resource', $resource)
            ->where('field_name', $field)
            ->first();

        return $permission ? $permission->read_only : false;
    }
}
