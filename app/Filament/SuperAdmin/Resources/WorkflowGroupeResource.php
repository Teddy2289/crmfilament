<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\WorkflowGroupeResource\Pages\EditWorkflowGroupe;
use App\Filament\SuperAdmin\Resources\WorkflowGroupeResource\Pages\ListWorkflowGroupes;
use App\Filament\SuperAdmin\Resources\WorkflowGroupeResource\RelationManagers\WorkflowStepsRelationManager;
use App\Models\WorkflowGroupe;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WorkflowGroupeResource extends Resource
{
    protected static ?string $model = WorkflowGroupe::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $navigationLabel = 'Groupes de parcours';

    protected static ?string $navigationGroup = 'Paramétrage CRM';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Groupe de parcours';

    protected static ?string $pluralModelLabel = 'Groupes de parcours';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Parcours')
                ->tabs([
                    Forms\Components\Tabs\Tab::make('Informations')
                        ->schema([
                            Forms\Components\Select::make('model_type')
                                ->label('Type de modèle')
                                ->options(['prospect' => 'Prospect', 'partenaire' => 'Partenaire'])
                                ->required()
                                ->native(false),
                            Forms\Components\TextInput::make('code')->label('Code')->required(),
                            Forms\Components\TextInput::make('label')->label('Libellé')->required(),
                            Forms\Components\TextInput::make('ordre')->label('Ordre')->numeric()->default(0),
                            Forms\Components\Toggle::make('actif')->label('Actif')->default(true),
                        ])
                        ->columns(2),
                    
                    Forms\Components\Tabs\Tab::make('Éditeur visuel')
                        ->schema([
                            Forms\Components\Livewire::make('workflow-visual-editor')
                                ->key(fn ($record) => $record?->id ?? 'new')
                                ->livewireArgs(fn ($record) => [
                                    'workflowId' => $record?->id,
                                ]),
                        ]),
                ])
                ->persistTabInQueryString()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('model_type')->label('Type')->badge(),
                Tables\Columns\TextColumn::make('ordre')->label('Ordre')->sortable(),
                Tables\Columns\TextColumn::make('code')->label('Code')->fontFamily('mono'),
                Tables\Columns\TextColumn::make('label')->label('Libellé')->searchable(),
                Tables\Columns\IconColumn::make('actif')->label('Actif')->boolean(),
            ])
            ->reorderable('ordre')
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            WorkflowStepsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkflowGroupes::route('/'),
            'edit' => EditWorkflowGroupe::route('/{record}/edit'),
        ];
    }
}
