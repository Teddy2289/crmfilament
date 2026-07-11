<?php

namespace App\Filament\NsConseil\Resources;

use App\Enums\OrganizationCategory;
use App\Filament\NsConseil\Resources\DocumentResource\Pages;
use App\Models\Document;
use App\Models\Partenaire;
use App\Models\Prospect;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationLabel = 'Documents';

    protected static ?string $navigationGroup = 'Gestion documentaire';

    protected static ?string $modelLabel = 'document';

    protected static ?string $pluralModelLabel = 'documents';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Fichier')
                    ->schema([
                        Forms\Components\FileUpload::make('path')
                            ->label('Fichier')
                            ->disk('public')
                            ->directory(fn(Forms\Get $get) => $get('categorie')
                                ? strtolower($get('categorie'))
                                : 'documents')
                            ->required()
                            ->preserveFilenames()
                            ->maxSize(20 * 1024) // 20 Mo
                            ->columnSpanFull(),

                        Forms\Components\Select::make('categorie')
                            ->label('Catégorie')
                            ->options(
                                collect(OrganizationCategory::cases())
                                    ->mapWithKeys(fn($case) => [$case->value => $case->label()])
                            )
                            ->required()
                            ->live()
                            ->native(false),

                        Forms\Components\MorphToSelect::make('documentable')
                            ->label('Rattaché à')
                            ->types([
                                Forms\Components\MorphToSelect\Type::make(Partenaire::class)
                                    ->titleAttribute('nom'), // adapte le champ titre réel
                                Forms\Components\MorphToSelect\Type::make(Prospect::class)
                                    ->titleAttribute('nom'), // adapte le champ titre réel
                            ])
                            ->searchable()
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nom_fichier')
                    ->label('Fichier')
                    ->icon(fn(Document $record) => $record->icon)
                    ->searchable()
                    ->description(fn(Document $record) => $record->taille_formatee)
                    ->wrap(),

                Tables\Columns\TextColumn::make('categorie')
                    ->label('Catégorie')
                    ->badge()
                    ->formatStateUsing(fn(Document $record) => $record->categorie_label)
                    ->color(fn(Document $record) => $record->categorie_color),

                Tables\Columns\TextColumn::make('documentable_type')
                    ->label('Rattaché à')
                    ->formatStateUsing(fn(string $state) => class_basename($state))
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('mime_type')
                    ->label('Type')
                    ->formatStateUsing(fn(Document $record) => strtoupper($record->extension))
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('uploadedBy.name')
                    ->label('Uploadé par')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ajouté le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('categorie')
                    ->label('Catégorie')
                    ->options(
                        collect(OrganizationCategory::cases())
                            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
                    ),

                Tables\Filters\Filter::make('images')
                    ->label('Images uniquement')
                    ->query(fn(Builder $query) => $query->images())
                    ->toggle(),

                Tables\Filters\Filter::make('pdfs')
                    ->label('PDF uniquement')
                    ->query(fn(Builder $query) => $query->pDFs())
                    ->toggle(),

                Tables\Filters\Filter::make('volumineux')
                    ->label('Fichiers volumineux (> 1 Mo)')
                    ->query(fn(Builder $query) => $query->volumineux())
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('telecharger')
                    ->label('Télécharger')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(fn(Document $record) => response()->streamDownload(
                        fn() => print(Storage::get($record->path)),
                        $record->nom_fichier
                    )),
                Tables\Actions\Action::make('apercu')
                    ->label('Aperçu')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn(Document $record) => $record->nom_fichier)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fermer')
                    ->modalWidth('4xl')
                    ->modalContent(fn(Document $record) => view('filament.modals.document-preview', [
                        'record' => $record,
                    ])),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }
}
