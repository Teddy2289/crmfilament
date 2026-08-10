<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\AuditLogResource\Pages;
use App\Filament\NsConseil\Resources\AuditLogResource\RelationManagers;
use App\Models\AuditLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Journal d\'audit';

    protected static bool $isGloballySearchable = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Détails de l\'action')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Utilisateur')
                            ->relationship('user', 'name')
                            ->disabled(),
                        Forms\Components\TextInput::make('action_label')
                            ->label('Action')
                            ->disabled(),
                        Forms\Components\TextInput::make('model_name')
                            ->label('Modèle')
                            ->disabled(),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->disabled()
                            ->rows(3),
                    ]),
                Forms\Components\Section::make('Informations techniques')
                    ->schema([
                        Forms\Components\TextInput::make('ip_address')
                            ->label('Adresse IP')
                            ->disabled(),
                        Forms\Components\Textarea::make('user_agent')
                            ->label('User Agent')
                            ->disabled()
                            ->rows(2),
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Date')
                            ->disabled(),
                    ]),
                Forms\Components\Section::make('Modifications')
                    ->schema([
                        Forms\Components\KeyValue::make('changes')
                            ->label('Changements')
                            ->disabled(),
                    ])
                    ->visible(fn (AuditLog $record) => !empty($record->changes)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('action_label')
                    ->label('Action')
                    ->color(fn (AuditLog $record) => $record->action_color),
                Tables\Columns\TextColumn::make('model_name')
                    ->label('Modèle')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->label('Action')
                    ->options([
                        'created' => 'Création',
                        'updated' => 'Modification',
                        'deleted' => 'Suppression',
                        'restored' => 'Restauration',
                        'viewed' => 'Consultation',
                        'exported' => 'Export',
                        'imported' => 'Import',
                    ]),
                Tables\Filters\SelectFilter::make('model_type')
                    ->label('Modèle')
                    ->options([
                        'App\Models\Prospect' => 'Prospect',
                        'App\Models\Client' => 'Client',
                        'App\Models\Partenaire' => 'Partenaire',
                        'App\Models\Opportunite' => 'Opportunité',
                        'App\Models\RendezVous' => 'Rendez-vous',
                        'App\Models\Appel' => 'Appel',
                    ]),
                Tables\Filters\Filter::make('date_range')
                    ->label('Période')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Du'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Au'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'], fn (Builder $query, $date) => $query->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn (Builder $query, $date) => $query->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
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
            'index' => Pages\ListAuditLogs::route('/'),
            'view' => Pages\ViewAuditLog::route('/{record}'),
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
