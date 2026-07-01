<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\TemplateFicheResource\Pages;
use App\Filament\SuperAdmin\Resources\TemplateFicheResource\RelationManagers;
use App\Models\TemplateFiche;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TemplateFicheResource extends Resource
{
    protected static ?string $model = TemplateFiche::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Modèles de fiches';

    protected static ?string $navigationGroup = 'Paramétrage CRM';

    protected static ?string $modelLabel = 'Modèle de fiche';

    protected static ?string $pluralModelLabel = 'Modèles de fiches';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('code')
                    ->label('Code')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                TextInput::make('nom')
                    ->label('Nom')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->label('Type')
                    ->options([
                        'bleue' => 'Bleue',
                        'jaune' => 'Jaune',
                        'verte' => 'Verte',
                    ])
                    ->default('bleue')
                    ->required(),
                Textarea::make('description')
                    ->label('Description')
                    ->columnSpanFull,
                FileUpload::make('fichier_path')
                    ->label('Fichier Word')
                    ->acceptedFileTypes(['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                    ->maxSize(10240) // 10MB
                    ->directory('templates_fiches')
                    ->visibility('public')
                    ->preserveFilenames()
                    ->downloadable()
                    ->openable()
                    ->required(),
                Textarea::make('variables')
                    ->label('Variables (JSON)')
                    ->help('Exemple: {"nom_client": "Nom du client", "date_rdv": "Date du rendez-vous"}')
                    ->columnSpanFull,
                Toggle::make('actif')
                    ->label('Actif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nom')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'bleue' => 'blue',
                        'jaune' => 'yellow',
                        'verte' => 'green',
                        default => 'gray',
                    }),
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50),
                TextColumn::make('fichier_path')
                    ->label('Fichier')
                    ->getStateUsing(fn (string $state): string => basename($state))
                    ->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('actif')
                    ->label('Actif')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Mis à jour le')
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
            'index' => Pages\ListTemplateFiches::route('/'),
            'create' => Pages\CreateTemplateFiche::route('/create'),
            'edit' => Pages\EditTemplateFiche::route('/{record}/edit'),
        ];
    }
}
