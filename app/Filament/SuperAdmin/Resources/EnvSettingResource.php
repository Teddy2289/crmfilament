<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\EnvSettingResource\Pages;
use App\Models\EnvSetting;
use App\Services\EnvSettingsService;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EnvSettingResource extends Resource
{
    protected static ?string $model = EnvSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Variables .env';

    protected static ?string $navigationGroup = 'Paramétrage CRM';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Variable';

    protected static ?string $pluralModelLabel = 'Variables .env';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informations')
                ->schema([
                    Forms\Components\TextInput::make('key')
                        ->label('Clé')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText('Nom de la variable d\'environnement, ex: APP_NAME'),

                    Forms\Components\TextInput::make('label')
                        ->label('Libellé')
                        ->required()
                        ->helperText('Nom affiché dans l\'interface'),

                    Forms\Components\Select::make('group')
                        ->label('Groupe')
                        ->options(static::groupOptions())
                        ->default('general')
                        ->required()
                        ->searchable(),

                    Forms\Components\Select::make('type')
                        ->label('Type')
                        ->options(static::typeOptions())
                        ->default('string')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                            if ($state === 'bool' && blank($get('value'))) {
                                $set('value', 'false');
                            }
                        }),
                ])
                ->columns(2),

            Forms\Components\Section::make('Valeur')
                ->schema([
                    Forms\Components\Textarea::make('value')
                        ->label('Valeur')
                        ->required()
                        ->rows(fn (Get $get): int => $get('type') === 'json' ? 8 : 3)
                        ->helperText(fn (Get $get): string => static::valueHelper($get('type')))
                        ->rule(fn (Get $get): Closure => static::valueValidationRule($get('type')))
                        ->password(fn (Get $get): bool => $get('is_sensitive') === true)
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('is_sensitive')
                        ->label('Donnée sensible')
                        ->helperText('Masquer la valeur dans l\'interface'),

                    Forms\Components\Toggle::make('is_editable')
                        ->label('Modifiable')
                        ->default(true)
                        ->helperText('Autoriser la modification de cette variable'),
                ])
                ->columns(2),

            Forms\Components\Section::make('Documentation')
                ->schema([
                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->rows(4)
                        ->helperText('Expliquez l\'utilisation de cette variable')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('sort_order')
                        ->label('Ordre d\'affichage')
                        ->numeric()
                        ->default(0),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('group')
                    ->label('Groupe')
                    ->formatStateUsing(fn (string $state): string => static::groupOptions()[$state] ?? $state)
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('key')
                    ->label('Clé')
                    ->searchable()
                    ->fontFamily('mono')
                    ->copyable(),

                Tables\Columns\TextColumn::make('label')
                    ->label('Libellé')
                    ->searchable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('value')
                    ->label('Valeur')
                    ->formatStateUsing(fn (EnvSetting $record): string => static::formatValue($record))
                    ->limit(50)
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_sensitive')
                    ->label('Sensible')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_editable')
                    ->label('Modifiable')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Ordre')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('group', 'sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->label('Groupe')
                    ->options(static::groupOptions()),

                Tables\Filters\TernaryFilter::make('is_sensitive')
                    ->label('Donnée sensible')
                    ->placeholder('Toutes')
                    ->trueLabel('Sensible')
                    ->falseLabel('Non sensible'),

                Tables\Filters\TernaryFilter::make('is_editable')
                    ->label('Modifiable')
                    ->placeholder('Toutes')
                    ->trueLabel('Modifiable')
                    ->falseLabel('Non modifiable'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(fn () => app(EnvSettingsService::class)->syncToEnv()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEnvSettings::route('/'),
            'create' => Pages\CreateEnvSetting::route('/create'),
            'edit' => Pages\EditEnvSetting::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function groupOptions(): array
    {
        return [
            'general' => 'Général',
            'database' => 'Base de données',
            'mail' => 'Email',
            'cache' => 'Cache',
            'queue' => 'Queue',
            'storage' => 'Stockage',
            'security' => 'Sécurité',
            'third_party' => 'Services tiers',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            'string' => 'Texte',
            'int' => 'Nombre entier',
            'bool' => 'Booléen',
            'json' => 'JSON',
        ];
    }

    private static function valueHelper(?string $type): string
    {
        return match ($type) {
            'int' => 'Saisissez un nombre entier, par exemple 3.',
            'bool' => 'Valeurs acceptées : true, false, 1, 0, yes, no.',
            'json' => 'Saisissez une liste ou un objet JSON valide, par exemple ["value1","value2"].',
            default => 'Saisissez une valeur texte.',
        };
    }

    private static function valueValidationRule(?string $type): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($type): void {
            if ($type === 'int' && filter_var($value, FILTER_VALIDATE_INT) === false) {
                $fail('La valeur doit être un nombre entier.');
            }

            if ($type === 'bool' && ! in_array(strtolower((string) $value), ['true', 'false', '1', '0', 'yes', 'no'], true)) {
                $fail('La valeur doit être un booléen : true, false, yes ou no.');
            }

            if ($type === 'json') {
                json_decode((string) $value, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $fail('La valeur doit être un JSON valide.');
                }
            }
        };
    }

    private static function formatValue(EnvSetting $setting): string
    {
        if ($setting->is_sensitive) {
            return '••••••••';
        }

        if ($setting->type === 'json') {
            $decoded = json_decode($setting->value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        if ($setting->type === 'bool') {
            return filter_var($setting->value, FILTER_VALIDATE_BOOLEAN) ? 'Oui' : 'Non';
        }

        return (string) $setting->value;
    }
}
