<?php

namespace App\Support;

trait UsesResourcePermissions
{
    public static function canAccess(): bool
    {
        return static::userCanViewResourceList();
    }

    public static function canViewAny(): bool
    {
        return static::userCanViewResourceList();
    }

    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return static::userCanResourcePermission('view');
    }

    public static function canCreate(): bool
    {
        return static::userCanResourcePermission('create');
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return static::userCanResourcePermission('update');
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return static::userCanResourcePermission('delete');
    }

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

    public static function userCanCreateField(string $field): bool
    {
        return static::userCanFieldPermission($field, 'create');
    }

    public static function userCanEditField(string $field): bool
    {
        return static::userCanFieldPermission($field, 'edit');
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
     * @param  array<int, object>  $components
     * @param  array<string, string>  $fieldMap
     * @return array<int, object>
     */
    public static function applyFormFieldPermissions(array $components, array $fieldMap = []): array
    {
        return array_map(
            fn (object $component): object => static::applyFormFieldPermissionToComponent($component, $fieldMap),
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

    public static function shouldHideFormField(string $field, ?string $operation): bool
    {
        return match ($operation) {
            'create' => ! static::userCanCreateField($field),
            'edit' => ! static::userCanShowField($field) && ! static::userCanEditField($field),
            'view' => ! static::userCanShowField($field),
            default => false,
        };
    }

    public static function shouldDisableFormField(string $field, ?string $operation): bool
    {
        return $operation === 'edit'
            && static::userCanShowField($field)
            && ! static::userCanEditField($field);
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
    protected static function applyFormFieldPermissionToComponent(object $component, array $fieldMap = []): object
    {
        if (method_exists($component, 'getChildComponents') && method_exists($component, 'childComponents')) {
            $component->childComponents(static::applyFormFieldPermissions($component->getChildComponents(), $fieldMap));
        }

        if (method_exists($component, 'getComponents') && method_exists($component, 'components')) {
            $component->components(static::applyFormFieldPermissions($component->getComponents(), $fieldMap));
        }

        if (! method_exists($component, 'getName')) {
            return $component;
        }

        $field = static::fieldNameForPermission($component->getName(), $fieldMap);

        if (method_exists($component, 'hidden')) {
            $component->hidden(fn (?string $operation = null): bool => static::shouldHideFormField($field, $operation));
        }

        if (method_exists($component, 'disabled') && ! static::componentHasCustomDisabledCondition($component)) {
            $component->disabled(fn (?string $operation = null): bool => static::shouldDisableFormField($field, $operation));
        }

        return $component;
    }

    /**
     * @param  array<string, string>  $fieldMap
     */
    protected static function fieldNameForShowPermission(string $componentName, array $fieldMap = []): string
    {
        return static::fieldNameForPermission($componentName, $fieldMap);
    }

    /**
     * @param  array<string, string>  $fieldMap
     */
    protected static function fieldNameForPermission(string $componentName, array $fieldMap = []): string
    {
        if (array_key_exists($componentName, $fieldMap)) {
            return $fieldMap[$componentName];
        }

        $field = str($componentName)->before('.')->snake()->toString();

        if (str_contains($componentName, '.')) {
            if (AccessRightsCatalog::hasFieldDefinition(static::$permissionPrefix, $field)) {
                return $field;
            }

            $foreignKey = "{$field}_id";

            if (AccessRightsCatalog::hasFieldDefinition(static::$permissionPrefix, $foreignKey)) {
                return $foreignKey;
            }
        }

        return $field;
    }

    protected static function componentHasCustomDisabledCondition(object $component): bool
    {
        if (! property_exists($component, 'isDisabled')) {
            return false;
        }

        try {
            $property = new \ReflectionProperty($component, 'isDisabled');
            $property->setAccessible(true);

            return $property->getValue($component) !== false;
        } catch (\Throwable) {
            return false;
        }
    }
}
