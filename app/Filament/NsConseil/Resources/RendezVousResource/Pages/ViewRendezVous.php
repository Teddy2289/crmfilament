<?php

// ── app/Filament/NsConseil/Resources/RendezVousResource/Pages/ViewRendezVous.php

namespace App\Filament\NsConseil\Resources\RendezVousResource\Pages;

use App\Filament\NsConseil\Resources\RendezVousResource;
use App\Filament\NsConseil\Resources\ProspectResource;
use App\Filament\NsConseil\Resources\PartenaireResource;
use App\Filament\NsConseil\Resources\ClientResource;
use App\Models\Prospect;
use App\Models\Partenaire;
use App\Models\RendezVousAssociation;
use App\Services\GoogleCalendarService;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\Select as FormSelect;
use Filament\Resources\Pages\ViewRecord;

class ViewRendezVous extends ViewRecord
{
    protected static string $resource = RendezVousResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->check() && auth()->user()?->actif;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return static::canAccess();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('lancer_appel_phoning')
                ->label('Lancer l\'appel')
                ->icon('heroicon-o-phone-arrow-up-right')
                ->color('primary')
                ->url(fn (): ?string => match ($this->record->rdvable_type) {
                    'App\Models\Prospect' => route('filament.ns-conseil.pages.phoning-workflow', [
                        'contact_id' => $this->record->rdvable_id,
                        'contact_type' => 'prospect',
                    ]),
                    'App\Models\Partenaire' => route('filament.ns-conseil.pages.phoning-workflow', [
                        'contact_id' => $this->record->rdvable_id,
                        'contact_type' => 'partenaire',
                    ]),
                    'App\Models\Client' => route('filament.ns-conseil.pages.phoning-workflow', [
                        'contact_id' => $this->record->rdvable_id,
                        'contact_type' => 'client',
                    ]),
                    default => null,
                })
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->record->rdvable_type !== null && $this->record->rdvable_id !== null),

            Actions\Action::make('voir_fiche')
                ->label('Voir la fiche liée')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->url(fn (): ?string => match ($this->record->rdvable_type) {
                    'App\\Models\\Prospect' => ProspectResource::getUrl('view', ['record' => $this->record->rdvable_id], panel: 'ns-conseil'),
                    'App\\Models\\Partenaire' => PartenaireResource::getUrl('view', ['record' => $this->record->rdvable_id], panel: 'ns-conseil'),
                    'App\\Models\\Client' => ClientResource::getUrl('view', ['record' => $this->record->rdvable_id], panel: 'ns-conseil'),
                    default => null,
                })
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->record->rdvable_type !== null && $this->record->rdvable_id !== null),

            Actions\Action::make('associer_contact')
                ->label('Associer au contact')
                ->icon('heroicon-o-link')
                ->color('secondary')
                ->visible(fn (): bool => $this->record->rdvable_type === null || $this->record->rdvable_id === null)
                ->form([
                    FormSelect::make('candidate')
                        ->label('Choisir un contact')
                        ->options(function () {
                            $rdv = $this->record;
                            $options = [];

                            if (! $rdv) {
                                return $options;
                            }

                            $email = $rdv->interlocuteur_email;
                            $tel = $rdv->interlocuteur_tel ? preg_replace('/[^0-9]/', '', $rdv->interlocuteur_tel) : null;
                            $name = $rdv->interlocuteur_nom;

                            // Exact email matches
                            if ($email) {
                                $pros = Prospect::where('interlocuteur_email', $email)->get();
                                foreach ($pros as $p) {
                                    $options["prospect:{$p->id}"] = "Prospect: {$p->prenom} {$p->nom} — {$p->interlocuteur_email}";
                                }

                                $parts = Partenaire::where('email', $email)->get();
                                foreach ($parts as $p) {
                                    $options["partenaire:{$p->id}"] = "Partenaire: {$p->nom} — {$p->email}";
                                }
                            }

                            // Exact phone matches
                            if ($tel) {
                                $pros = Prospect::whereRaw("REGEXP_REPLACE(interlocuteur_telephone, '[^0-9]', '') LIKE ?", ["%{$tel}%"])->get();
                                foreach ($pros as $p) {
                                    $key = "prospect:{$p->id}";
                                    if (! isset($options[$key])) {
                                        $options[$key] = "Prospect: {$p->prenom} {$p->nom} — {$p->interlocuteur_telephone}";
                                    }
                                }

                                $parts = Partenaire::whereRaw("REGEXP_REPLACE(telephone, '[^0-9]', '') LIKE ?", ["%{$tel}%"])->get();
                                foreach ($parts as $p) {
                                    $key = "partenaire:{$p->id}";
                                    if (! isset($options[$key])) {
                                        $options[$key] = "Partenaire: {$p->nom} — {$p->telephone}";
                                    }
                                }
                            }

                            // Fuzzy name matches (if still empty)
                            if (empty($options) && $name) {
                                $like = "%{$name}%";
                                $pros = Prospect::where('nom', 'like', $like)->orWhere('prenom', 'like', $like)->get();
                                foreach ($pros as $p) {
                                    $options["prospect:{$p->id}"] = "Prospect: {$p->prenom} {$p->nom}";
                                }

                                $parts = Partenaire::where('nom', 'like', $like)->get();
                                foreach ($parts as $p) {
                                    $options["partenaire:{$p->id}"] = "Partenaire: {$p->nom}";
                                }
                            }

                            return $options;
                        })
                        ->searchable()
                        ->required(),
                ])
                ->modalHeading('Associer le rendez-vous à un contact')
                ->modalButton('Associer')
                ->action(function (array $data) {
                    $rdv = $this->record;
                    if (! isset($data['candidate']) || ! $data['candidate']) {
                        $this->notify('danger', 'Aucun candidat sélectionné.');
                        return;
                    }

                    [$type, $id] = explode(':', $data['candidate']);
                    $mapping = [
                        'prospect' => Prospect::class,
                        'partenaire' => Partenaire::class,
                        'client' => '\\App\\Models\\Client',
                    ];

                    if (! isset($mapping[$type])) {
                        $this->notify('danger', 'Type de contact invalide.');
                        return;
                    }

                    $rdv->update(['rdvable_type' => $mapping[$type], 'rdvable_id' => $id]);

                    // Log association for audit
                    try {
                        RendezVousAssociation::create([
                            'rendez_vous_id' => $rdv->id,
                            'rdvable_type' => $mapping[$type],
                            'rdvable_id' => $id,
                            'method' => 'user_choice',
                            'user_id' => auth()->id(),
                            'meta' => ['candidate' => $data['candidate'] ?? null],
                        ]);
                    } catch (\Throwable $e) {
                        // swallow to avoid breaking UI if logging fails
                    }

                    $this->notify('success', 'RDV associé au contact choisi.');
                    $this->refresh();
                }),

            Actions\Action::make('sync_google')
                ->label('Sync Google Calendar')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->visible(fn () => ! $this->record->google_event_id)
                ->action(function () {
                    app(GoogleCalendarService::class)->createEvent($this->record);
                    $this->refreshFormData(['google_event_id']);
                }),

            Actions\Action::make('voir_calendrier')
                ->label('Voir dans le calendrier')
                ->icon('heroicon-o-calendar-days')
                ->color('gray')
                ->url('/ns-conseil/calendar'),

            Actions\DeleteAction::make(),
        ];
    }
}

