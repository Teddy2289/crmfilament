<?php

namespace App\Filament\Shared\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Form;

class SentEmailsRelationManager extends RelationManager
{
    protected static string $relationship = 'sentEmails';
    protected static ?string $title = 'Historique emails';
    protected static ?string $icon = 'heroicon-o-envelope';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sujet')
            ->columns([
                Tables\Columns\TextColumn::make('envoye_at')
                    ->label('Envoyé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('sujet')
                    ->label('Sujet')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->sujet)
                    ->searchable(),

                Tables\Columns\TextColumn::make('destinataire')
                    ->label('Destinataire')
                    ->limit(40)
                    ->copyable(),

                Tables\Columns\TextColumn::make('cc')
                    ->label('Cc')
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('template_cle')
                    ->label('Template')
                    ->badge()
                    ->color('gray')
                    ->fontFamily('mono')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('envoyePar.nom')
                    ->label('Envoyé par')
                    ->formatStateUsing(fn ($record) => $record->envoyePar
                        ? "{$record->envoyePar->prenom} {$record->envoyePar->nom}"
                        : '—')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->defaultSort('envoye_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('voir_corps')
                    ->label('Voir contenu')
                    ->icon('heroicon-o-eye')
                    ->modalContent(fn ($record) => view('filament.shared.email-corps-modal', ['email' => $record]))
                    ->modalHeading(fn ($record) => $record->sujet)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fermer'),
            ])
            ->headerActions([])
            ->bulkActions([])
            ->emptyStateHeading('Aucun email envoyé')
            ->emptyStateIcon('heroicon-o-envelope');
    }
}
