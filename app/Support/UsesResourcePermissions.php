<?php

namespace App\Support;

trait UsesResourcePermissions
{
    protected static function userCanResourcePermission(string $action): bool
    {
        $permission = static::resourcePermissionName($action);

        return AccessRightsCatalog::userCan(auth()->user(), $permission);
    }

    protected static function userCanViewResourceList(): bool
    {
        if (AccessRightsCatalog::hasPermission(static::resourcePermissionName('view_any'))) {
            return static::userCanResourcePermission('view_any');
        }

        return static::userCanResourcePermission('view');
    }

    protected static function resourcePermissionName(string $action): string
    {
        $mappedAction = static::$permissionActionMap[$action] ?? $action;

        return static::$permissionPrefix.'.'.$mappedAction;
    }

    public static function userCanFieldPermission(string $field, string $action): bool
    {
        return AccessRightsCatalog::userCanField(auth()->user(), static::$permissionPrefix, $field, $action);
    }

    public static function userCanShowField(string $field): bool
    {
        return static::userCanFieldPermission($field, 'show');
    }

    /**
     * @param  array<int, object>  $components
     * @param  array<string, string>  $fieldMap
     * @return array<int, object>
     */
    public static function applyShowFieldPermissions(array $components, array $fieldMap = []): array
    {
        return array_map(
            fn (object $component): object => static::applyShowFieldPermissionToComponent($component, $fieldMap),
            $components
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function filterFormDataForFieldPermissions(array $data, string $action): array
    {
        return AccessRightsCatalog::filterFieldDataForUser(auth()->user(), static::$permissionPrefix, $data, $action);
    }

    /**
     * @param  array<string, string>  $fieldMap
     */
    protected static function applyShowFieldPermissionToComponent(object $component, array $fieldMap = []): object
    {
        if (method_exists($component, 'getChildComponents') && method_exists($component, 'childComponents')) {
            $component->childComponents(static::applyShowFieldPermissions($component->getChildComponents(), $fieldMap));
        }

        if (method_exists($component, 'getComponents') && method_exists($component, 'components')) {
            $component->components(static::applyShowFieldPermissions($component->getComponents(), $fieldMap));
        }

        if (! method_exists($component, 'getName') || ! method_exists($component, 'hidden')) {
            return $component;
        }

        $field = static::fieldNameForShowPermission($component->getName(), $fieldMap);

        return $component->hidden(fn (): bool => ! static::userCanShowField($field));
    }

    /**
     * @param  array<string, string>  $fieldMap
     */
    protected static function fieldNameForShowPermission(string $componentName, array $fieldMap = []): string
    {
        if (array_key_exists($componentName, $fieldMap)) {
            return $fieldMap[$componentName];
        }

        return str($componentName)->before('.')->snake()->toString();
    }
}
