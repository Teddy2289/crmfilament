<?php

namespace App\Filament\Allopro\Resources\TicketResource\RelationManagers;

use App\Enums\StatutAffaireIntervention;
use App\Filament\Allopro\Resources\AffaireInterventionResource;
use App\Models\AffaireIntervention;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AffairesInterventionsRelationManager extends RelationManager
{
    protected static string $relationship = 'affairesInterventions';

    protected static ?string $title = 'Affaires / Interventions';

    protected static ?string $icon = 'heroicon-o-wrench-screwdriver';

    public function form(Form $form): Form
    {
        return AffaireInterventionResource::form($form);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Réf.')
                    ->weight('semibold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof StatutAffaireIntervention ? $state->label() : $state)
                    ->color(fn ($state) => $state instanceof StatutAffaireIntervention ? $state->color() : 'gray')
                    ->icon(fn ($state) => $state instanceof StatutAffaireIntervention ? $state->icon() : null),

                Tables\Columns\TextColumn::make('artisan.nom')
                    ->label('Artisan')
                    ->formatStateUsing(fn ($state, $record) => $record->artisan?->nom_complet ?? '—')
                    ->description(fn ($record) => $record->artisan?->corps_de_metier?->label()),

                Tables\Columns\TextColumn::make('date_rdv_prevue')
                    ->label('RDV')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('delai_confirmation_minutes')
                    ->label('SLA P4')
                    ->formatStateUsing(fn ($state) => $state ? $state.' min' : '—')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        $state <= 5 => 'success',
                        $state <= 30 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('numero_tentative')
                    ->label('Tentative')
                    ->badge()
                    ->color(fn ($state) => $state > 1 ? 'warning' : 'gray'),
            ])

            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nouveau dispatch')
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['ticket_id'] = $this->getOwnerRecord()->id;
                        $data['operateur_dispatch_id'] = $data['operateur_dispatch_id'] ?? auth()->id();
                        $data['reference'] = AffaireIntervention::genererReference();
                        $data['date_notification_artisan'] = now()->toDateTimeString();
                        $data['numero_tentative'] = AffaireIntervention::where('ticket_id', $this->getOwnerRecord()->id)->count() + 1;

                        return $data;
                    }),
            ])

            ->actions([
                Tables\Actions\Action::make('confirmer')
                    ->label('Confirmer')
                    ->icon('heroicon-o-check-circle')
                    ->color('primary')
                    ->visible(fn (AffaireIntervention $r) => $r->statut === StatutAffaireIntervention::EnAttente)
                    ->requiresConfirmation()
                    ->action(function (AffaireIntervention $record) {
                        $record->confirmerParArtisan();
                        Notification::make()->title('Artisan confirmé')->success()->send();
                    }),

                Tables\Actions\Action::make('finaliser')
                    ->label('Finaliser')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('teal')
                    ->visible(fn (AffaireIntervention $r) => $r->statut === StatutAffaireIntervention::EnCours)
                    ->form([
                        Forms\Components\Textarea::make('compte_rendu_artisan')
                            ->label('Compte-rendu')
                            ->rows(3)
                            ->required(),
                    ])
                    ->action(function (AffaireIntervention $record, array $data) {
                        $record->finaliserParArtisan($data['compte_rendu_artisan']);
                        Notification::make()->title('Intervention finalisée')->success()->send();
                    }),

                Tables\Actions\ViewAction::make()
                    ->url(fn (AffaireIntervention $record) => AffaireInterventionResource::getUrl('view', ['record' => $record])
                    ),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()?->hasRole('responsable_plateau')),
            ]);
    }
}
