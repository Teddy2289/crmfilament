<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\WorkflowResource\Pages;
use App\Filament\NsConseil\Resources\WorkflowResource\RelationManagers;
use App\Models\Workflow;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class WorkflowResource extends Resource
{
    protected static ?string $model = Workflow::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Workflows';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('nom')
                            ->label('Nom du workflow')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3),
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options(Workflow::TYPES)
                            ->required()
                            ->default('prospect'),
                        Forms\Components\Select::make('statut')
                            ->label('Statut')
                            ->options(Workflow::STATUTS)
                            ->required()
                            ->default('draft'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nom')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('type_label')
                    ->label('Type'),
                Tables\Columns\BadgeColumn::make('statut_label')
                    ->label('Statut')
                    ->color(fn (Workflow $record) => $record->statut_color),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Créé par')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options(Workflow::TYPES),
                Tables\Filters\SelectFilter::make('statut')
                    ->label('Statut')
                    ->options(Workflow::STATUTS),
            ])
            ->actions([
                Tables\Actions\Action::make('activate')
                    ->label('Activer')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn (Workflow $record) => $record->statut === 'draft')
                    ->requiresConfirmation()
                    ->action(function (Workflow $record) {
                        $record->update(['statut' => 'active']);
                        \Filament\Notifications\Notification::make()
                            ->title('Workflow activé')
                            ->success()
                            ->send();
                    }),
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
        return [
            RelationManagers\StepsRelationManager::class,
            RelationManagers\ApprovalsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkflows::route('/'),
            'create' => Pages\CreateWorkflow::route('/create'),
            'edit' => Pages\EditWorkflow::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
