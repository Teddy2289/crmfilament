<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\FicheTemplateResource\Pages\CreateFicheTemplate;
use App\Filament\SuperAdmin\Resources\FicheTemplateResource\Pages\EditFicheTemplate;
use App\Filament\SuperAdmin\Resources\FicheTemplateResource\Pages\ListFicheTemplates;
use App\Models\FicheTemplate;
use App\Models\StatutPhoning;
use App\Services\Aopia\FicheGenerationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FicheTemplateResource extends Resource
{
    protected static ?string $model = FicheTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationLabel = 'Modèles de fiches';

    protected static ?string $navigationGroup = 'Paramétrage CRM';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Modèle de fiche';

    protected static ?string $pluralModelLabel = 'Modèles de fiches';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identification')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('type')
                        ->label('Type de fiche')
                        ->options(FicheTemplate::TYPES)
                        ->required()
                        ->native(false)
                        ->helperText('Bleue = RDV pris, Jaune = pas intéressé (rappel J+7), Verte = RDV à conclure'),

                    Forms\Components\TextInput::make('nom')
                        ->label('Nom du modèle')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Fichier template Word')
                ->schema([
                    Forms\Components\FileUpload::make('template_path')
                        ->label('Template Word (.docx)')
                        ->required()
                        ->directory('fiche-templates')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ])
                        ->maxSize(5120)
                        ->helperText('Uploadez le fichier .docx contenant les variables ${NOM_VARIABLE} à remplacer.')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Mapping des variables')
                ->description('Associez chaque variable du template Word à un champ du prospect. Format : ${VARIABLE} → champ_prospect (utiliser | pour les fallbacks).')
                ->schema([
                    Forms\Components\KeyValue::make('placeholders')
                        ->label('Variables → Champs')
                        ->keyLabel('Variable template (ex: ${RAISON_SOCIALE})')
                        ->valueLabel('Champ prospect (ex: raison_sociale|nom)')
                        ->default(FicheGenerationService::mappingParDefaut())
                        ->addActionLabel('Ajouter une variable')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Déclenchement workflow')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('statut_phoning_codes')
                        ->label('Statuts phoning déclencheurs')
                        ->multiple()
                        ->options(fn () => StatutPhoning::where('actif', true)
                            ->pluck('label', 'code'))
                        ->searchable()
                        ->native(false)
                        ->helperText('Quand un de ces statuts est appliqué, cette fiche peut être générée.'),

                    Forms\Components\Toggle::make('auto_generation')
                        ->label('Génération automatique')
                        ->helperText('Si activé, la fiche est générée automatiquement lors du changement de statut.'),

                    Forms\Components\Toggle::make('actif')
                        ->label('Actif')
                        ->default(true)
                        ->helperText('Un modèle inactif ne sera ni proposé ni généré automatiquement.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn ($state) => FicheTemplate::TYPE_COLORS[$state] ?? 'gray')
                    ->formatStateUsing(fn ($state) => FicheTemplate::TYPES[$state] ?? $state)
                    ->sortable(),

                Tables\Columns\TextColumn::make('nom')
                    ->label('Nom')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('statut_phoning_codes')
                    ->label('Déclencheurs')
                    ->badge()
                    ->separator(',')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : $state),

                Tables\Columns\IconColumn::make('auto_generation')
                    ->label('Auto')
                    ->boolean(),

                Tables\Columns\ToggleColumn::make('actif')
                    ->label('Actif'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(FicheTemplate::TYPES)
                    ->label('Type'),

                Tables\Filters\TernaryFilter::make('actif')
                    ->label('Actif'),

                Tables\Filters\TernaryFilter::make('auto_generation')
                    ->label('Auto-génération'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFicheTemplates::route('/'),
            'create' => CreateFicheTemplate::route('/create'),
            'edit' => EditFicheTemplate::route('/{record}/edit'),
        ];
    }
}
