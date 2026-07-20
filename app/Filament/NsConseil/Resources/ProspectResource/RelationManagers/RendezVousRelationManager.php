<?php

namespace App\Filament\NsConseil\Resources\ProspectResource\RelationManagers;

use App\Enums\RendezVousStatut;
use App\Enums\RendezVousType;
use App\Filament\Shared\Components\PhoneNumberInput;
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
            Forms\Components\TextInput::make('interlocuteur_nom')
                ->label('Interlocuteur'),
            PhoneNumberInput::make('interlocuteur_tel')
                ->label('Tél.'),
            Forms\Components\TextInput::make('interlocuteur_email')
                ->label('Email')
                ->email(),
            Forms\Components\Textarea::make('notes')
                ->label('Notes')
                ->rows(3)
                ->columnSpanFull(),
        ]);
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
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Planifier un RDV'),
            ]);
    }
}
