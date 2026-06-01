<?php
namespace App\Filament\Allopro\Resources\TicketResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RapportsSatisfactionRelationManager extends RelationManager
{
    protected static string $relationship = 'rapportsSatisfaction';
    protected static ?string $title = 'Rapport satisfaction P6';
    protected static ?string $icon  = 'heroicon-o-star';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('date_appel_j1')
                ->label('Date appel J+1')
                ->required()
                ->native(false)
                ->default(now()->addDay()),

            Forms\Components\Select::make('note_nps')
                ->label('Note NPS (1-10)')
                ->options(array_combine(range(1, 10), range(1, 10)))
                ->required()
                ->native(false)
                ->live()
                ->helperText(fn($state) => match(true) {
                    $state >= 9  => '😊 Promoteur',
                    $state >= 7  => '😐 Passif',
                    $state !== null => '😞 Détracteur — ouvrira une réclamation P8',
                    default => '',
                }),

            Forms\Components\Select::make('statut_cloture')
                ->label('Statut de clôture')
                ->options([
                    'satisfait'            => 'Satisfait',
                    'suivi_qualite_requis'  => 'Suivi qualité requis',
                    'reclamation_ouverte'   => 'Réclamation ouverte',
                ])
                ->required()
                ->native(false),

            Forms\Components\Toggle::make('feedback_artisan')
                ->label('Feedback transmis à l\'artisan')
                ->default(false),

            Forms\Components\Textarea::make('verbatim_client')
                ->label('Verbatim client')
                ->rows(3),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('date_appel_j1', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('note_nps')
                    ->label('NPS')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state . ' / 10')
                    ->color(fn($state) => match(true) {
                        $state >= 9 => 'success',
                        $state >= 7 => 'warning',
                        default     => 'danger',
                    }),

                Tables\Columns\TextColumn::make('statut_cloture')
                    ->label('Statut')
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'satisfait'            => 'success',
                        'suivi_qualite_requis' => 'warning',
                        'reclamation_ouverte'  => 'danger',
                        default                => 'gray',
                    }),

                Tables\Columns\IconColumn::make('feedback_artisan')
                    ->label('Feedback transmis')
                    ->boolean(),

                Tables\Columns\TextColumn::make('verbatim_client')
                    ->label('Verbatim')
                    ->limit(60)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('date_appel_j1')
                    ->label('Date J+1')
                    ->date('d/m/Y'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Ajouter rapport P6')
                    ->visible(fn() =>
                        $this->getOwnerRecord()->statut === \App\Enums\TicketStatut::InterventionRealisee
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }
}
