<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\AnalyticDashboardResource\Pages;
use App\Filament\NsConseil\Resources\AnalyticDashboardResource\RelationManagers;
use App\Models\AnalyticDashboard;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AnalyticDashboardResource extends Resource
{
    protected static ?string $model = AnalyticDashboard::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Tableaux de bord analytiques';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('nom')
                            ->label('Nom du tableau de bord')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3),
                        Forms\Components\Toggle::make('public')
                            ->label('Public')
                            ->helperText('Rendre ce tableau de bord visible par tous les utilisateurs'),
                        Forms\Components\Toggle::make('default')
                            ->label('Définir par défaut')
                            ->helperText('Ce tableau de bord sera affiché par défaut pour vous'),
                    ]),
                Forms\Components\Section::make('Configuration des widgets')
                    ->schema([
                        Forms\Components\Repeater::make('widgets')
                            ->label('Widgets')
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->label('Type de widget')
                                    ->options([
                                        'stats' => 'Statistiques',
                                        'chart' => 'Graphique',
                                        'table' => 'Tableau',
                                        'list' => 'Liste',
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('title')
                                    ->label('Titre'),
                                Forms\Components\Select::make('data_source')
                                    ->label('Source de données')
                                    ->options([
                                        'prospects' => 'Prospects',
                                        'clients' => 'Clients',
                                        'partenaires' => 'Partenaires',
                                        'opportunites' => 'Opportunités',
                                        'taches' => 'Tâches',
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('position_x')
                                    ->label('Position X')
                                    ->numeric()
                                    ->default(0),
                                Forms\Components\TextInput::make('position_y')
                                    ->label('Position Y')
                                    ->numeric()
                                    ->default(0),
                                Forms\Components\TextInput::make('width')
                                    ->label('Largeur')
                                    ->numeric()
                                    ->default(1),
                                Forms\Components\TextInput::make('height')
                                    ->label('Hauteur')
                                    ->numeric()
                                    ->default(1),
                            ])
                            ->columns(3)
                            ->reorderableWithDragAndDrop()
                            ->collapsible(),
                    ]),
                Forms\Components\Section::make('Filtres')
                    ->schema([
                        Forms\Components\KeyValue::make('filters')
                            ->label('Filtres par défaut')
                            ->keyLabel('Champ')
                            ->valueLabel('Valeur')
                            ->addActionLabel('Ajouter un filtre'),
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('public')
                    ->label('Public')
                    ->boolean(),
                Tables\Columns\IconColumn::make('default')
                    ->label('Défaut')
                    ->boolean(),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Créé par')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('public')
                    ->label('Public'),
                Tables\Filters\TernaryFilter::make('default')
                    ->label('Par défaut'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->url(fn (AnalyticDashboard $record) => route('filament.ns-conseil.resources.analytic-dashboards.view', $record)),
                Tables\Actions\Action::make('set_default')
                    ->label('Définir par défaut')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->visible(fn (AnalyticDashboard $record) => !$record->default)
                    ->requiresConfirmation()
                    ->action(function (AnalyticDashboard $record) {
                        $record->setAsDefault(auth()->user());
                        \Filament\Notifications\Notification::make()
                            ->title('Tableau de bord défini par défaut')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListAnalyticDashboards::route('/'),
            'create' => Pages\CreateAnalyticDashboard::route('/create'),
            'edit' => Pages\EditAnalyticDashboard::route('/{record}/edit'),
            'view' => Pages\ViewAnalyticDashboard::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
