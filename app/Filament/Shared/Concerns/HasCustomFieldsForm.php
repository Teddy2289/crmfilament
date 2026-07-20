<?php

namespace App\Filament\Shared\Concerns;

use App\Filament\Shared\Components\PhoneNumberInput;
use App\Models\CustomField;
use App\Models\CustomFieldValue;
use Filament\Forms;
use Illuminate\Database\Eloquent\Model;

/**
 * Wires a Filament resource's "Champs personnalisés" section: renders the
 * dynamic fields defined in CustomField for the resource's model, and
 * persists their values as CustomFieldValue rows on create/update.
 */
trait HasCustomFieldsForm
{
    public static function afterCreate(Model $record): void
    {
        static::saveCustomFields($record);
    }

    public static function afterUpdate(Model $record): void
    {
        static::saveCustomFields($record);
    }

    protected static function saveCustomFields(Model $record): void
    {
        $customFields = CustomField::forModel(static::$model)->active()->get();

        foreach ($customFields as $field) {
            $fieldName = 'custom_field_'.$field->id;
            $value = request()->input($fieldName);

            if ($value !== null) {
                CustomFieldValue::updateOrCreate(
                    [
                        'custom_field_id' => $field->id,
                        'model_type' => static::$model,
                        'model_id' => $record->id,
                    ],
                    [
                        'value' => $value,
                    ]
                );
            }
        }
    }

    protected static function customFieldsFormSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Champs personnalisés')
            ->icon('heroicon-o-adjustments-horizontal')
            ->schema(function (callable $get, ?Model $record) {
                $customFields = CustomField::forModel(static::$model)
                    ->active()
                    ->ordered()
                    ->get();

                return $customFields->map(function ($field) use ($record) {
                    $component = match ($field->type) {
                        'text' => Forms\Components\TextInput::make('custom_field_'.$field->id),
                        'textarea' => Forms\Components\Textarea::make('custom_field_'.$field->id),
                        'number' => Forms\Components\TextInput::make('custom_field_'.$field->id)->numeric(),
                        'select' => Forms\Components\Select::make('custom_field_'.$field->id)
                            ->options($field->options ?? []),
                        'checkbox' => Forms\Components\Checkbox::make('custom_field_'.$field->id),
                        'date' => Forms\Components\DatePicker::make('custom_field_'.$field->id),
                        'email' => Forms\Components\TextInput::make('custom_field_'.$field->id)->email(),
                        'tel' => PhoneNumberInput::make('custom_field_'.$field->id),
                        default => Forms\Components\TextInput::make('custom_field_'.$field->id),
                    };

                    $component->label($field->name);

                    if ($field->required) {
                        $component->required();
                    }

                    if ($field->placeholder && method_exists($component, 'placeholder')) {
                        $component->placeholder($field->placeholder);
                    }

                    if ($field->helper_text) {
                        $component->helperText($field->helper_text);
                    }

                    // Charger la valeur existante si on est en édition
                    if ($record) {
                        $value = CustomFieldValue::where('custom_field_id', $field->id)
                            ->where('model_type', static::$model)
                            ->where('model_id', $record->id)
                            ->first();

                        if ($value) {
                            $component->default($value->value);
                        }
                    }

                    return $component;
                })->toArray() ?: [
                    Forms\Components\Placeholder::make('no_custom_fields')
                        ->label('Aucun champ personnalisé configuré')
                        ->content('Configurez des champs personnalisés dans la section "Champs personnalisés" du menu Configuration.'),
                ];
            });
    }
}
