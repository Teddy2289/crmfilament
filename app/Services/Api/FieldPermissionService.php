<?php

namespace App\Services\Api;

use App\Models\FieldPermission;

class FieldPermissionService
{
    /**
     * Per-request cache keyed by "{role}:{resource}:{action}" to avoid N+1
     * when rendering resource collections.
     *
     * @var array<string, array<string, bool>>
     */
    private array $cache = [];

    /**
     * Filter the given data array based on FieldPermission records.
     *
     * - If NO FieldPermission records exist for the role+resource combination,
     *   all fields are returned unchanged (default-visible behaviour).
     * - If records exist, fields whose relevant visibility column is FALSE are
     *   removed from the array.
     *
     * Supported actions:
     *   'view'             → uses visible_view
     *   'edit'|'update'|'store' → uses visible_edit
     *   anything else      → falls back to visible_view
     *
     * @param  array<string, mixed>  $data
     * @param  string                $role      User role name
     * @param  string                $resource  Resource slug (e.g. 'prospects')
     * @param  string                $action    Context: 'view', 'edit', 'update', 'store'
     * @return array<string, mixed>
     */
    public function filterFields(array $data, string $role, string $resource, string $action): array
    {
        $cacheKey = "{$role}:{$resource}:{$action}";

        // Load and cache the visibility map for this role+resource+action combo
        if (! array_key_exists($cacheKey, $this->cache)) {
            $this->cache[$cacheKey] = $this->buildVisibilityMap($role, $resource, $action);
        }

        $visibilityMap = $this->cache[$cacheKey];

        // No permissions configured → default visible, return all fields
        if (empty($visibilityMap)) {
            return $data;
        }

        // Remove fields explicitly marked as not visible
        return array_filter($data, function (mixed $value, string $key) use ($visibilityMap): bool {
            // If the field is not in the permission table, it is visible by default
            if (! array_key_exists($key, $visibilityMap)) {
                return true;
            }

            return $visibilityMap[$key] === true;
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Build a map of [field_name => bool] for the given role/resource/action.
     * Returns an empty array when no records exist (default-visible fallback).
     *
     * @return array<string, bool>
     */
    private function buildVisibilityMap(string $role, string $resource, string $action): array
    {
        $records = FieldPermission::where('role', $role)
            ->where('resource', $resource)
            ->get(['field_name', 'visible_view', 'visible_edit']);

        if ($records->isEmpty()) {
            return [];
        }

        $column = $this->resolveVisibilityColumn($action);

        $map = [];
        foreach ($records as $record) {
            $map[$record->field_name] = (bool) $record->{$column};
        }

        return $map;
    }

    /**
     * Resolve which FieldPermission column to check based on the action.
     */
    private function resolveVisibilityColumn(string $action): string
    {
        return match ($action) {
            'edit', 'update', 'store' => 'visible_edit',
            default                   => 'visible_view',
        };
    }

    /**
     * Flush the in-memory cache (useful in tests or long-running processes).
     */
    public function flushCache(): void
    {
        $this->cache = [];
    }
}
