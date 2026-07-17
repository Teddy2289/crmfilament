<?php

namespace App\Filament\NsConseil\Resources\PartenaireResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
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
            Forms\Components\TextInput::make('nom_syndicat')
                ->label('Syndicat')
                ->placeholder('CGT, CFDT…'),
            Forms\Components\TextInput::make('telephone_direct')->label('Tél. direct')->tel(),
            Forms\Components\TextInput::make('telephone_perso')->label('Tél. perso')->tel(),
            Forms\Components\TextInput::make('email')->label('Email pro')->email(),
            Forms\Components\TextInput::make('email_perso')->label('Email perso')->email(),
            Forms\Components\Textarea::make('notes')->label('Notes')->rows(2)->columnSpanFull(),
        ])->columns(2);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Identité')
                ->schema([
                    Infolists\Components\Grid::make(3)->schema([
                        Infolists\Components\TextEntry::make('nom_complet')
                            ->label('Nom complet'),
                        Infolists\Components\TextEntry::make('fonction')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('nom_syndicat')
                            ->label('Syndicat')
                            ->placeholder('—')
                            ->badge()
                            ->color('warning'),
                        Infolists\Components\TextEntry::make('service')
                            ->placeholder('—'),
                    ]),
                ]),

            Infolists\Components\Section::make('Coordonnées')
                ->schema([
                    Infolists\Components\Grid::make(2)->schema([
                        Infolists\Components\TextEntry::make('email')
                            ->label('Email pro')
                            ->placeholder('—')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('email_perso')
                            ->label('Email perso')
                            ->placeholder('—')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('telephone_direct')
                            ->label('Tél. direct')
                            ->placeholder('—')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('telephone_mobile')
                            ->label('Tél. mobile')
                            ->placeholder('—')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('telephone_perso')
                            ->label('Tél. perso')
                            ->placeholder('—')
                            ->copyable(),
                    ]),
                ]),

            Infolists\Components\Section::make('Qualification')
                ->schema([
                    Infolists\Components\Grid::make(3)->schema([
                        Infolists\Components\IconEntry::make('est_principal')
                            ->label('Contact principal')
                            ->boolean(),
                        Infolists\Components\IconEntry::make('est_decisionnaire')
                            ->label('Décisionnaire')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('niveau_influence_label')
                            ->label('Niveau d\'influence')
                            ->badge()
                            ->color(fn ($record) => $record->niveau_influence_color),
                    ]),
                ])
                ->collapsible(),

            Infolists\Components\Section::make('Notes')
                ->schema([
                    Infolists\Components\TextEntry::make('notes')
                        ->hiddenLabel()
                        ->placeholder('Aucune note')
                        ->columnSpanFull(),
                ])
                ->collapsible(),
        ]);
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

                Tables\Columns\TextColumn::make('nom_syndicat')
                    ->label('Syndicat')
                    ->placeholder('—')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('telephone_direct')
                    ->label('Tél.')
                    ->placeholder('—')
                    ->copyable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->placeholder('—')
                    ->copyable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Ajouter un contact'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
