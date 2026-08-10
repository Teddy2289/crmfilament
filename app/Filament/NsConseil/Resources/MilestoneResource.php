<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\MilestoneResource\Pages;
use App\Filament\NsConseil\Resources\MilestoneResource\RelationManagers;
use App\Models\Milestone;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MilestoneResource extends Resource
{
    protected static ?string $model = Milestone::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Jalons (Milestones)';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('nom')
                            ->label('Nom du jalon')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3),
                    ]),
                Forms\Components\Section::make('Planification')
                    ->schema([
                        Forms\Components\DatePicker::make('date_prevue')
                            ->label('Date prévue')
                            ->required()
                            ->native(false),
                        Forms\Components\DatePicker::make('date_reelle')
                            ->label('Date réelle')
                            ->native(false),
                        Forms\Components\Select::make('statut')
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
                            ->default(0),
                    ]),
                Forms\Components\Section::make('Assignation')
                    ->schema([
                        Forms\Components\Select::make('assigned_to')
                            ->label('Assigné à')
                            ->relationship('assignedTo', 'name')
                            ->searchable()
                            ->preload(),
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
                Tables\Columns\BadgeColumn::make('statut_label')
                    ->label('Statut')
                    ->color(fn (Milestone $record) => $record->statut_color),
                Tables\Columns\ProgressColumn::make('progress')
                    ->label('Progression'),
                Tables\Columns\TextColumn::make('date_prevue')
                    ->label('Date prévue')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_reelle')
                    ->label('Date réelle')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('days_remaining')
                    ->label('Jours restants')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state === null ? '-' : ($state < 0 ? abs($state) . ' jours de retard' : $state . ' jours')),
                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Assigné à')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('date_prevue', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('statut')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'in_progress' => 'En cours',
                        'completed' => 'Terminé',
                        'delayed' => 'Retardé',
                    ]),
                Tables\Filters\Filter::make('overdue')
                    ->label('En retard')
                    ->query(fn (Builder $query) => $query->overdue()),
                Tables\Filters\Filter::make('upcoming')
                    ->label('À venir')
                    ->query(fn (Builder $query) => $query->upcoming()),
            ])
            ->actions([
                Tables\Actions\Action::make('complete')
                    ->label('Marquer terminé')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Milestone $record) => !$record->isCompleted())
                    ->requiresConfirmation()
                    ->action(function (Milestone $record) {
                        $record->markAsCompleted();
                        \Filament\Notifications\Notification::make()
                            ->title('Jalon marqué comme terminé')
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMilestones::route('/'),
            'create' => Pages\CreateMilestone::route('/create'),
            'edit' => Pages\EditMilestone::route('/{record}/edit'),
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
