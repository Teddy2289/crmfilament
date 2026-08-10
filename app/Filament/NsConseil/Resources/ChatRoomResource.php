<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\ChatRoomResource\Pages;
use App\Filament\NsConseil\Resources\ChatRoomResource\RelationManagers;
use App\Models\ChatRoom;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChatRoomResource extends Resource
{
    protected static ?string $model = ChatRoom::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?string $navigationLabel = 'Chat';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->label('Type')
                    ->options([
                        'direct' => 'Message direct',
                        'group' => 'Groupe',
                        'channel' => 'Canal',
                    ])
                    ->required()
                    ->default('direct')
                    ->live(),
                Forms\Components\TextInput::make('nom')
                    ->label('Nom')
                    ->required()
                    ->maxLength(255)
                    ->visible(fn (Forms\Get $get) => $get('type') !== 'direct'),
                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->columnSpanFull()
                    ->visible(fn (Forms\Get $get) => $get('type') !== 'direct'),
                Forms\Components\Select::make('participants')
                    ->label('Participants')
                    ->relationship('participants.user', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->visible(fn (Forms\Get $get) => $get('type') !== 'direct'),
                Forms\Components\Toggle::make('actif')
                    ->label('Actif')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nom')
                    ->label('Nom')
                    ->searchable()
                    ->default('Message direct'),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'direct' => 'primary',
                        'group' => 'success',
                        'channel' => 'warning',
                    ]),
                Tables\Columns\TextColumn::make('participants_count')
                    ->label('Participants')
                    ->counts('participants')
                    ->sortable(),
                Tables\Columns\TextColumn::make('messages_count')
                    ->label('Messages')
                    ->counts('messages')
                    ->sortable(),
                Tables\Columns\IconColumn::make('actif')
                    ->label('Actif')
                    ->boolean(),
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
                        'direct' => 'Message direct',
                        'group' => 'Groupe',
                        'channel' => 'Canal',
                    ]),
                Tables\Filters\TernaryFilter::make('actif')
                    ->label('Actif'),
            ])
            ->actions([
                Tables\Actions\Action::make('open_chat')
                    ->label('Ouvrir')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('primary')
                    ->url(fn (ChatRoom $record) => route('filament.ns-conseil.resources.chat-rooms.chat', $record)),
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
            RelationManagers\MessagesRelationManager::class,
            RelationManagers\ParticipantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChatRooms::route('/'),
            'create' => Pages\CreateChatRoom::route('/create'),
            'edit' => Pages\EditChatRoom::route('/{record}/edit'),
            'chat' => Pages\ChatRoomChat::route('/{record}/chat'),
        ];
    }
}
