<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\DocumentKnowledgeResource\Pages;
use App\Models\DocumentKnowledge;
use App\Support\UsesResourcePermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DocumentKnowledgeResource extends Resource
{
    use UsesResourcePermissions;

    protected static ?string $model = DocumentKnowledge::class;

    protected static string $permissionPrefix = 'document_knowledges';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Paramètres';

    protected static ?string $navigationLabel = 'Base de connaissances';

    protected static ?string $modelLabel = 'document';

    protected static ?string $pluralModelLabel = 'Base de connaissances';

    protected static ?int $navigationSort = 10;

    public static function canAccess(): bool
    {
        return static::userCanViewResourceList();
    }

    public static function canViewAny(): bool
    {
        return static::userCanViewResourceList();
    }

    public static function canView(Model $record): bool
    {
        return static::userCanResourcePermission('view');
    }

    public static function canCreate(): bool
    {
        return static::userCanResourcePermission('create');
    }

    public static function canEdit(Model $record): bool
    {
        return static::userCanResourcePermission('update');
    }

    public static function canDelete(Model $record): bool
    {
        return static::userCanResourcePermission('delete');
    }

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
                            ->disk('public')
                            ->directory('knowledge-base')
                            ->preserveFilenames()
                            ->downloadable()
                            ->openable()
                            ->maxSize(10240) // 10MB
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-powerpoint',
                                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                                'text/plain',
                            ])
                            ->required(fn (string $operation): bool => $operation === 'create'),
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
                Tables\Actions\Action::make('open_file')
                    ->label('Ouvrir')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (DocumentKnowledge $record): string => $record->url, true)
                    ->visible(fn (DocumentKnowledge $record): bool => filled($record->fichier_path)),
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Document')
                ->schema([
                    Infolists\Components\TextEntry::make('titre')
                        ->label('Titre')
                        ->weight('bold'),
                    Infolists\Components\TextEntry::make('description')
                        ->label('Description')
                        ->columnSpanFull()
                        ->placeholder('Aucune description'),
                    Infolists\Components\TextEntry::make('type')
                        ->label('Type')
                        ->badge()
                        ->formatStateUsing(fn ($state) => DocumentKnowledge::TYPES[$state] ?? $state),
                    Infolists\Components\TextEntry::make('categorie')
                        ->label('Categorie')
                        ->badge()
                        ->formatStateUsing(fn ($state) => DocumentKnowledge::CATEGORIES[$state] ?? $state),
                    Infolists\Components\IconEntry::make('est_publique')
                        ->label('Public')
                        ->boolean(),
                    Infolists\Components\TextEntry::make('ordre')
                        ->label('Ordre'),
                ])
                ->columns(3),

            Infolists\Components\Section::make('Fichier')
                ->schema([
                    Infolists\Components\TextEntry::make('fichier_nom')
                        ->label('Nom du fichier')
                        ->placeholder('Aucun fichier'),
                    Infolists\Components\TextEntry::make('fichier_type')
                        ->label('Type fichier')
                        ->placeholder('-'),
                    Infolists\Components\TextEntry::make('taille_formatee')
                        ->label('Taille'),
                    Infolists\Components\TextEntry::make('url')
                        ->label('Lien')
                        ->state(fn (DocumentKnowledge $record): string => $record->fichier_path ? 'Ouvrir le fichier' : 'Aucun fichier')
                        ->url(fn (DocumentKnowledge $record): ?string => $record->fichier_path ? $record->url : null, true),
                ])
                ->columns(4),

            Infolists\Components\Section::make('Suivi')
                ->schema([
                    Infolists\Components\TextEntry::make('createdBy.name')
                        ->label('Créé par')
                        ->placeholder('-'),
                    Infolists\Components\TextEntry::make('updatedBy.name')
                        ->label('Mis à jour par')
                        ->placeholder('-'),
                    Infolists\Components\TextEntry::make('created_at')
                        ->label('Créé le')
                        ->dateTime('d/m/Y H:i'),
                    Infolists\Components\TextEntry::make('updated_at')
                        ->label('Mis à jour le')
                        ->dateTime('d/m/Y H:i'),
                ])
                ->columns(4),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function enrichFileMetadata(array $data): array
    {
        $path = $data['fichier_path'] ?? null;

        if (is_array($path)) {
            $path = collect($path)->filter()->first();
        }

        if (! is_string($path) || $path === '') {
            return $data;
        }

        $data['fichier_path'] = $path;
        $data['fichier_nom'] = basename(str_replace('\\', '/', $path));
        $data['fichier_type'] = pathinfo($data['fichier_nom'], PATHINFO_EXTENSION) ?: null;

        if (Storage::disk('public')->exists($path)) {
            $data['taille_octets'] = Storage::disk('public')->size($path);
        }

        return $data;
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
