<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\RingoverApiKeyResource\Pages;
use App\Models\RingoverApiKey;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RingoverApiKeyResource extends Resource
{
    protected static ?string $model = RingoverApiKey::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Clés API Ringover';

    protected static ?string $navigationGroup = 'Intégrations';

    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de la clé API')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Ex: Admin Ringover, Télépro 1, etc.'),

                        Forms\Components\TextInput::make('api_key')
                            ->label('Clé API')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->password()
                            ->revealable()
                            ->helperText('Clé API Ringover'),

                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->required()
                            ->options([
                                'admin' => 'Admin',
                                'telepro' => 'Télépro',
                                'standard' => 'Standard',
                            ])
                            ->default('standard')
                            ->helperText('Type d\'utilisation de la clé'),

                        Forms\Components\Select::make('user_id')
                            ->label('Utilisateur associé')
                            ->relationship('user', 'email')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Utilisateur CRM associé à cette clé (optionnel)'),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->nullable(),

                        Forms\Components\TextInput::make('click_to_call_url')
                            ->label('URL Click-to-Call')
                            ->url()
                            ->nullable()
                            ->helperText('URL Ringover pour le click-to-call (ex: https://ringover.me/xxx?region=eu)'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Activer ou désactiver cette clé API'),
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
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'admin' => 'danger',
                        'telepro' => 'primary',
                        'standard' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('api_key')
                    ->label('Clé API')
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => substr($state, 0, 8) . '...'),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Utilisateur')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->toggleable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'admin' => 'Admin',
                        'telepro' => 'Télépro',
                        'standard' => 'Standard',
                    ]),

                Tables\Filters\Filter::make('is_active')
                    ->label('Actives')
                    ->query(fn ($query) => $query->where('is_active', true))
                    ->toggle(),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('click_to_call')
                    ->label('Appeler')
                    ->icon('heroicon-o-phone')
                    ->url(fn ($record) => $record->click_to_call_url)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->click_to_call_url && $record->is_active),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Aucune clé API Ringover')
            ->emptyStateDescription('Créez votre première clé API Ringover pour intégrer le système.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRingoverApiKeys::route('/'),
            'create' => Pages\CreateRingoverApiKey::route('/create'),
            'edit' => Pages\EditRingoverApiKey::route('/{record}/edit'),
        ];
    }
}
