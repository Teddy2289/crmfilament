<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\WorkflowStepResource\Pages;
use App\Models\WorkflowStep;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WorkflowStepResource extends Resource
{
    protected static ?string $model = WorkflowStep::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Étapes de parcours';

    protected static ?string $navigationGroup = 'Paramétrage CRM';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Étape de parcours';

    protected static ?string $pluralModelLabel = 'Étapes de parcours';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('workflow_groupe_id')
                ->label('Groupe de parcours')
                ->relationship('workflowGroupe', 'label')
                ->required()
                ->native(false),

            Forms\Components\TextInput::make('label')
                ->label('Libellé')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('code')
                ->label('Code')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(100)
                ->helperText('Identifiant unique pour cette étape'),

            Forms\Components\Select::make('type')
                ->label('Type')
                ->options(WorkflowStep::TYPES)
                ->required()
                ->native(false)
                ->default('task'),

            Forms\Components\TextInput::make('ordre')
                ->label('Ordre')
                ->numeric()
                ->default(0)
                ->helperText('Ordre d\'exécution dans le parcours'),

            Forms\Components\KeyValue::make('config')
                ->label('Configuration')
                ->keyLabel('Clé')
                ->valueLabel('Valeur')
                ->reorderable()
                ->addable()
                ->deletable()
                ->columnSpanFull(),

            Forms\Components\Toggle::make('actif')
                ->label('Actif')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('workflowGroupe.label')
                    ->label('Groupe')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('label')
                    ->label('Libellé')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->fontFamily('mono')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'blue' => 'task',
                        'yellow' => 'condition',
                        'green' => 'action',
                        'purple' => 'notification',
                        'red' => 'approval',
                    ]),

                Tables\Columns\TextColumn::make('ordre')
                    ->label('Ordre')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('actif')
                    ->label('Actif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(WorkflowStep::TYPES),
                Tables\Filters\TernaryFilter::make('actif')->label('Statut'),
            ])
            ->reorderable('ordre')
            ->defaultSort('ordre')
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkflowSteps::route('/'),
            'create' => Pages\CreateWorkflowStep::route('/create'),
            'edit' => Pages\EditWorkflowStep::route('/{record}/edit'),
        ];
    }
}
