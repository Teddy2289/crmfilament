<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\IntegrationResource\Pages;
use App\Filament\NsConseil\Resources\IntegrationResource\RelationManagers;
use App\Models\Integration;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class IntegrationResource extends Resource
{
    protected static ?string $model = Integration::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Intégrations';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Type d\'intégration')
                            ->options([
                                'slack' => 'Slack',
                                'teams' => 'Microsoft Teams',
                                'outlook' => 'Outlook',
                            ])
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2),
                    ]),
                Forms\Components\Section::make('Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('config.webhook_url')
                            ->label('URL du Webhook')
                            ->url()
                            ->required()
                            ->visible(fn (Forms\Get $get) => in_array($get('type'), ['slack', 'teams']))
                            ->helperText('URL du webhook reçu depuis Slack ou Teams'),
                        Forms\Components\TextInput::make('config.channel')
                            ->label('Canal')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'slack')
                            ->helperText('Canal Slack par défaut (optionnel)'),
                        Forms\Components\TextInput::make('config.client_id')
                            ->label('Client ID')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'outlook')
                            ->helperText('ID client Microsoft Azure'),
                        Forms\Components\TextInput::make('config.client_secret')
                            ->label('Client Secret')
                            ->password()
                            ->visible(fn (Forms\Get $get) => $get('type') === 'outlook')
                            ->helperText('Secret client Microsoft Azure'),
                        Forms\Components\TextInput::make('config.tenant_id')
                            ->label('Tenant ID')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'outlook')
                            ->helperText('ID du locataire Microsoft Azure'),
                    ]),
                Forms\Components\Section::make('Statut')
                    ->schema([
                        Forms\Components\Toggle::make('actif')
                            ->label('Actif')
                            ->default(true),
                        Forms\Components\Toggle::make('verified')
                            ->label('Vérifié')
                            ->disabled()
                            ->helperText('Coché après vérification réussie'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'slack' => 'success',
                        'teams' => 'primary',
                        'outlook' => 'info',
                    ]),
                Tables\Columns\IconColumn::make('verified')
                    ->label('Vérifié')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                Tables\Columns\IconColumn::make('actif')
                    ->label('Actif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('last_sync_at')
                    ->label('Dernière synchronisation')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'slack' => 'Slack',
                        'teams' => 'Microsoft Teams',
                        'outlook' => 'Outlook',
                    ]),
                Tables\Filters\TernaryFilter::make('verified')
                    ->label('Vérifié'),
                Tables\Filters\TernaryFilter::make('actif')
                    ->label('Actif'),
            ])
            ->actions([
                Tables\Actions\Action::make('verify')
                    ->label('Vérifier')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->action(function (Integration $record) {
                        $success = $record->verify();
                        if ($success) {
                            \Filament\Notifications\Notification::make()
                                ->title('Intégration vérifiée avec succès')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Échec de la vérification')
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('test')
                    ->label('Tester')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->action(function (Integration $record) {
                        $success = $record->sendNotification('Test de notification CRM');
                        if ($success) {
                            \Filament\Notifications\Notification::make()
                                ->title('Notification de test envoyée')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Échec de l\'envoi')
                                ->danger()
                                ->send();
                        }
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
            'index' => Pages\ListIntegrations::route('/'),
            'create' => Pages\CreateIntegration::route('/create'),
            'edit' => Pages\EditIntegration::route('/{record}/edit'),
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
