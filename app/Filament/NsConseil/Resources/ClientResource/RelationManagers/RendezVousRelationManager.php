<?php

namespace App\Filament\NsConseil\Resources\ClientResource\RelationManagers;

use App\Enums\RendezVousType;
use App\Enums\RendezVousStatut;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RendezVousRelationManager extends RelationManager
{
    protected static string $relationship = 'rendezVous';
    protected static ?string $title = 'Rendez-vous';
    protected static ?string $icon = 'heroicon-o-calendar';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('type')
                ->label('Type')
                ->options(RendezVousType::class)
                ->required(),

            Forms\Components\Select::make('statut')
                ->label('Statut')
                ->options(RendezVousStatut::class)
                ->default(RendezVousStatut::Planifie)
                ->required(),

            Forms\Components\DateTimePicker::make('date_heure')
                ->label('Date et heure')
                ->required(),

            Forms\Components\Select::make('commercial_id')
                ->label('Commercial')
                ->relationship('commercial', 'nom')
                ->default(auth()->id()),

            Forms\Components\TextInput::make('lieu')
                ->label('Lieu'),

            Forms\Components\Textarea::make('adresse_lieu')
                ->label('Adresse')
                ->rows(2),

            Forms\Components\TextInput::make('interlocuteur_nom')
                ->label('Interlocuteur'),

            Forms\Components\TextInput::make('interlocuteur_tel')
                ->label('Tél.')
                ->tel(),

            Forms\Components\TextInput::make('interlocuteur_email')
                ->label('Email')
                ->email(),

            Forms\Components\Toggle::make('email_confirmation_envoye')
                ->label('Confirmation envoyée'),

            Forms\Components\Toggle::make('email_invitation_envoye')
                ->label('Invitation envoyée'),

            Forms\Components\Textarea::make('notes')
                ->label('Notes')
                ->rows(3)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('date_heure', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('date_heure')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge(),

                Tables\Columns\TextColumn::make('statut')
                    ->label('Statut')
                    ->badge(),

                Tables\Columns\TextColumn::make('interlocuteur_nom')
                    ->label('Interlocuteur'),

                Tables\Columns\IconColumn::make('email_confirmation_envoye')
                    ->label('Conf.')
                    ->boolean(),

                Tables\Columns\IconColumn::make('email_invitation_envoye')
                    ->label('Inv.')
                    ->boolean(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Planifier un RDV'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
