<?php

namespace App\Filament\NsConseil\Resources\PartenaireResource\RelationManagers;

use App\Filament\Shared\Components\PhoneNumberInput;
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
            PhoneNumberInput::make('telephone_direct')->label('Téléphone professionnel'),
            PhoneNumberInput::make('telephone_perso')->label('Téléphone personnel'),
            Forms\Components\TextInput::make('email')->label('Email professionnel')->email(),
            Forms\Components\TextInput::make('email_perso')->label('Email personnel')->email(),
            Forms\Components\Textarea::make('notes')->label('Notes')->rows(2)->columnSpanFull(),
        ])->columns(2);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Identité')
                ->schema([
                    Infolists\Components\Grid::make(3)->schema([
                        Infolists\Components\TextEntry::make('nom')
                            ->label('Nom'),
                        Infolists\Components\TextEntry::make('prenom')
                            ->label('Prénom'),
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
                            ->label('Email professionnel')
                            ->placeholder('—')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('email_perso')
                            ->label('Email personnel')
                            ->placeholder('—')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('telephone_direct')
                            ->label('Téléphone professionnel')
                            ->badge()
                            ->color('green')
                            ->icon('heroicon-o-phone')
                            ->placeholder('—')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('telephone_mobile')
                            ->label('Téléphone mobile')
                            ->badge()
                            ->color('green')
                            ->icon('heroicon-o-phone')
                            ->placeholder('—')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('telephone_perso')
                            ->label('Téléphone personnel')
                            ->badge()
                            ->color('green')
                            ->icon('heroicon-o-phone')
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
                            ->color(fn($record) => $record->niveau_influence_color),
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
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('prenom')
                    ->label('Prénom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('fonction')
                    ->label('Fonction'),

                Tables\Columns\TextColumn::make('nom_syndicat')
                    ->label('Syndicat')
                    ->placeholder('—')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('telephone_direct')
                    ->label('Téléphone')
                    ->badge()
                    ->color('green')
                    ->icon('heroicon-o-phone')
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
