<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\EmailTemplateResource\Pages;
use App\Models\EmailTemplate;
use App\Support\UsesResourcePermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmailTemplateResource extends Resource
{
    use UsesResourcePermissions;

    protected static ?string $model = EmailTemplate::class;

    protected static string $permissionPrefix = 'email_templates';
    protected static ?string $navigationIcon = 'heroicon-o-envelope-open';
    protected static ?string $navigationGroup = 'Communication';
    protected static ?string $navigationLabel = 'Modèles d\'e-mail';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Modèle d\'e-mail';
    protected static ?string $pluralModelLabel = 'Modèles d\'e-mail';

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
                        ->columnSpanFull()
                        ->helperText('Variables disponibles entre doubles accolades : {{prenom}}, {{nom}}, {{date}}, etc.'),

                    Forms\Components\Section::make('Variables disponibles')
                        ->description('Cliquez sur l\'icône de copie pour copier la variable')
                        ->schema([
                            Forms\Components\Grid::make(4)
                                ->schema([
                                    Forms\Components\TextInput::make('var_prenom')
                                        ->label('Prénom')
                                        ->default('{{prenom}}')
                                        ->readOnly()
                                        ->suffixAction(
                                            Forms\Components\Actions\Action::make('copy_prenom')
                                                ->icon('heroicon-o-clipboard')
                                                ->tooltip('Copier')
                                                ->action(fn () => null)
                                                ->extraAttributes(['x-on:click' => "navigator.clipboard.writeText('{{prenom}}')"])
                                        ),
                                    Forms\Components\TextInput::make('var_nom')
                                        ->label('Nom')
                                        ->default('{{nom}}')
                                        ->readOnly()
                                        ->suffixAction(
                                            Forms\Components\Actions\Action::make('copy_nom')
                                                ->icon('heroicon-o-clipboard')
                                                ->tooltip('Copier')
                                                ->action(fn () => null)
                                                ->extraAttributes(['x-on:click' => "navigator.clipboard.writeText('{{nom}}')"])
                                        ),
                                    Forms\Components\TextInput::make('var_email')
                                        ->label('Email')
                                        ->default('{{email}}')
                                        ->readOnly()
                                        ->suffixAction(
                                            Forms\Components\Actions\Action::make('copy_email')
                                                ->icon('heroicon-o-clipboard')
                                                ->tooltip('Copier')
                                                ->action(fn () => null)
                                                ->extraAttributes(['x-on:click' => "navigator.clipboard.writeText('{{email}}')"])
                                        ),
                                    Forms\Components\TextInput::make('var_telephone')
                                        ->label('Téléphone')
                                        ->default('{{telephone}}')
                                        ->readOnly()
                                        ->suffixAction(
                                            Forms\Components\Actions\Action::make('copy_telephone')
                                                ->icon('heroicon-o-clipboard')
                                                ->tooltip('Copier')
                                                ->action(fn () => null)
                                                ->extraAttributes(['x-on:click' => "navigator.clipboard.writeText('{{telephone}}')"])
                                        ),
                                    Forms\Components\TextInput::make('var_raison_sociale')
                                        ->label('Raison sociale')
                                        ->default('{{raison_sociale}}')
                                        ->readOnly()
                                        ->suffixAction(
                                            Forms\Components\Actions\Action::make('copy_raison_sociale')
                                                ->icon('heroicon-o-clipboard')
                                                ->tooltip('Copier')
                                                ->action(fn () => null)
                                                ->extraAttributes(['x-on:click' => "navigator.clipboard.writeText('{{raison_sociale}}')"])
                                        ),
                                    Forms\Components\TextInput::make('var_date')
                                        ->label('Date')
                                        ->default('{{date}}')
                                        ->readOnly()
                                        ->suffixAction(
                                            Forms\Components\Actions\Action::make('copy_date')
                                                ->icon('heroicon-o-clipboard')
                                                ->tooltip('Copier')
                                                ->action(fn () => null)
                                                ->extraAttributes(['x-on:click' => "navigator.clipboard.writeText('{{date}}')"])
                                        ),
                                    Forms\Components\TextInput::make('var_heure')
                                        ->label('Heure')
                                        ->default('{{heure}}')
                                        ->readOnly()
                                        ->suffixAction(
                                            Forms\Components\Actions\Action::make('copy_heure')
                                                ->icon('heroicon-o-clipboard')
                                                ->tooltip('Copier')
                                                ->action(fn () => null)
                                                ->extraAttributes(['x-on:click' => "navigator.clipboard.writeText('{{heure}}')"])
                                        ),
                                    Forms\Components\TextInput::make('var_lieu')
                                        ->label('Lieu')
                                        ->default('{{lieu}}')
                                        ->readOnly()
                                        ->suffixAction(
                                            Forms\Components\Actions\Action::make('copy_lieu')
                                                ->icon('heroicon-o-clipboard')
                                                ->tooltip('Copier')
                                                ->action(fn () => null)
                                                ->extraAttributes(['x-on:click' => "navigator.clipboard.writeText('{{lieu}}')"])
                                        ),
                                ]),
                        ])
                        ->columnSpanFull()
                        ->collapsed(),
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
