<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\CrmSettingResource\Pages\CreateCrmSetting;
use App\Filament\SuperAdmin\Resources\CrmSettingResource\Pages\EditCrmSetting;
use App\Filament\SuperAdmin\Resources\CrmSettingResource\Pages\ListCrmSettings;
use App\Models\CrmSetting;
use App\Services\Crm\CrmSettingsService;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class CrmSettingResource extends Resource
{
    protected static ?string $model = CrmSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Paramètres CRM';

    protected static ?string $navigationGroup = 'Paramétrage CRM';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Paramètre';

    protected static ?string $pluralModelLabel = 'Paramètres CRM';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Paramètre CRM')
                ->persistTabInQueryString()
                ->columnSpanFull()
                ->tabs([
                    Forms\Components\Tabs\Tab::make('Classement')
                        ->icon('heroicon-o-rectangle-group')
                        ->schema([
                            Forms\Components\Section::make('Application')
                                ->description('Regroupe le paramètre dans le bon CRM et le bon domaine fonctionnel.')
                                ->schema([
                                    Forms\Components\Radio::make('default_crm')
                                        ->label('CRM par défaut')
                                        ->options([
                                            'ns-conseil' => 'NS Conseil',
                                            'allopro' => 'AlloPro',
                                        ])
                                        ->default('ns-conseil')
                                        ->inline()
                                        ->required(),

                                    Forms\Components\Select::make('groupe')
                                        ->label('Groupe')
                                        ->options(static::groupOptions())
                                        ->searchable()
                                        ->live()
                                        ->required()
                                        ->native(false),

                                    Forms\Components\TextInput::make('ordre')
                                        ->label('Ordre')
                                        ->numeric()
                                        ->default(0),
                                ])
                                ->columns(3),

                            Forms\Components\Section::make('Identification')
                                ->schema([
                                    Forms\Components\TextInput::make('cle')
                                        ->label('Clé technique')
                                        ->prefix(fn (Get $get): ?string => $get('groupe') ? $get('groupe').'.' : null)
                                        ->required()
                                        ->helperText('Exemple : max_standard_attempts'),

                                    Forms\Components\TextInput::make('label')
                                        ->label('Libellé')
                                        ->required(),
                                ])
                                ->columns(2),
                        ]),

                    Forms\Components\Tabs\Tab::make('Valeur')
                        ->icon('heroicon-o-code-bracket-square')
                        ->schema([
                            Forms\Components\Section::make('Saisie')
                                ->schema([
                                    Forms\Components\Select::make('type')
                                        ->label('Type de valeur')
                                        ->options(static::typeOptions())
                                        ->required()
                                        ->live()
                                        ->native(false)
                                        ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                                            if ($state === 'bool' && blank($get('valeur'))) {
                                                $set('valeur', 'false');
                                            }

                                            if ($state === 'json' && blank($get('valeur'))) {
                                                $set('valeur', '[]');
                                            }
                                        }),

                                    Forms\Components\Textarea::make('valeur')
                                        ->label('Valeur')
                                        ->required()
                                        ->rows(fn (Get $get): int => $get('type') === 'json' ? 8 : 3)
                                        ->helperText(fn (Get $get): string => static::valueHelper($get('type')))
                                        ->rule(fn (Get $get): Closure => static::valueValidationRule($get('type')))
                                        ->columnSpanFull(),

                                    Forms\Components\Placeholder::make('apercu_valeur')
                                        ->label('Aperçu')
                                        ->content(fn (Get $get): HtmlString => static::valuePreview(
                                            type: $get('type'),
                                            value: $get('valeur'),
                                        ))
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),
                        ]),

                    Forms\Components\Tabs\Tab::make('Documentation')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Forms\Components\Section::make('Note administrateur')
                                ->schema([
                                    Forms\Components\Textarea::make('description')
                                        ->label('Description')
                                        ->rows(4)
                                        ->helperText('Expliquez l’impact métier du paramètre pour les prochains administrateurs.')
                                        ->columnSpanFull(),
                                ]),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('default_crm')
                    ->label('CRM')
                    ->formatStateUsing(fn (?string $state): string => static::crmLabel($state))
                    ->badge()
                    ->color(fn (?string $state): string => $state === 'allopro' ? 'warning' : 'info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('groupe')
                    ->label('Groupe')
                    ->formatStateUsing(fn (?string $state): string => static::groupOptions()[$state] ?? (string) $state)
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('label')
                    ->label('Paramètre')
                    ->searchable()
                    ->weight('semibold')
                    ->description(fn (CrmSetting $record): string => $record->description ?: $record->cle),

                Tables\Columns\TextColumn::make('cle')
                    ->label('Clé')
                    ->fontFamily('mono')
                    ->copyable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('valeur')
                    ->label('Valeur')
                    ->formatStateUsing(fn (?string $state, CrmSetting $record): string => static::formatSettingValue($record))
                    ->limit(80)
                    ->wrap(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (?string $state): string => static::typeOptions()[$state] ?? (string) $state)
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('ordre')
                    ->label('Ordre')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('groupe')
            ->groups([
                Group::make('groupe')
                    ->label('Groupe')
                    ->getTitleFromRecordUsing(fn (CrmSetting $record): string => static::groupOptions()[$record->groupe] ?? $record->groupe),
                Group::make('default_crm')
                    ->label('CRM')
                    ->getTitleFromRecordUsing(fn (CrmSetting $record): string => static::crmLabel($record->default_crm)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('groupe')
                    ->label('Groupe')
                    ->options(static::groupOptions()),

                Tables\Filters\SelectFilter::make('default_crm')
                    ->label('CRM')
                    ->options([
                        'ns-conseil' => 'NS Conseil',
                        'allopro' => 'AlloPro',
                    ]),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options(static::typeOptions()),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(fn () => app(CrmSettingsService::class)->forget()),
            ])
            ->bulkActions([])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCrmSettings::route('/'),
            'create' => CreateCrmSetting::route('/create'),
            'edit' => EditCrmSetting::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function groupOptions(): array
    {
        return [
            'prospection' => 'Prospection',
            'qf' => 'Qualification QF',
            'roles' => 'Rôles',
            'mail' => 'Emails',
            'workflow' => 'Workflow',
            'ringover' => 'Ringover',
            'theme' => 'Thèmes',
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
            'bool' => 'Oui / Non',
            'json' => 'JSON',
        ];
    }

    private static function crmLabel(?string $crm): string
    {
        return match ($crm) {
            'allopro' => 'AlloPro',
            default => 'NS Conseil',
        };
    }

    private static function valueHelper(?string $type): string
    {
        return match ($type) {
            'int' => 'Saisissez un nombre entier, par exemple 3.',
            'bool' => 'Valeurs acceptées : true, false, 1, 0, oui, non.',
            'json' => 'Saisissez une liste ou un objet JSON valide, par exemple ["team_leader","administrateur"].',
            default => 'Saisissez une valeur texte.',
        };
    }

    private static function valueValidationRule(?string $type): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($type): void {
            if ($type === 'int' && filter_var($value, FILTER_VALIDATE_INT) === false) {
                $fail('La valeur doit être un nombre entier.');
            }

            if ($type === 'bool' && ! in_array(strtolower((string) $value), ['true', 'false', '1', '0', 'yes', 'no', 'oui', 'non'], true)) {
                $fail('La valeur doit être un booléen : true, false, oui ou non.');
            }

            if ($type === 'json') {
                json_decode((string) $value, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $fail('La valeur doit être un JSON valide.');
                }
            }
        };
    }

    private static function valuePreview(?string $type, mixed $value): HtmlString
    {
        if ($value === null || $value === '') {
            return new HtmlString('<span class="text-sm text-gray-500">Aucune valeur saisie.</span>');
        }

        $preview = match ($type) {
            'json' => static::formatJsonPreview((string) $value),
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Oui' : 'Non',
            'int' => (string) (int) $value,
            default => (string) $value,
        };

        return new HtmlString('<code class="text-sm">'.e($preview).'</code>');
    }

    private static function formatSettingValue(CrmSetting $setting): string
    {
        if ($setting->type === 'json') {
            return static::formatJsonPreview((string) $setting->valeur);
        }

        if ($setting->type === 'bool') {
            return filter_var($setting->valeur, FILTER_VALIDATE_BOOLEAN) ? 'Oui' : 'Non';
        }

        return (string) $setting->valeur;
    }

    private static function formatJsonPreview(string $value): string
    {
        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $value;
        }

        return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: $value;
    }
}
