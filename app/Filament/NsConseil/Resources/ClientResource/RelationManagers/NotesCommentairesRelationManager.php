<?php

namespace App\Filament\NsConseil\Resources\ClientResource\RelationManagers;

use App\Models\NoteCommentaire;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NotesCommentairesRelationManager extends RelationManager
{
    protected static string $relationship = 'notesCommentaires';

    protected static ?string $title = 'Notes & Commentaires';

    protected static ?string $modelLabel = 'Note/Commentaire';

    protected static ?string $pluralModelLabel = 'Notes & Commentaires';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type_note')
                    ->label('Type')
                    ->options([
                        'note' => 'Note',
                        'commentaire' => 'Commentaire',
                        'suivi' => 'Suivi',
                        'fiche' => 'Note Fiche',
                        'rapport' => 'Rapport',
                    ])
                    ->default('note')
                    ->required(),

                Forms\Components\Textarea::make('contenu')
                    ->label('Contenu')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('is_prive')
                    ->label('Note privée')
                    ->default(false)
                    ->helperText('Les notes privées ne sont visibles que par vous'),

                Forms\Components\Hidden::make('user_id')
                    ->default(auth()->id()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type_note')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'note' => 'info',
                        'commentaire' => 'primary',
                        'suivi' => 'success',
                        'fiche' => 'orange',
                        'rapport' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('contenu')
                    ->label('Contenu')
                    ->limit(100)
                    ->wrap()
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_prive')
                    ->label('Privé')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('danger')
                    ->falseColor('success'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Auteur')
                    ->default('Inconnu'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type_note')
                    ->label('Type')
                    ->options([
                        'note' => 'Note',
                        'commentaire' => 'Commentaire',
                        'suivi' => 'Suivi',
                        'fiche' => 'Note Fiche',
                        'rapport' => 'Rapport',
                    ]),

                Tables\Filters\TernaryFilter::make('is_prive')
                    ->label('Privé')
                    ->placeholder('Tous')
                    ->trueLabel('Privées uniquement')
                    ->falseLabel('Publiques uniquement'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Ajouter une note'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Modifier'),
                Tables\Actions\DeleteAction::make()
                    ->label('Supprimer'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}