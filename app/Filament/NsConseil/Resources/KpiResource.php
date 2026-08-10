<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\KpiResource\Pages;
use App\Filament\NsConseil\Resources\KpiResource\RelationManagers;
use App\Models\Kpi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KpiResource extends Resource
{
    protected static ?string $model = Kpi::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationGroup = 'Reporting';

    protected static ?string $navigationLabel = 'KPIs';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('nom')
                            ->label('Nom du KPI')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3),
                    ]),
                Forms\Components\Section::make('Configuration du calcul')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Type de calcul')
                            ->options([
                                'count' => 'Comptage',
                                'sum' => 'Somme',
                                'average' => 'Moyenne',
                                'percentage' => 'Pourcentage',
                            ])
                            ->required()
                            ->default('count')
                            ->live(),
                        Forms\Components\Select::make('model')
                            ->label('Modèle de données')
                            ->options([
                                'prospect' => 'Prospects',
                                'client' => 'Clients',
                                'partenaire' => 'Partenaires',
                                'opportunite' => 'Opportunités',
                                'rendez_vous' => 'Rendez-vous',
                                'appel' => 'Appels',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('field')
                            ->label('Champ à calculer')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Requis pour sum, average et percentage'),
                        Forms\Components\Select::make('aggregation_period')
                            ->label('Période d\'agrégation')
                            ->options([
                                'daily' => 'Quotidien',
                                'weekly' => 'Hebdomadaire',
                                'monthly' => 'Mensuel',
                                'yearly' => 'Annuel',
                            ])
                            ->required()
                            ->default('daily'),
                        Forms\Components\KeyValue::make('filters')
                            ->label('Filtres')
                            ->keyLabel('Champ')
                            ->valueLabel('Valeur')
                            ->addActionLabel('Ajouter un filtre'),
                    ]),
                Forms\Components\Section::make('Objectifs')
                    ->schema([
                        Forms\Components\TextInput::make('target_value')
                            ->label('Valeur cible')
                            ->numeric()
                            ->step(0.01),
                        Forms\Components\Select::make('target_operator')
                            ->label('Opérateur de comparaison')
                            ->options([
                                '>=' => 'Supérieur ou égal',
                                '<=' => 'Inférieur ou égal',
                                '=' => 'Égal',
                                '>' => 'Supérieur',
                                '<' => 'Inférieur',
                            ])
                            ->required()
                            ->default('>='),
                    ]),
                Forms\Components\Section::make('Partage')
                    ->schema([
                        Forms\Components\Toggle::make('public')
                            ->label('KPI public')
                            ->helperText('Visible par tous les utilisateurs'),
                        Forms\Components\Toggle::make('actif')
                            ->label('Actif')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nom')
                    ->label('Nom')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'count' => 'primary',
                        'sum' => 'success',
                        'average' => 'warning',
                        'percentage' => 'danger',
                    ]),
                Tables\Columns\TextColumn::make('model')
                    ->label('Modèle')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('aggregation_period')
                    ->label('Période'),
                Tables\Columns\TextColumn::make('target_value')
                    ->label('Cible')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('public')
                    ->label('Public')
                    ->boolean(),
                Tables\Columns\IconColumn::make('actif')
                    ->label('Actif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'count' => 'Comptage',
                        'sum' => 'Somme',
                        'average' => 'Moyenne',
                        'percentage' => 'Pourcentage',
                    ]),
                Tables\Filters\SelectFilter::make('model')
                    ->label('Modèle')
                    ->options([
                        'prospect' => 'Prospects',
                        'client' => 'Clients',
                        'partenaire' => 'Partenaires',
                        'opportunite' => 'Opportunités',
                        'rendez_vous' => 'Rendez-vous',
                        'appel' => 'Appels',
                    ]),
                Tables\Filters\TernaryFilter::make('public')
                    ->label('Public'),
                Tables\Filters\TernaryFilter::make('actif')
                    ->label('Actif'),
            ])
            ->actions([
                Tables\Actions\Action::make('calculate')
                    ->label('Calculer')
                    ->icon('heroicon-o-calculator')
                    ->color('primary')
                    ->action(function (Kpi $record) {
                        $value = $record->calculateValue();
                        return redirect()->back()->with('kpi_value', $value);
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
            'index' => Pages\ListKpis::route('/'),
            'create' => Pages\CreateKpi::route('/create'),
            'edit' => Pages\EditKpi::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->when(!auth()->user()->hasRole('admin') && !auth()->user()->isSuperAdmin(), function ($query) {
                $query->where(function ($q) {
                    $q->where('public', true)
                      ->orWhere('user_id', auth()->id());
                });
            });
    }
}
