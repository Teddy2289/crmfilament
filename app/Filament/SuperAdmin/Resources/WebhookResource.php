<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\WebhookResource\Pages;
use App\Models\Webhook;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WebhookResource extends Resource
{
    protected static ?string $model = Webhook::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'Webhooks';

    protected static ?string $modelLabel = 'Webhook';

    protected static ?string $pluralModelLabel = 'Webhooks';

    protected static ?string $navigationGroup = 'Intégrations';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Nom descriptif pour identifier ce webhook'),

                        Forms\Components\TextInput::make('url')
                            ->label('URL du webhook')
                            ->required()
                            ->url()
                            ->maxLength(500)
                            ->helperText('URL de destination pour les appels webhook')
                            ->default(fn () => config('app.url') . '/api/webhooks/receive'),

                        Forms\Components\Select::make('event')
                            ->label('Événement')
                            ->required()
                            ->options([
                                'call.started' => 'Appel démarré',
                                'call.ended' => 'Appel terminé',
                                'call.missed' => 'Appel manqué',
                                'contact.created' => 'Contact créé',
                                'contact.updated' => 'Contact mis à jour',
                                'client.created' => 'Client créé',
                                'client.updated' => 'Client mis à jour',
                                'prospect.created' => 'Prospect créé',
                                'prospect.updated' => 'Prospect mis à jour',
                                'rdv.created' => 'Rendez-vous créé',
                                'rdv.updated' => 'Rendez-vous mis à jour',
                            ])
                            ->default('call.started')
                            ->helperText('Type d\'événement qui déclenche le webhook'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true)
                            ->helperText('Activer ou désactiver ce webhook'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Configuration avancée')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->nullable()
                            ->helperText('Description détaillée du webhook'),

                        Forms\Components\KeyValue::make('headers')
                            ->label('En-têtes HTTP')
                            ->keyLabel('Nom')
                            ->valueLabel('Valeur')
                            ->addable()
                            ->deletable()
                            ->nullable()
                            ->helperText('En-têtes HTTP personnalisés à envoyer avec chaque appel'),

                        Forms\Components\TextInput::make('secret')
                            ->label('Secret de signature')
                            ->password()
                            ->nullable()
                            ->maxLength(255)
                            ->helperText('Clé secrète pour vérifier la signature des webhooks'),

                        Forms\Components\Select::make('user_id')
                            ->label('Utilisateur associé')
                            ->relationship('user', 'email')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Utilisateur CRM associé à ce webhook (optionnel)'),
                    ])
                    ->columns(2),
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

                Tables\Columns\TextColumn::make('event')
                    ->label('Événement')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'call.started', 'call.ended', 'call.missed' => 'info',
                        'contact.created', 'contact.updated' => 'success',
                        'client.created', 'client.updated' => 'primary',
                        'prospect.created', 'prospect.updated' => 'warning',
                        'rdv.created', 'rdv.updated' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('url')
                    ->label('URL')
                    ->searchable()
                    ->limit(50)
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Utilisateur')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Mis à jour le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event')
                    ->label('Événement')
                    ->options([
                        'call.started' => 'Appel démarré',
                        'call.ended' => 'Appel terminé',
                        'call.missed' => 'Appel manqué',
                        'contact.created' => 'Contact créé',
                        'contact.updated' => 'Contact mis à jour',
                        'client.created' => 'Client créé',
                        'client.updated' => 'Client mis à jour',
                        'prospect.created' => 'Prospect créé',
                        'prospect.updated' => 'Prospect mis à jour',
                        'rdv.created' => 'Rendez-vous créé',
                        'rdv.updated' => 'Rendez-vous mis à jour',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif')
                    ->placeholder('Tous')
                    ->trueLabel('Actifs')
                    ->falseLabel('Inactifs'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListWebhooks::route('/'),
            'create' => Pages\CreateWebhook::route('/create'),
            'edit' => Pages\EditWebhook::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'administrateur']) ?? false;
    }
}
