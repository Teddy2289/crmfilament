<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\TaskResource\Pages;
use App\Models\Task;
use App\Support\UsesResourcePermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TaskResource extends Resource
{
    use UsesResourcePermissions;

    protected static ?string $model = Task::class;

    protected static string $permissionPrefix = 'tasks';

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $navigationLabel = 'Tâches';

    protected static ?string $modelLabel = 'tâche';

    protected static ?string $pluralModelLabel = 'tâches';

    protected static ?string $navigationGroup = 'Productivité';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('titre')
                            ->label('Titre')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options(Task::TYPES)
                            ->default('tache')
                            ->required(),

                        Forms\Components\Select::make('statut')
                            ->label('Statut')
                            ->options(Task::STATUTS)
                            ->default('a_faire')
                            ->required(),

                        Forms\Components\Select::make('priorite')
                            ->label('Priorité')
                            ->options(Task::PRIORITES)
                            ->default('normale')
                            ->required(),

                        Forms\Components\DateTimePicker::make('date_echeance')
                            ->label('Date d\'échéance')
                            ->seconds(false),

                        Forms\Components\Select::make('assigne_a')
                            ->label('Assignée à')
                            ->relationship('assigneA', 'nom')
                            ->searchable()
                            ->preload()
                            ->default(Auth::id()),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Liaison')
                    ->schema([
                        Forms\Components\Select::make('prospect_id')
                            ->label('Prospect')
                            ->relationship('prospect', 'nom')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\Select::make('partenaire_id')
                            ->label('Partenaire')
                            ->relationship('partenaire', 'nom')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\Select::make('client_id')
                            ->label('Client')
                            ->relationship('client', 'nom_tiers')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\Select::make('rendez_vous_id')
                            ->label('Rendez-vous')
                            ->relationship('rendezVous', 'date_heure')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\Select::make('opportunite_id')
                            ->label('Opportunité')
                            ->relationship('opportunite', 'nom')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\Select::make('appel_id')
                            ->label('Appel')
                            ->relationship('appel', 'date_heure')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('titre')
                    ->label('Titre')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Task $record): string => $record->description ?? '')
                    ->wrap(),

                Tables\Columns\TextColumn::make('type_label')
                    ->label('Type')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('statut_label')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (Task $record): string => $record->statut_color),

                Tables\Columns\TextColumn::make('date_echeance')
                    ->label('Échéance')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->description(fn (Task $record): string => $record->est_en_retard ? 'En retard' : '')
                    ->color(fn (Task $record): string => $record->est_en_retard ? 'danger' : null),

                Tables\Columns\TextColumn::make('assigneA.nom')
                    ->label('Assignée à')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('statut')
                    ->label('Statut')
                    ->options(Task::STATUTS),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options(Task::TYPES),

                Tables\Filters\SelectFilter::make('assigne_a')
                    ->label('Assignée à')
                    ->relationship('assigneA', 'nom'),

                Tables\Filters\Filter::make('en_retard')
                    ->label('En retard')
                    ->query(fn (Builder $query): Builder => $query->enRetard())
                    ->toggle(),

                Tables\Filters\Filter::make('urgentes')
                    ->label('Urgentes (aujourd\'hui)')
                    ->query(fn (Builder $query): Builder => $query->urgentes())
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('marquer_en_cours')
                    ->label('En cours')
                    ->icon('heroicon-o-play')
                    ->color('warning')
                    ->visible(fn (Task $record) => $record->statut === 'a_faire')
                    ->action(fn (Task $record) => $record->marquerEnCours()),

                Tables\Actions\Action::make('marquer_terminee')
                    ->label('Terminer')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Task $record) => in_array($record->statut, ['a_faire', 'en_cours']))
                    ->action(fn (Task $record) => $record->marquerTerminee()),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('marquer_terminees')
                        ->label('Marquer comme terminées')
                        ->icon('heroicon-o-check')
                        ->action(fn ($records) => $records->each->marquerTerminee()),
                ]),
            ])
            ->defaultSort('date_echeance', 'asc');
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
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}
