<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\DataDeletionRequestResource\Pages;
use App\Filament\NsConseil\Resources\DataDeletionRequestResource\RelationManagers;
use App\Models\DataDeletionRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DataDeletionRequestResource extends Resource
{
    protected static ?string $model = DataDeletionRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'RGPD - Suppression';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Demande de suppression')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Utilisateur')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn (string $context) => $context === 'edit'),
                        Forms\Components\Textarea::make('reason')
                            ->label('Motif de la demande')
                            ->required()
                            ->rows(3),
                        Forms\Components\CheckboxList::make('data_types')
                            ->label('Types de données à supprimer')
                            ->options([
                                'personal' => 'Données personnelles',
                                'communications' => 'Historique des communications',
                                'documents' => 'Documents',
                                'activities' => 'Historique d\'activité',
                                'all' => 'Toutes mes données',
                            ])
                            ->required(),
                    ]),
                Forms\Components\Section::make('Traitement')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending' => 'En attente',
                                'approved' => 'Approuvé',
                                'rejected' => 'Rejeté',
                                'completed' => 'Terminé',
                            ])
                            ->required()
                            ->disabled(fn (string $context) => $context === 'create'),
                        Forms\Components\Select::make('processed_by')
                            ->label('Traité par')
                            ->relationship('processedBy', 'name')
                            ->searchable()
                            ->preload()
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('processed_at')
                            ->label('Date de traitement')
                            ->disabled(),
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Motif du rejet')
                            ->rows(3)
                            ->visible(fn (Forms\Get $get) => $get('status') === 'rejected'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status_label')
                    ->label('Statut')
                    ->color(fn (DataDeletionRequest $record) => $record->status_color),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Motif')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('processedBy.name')
                    ->label('Traité par')
                    ->sortable(),
                Tables\Columns\TextColumn::make('processed_at')
                    ->label('Date de traitement')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Demandé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'approved' => 'Approuvé',
                        'rejected' => 'Rejeté',
                        'completed' => 'Terminé',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (DataDeletionRequest $record) => $record->isPending())
                    ->requiresConfirmation()
                    ->action(function (DataDeletionRequest $record) {
                        $record->approve(auth()->user());
                        \Filament\Notifications\Notification::make()
                            ->title('Demande approuvée')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (DataDeletionRequest $record) => $record->isPending())
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Motif du rejet')
                            ->required(),
                    ])
                    ->action(function (DataDeletionRequest $record, array $data) {
                        $record->reject(auth()->user(), $data['reason']);
                        \Filament\Notifications\Notification::make()
                            ->title('Demande rejetée')
                            ->danger()
                            ->send();
                    }),
                Tables\Actions\Action::make('complete')
                    ->label('Marquer terminé')
                    ->icon('heroicon-o-flag')
                    ->color('primary')
                    ->visible(fn (DataDeletionRequest $record) => $record->isApproved())
                    ->requiresConfirmation()
                    ->action(function (DataDeletionRequest $record) {
                        $record->complete(auth()->user());
                        \Filament\Notifications\Notification::make()
                            ->title('Suppression terminée')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListDataDeletionRequests::route('/'),
            'create' => Pages\CreateDataDeletionRequest::route('/create'),
            'edit' => Pages\EditDataDeletionRequest::route('/{record}/edit'),
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
