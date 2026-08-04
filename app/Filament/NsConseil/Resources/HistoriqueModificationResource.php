<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\HistoriqueModificationResource\Pages;
use App\Models\HistoriqueModification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class HistoriqueModificationResource extends Resource
{
    protected static ?string $model = HistoriqueModification::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Historique des modifications';

    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool
    {
        return Auth::user()?->can('view_historique_modifications') ?? false;
    }

    // ─────────────────────────────────────────────────────────────────
    // FORMULAIRE
    // ─────────────────────────────────────────────────────────────────
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informations')
                ->schema([
                    Forms\Components\Select::make('model_type')
                        ->label('Type de modèle')
                        ->options([
                            'App\Models\Prospect' => 'Prospect',
                            'App\Models\Partenaire' => 'Partenaire',
                            'App\Models\Client' => 'Client',
                            'App\Models\ContactPartenaire' => 'Contact Partenaire',
                        ])
                        ->required()
                        ->disabled(),

                    Forms\Components\TextInput::make('model_id')
                        ->label('ID du modèle')
                        ->numeric()
                        ->required()
                        ->disabled(),

                    Forms\Components\Select::make('user_id')
                        ->label('Utilisateur')
                        ->relationship('user', 'nom_complet')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabled(),

                    Forms\Components\Select::make('type_modification')
                        ->label('Type de modification')
                        ->options(HistoriqueModification::TYPES_MODIFICATION)
                        ->required()
                        ->disabled(),

                    Forms\Components\TextInput::make('champ')
                        ->label('Champ modifié')
                        ->disabled(),

                    Forms\Components\Textarea::make('ancienne_valeur')
                        ->label('Ancienne valeur')
                        ->rows(3)
                        ->disabled(),

                    Forms\Components\Textarea::make('nouvelle_valeur')
                        ->label('Nouvelle valeur')
                        ->rows(3)
                        ->disabled(),

                    Forms\Components\DateTimePicker::make('date_modification')
                        ->label('Date de modification')
                        ->disabled(),
                ])
                ->columns(2),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // TABLE
    // ─────────────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date_modification')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->description(fn ($record): string => $record->type_modification_label),

                Tables\Columns\TextColumn::make('model_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'App\Models\Prospect' => 'Prospect',
                        'App\Models\Partenaire' => 'Partenaire',
                        'App\Models\Client' => 'Client',
                        'App\Models\ContactPartenaire' => 'Contact',
                        default => $state,
                    })
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('model_id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('user.nom_complet')
                    ->label('Utilisateur')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('champ')
                    ->label('Champ')
                    ->formatStateUsing(fn ($state) => $state ? $state : '-')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ancienne_valeur')
                    ->label('Ancienne valeur')
                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state) : (string) $state)
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('nouvelle_valeur')
                    ->label('Nouvelle valeur')
                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state) : (string) $state)
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BadgeColumn::make('type_modification')
                    ->label('Type')
                    ->colors([
                        'success' => 'creation',
                        'warning' => 'modification',
                        'danger' => 'suppression',
                        'info' => 'restauration',
                    ])
                    ->formatStateUsing(fn ($state) => HistoriqueModification::TYPES_MODIFICATION[$state] ?? $state),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('model_type')
                    ->label('Type de modèle')
                    ->options(fn (): array => HistoriqueModification::query()
                        ->distinct()
                        ->orderBy('model_type')
                        ->pluck('model_type', 'model_type')
                        ->mapWithKeys(fn (string $value): array => [
                            $value => match ($value) {
                                'App\Models\Prospect' => 'Prospect',
                                'App\Models\Partenaire' => 'Partenaire',
                                'App\Models\Client' => 'Client',
                                'App\Models\ContactPartenaire' => 'Contact Partenaire',
                                default => $value,
                            },
                        ])
                        ->toArray()),

                Tables\Filters\SelectFilter::make('type_modification')
                    ->label('Type de modification')
                    ->options(fn (): array => HistoriqueModification::query()
                        ->distinct()
                        ->orderBy('type_modification')
                        ->pluck('type_modification', 'type_modification')
                        ->mapWithKeys(fn (string $value): array => [
                            $value => HistoriqueModification::TYPES_MODIFICATION[$value] ?? $value,
                        ])
                        ->toArray()),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Utilisateur')
                    ->relationship('user', 'nom_complet')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('date_modification')
                    ->label('Date de modification')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Du'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Au'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($query) => $query->whereDate('date_modification', '>=', $data['from']))
                            ->when($data['until'], fn ($query) => $query->whereDate('date_modification', '<=', $data['until']));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // Pas d'actions bulk pour l'historique (lecture seule)
            ])
            ->defaultSort('date_modification', 'desc');
    }

    // ─────────────────────────────────────────────────────────────────
    // PAGES
    // ─────────────────────────────────────────────────────────────────
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHistoriqueModifications::route('/'),
            'view' => Pages\ViewHistoriqueModification::route('/{record}'),
        ];
    }
}
