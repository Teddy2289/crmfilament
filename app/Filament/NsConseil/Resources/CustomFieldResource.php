<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\CustomFieldResource\Pages;
use App\Models\CustomField;
use App\Support\UsesResourcePermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;


class CustomFieldResource extends Resource
{
    use UsesResourcePermissions;

    protected static ?string $model = CustomField::class;

    protected static string $permissionPrefix = 'custom_fields';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Champs personnalisés';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom du champ')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Nom affiché dans le formulaire'),

                        Forms\Components\TextInput::make('slug')
                            ->label('Identifiant unique')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Utilisé pour stocker et récupérer la valeur (ex: code_siret)')
                            ->alphaDash(),

                        Forms\Components\Select::make('type')
                            ->label('Type de champ')
                            ->options(CustomField::TYPES)
                            ->required()
                            ->default('text')
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('options', null)),

                        Forms\Components\Select::make('target_model')
                            ->label('Modèle cible')
                            ->options([
                                'App\Models\EntiteCommerciale' => 'Entité commerciale',
                                'App\Models\Partenaire' => 'Partenaire',
                                'App\Models\Prospect' => 'Prospect',
                                'App\Models\Client' => 'Client',
                            ])
                            ->required()
                            ->searchable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Options du champ')
                    ->schema([
                        Forms\Components\Textarea::make('options')
                            ->label('Options (JSON)')
                            ->rows(3)
                            ->helperText('Pour les champs select, entrez les options au format JSON: ["Option 1", "Option 2"]')
                            ->visible(fn (callable $get) => $get('type') === 'select'),

                        Forms\Components\Toggle::make('required')
                            ->label('Champ obligatoire')
                            ->default(false),

                        Forms\Components\TextInput::make('placeholder')
                            ->label('Texte de remplacement')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('helper_text')
                            ->label('Texte d\'aide')
                            ->rows(2)
                            ->helperText('Texte affiché sous le champ pour guider l\'utilisateur'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Affichage')
                    ->schema([
                        Forms\Components\TextInput::make('order')
                            ->label('Ordre d\'affichage')
                            ->numeric()
                            ->default(0)
                            ->helperText('Champs avec un ordre inférieur apparaissent en premier'),

                        Forms\Components\Toggle::make('active')
                            ->label('Actif')
                            ->default(true)
                            ->helperText('Désactiver pour masquer le champ sans le supprimer'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Identifiant')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => CustomField::TYPES[$state] ?? $state)
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('target_model')
                    ->label('Modèle cible')
                    ->formatStateUsing(fn ($state) => class_basename($state))
                    ->badge()
                    ->color('gray'),

                Tables\Columns\IconColumn::make('required')
                    ->label('Obligatoire')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                Tables\Columns\TextColumn::make('order')
                    ->label('Ordre')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('active')
                    ->label('Actif')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('order', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options(CustomField::TYPES),

                Tables\Filters\SelectFilter::make('target_model')
                    ->label('Modèle cible')
                    ->options([
                        'App\Models\EntiteCommerciale' => 'Entité commerciale',
                        'App\Models\Partenaire' => 'Partenaire',
                        'App\Models\Prospect' => 'Prospect',
                        'App\Models\Client' => 'Client',
                    ]),

                Tables\Filters\TernaryFilter::make('active')
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
            ->reorderable('order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomFields::route('/'),
            'create' => Pages\CreateCustomField::route('/create'),
            'edit' => Pages\EditCustomField::route('/{record}/edit'),
        ];
    }
}
