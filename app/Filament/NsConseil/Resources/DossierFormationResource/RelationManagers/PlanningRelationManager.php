<?php

namespace App\Filament\NsConseil\Resources\DossierFormationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Carbon\Carbon;

class PlanningRelationManager extends RelationManager
{
    protected static string $relationship = 'planning';
    protected static ?string $recordTitleAttribute = 'dossier_id';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Planning de la formation')
                ->schema([
                    Forms\Components\DatePicker::make('date_lancement')
                        ->label('Date de lancement')
                        ->displayFormat('d/m/Y')
                        ->required(),

                    Forms\Components\DatePicker::make('date_debut')
                        ->label('Date de début')
                        ->displayFormat('d/m/Y')
                        ->required()
                        ->afterOrEqual('date_lancement'),

                    Forms\Components\DatePicker::make('date_fin_theorique')
                        ->label('Date de fin théorique')
                        ->displayFormat('d/m/Y')
                        ->required()
                        ->afterOrEqual('date_debut'),

                    Forms\Components\DatePicker::make('date_certification')
                        ->label('Date de certification')
                        ->displayFormat('d/m/Y')
                        ->nullable()
                        ->afterOrEqual('date_fin_theorique'),

                    Forms\Components\DatePicker::make('date_questionnaire_chaud')
                        ->label('Date questionnaire chaud')
                        ->displayFormat('d/m/Y')
                        ->nullable()
                        ->afterOrEqual('date_debut'),
                ])->columns(2),

            Forms\Components\Section::make('Informations')
                ->schema([
                    Forms\Components\Placeholder::make('duree')
                        ->label('Durée estimée')
                        ->content(function ($record, $get) {
                            $debut = $get('date_debut');
                            $fin = $get('date_fin_theorique');
                            if ($debut && $fin) {
                                $diff = Carbon::parse($debut)->diffInDays(Carbon::parse($fin));
                                return "{$diff} jours";
                            }
                            return 'Non calculée';
                        }),

                    Forms\Components\Placeholder::make('statut_planning')
                        ->label('Statut')
                        ->content(function ($get) {
                            $debut = $get('date_debut');
                            $fin = $get('date_fin_theorique');
                            if ($debut && $fin) {
                                $now = Carbon::now();
                                if ($now->lt(Carbon::parse($debut))) return '🔵 À venir';
                                if ($now->between(Carbon::parse($debut), Carbon::parse($fin))) return '🟡 En cours';
                                return '✅ Terminé';
                            }
                            return 'Non défini';
                        }),
                ])->columns(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date_lancement')
                    ->label('Lancement')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('date_debut')
                    ->label('Début')
                    ->date('d/m/Y')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('date_fin_theorique')
                    ->label('Fin théorique')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('date_certification')
                    ->label('Certification')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('date_questionnaire_chaud')
                    ->label('Questionnaire chaud')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                Tables\Columns\TextColumn::make('duree')
                    ->label('Durée (jours)')
                    ->getStateUsing(function ($record) {
                        if ($record->date_debut && $record->date_fin_theorique) {
                            return Carbon::parse($record->date_debut)
                                ->diffInDays(Carbon::parse($record->date_fin_theorique));
                        }
                        return null;
                    })
                    ->numeric(0)
                    ->sortable(),

                Tables\Columns\IconColumn::make('est_encours')
                    ->label('En cours')
                    ->getStateUsing(function ($record) {
                        $now = Carbon::now();
                        if ($record->date_debut && $record->date_fin_theorique) {
                            return $now->between(Carbon::parse($record->date_debut), Carbon::parse($record->date_fin_theorique));
                        }
                        return false;
                    })
                    ->boolean()
                    ->trueColor('warning')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->filters([
                Tables\Filters\Filter::make('en_cours')
                    ->label('En cours')
                    ->query(function ($query) {
                        $now = Carbon::now();
                        return $query->where('date_debut', '<=', $now)
                            ->where('date_fin_theorique', '>=', $now);
                    }),

                Tables\Filters\Filter::make('a_venir')
                    ->label('À venir')
                    ->query(function ($query) {
                        return $query->where('date_debut', '>', Carbon::now());
                    }),

                Tables\Filters\Filter::make('termine')
                    ->label('Terminé')
                    ->query(function ($query) {
                        return $query->where('date_fin_theorique', '<', Carbon::now());
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Ajouter un planning'),
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
            ->emptyStateHeading('Aucun planning enregistré')
            ->emptyStateDescription('Ajoutez un planning pour suivre les dates clés de cette formation.');
    }
}
