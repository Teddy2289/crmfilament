<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\EmailTemplateResource\Pages;
use App\Models\EmailTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;
    protected static ?string $navigationIcon = 'heroicon-o-envelope-open';
    protected static ?string $navigationGroup = 'Communication';
    protected static ?string $navigationLabel = 'Templates Email';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Template Email';
    protected static ?string $pluralModelLabel = 'Templates Email';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identification')
                ->schema([
                    Forms\Components\TextInput::make('nom')
                        ->label('Nom')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('cle')
                        ->label('Clé (identifiant programmatique)')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(100)
                        ->helperText('Ex : rdv.confirmation_cse — ne pas modifier après création'),

                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('actif')
                        ->label('Actif')
                        ->default(true),
                ])->columns(2),

            Forms\Components\Section::make('Contenu')
                ->schema([
                    Forms\Components\TextInput::make('sujet')
                        ->label('Sujet')
                        ->required()
                        ->maxLength(500)
                        ->helperText('Utilisez {{variable}} pour les variables dynamiques')
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('corps')
                        ->label('Corps du mail')
                        ->required()
                        ->rows(15)
                        ->helperText('Variables disponibles entre doubles accolades : {{prenom}}, {{date}}, etc.')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nom')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('cle')
                    ->label('Clé')
                    ->searchable()
                    ->badge()
                    ->color('gray')
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('sujet')
                    ->label('Sujet')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->sujet),

                Tables\Columns\IconColumn::make('actif')
                    ->label('Actif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('actif')->label('Statut'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('nom');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListEmailTemplates::route('/'),
            'create' => Pages\CreateEmailTemplate::route('/create'),
            'edit'   => Pages\EditEmailTemplate::route('/{record}/edit'),
        ];
    }
}
