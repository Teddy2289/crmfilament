<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\GanttTaskResource\Pages;
use App\Filament\NsConseil\Resources\GanttTaskResource\RelationManagers;
use App\Models\GanttTask;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GanttTaskResource extends Resource
{
    protected static ?string $model = GanttTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'Gestion de Projet';

    protected static ?string $navigationLabel = 'Diagrammes Gantt';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('nom')
                            ->label('Nom de la tâche')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3),
                        Forms\Components\Select::make('parent_id')
                            ->label('Tâche parente')
                            ->relationship('parent', 'nom')
                            ->searchable()
                            ->preload(),
                    ]),
                Forms\Components\Section::make('Planning')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Date de début')
                            ->required(),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Date de fin')
                            ->required()
                            ->after('start_date'),
                        Forms\Components\TextInput::make('duration')
                            ->label('Durée (jours)')
                            ->numeric()
                            ->default(0)
                            ->helperText('Calculé automatiquement si les dates sont définies'),
                        Forms\Components\Toggle::make('milestone')
                            ->label('Jalon')
                            ->default(false)
                            ->helperText('Marque un point important dans le projet'),
                    ]),
                Forms\Components\Section::make('Progression')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending' => 'En attente',
                                'in_progress' => 'En cours',
                                'completed' => 'Terminé',
                                'delayed' => 'Retardé',
                            ])
                            ->required()
                            ->default('pending'),
                        Forms\Components\Slider::make('progress')
                            ->label('Progression')
                            ->min(0)
                            ->max(100)
                            ->default(0)
                            ->live(),
                    ]),
                Forms\Components\Section::make('Assignation')
                    ->schema([
                        Forms\Components\Select::make('assigned_to')
                            ->label('Assigné à')
                            ->relationship('assignedTo', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('prospect_id')
                            ->label('Prospect lié')
                            ->relationship('prospect', 'nom')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('client_id')
                            ->label('Client lié')
                            ->relationship('client', 'nom')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('partenaire_id')
                            ->label('Partenaire lié')
                            ->relationship('partenaire', 'nom')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('opportunite_id')
                            ->label('Opportunité liée')
                            ->relationship('opportunite', 'nom')
                            ->searchable()
                            ->preload(),
                    ]),
                Forms\Components\Section::make('Affichage')
                    ->schema([
                        Forms\Components\ColorPicker::make('color')
                            ->label('Couleur')
                            ->hex(),
                        Forms\Components\TextInput::make('order')
                            ->label('Ordre')
                            ->numeric()
                            ->default(0),
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
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'pending' => 'gray',
                        'in_progress' => 'primary',
                        'completed' => 'success',
                        'delayed' => 'danger',
                    ]),
                Tables\Columns\ProgressBar::make('progress')
                    ->label('Progression')
                    ->color(fn ($state) => match($state) {
                        $state >= 100 => 'success',
                        $state >= 50 => 'primary',
                        $state >= 25 => 'warning',
                        default => 'danger',
                    }),
                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Assigné à')
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Début')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\IconColumn::make('milestone')
                    ->label('Jalon')
                    ->boolean()
                    ->trueIcon('heroicon-o-flag')
                    ->falseIcon('heroicon-o-minus'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('start_date', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'in_progress' => 'En cours',
                        'completed' => 'Terminé',
                        'delayed' => 'Retardé',
                    ]),
                Tables\Filters\TernaryFilter::make('milestone')
                    ->label('Jalons uniquement'),
                Tables\Filters\Filter::make('overdue')
                    ->label('En retard')
                    ->query(fn (Builder $query) => $query->where('end_date', '<', now())->where('status', '!=', 'completed')),
            ])
            ->actions([
                Tables\Actions\Action::make('complete')
                    ->label('Terminer')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (GanttTask $record) => !$record->isCompleted())
                    ->action(function (GanttTask $record) {
                        $record->updateProgress(100);
                        \Filament\Notifications\Notification::make()
                            ->title('Tâche terminée')
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
            RelationManagers\ChildrenRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGanttTasks::route('/'),
            'create' => Pages\CreateGanttTask::route('/create'),
            'edit' => Pages\EditGanttTask::route('/{record}/edit'),
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
