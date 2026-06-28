<?php

namespace App\Filament\SuperAdmin\Resources\WorkflowGroupeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WorkflowStepsRelationManager extends RelationManager
{
    protected static string $relationship = 'workflowSteps';

    protected static ?string $title = 'Étapes du workflow';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                    ->options([
                        'task' => 'Tâche',
                        'condition' => 'Condition',
                        'action' => 'Action',
                        'notification' => 'Notification',
                        'approval' => 'Validation',
                    ])
                    ->required()
                    ->native(false)
                    ->default('task'),

                Forms\Components\TextInput::make('ordre')
                    ->label('Ordre')
                    ->numeric()
                    ->default(0)
                    ->helperText('Ordre d\'exécution dans le workflow'),

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
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                Tables\Columns\TextColumn::make('ordre')
                    ->label('Ordre')
                    ->sortable()
                    ->width('10%'),

                Tables\Columns\TextColumn::make('label')
                    ->label('Libellé')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->fontFamily('mono')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'blue' => 'task',
                        'yellow' => 'condition',
                        'green' => 'action',
                        'purple' => 'notification',
                        'red' => 'approval',
                    ]),

                Tables\Columns\IconColumn::make('actif')
                    ->label('Actif')
                    ->boolean()
                    ->toggleable(),
            ])
            ->reorderable('ordre')
            ->defaultSort('ordre')
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
            ->emptyStateHeading('Aucune étape configurée')
            ->emptyStateDescription('Créez des étapes pour construire votre workflow')
            ->emptyStateIcon('heroicon-o-cube');
    }
}
