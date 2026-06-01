<?php
namespace App\Filament\Allopro\Resources\TicketResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ReclamationsRelationManager extends RelationManager
{
    protected static string $relationship = 'reclamations';
    protected static ?string $title = 'Réclamations P8';
    protected static ?string $icon  = 'heroicon-o-exclamation-triangle';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DateTimePicker::make('date_ouverture')
                ->label('Date d\'ouverture')
                ->required()
                ->native(false)
                ->default(now()),

            Forms\Components\Textarea::make('description_reclamation')
                ->label('Description de la réclamation')
                ->required()
                ->rows(4),

            Forms\Components\Select::make('statut')
                ->label('Statut')
                ->options([
                    'ouverte'             => 'Ouverte',
                    'en_traitement'       => 'En traitement',
                    'validee_superviseur' => 'Validée superviseur',
                    'cloturee'            => 'Clôturée',
                ])
                ->required()
                ->native(false)
                ->default('ouverte'),

            Forms\Components\DatePicker::make('date_resolution_cible')
                ->label('Date de résolution cible (J+5 max)')
                ->native(false)
                ->default(now()->addWeekdays(5)),

            Forms\Components\DatePicker::make('date_resolution_effective')
                ->label('Date de résolution effective')
                ->native(false)
                ->nullable(),

            Forms\Components\Toggle::make('validation_superviseur')
                ->label('Validation superviseur requise')
                ->default(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('date_ouverture', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'ouverte'             => 'danger',
                        'en_traitement'       => 'warning',
                        'validee_superviseur' => 'info',
                        'cloturee'            => 'success',
                        default               => 'gray',
                    }),

                Tables\Columns\TextColumn::make('description_reclamation')
                    ->label('Description')
                    ->limit(60),

                Tables\Columns\TextColumn::make('date_ouverture')
                    ->label('Ouverte le')
                    ->dateTime('d/m/Y H:i'),

                Tables\Columns\TextColumn::make('date_resolution_cible')
                    ->label('Résolution cible')
                    ->date('d/m/Y')
                    ->color(fn($state, $record) =>
                        $record->date_resolution_effective === null &&
                        $state < now() ? 'danger' : 'gray'
                    ),

                Tables\Columns\TextColumn::make('date_resolution_effective')
                    ->label('Résolue le')
                    ->date('d/m/Y')
                    ->placeholder('En cours'),

                Tables\Columns\IconColumn::make('validation_superviseur')
                    ->label('Superviseur')
                    ->boolean(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Ouvrir réclamation P8')
                    ->visible(fn() => auth()->user()?->hasAnyRole(['back_office', 'responsable_plateau'])),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }
}
