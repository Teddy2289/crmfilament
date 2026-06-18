<?php

namespace App\Filament\NsConseil\Resources\PartenaireResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    protected static ?string $title = 'Contacts';

    protected static ?string $icon = 'heroicon-o-user';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nom')->required(),
            Forms\Components\TextInput::make('prenom')->required(),
            Forms\Components\TextInput::make('fonction')
                ->placeholder('Secrétaire CSE, Trésorier, RH…'),
            Forms\Components\TextInput::make('syndicat')
                ->placeholder('CGT, CFDT…'),
            Forms\Components\TextInput::make('tel_direct')->label('Tél. direct')->tel(),
            Forms\Components\TextInput::make('tel_perso')->label('Tél. perso')->tel(),
            Forms\Components\TextInput::make('email_pro')->label('Email pro')->email(),
            Forms\Components\TextInput::make('email_perso')->label('Email perso')->email(),
            Forms\Components\Textarea::make('disponibilites')->label('Disponibilités')->rows(2),
            Forms\Components\Textarea::make('notes')->label('Notes')->rows(2)->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nom')
            ->columns([
                Tables\Columns\TextColumn::make('nom')
                    ->label('Nom complet')
                    ->formatStateUsing(fn ($record) => "{$record->prenom} {$record->nom}")
                    ->searchable(['nom', 'prenom']),

                Tables\Columns\TextColumn::make('fonction')
                    ->label('Fonction'),

                // ✅ TextColumn avec badge() au lieu de BadgeColumn
                Tables\Columns\TextColumn::make('syndicat')
                    ->label('Syndicat')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('tel_direct')
                    ->label('Tél.')
                    ->copyable(),

                Tables\Columns\TextColumn::make('email_pro')
                    ->label('Email')
                    ->copyable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Ajouter un contact'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
