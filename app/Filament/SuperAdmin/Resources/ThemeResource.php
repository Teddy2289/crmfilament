<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\ThemeResource\Pages;
use App\Filament\SuperAdmin\Resources\ThemeResource\RelationManagers;
use App\Models\Theme;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ThemeResource extends Resource
{
    protected static ?string $model = Theme::class;

    protected static ?string $navigationIcon = 'heroicon-o-palette';

    protected static ?string $navigationLabel = 'Thèmes';

    protected static ?string $modelLabel = 'Thème';

    protected static ?string $pluralModelLabel = 'Thèmes';

    protected static ?string $navigationGroup = 'Système';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom technique')
                            ->required()
                            ->unique()
                            ->helperText('Identifiant unique du thème (ex: ns-conseil-blue)'),
                        Forms\Components\TextInput::make('label')
                            ->label('Libellé')
                            ->required()
                            ->helperText('Nom affiché dans l\'interface'),
                        Forms\Components\Select::make('panel')
                            ->label('Panel')
                            ->options([
                                'ns-conseil' => 'NS Conseil',
                                'admin' => 'Admin',
                                'super-admin' => 'Super Admin',
                                'allopro' => 'Allopro',
                            ])
                            ->required()
                            ->default('ns-conseil'),
                        Forms\Components\Toggle::make('is_default')
                            ->label('Thème par défaut')
                            ->helperText('Ce thème sera utilisé par défaut pour le panel sélectionné'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Couleurs (Mode clair)')
                    ->schema([
                        Forms\Components\Select::make('primary_color')
                            ->label('Couleur principale')
                            ->options([
                                'blue' => 'Bleu',
                                'indigo' => 'Indigo',
                                'purple' => 'Violet',
                                'pink' => 'Rose',
                                'red' => 'Rouge',
                                'orange' => 'Orange',
                                'amber' => 'Ambre',
                                'yellow' => 'Jaune',
                                'lime' => 'Vert lime',
                                'green' => 'Vert',
                                'emerald' => 'Émeraude',
                                'teal' => 'Bleu canard',
                                'cyan' => 'Cyan',
                                'sky' => 'Ciel',
                                'slate' => 'Ardoise',
                                'gray' => 'Gris',
                                'zinc' => 'Zinc',
                                'neutral' => 'Neutre',
                                'stone' => 'Pierre',
                            ])
                            ->default('blue')
                            ->required(),
                        Forms\Components\Select::make('success_color')
                            ->label('Couleur succès')
                            ->options([
                                'emerald' => 'Émeraude',
                                'green' => 'Vert',
                                'lime' => 'Vert lime',
                            ])
                            ->default('emerald')
                            ->required(),
                        Forms\Components\Select::make('warning_color')
                            ->label('Couleur avertissement')
                            ->options([
                                'amber' => 'Ambre',
                                'yellow' => 'Jaune',
                                'orange' => 'Orange',
                            ])
                            ->default('amber')
                            ->required(),
                        Forms\Components\Select::make('danger_color')
                            ->label('Couleur danger')
                            ->options([
                                'rose' => 'Rose',
                                'red' => 'Rouge',
                                'pink' => 'Rose clair',
                            ])
                            ->default('rose')
                            ->required(),
                        Forms\Components\Select::make('info_color')
                            ->label('Couleur information')
                            ->options([
                                'sky' => 'Ciel',
                                'blue' => 'Bleu',
                                'cyan' => 'Cyan',
                            ])
                            ->default('sky')
                            ->required(),
                        Forms\Components\Select::make('gray_color')
                            ->label('Couleur grise')
                            ->options([
                                'slate' => 'Ardoise',
                                'gray' => 'Gris',
                                'zinc' => 'Zinc',
                                'neutral' => 'Neutre',
                                'stone' => 'Pierre',
                            ])
                            ->default('slate')
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Couleurs (Mode sombre)')
                    ->schema([
                        Forms\Components\Select::make('primary_color_dark')
                            ->label('Couleur principale')
                            ->options([
                                'blue' => 'Bleu',
                                'indigo' => 'Indigo',
                                'purple' => 'Violet',
                                'pink' => 'Rose',
                                'red' => 'Rouge',
                                'orange' => 'Orange',
                                'amber' => 'Ambre',
                                'yellow' => 'Jaune',
                                'lime' => 'Vert lime',
                                'green' => 'Vert',
                                'emerald' => 'Émeraude',
                                'teal' => 'Bleu canard',
                                'cyan' => 'Cyan',
                                'sky' => 'Ciel',
                                'slate' => 'Ardoise',
                                'gray' => 'Gris',
                                'zinc' => 'Zinc',
                                'neutral' => 'Neutre',
                                'stone' => 'Pierre',
                            ])
                            ->helperText('Laisser vide pour utiliser la couleur claire'),
                        Forms\Components\Select::make('success_color_dark')
                            ->label('Couleur succès')
                            ->options([
                                'emerald' => 'Émeraude',
                                'green' => 'Vert',
                                'lime' => 'Vert lime',
                            ])
                            ->helperText('Laisser vide pour utiliser la couleur claire'),
                        Forms\Components\Select::make('warning_color_dark')
                            ->label('Couleur avertissement')
                            ->options([
                                'amber' => 'Ambre',
                                'yellow' => 'Jaune',
                                'orange' => 'Orange',
                            ])
                            ->helperText('Laisser vide pour utiliser la couleur claire'),
                        Forms\Components\Select::make('danger_color_dark')
                            ->label('Couleur danger')
                            ->options([
                                'rose' => 'Rose',
                                'red' => 'Rouge',
                                'pink' => 'Rose clair',
                            ])
                            ->helperText('Laisser vide pour utiliser la couleur claire'),
                        Forms\Components\Select::make('info_color_dark')
                            ->label('Couleur information')
                            ->options([
                                'sky' => 'Ciel',
                                'blue' => 'Bleu',
                                'cyan' => 'Cyan',
                            ])
                            ->helperText('Laisser vide pour utiliser la couleur claire'),
                        Forms\Components\Select::make('gray_color_dark')
                            ->label('Couleur grise')
                            ->options([
                                'slate' => 'Ardoise',
                                'gray' => 'Gris',
                                'zinc' => 'Zinc',
                                'neutral' => 'Neutre',
                                'stone' => 'Pierre',
                            ])
                            ->helperText('Laisser vide pour utiliser la couleur claire'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Branding')
                    ->schema([
                        Forms\Components\TextInput::make('brand_name')
                            ->label('Nom de la marque')
                            ->helperText('Remplace le nom par défaut du panel'),
                        Forms\Components\FileUpload::make('brand_logo_path')
                            ->label('Logo')
                            ->directory('brand-logos')
                            ->image()
                            ->maxSize(1024),
                        Forms\Components\FileUpload::make('favicon_path')
                            ->label('Favicon')
                            ->directory('favicons')
                            ->image()
                            ->maxSize(512),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Personnalisation avancée')
                    ->schema([
                        Forms\Components\Textarea::make('custom_css')
                            ->label('CSS personnalisé')
                            ->rows(5)
                            ->helperText('CSS personnalisé à appliquer à ce thème'),
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Métadonnées')
                            ->keyLabel('Clé')
                            ->valueLabel('Valeur')
                            ->reorderable()
                            ->addable()
                            ->deletable()
                            ->helperText('Données supplémentaires pour ce thème'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom technique')
                    ->searchable(),
                Tables\Columns\TextColumn::make('label')
                    ->label('Libellé')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('panel')
                    ->label('Panel')
                    ->colors([
                        'blue' => 'ns-conseil',
                        'indigo' => 'admin',
                        'purple' => 'super-admin',
                        'orange' => 'allopro',
                    ]),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('Défaut')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('panel')
                    ->label('Panel')
                    ->options([
                        'ns-conseil' => 'NS Conseil',
                        'admin' => 'Admin',
                        'super-admin' => 'Super Admin',
                        'allopro' => 'Allopro',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif')
                    ->placeholder('Tous')
                    ->trueLabel('Actifs')
                    ->falseLabel('Inactifs'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('panel')
            ->defaultSort('is_default', 'desc');
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
            'index' => Pages\ListThemes::route('/'),
            'create' => Pages\CreateTheme::route('/create'),
            'edit' => Pages\EditTheme::route('/{record}/edit'),
        ];
    }
}
