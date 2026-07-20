<?php

namespace App\Filament\Allopro\Resources\ArtisanResource\Pages;

use App\Filament\Allopro\Resources\ArtisanResource;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewArtisan extends ViewRecord
{
    protected static string $resource = ArtisanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('activer')
                ->label('Activer')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn() => $this->record->estEnAttente())
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->activer();
                    $this->refreshFormData(['statut_compte', 'date_activation']);
                    Notification::make()->title('Artisan activé')->success()->send();
                }),

            Actions\Action::make('suspendre')
                ->label('Suspendre')
                ->icon('heroicon-o-pause-circle')
                ->color('danger')
                ->visible(fn() => $this->record->estActif())
                ->form([
                    Textarea::make('motif')
                        ->label('Motif')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->record->suspendre($data['motif']);
                    $this->refreshFormData(['statut_compte']);
                    Notification::make()->title('Artisan suspendu')->warning()->send();
                }),

            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([

            Section::make('Identité')
                ->icon('heroicon-o-user')
                ->columns(3)
                ->schema([
                    TextEntry::make('nom_complet')
                        ->label('Nom complet')
                        ->weight('bold')
                        ->size('lg'),

                    TextEntry::make('raison_sociale')
                        ->label('Raison sociale')
                        ->placeholder('—'),

                    TextEntry::make('siret')
                        ->label('SIRET')
                        ->copyable()
                        ->placeholder('—'),
                ]),

            Section::make('Activité & Contact')
                ->icon('heroicon-o-briefcase')
                ->columns(3)
                ->schema([
                    TextEntry::make('corps_de_metier')
                        ->label('Métier')
                        ->formatStateUsing(fn($state) => $state->label())
                        ->badge()
                        ->color(fn($state) => $state->color()),

                    TextEntry::make('telephone_principal')
                        ->label('Téléphone principal')
                        ->badge()
                        ->color('green')
                        ->icon('heroicon-o-phone')
                        ->copyable(),

                    TextEntry::make('email')
                        ->label('Email')
                        ->icon('heroicon-o-envelope')
                        ->copyable(),

                    TextEntry::make('canal_alerte')
                        ->label('Canal d\'alerte')
                        ->formatStateUsing(fn($state) => $state->label())
                        ->badge(),

                    TextEntry::make('zone_intervention')
                        ->label('Zone d\'intervention')
                        ->columnSpan(2),
                ]),

            Section::make('Statut & Performance')
                ->icon('heroicon-o-chart-bar')
                ->columns(4)
                ->schema([
                    TextEntry::make('statut_compte')
                        ->label('Statut')
                        ->formatStateUsing(fn($state) => $state->label())
                        ->badge()
                        ->color(fn($state) => $state->color()),

                    IconEntry::make('agenda_disponibilites')
                        ->label('Agenda configuré')
                        ->boolean(),

                    TextEntry::make('nb_interventions')
                        ->label('Interventions')
                        ->numeric(),

                    TextEntry::make('note_moyenne')
                        ->label('Note moyenne')
                        ->formatStateUsing(fn($state) => $state ? number_format($state, 1) . ' / 10' : '—')
                        ->badge()
                        ->color(fn($state) => match (true) {
                            $state >= 8 => 'success',
                            $state >= 6 => 'warning',
                            $state !== null => 'danger',
                            default => 'gray',
                        }),

                    TextEntry::make('date_souscription')
                        ->label('Date souscription')
                        ->date('d/m/Y'),

                    TextEntry::make('date_activation')
                        ->label('Date activation')
                        ->date('d/m/Y')
                        ->placeholder('Non activé'),

                    TextEntry::make('priorite_segment')
                        ->label('Priorité segment')
                        ->badge()
                        ->color(fn($state) => $state === 'Haute' ? 'danger' : 'gray'),
                ]),

            Section::make('Notes internes')
                ->icon('heroicon-o-document-text')
                ->collapsible()
                ->schema([
                    TextEntry::make('notes')
                        ->label('')
                        ->prose()
                        ->placeholder('Aucune note'),
                ]),
        ]);
    }
}
