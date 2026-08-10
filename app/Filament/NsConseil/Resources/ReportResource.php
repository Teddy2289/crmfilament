<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\ReportResource\Pages;
use App\Filament\NsConseil\Resources\ReportResource\RelationManagers;
use App\Models\Report;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Reporting';

    protected static ?string $navigationLabel = 'Rapports';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('nom')
                            ->label('Nom du rapport')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3),
                        Forms\Components\Select::make('type')
                            ->label('Type de rapport')
                            ->options([
                                'custom' => 'Personnalisé',
                                'sales' => 'Ventes',
                                'activity' => 'Activité',
                                'performance' => 'Performance',
                            ])
                            ->required()
                            ->default('custom')
                            ->live(),
                    ]),
                Forms\Components\Section::make('Configuration')
                    ->schema([
                        Forms\Components\Select::make('config.model')
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
                        Forms\Components\KeyValue::make('config.filters')
                            ->label('Filtres')
                            ->keyLabel('Champ')
                            ->valueLabel('Valeur')
                            ->addActionLabel('Ajouter un filtre'),
                        Forms\Components\TagsInput::make('config.columns')
                            ->label('Colonnes à afficher')
                            ->suggestions([
                                'id', 'nom', 'email', 'telephone', 'statut', 
                                'created_at', 'updated_at', 'consultant_id'
                            ])
                            ->required(),
                        Forms\Components\Select::make('config.group_by')
                            ->label('Grouper par')
                            ->options([
                                'statut' => 'Statut',
                                'consultant' => 'Consultant',
                                'created_at' => 'Date de création',
                            ])
                            ->nullable(),
                    ]),
                Forms\Components\Section::make('Partage')
                    ->schema([
                        Forms\Components\Toggle::make('public')
                            ->label('Rapport public')
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
                        'custom' => 'primary',
                        'sales' => 'success',
                        'activity' => 'warning',
                        'performance' => 'danger',
                    ]),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Créé par')
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
                        'custom' => 'Personnalisé',
                        'sales' => 'Ventes',
                        'activity' => 'Activité',
                        'performance' => 'Performance',
                    ]),
                Tables\Filters\TernaryFilter::make('public')
                    ->label('Public'),
                Tables\Filters\TernaryFilter::make('actif')
                    ->label('Actif'),
            ])
            ->actions([
                Tables\Actions\Action::make('generate')
                    ->label('Générer')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->url(fn (Report $record) => route('filament.ns-conseil.resources.reports.generate', $record)),
                Tables\Actions\Action::make('export_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('danger')
                    ->url(fn (Report $record) => route('filament.ns-conseil.resources.reports.export-pdf', $record)),
                Tables\Actions\Action::make('export_excel')
                    ->label('Excel')
                    ->icon('heroicon-o-table-cells')
                    ->color('success')
                    ->url(fn (Report $record) => route('filament.ns-conseil.resources.reports.export-excel', $record)),
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
            'index' => Pages\ListReports::route('/'),
            'create' => Pages\CreateReport::route('/create'),
            'edit' => Pages\EditReport::route('/{record}/edit'),
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
                      ->orWhere('created_by', auth()->id());
                });
            });
    }
}
