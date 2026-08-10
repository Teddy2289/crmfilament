<?php

namespace App\Filament\NsConseil\Resources\WorkflowResource\RelationManagers;

use App\Models\WorkflowStep;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class StepsRelationManager extends RelationManager
{
    protected static string $relationship = 'steps';

    protected static ?string $title = 'Étapes du workflow';

    protected static ?string $modelLabel = 'Étape';

    protected static ?string $pluralModelLabel = 'Étapes';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de l\'étape')
                    ->schema([
                        Forms\Components\TextInput::make('nom')
                            ->label('Nom de l\'étape')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3),
                        Forms\Components\Select::make('type_action')
                            ->label('Type d\'action')
                            ->options(WorkflowStep::TYPES_ACTION)
                            ->required()
                            ->default('approval'),
                        Forms\Components\Select::make('assigne_a')
                            ->label('Assignée à')
                            ->relationship('assigneA', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\TextInput::make('ordre')
                            ->label('Ordre')
                            ->numeric()
                            ->default(0),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nom')
            ->columns([
                Tables\Columns\TextColumn::make('ordre')
                    ->label('Ordre')
                    ->sortable(),
                Tables\Columns\TextColumn::make('nom')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('type_action_label')
                    ->label('Type d\'action'),
                Tables\Columns\TextColumn::make('assigneA.name')
                    ->label('Assignée à')
                    ->sortable(),
            ])
            ->defaultSort('ordre')
            ->reorderable('ordre')
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
            ]);
    }
}
