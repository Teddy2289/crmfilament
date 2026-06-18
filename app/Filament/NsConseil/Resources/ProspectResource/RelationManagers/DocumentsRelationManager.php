<?php

namespace App\Filament\NsConseil\Resources\ProspectResource\RelationManagers;

use App\Enums\OrganizationCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documents';

    protected static ?string $icon = 'heroicon-o-paper-clip';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nom_fichier')
                ->label('Nom du document')
                ->required(),
            Forms\Components\Select::make('categorie')
                ->label('Catégorie')
                ->options(OrganizationCategory::class)
                ->required(),
            Forms\Components\FileUpload::make('path')
                ->label('Fichier')
                ->required()
                ->directory('documents/prospects')
                ->maxSize(10240)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nom_fichier')
                    ->label('Nom')
                    ->searchable(),
                Tables\Columns\TextColumn::make('categorie')
                    ->label('Catégorie')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Déposé le')
                    ->dateTime('d/m/Y'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Ajouter un document'),
            ])
            ->actions([
                Tables\Actions\Action::make('telecharger')
                    ->label('Télécharger')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record) => asset('storage/'.$record->path))
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
