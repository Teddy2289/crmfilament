<?php

namespace App\Filament\NsConseil\Resources\ChatRoomResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ParticipantsRelationManager extends RelationManager
{
    protected static string $relationship = 'participants';

    protected static ?string $title = 'Participants';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Utilisateur')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('role')
                    ->label('Rôle')
                    ->options([
                        'admin' => 'Administrateur',
                        'member' => 'Membre',
                    ])
                    ->required()
                    ->default('member'),
                Forms\Components\Toggle::make('actif')
                    ->label('Actif')
                    ->default(true)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('user.name')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('role')
                    ->label('Rôle')
                    ->colors([
                        'admin' => 'danger',
                        'member' => 'success',
                    ]),
                Tables\Columns\IconColumn::make('actif')
                    ->label('Actif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('last_read_at')
                    ->label('Dernière lecture')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Rôle')
                    ->options([
                        'admin' => 'Administrateur',
                        'member' => 'Membre',
                    ]),
                Tables\Filters\TernaryFilter::make('actif')
                    ->label('Actif'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
