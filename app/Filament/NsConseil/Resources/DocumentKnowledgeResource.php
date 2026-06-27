<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\DocumentKnowledgeResource\Pages;
use App\Models\DocumentKnowledge;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DocumentKnowledgeResource extends Resource
{
    protected static ?string $model = DocumentKnowledge::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Base de connaissances';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('titre')
                            ->label('Titre')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3),

                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options(DocumentKnowledge::TYPES)
                            ->required(),

                        Forms\Components\Select::make('categorie')
                            ->label('Catégorie')
                            ->options(DocumentKnowledge::CATEGORIES),

                        Forms\Components\Toggle::make('est_publique')
                            ->label('Document public')
                            ->default(true),

                        Forms\Components\TextInput::make('ordre')
                            ->label('Ordre d\'affichage')
                            ->numeric()
                            ->default(0),
                    ]),

                Forms\Components\Section::make('Fichier')
                    ->schema([
                        Forms\Components\FileUpload::make('fichier_path')
                            ->label('Fichier')
                            ->directory('knowledge-base')
                            ->preserveFilenames()
                            ->maxSize(10240) // 10MB
                            ->acceptedFileTypes(['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'])
                            ->required()
                            ->saveUploadedFileUsing(function ($file) {
                                $fileName = $file->getClientOriginalName();
                                $filePath = $file->storeAs('knowledge-base', $fileName, 'public');
                                return [
                                    'fichier_path' => $filePath,
                                    'fichier_nom' => $fileName,
                                    'fichier_type' => $file->getClientOriginalExtension(),
                                    'taille_octets' => $file->getSize(),
                                ];
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('titre')
                    ->label('Titre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => DocumentKnowledge::TYPES[$state] ?? $state),

                Tables\Columns\BadgeColumn::make('categorie')
                    ->label('Catégorie')
                    ->formatStateUsing(fn ($state) => DocumentKnowledge::CATEGORIES[$state] ?? $state),

                Tables\Columns\IconColumn::make('est_publique')
                    ->label('Public')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-open')
                    ->falseIcon('heroicon-o-lock-closed'),

                Tables\Columns\TextColumn::make('taille_formatee')
                    ->label('Taille')
                    ->sortable(),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Créé par')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(DocumentKnowledge::TYPES)
                    ->label('Type'),

                Tables\Filters\SelectFilter::make('categorie')
                    ->options(DocumentKnowledge::CATEGORIES)
                    ->label('Catégorie'),

                Tables\Filters\TernaryFilter::make('est_publique')
                    ->label('Public')
                    ->placeholder('Tous')
                    ->trueLabel('Public')
                    ->falseLabel('Privé'),
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
            ->defaultSort('ordre');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocumentKnowledge::route('/'),
            'create' => Pages\CreateDocumentKnowledge::route('/create'),
            'view' => Pages\ViewDocumentKnowledge::route('/{record}'),
            'edit' => Pages\EditDocumentKnowledge::route('/{record}/edit'),
        ];
    }
}
