<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\BackupResource\Pages;
use App\Filament\NsConseil\Resources\BackupResource\RelationManagers;
use App\Models\Backup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BackupResource extends Resource
{
    protected static ?string $model = Backup::class;

    protected static ?string $navigationIcon = 'heroicon-o-server';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Sauvegardes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom de la sauvegarde')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options([
                                'full' => 'Complet',
                                'incremental' => 'Incrémental',
                                'differential' => 'Différentiel',
                            ])
                            ->required()
                            ->default('full'),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3),
                    ]),
                Forms\Components\Section::make('Configuration')
                    ->schema([
                        Forms\Components\CheckboxList::make('tables')
                            ->label('Tables à inclure')
                            ->options([
                                'users' => 'Utilisateurs',
                                'prospects' => 'Prospects',
                                'clients' => 'Clients',
                                'partenaires' => 'Partenaires',
                                'opportunites' => 'Opportunités',
                                'rendez_vous' => 'Rendez-vous',
                                'appels' => 'Appels',
                                'tasks' => 'Tâches',
                                'all' => 'Toutes les tables',
                            ])
                            ->required(),
                        Forms\Components\Toggle::make('automatic')
                            ->label('Sauvegarde automatique')
                            ->default(false)
                            ->helperText('Cette sauvegarde sera exécutée automatiquement selon la planification'),
                    ]),
                Forms\Components\Section::make('Statut')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending' => 'En attente',
                                'running' => 'En cours',
                                'completed' => 'Terminé',
                                'failed' => 'Échoué',
                            ])
                            ->required()
                            ->default('pending')
                            ->disabled(fn (string $context) => $context === 'create'),
                        Forms\Components\DateTimePicker::make('started_at')
                            ->label('Date de début')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Date de fin')
                            ->disabled(),
                        Forms\Components\Textarea::make('error_message')
                            ->label('Message d\'erreur')
                            ->visible(fn (Forms\Get $get) => $get('status') === 'failed')
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('type_label')
                    ->label('Type'),
                Tables\Columns\BadgeColumn::make('status_label')
                    ->label('Statut')
                    ->color(fn (Backup $record) => $record->status_color),
                Tables\Columns\TextColumn::make('file_size_formatted')
                    ->label('Taille')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration')
                    ->label('Durée')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('automatic')
                    ->label('Auto')
                    ->boolean()
                    ->trueIcon('heroicon-o-clock')
                    ->falseIcon('heroicon-o-minus'),
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
                    ->options([
                        'full' => 'Complet',
                        'incremental' => 'Incrémental',
                        'differential' => 'Différentiel',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'running' => 'En cours',
                        'completed' => 'Terminé',
                        'failed' => 'Échoué',
                    ]),
                Tables\Filters\TernaryFilter::make('automatic')
                    ->label('Automatique'),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Télécharger')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->visible(fn (Backup $record) => $record->status === 'completed' && $record->file_path)
                    ->url(fn (Backup $record) => $record->file_path)
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('restore')
                    ->label('Restaurer')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->visible(fn (Backup $record) => $record->status === 'completed')
                    ->requiresConfirmation()
                    ->action(function (Backup $record) {
                        \Filament\Notifications\Notification::make()
                            ->title('Restauration démarrée')
                            ->body('La restauration de la sauvegarde a été initiée.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListBackups::route('/'),
            'create' => Pages\CreateBackup::route('/create'),
            'edit' => Pages\EditBackup::route('/{record}/edit'),
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
