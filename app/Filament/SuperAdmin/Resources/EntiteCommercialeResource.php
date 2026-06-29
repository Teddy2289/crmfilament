<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\EntiteCommercialeResource\Pages;
use App\Models\CustomField;
use App\Models\CustomFieldValue;
use App\Models\EntiteCommerciale;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EntiteCommercialeResource extends Resource
{
    protected static ?string $model = EntiteCommerciale::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Entités commerciales';

    protected static ?string $navigationGroup = 'Organisation';

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
        $customFields = CustomField::forModel(EntiteCommerciale::class)->active()->get();

        foreach ($customFields as $field) {
            $fieldName = 'custom_field_' . $field->id;
            $value = request()->input($fieldName);

            if ($value !== null) {
                CustomFieldValue::updateOrCreate(
                    [
                        'custom_field_id' => $field->id,
                        'model_type' => EntiteCommerciale::class,
                        'model_id' => $record->id,
                    ],
                    [
                        'value' => $value,
                    ]
                );
            }
        }
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('nom')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Champs personnalisés')
                    ->schema(function (callable $get, ?EntiteCommerciale $record) {
                        $customFields = CustomField::forModel(EntiteCommerciale::class)
                            ->active()
                            ->ordered()
                            ->get();

                        return $customFields->map(function ($field) use ($record) {
                            $component = match ($field->type) {
                                'text' => Forms\Components\TextInput::make('custom_field_' . $field->id),
                                'textarea' => Forms\Components\Textarea::make('custom_field_' . $field->id),
                                'number' => Forms\Components\TextInput::make('custom_field_' . $field->id)->numeric(),
                                'select' => Forms\Components\Select::make('custom_field_' . $field->id)
                                    ->options($field->options ?? []),
                                'checkbox' => Forms\Components\Checkbox::make('custom_field_' . $field->id),
                                'date' => Forms\Components\DatePicker::make('custom_field_' . $field->id),
                                'email' => Forms\Components\TextInput::make('custom_field_' . $field->id)->email(),
                                'tel' => Forms\Components\TextInput::make('custom_field_' . $field->id)->tel(),
                                default => Forms\Components\TextInput::make('custom_field_' . $field->id),
                            };

                            $component->label($field->name);

                            if ($field->required) {
                                $component->required();
                            }

                            if ($field->placeholder) {
                                $component->placeholder($field->placeholder);
                            }

                            if ($field->helper_text) {
                                $component->helperText($field->helper_text);
                            }

                            // Charger la valeur existante si on est en édition
                            if ($record) {
                                $value = CustomFieldValue::where('custom_field_id', $field->id)
                                    ->where('model_type', EntiteCommerciale::class)
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
                    })
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nom')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEntiteCommerciales::route('/'),
            'create' => Pages\CreateEntiteCommerciale::route('/create'),
            'edit' => Pages\EditEntiteCommerciale::route('/{record}/edit'),
        ];
    }
}
