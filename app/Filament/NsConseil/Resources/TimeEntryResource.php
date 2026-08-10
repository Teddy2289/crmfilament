<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\TimeEntryResource\Pages;
use App\Filament\NsConseil\Resources\TimeEntryResource\RelationManagers;
use App\Models\TimeEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TimeEntryResource extends Resource
{
    protected static ?string $model = TimeEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'id')
                    ->required(),
                Forms\Components\Select::make('task_id')
                    ->relationship('task', 'id'),
                Forms\Components\Select::make('prospect_id')
                    ->relationship('prospect', 'id'),
                Forms\Components\Select::make('client_id')
                    ->relationship('client', 'id'),
                Forms\Components\Select::make('partenaire_id')
                    ->relationship('partenaire', 'id'),
                Forms\Components\Select::make('opportunite_id')
                    ->relationship('opportunite', 'id'),
                Forms\Components\Select::make('rendez_vous_id')
                    ->relationship('rendezVous', 'id'),
                Forms\Components\Select::make('appel_id')
                    ->relationship('appel', 'id'),
                Forms\Components\TextInput::make('description')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('start_time')
                    ->required(),
                Forms\Components\DateTimePicker::make('end_time'),
                Forms\Components\TextInput::make('duration')
                    ->numeric(),
                Forms\Components\TextInput::make('type')
                    ->required()
                    ->maxLength(255)
                    ->default('work'),
                Forms\Components\Toggle::make('billable')
                    ->required(),
                Forms\Components\TextInput::make('hourly_rate')
                    ->numeric(),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('task.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('prospect.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('partenaire.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('opportunite.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rendezVous.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('appel.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\IconColumn::make('billable')
                    ->boolean(),
                Tables\Columns\TextColumn::make('hourly_rate')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListTimeEntries::route('/'),
            'create' => Pages\CreateTimeEntry::route('/create'),
            'edit' => Pages\EditTimeEntry::route('/{record}/edit'),
        ];
    }
}
