<?php
namespace App\Filament\Allopro\Resources\TicketResource\RelationManagers;

use App\Enums\AncienneteProbleme;
use App\Enums\CorpsDeMetier;
use App\Enums\NiveauPriorite;
use App\Enums\StatutOccupant;
use App\Enums\TypeLogement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class FicheP2RelationManager extends RelationManager
{
    protected static string $relationship = 'ficheP2';
    protected static ?string $title = 'Fiche P2 — Qualification';
    protected static ?string $icon  = 'heroicon-o-document-check';

    public function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Identification du problème')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('corps_de_metier')
                        ->label('Corps de métier')
                        ->options(
                            collect(CorpsDeMetier::cases())
                                ->mapWithKeys(fn($e) => [$e->value => $e->label()])
                                ->toArray()
                        )
                        ->required()
                        ->native(false)
                        ->searchable()
                        ->live(),

                    Forms\Components\Select::make('anciennete_probleme')
                        ->label('Ancienneté du problème')
                        ->options(
                            collect(AncienneteProbleme::cases())
                                ->mapWithKeys(fn($e) => [$e->value => $e->label()])
                                ->toArray()
                        )
                        ->required()
                        ->native(false),

                    Forms\Components\TextInput::make('nature_probleme')
                        ->label('Nature du problème')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('description_detaillee')
                        ->label('Description détaillée (min. 30 caractères)')
                        ->required()
                        ->minLength(30)
                        ->rows(4)
                        ->live(debounce: 500)
                        ->helperText(fn($state) => strlen($state ?? '') . ' / 30 min')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('localisation_precise')
                        ->label('Localisation précise')
                        ->required()
                        ->helperText('Ex : cuisine 1er étage, cave, WC')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Niveau de priorité')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('niveau_priorite')
                        ->label('Niveau de priorité')
                        ->options(
                            collect(NiveauPriorite::cases())
                                ->mapWithKeys(fn($e) => [$e->value => $e->label()])
                                ->toArray()
                        )
                        ->required()
                        ->native(false)
                        ->live(),

                    Forms\Components\Textarea::make('justificatif_priorite')
                        ->label('Justificatif')
                        ->required()
                        ->rows(2)
                        ->helperText('Critère objectif justifiant le niveau'),
                ]),

            Forms\Components\Section::make('Client & Logement')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('nom_client')
                        ->label('Nom client')
                        ->required(),

                    Forms\Components\TextInput::make('telephone_client')
                        ->label('Téléphone client')
                        ->required()
                        ->tel(),

                    Forms\Components\Textarea::make('adresse_intervention')
                        ->label("Adresse d'intervention")
                        ->required()
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\Select::make('type_logement')
                        ->label('Type de logement')
                        ->options(
                            collect(TypeLogement::cases())
                                ->mapWithKeys(fn($e) => [$e->value => $e->label()])
                                ->toArray()
                        )
                        ->required()
                        ->native(false),

                    Forms\Components\Select::make('statut_occupant')
                        ->label('Statut occupant')
                        ->options(
                            collect(StatutOccupant::cases())
                                ->mapWithKeys(fn($e) => [$e->value => $e->label()])
                                ->toArray()
                        )
                        ->required()
                        ->native(false),

                    Forms\Components\Toggle::make('presence_client')
                        ->label("Client présent lors de l'intervention")
                        ->default(true),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('corps_de_metier')
                    ->label('Métier')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state instanceof CorpsDeMetier ? $state->label() : '—')
                    ->color(fn($state) => $state instanceof CorpsDeMetier ? $state->color() : 'gray'),

                Tables\Columns\TextColumn::make('nature_probleme')
                    ->label('Problème')
                    ->limit(50),

                Tables\Columns\TextColumn::make('niveau_priorite')
                    ->label('Priorité')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state instanceof NiveauPriorite ? $state->label() : '—')
                    ->color(fn($state) => $state instanceof NiveauPriorite ? $state->color() : 'gray'),

                Tables\Columns\IconColumn::make('fiche_complete')
                    ->label('Complète')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('nom_client')
                    ->label('Client'),

                Tables\Columns\TextColumn::make('adresse_intervention')
                    ->label('Adresse')
                    ->limit(40),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Compléter fiche P2')
                    ->visible(fn() => !$this->getOwnerRecord()->ficheP2()->exists()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ]);
    }
}
