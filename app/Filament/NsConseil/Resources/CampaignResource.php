<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\CampaignResource\Pages;
use App\Filament\NsConseil\Resources\CampaignResource\RelationManagers;
use App\Models\Campaign;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Campagnes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('nom')
                            ->label('Nom de la campagne')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3),
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options([
                                'email' => 'Email',
                                'sms' => 'SMS',
                                'social' => 'Réseaux sociaux',
                                'print' => 'Impression',
                                'event' => 'Événement',
                            ])
                            ->required()
                            ->default('email'),
                        Forms\Components\Select::make('statut')
                            ->label('Statut')
                            ->options([
                                'draft' => 'Brouillon',
                                'active' => 'Active',
                                'paused' => 'En pause',
                                'completed' => 'Terminée',
                                'cancelled' => 'Annulée',
                            ])
                            ->required()
                            ->default('draft'),
                    ]),
                Forms\Components\Section::make('Planification')
                    ->schema([
                        Forms\Components\DatePicker::make('date_debut')
                            ->label('Date de début')
                            ->required()
                            ->native(false),
                        Forms\Components\DatePicker::make('date_fin')
                            ->label('Date de fin')
                            ->native(false),
                    ]),
                Forms\Components\Section::make('Budget')
                    ->schema([
                        Forms\Components\TextInput::make('budget')
                            ->label('Budget total')
                            ->numeric()
                            ->prefix('€')
                            ->step(0.01),
                        Forms\Components\TextInput::make('budget_depense')
                            ->label('Budget dépensé')
                            ->numeric()
                            ->prefix('€')
                            ->step(0.01)
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
                Forms\Components\Section::make('Statistiques')
                    ->schema([
                        Forms\Components\TextInput::make('envois_total')
                            ->label('Envois totaux')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                        Forms\Components\TextInput::make('ouvertures')
                            ->label('Ouvertures')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                        Forms\Components\TextInput::make('clics')
                            ->label('Clics')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                        Forms\Components\TextInput::make('conversions')
                            ->label('Conversions')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
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
                    ->color(fn (Campaign $record) => $record->statut_color),
                Tables\Columns\TextColumn::make('date_debut')
                    ->label('Date de début')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_fin')
                    ->label('Date de fin')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('budget')
                    ->label('Budget')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('budget_remaining')
                    ->label('Budget restant')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('open_rate')
                    ->label('Taux ouverture')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('click_rate')
                    ->label('Taux clic')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('conversion_rate')
                    ->label('Taux conversion')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Assigné à')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('date_debut', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'email' => 'Email',
                        'sms' => 'SMS',
                        'social' => 'Réseaux sociaux',
                        'print' => 'Impression',
                        'event' => 'Événement',
                    ]),
                Tables\Filters\SelectFilter::make('statut')
                    ->label('Statut')
                    ->options([
                        'draft' => 'Brouillon',
                        'active' => 'Active',
                        'paused' => 'En pause',
                        'completed' => 'Terminée',
                        'cancelled' => 'Annulée',
                    ]),
                Tables\Filters\Filter::make('running')
                    ->label('En cours')
                    ->query(fn (Builder $query) => $query->running()),
            ])
            ->actions([
                Tables\Actions\Action::make('activate')
                    ->label('Activer')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn (Campaign $record) => $record->statut === 'draft' || $record->statut === 'paused')
                    ->requiresConfirmation()
                    ->action(function (Campaign $record) {
                        $record->update(['statut' => 'active']);
                        \Filament\Notifications\Notification::make()
                            ->title('Campagne activée')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('pause')
                    ->label('Pause')
                    ->icon('heroicon-o-pause')
                    ->color('warning')
                    ->visible(fn (Campaign $record) => $record->statut === 'active')
                    ->requiresConfirmation()
                    ->action(function (Campaign $record) {
                        $record->update(['statut' => 'paused']);
                        \Filament\Notifications\Notification::make()
                            ->title('Campagne mise en pause')
                            ->warning()
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
            'index' => Pages\ListCampaigns::route('/'),
            'create' => Pages\CreateCampaign::route('/create'),
            'edit' => Pages\EditCampaign::route('/{record}/edit'),
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
