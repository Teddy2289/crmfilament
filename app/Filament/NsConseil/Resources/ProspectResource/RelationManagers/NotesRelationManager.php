<?php

namespace App\Filament\NsConseil\Resources\ProspectResource\RelationManagers;

use App\Models\ProspectNote;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class NotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    protected static ?string $recordTitleAttribute = 'content';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('user_id')->default(fn () => auth()->id()),
            Forms\Components\Textarea::make('content')
                ->label('Note')
                ->required()
                ->rows(3),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('content')->label('Note')->limit(200),
                Tables\Columns\TextColumn::make('user.nom_complet')->label('Utilisateur'),
                Tables\Columns\TextColumn::make('created_at')->label('Date')->since(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Ajouter une note'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
