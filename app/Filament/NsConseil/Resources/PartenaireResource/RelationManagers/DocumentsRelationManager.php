<?php

namespace App\Filament\NsConseil\Resources\PartenaireResource\RelationManagers;

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
                ->default(OrganizationCategory::Partenaires)
                ->required(),

            Forms\Components\FileUpload::make('path')
                ->label('Fichier')
                ->required()
                ->directory('documents/partenaires')
                ->acceptedFileTypes(['application/pdf', 'image/*', '.doc', '.docx', '.xls', '.xlsx'])
                ->maxSize(10240)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nom_fichier')
                    ->label('Nom')
                    ->searchable(),

                // ✅ TextColumn avec badge()
                Tables\Columns\TextColumn::make('categorie')
                    ->label('Catégorie')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof OrganizationCategory
                        ? $state->label()
                        : ($state ?? 'N/A')
                    )
                    ->color(fn ($state) => match (true) {
                        $state instanceof OrganizationCategory && $state === OrganizationCategory::Partenaires => 'primary',
                        $state instanceof OrganizationCategory && $state === OrganizationCategory::Artisans => 'warning',
                        $state instanceof OrganizationCategory && $state === OrganizationCategory::Contrats => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('taille')
                    ->label('Taille')
                    ->formatStateUsing(fn ($state) => $state
                        ? number_format($state / 1024, 1) . ' Ko'
                        : '—'),

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
                    ->url(fn ($record) => asset('storage/' . $record->path))
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
