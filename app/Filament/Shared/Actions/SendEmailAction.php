<?php

namespace App\Filament\Shared\Actions;

use App\Models\EmailTemplate;
use App\Models\SentEmail;
use Filament\Forms;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class SendEmailAction
{
    /**
     * Action générique d'envoi d'email utilisable sur n'importe quelle resource.
     *
     * @param  callable|string|null  $emailResolver  Callable qui reçoit $record et retourne l'email de destination
     */
    public static function make(callable|string|null $emailResolver = null): Action
    {
        return Action::make('envoyer_email')
            ->label('Envoyer un e-mail')
            ->icon('heroicon-o-envelope')
            ->color('gray')
            ->modalHeading('Envoyer un e-mail')
            ->modalWidth('2xl')
            ->form(function ($record) use ($emailResolver): array {
                $emailDefault = is_callable($emailResolver)
                    ? $emailResolver($record)
                    : ($record->email ?? '');

                return [
                    Forms\Components\Select::make('template_cle')
                        ->label('Modèle')
                        ->options(
                            EmailTemplate::where('actif', true)
                                ->orderBy('nom')
                                ->pluck('nom', 'cle')
                        )
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set) {
                            if (!$state) {
                                $set('sujet_apercu', '');
                                $set('corps_apercu', '');
                                return;
                            }
                            $template = EmailTemplate::findByCle($state);
                            if ($template) {
                                $set('sujet_apercu', $template->sujet);
                                $set('corps_apercu', $template->corps);
                            }
                        })
                        ->required(),

                    Forms\Components\TextInput::make('destinataire')
                        ->label('Destinataire (email)')
                        ->email()
                        ->default($emailDefault)
                        ->required(),

                    Forms\Components\TextInput::make('cc')
                        ->label('Cc (optionnel, séparés par des virgules)')
                        ->placeholder('email1@example.com, email2@example.com')
                        ->nullable(),

                Forms\Components\Section::make('Aperçu du modèle')
                        ->collapsible()
                        ->collapsed(false)
                        ->schema([
                            Forms\Components\TextInput::make('sujet_apercu')
                                ->label('Sujet')
                                ->disabled()
                                ->dehydrated(false)
                                ->placeholder('Sélectionnez un modèle pour voir le sujet'),

                            Forms\Components\Textarea::make('corps_apercu')
                                ->label('Corps (avec variables non résolues)')
                                ->disabled()
                                ->dehydrated(false)
                                ->rows(8)
                                ->placeholder('Sélectionnez un modèle pour voir le contenu'),
                        ]),
                ];
            })
            ->action(function (array $data, $record): void {
                $template = EmailTemplate::findByCle($data['template_cle']);

                if (!$template) {
                    Notification::make()
                        ->title('Modèle introuvable : ' . $data['template_cle'])
                        ->danger()
                        ->send();
                    return;
                }

                // Construire les variables depuis le record
                $variables = static::buildVariablesFromRecord($record);

                $sujet = $template->renderSujet($variables);
                $corps = $template->renderCorps($variables);
                $destinataire = $data['destinataire'];
                $cc = !empty($data['cc']) ? array_map('trim', explode(',', $data['cc'])) : [];

                // Envoi via Laravel Mail::raw
                Mail::send([], [], function (Message $message) use ($sujet, $corps, $destinataire, $cc) {
                    $message->to($destinataire)
                        ->subject($sujet)
                        ->setBody(nl2br(htmlspecialchars($corps)), 'text/html')
                        ->addPart($corps, 'text/plain');

                    foreach ($cc as $ccAddress) {
                        if (filter_var($ccAddress, FILTER_VALIDATE_EMAIL)) {
                            $message->cc($ccAddress);
                        }
                    }
                });

                // Log de l'envoi
                SentEmail::create([
                    'emailable_type' => get_class($record),
                    'emailable_id'   => $record->id,
                    'template_cle'   => $data['template_cle'],
                    'sujet'          => $sujet,
                    'destinataire'   => $destinataire,
                    'cc'             => !empty($cc) ? implode(', ', $cc) : null,
                    'corps'          => $corps,
                    'envoye_par'     => auth()->id(),
                    'envoye_at'      => now(),
                ]);

                Notification::make()
                    ->title('E-mail envoyé à ' . $destinataire)
                    ->success()
                    ->send();
            });
    }

    /**
     * Extrait les variables connues depuis n'importe quel record.
     */
    private static function buildVariablesFromRecord(mixed $record): array
    {
        $vars = [];

        // Variables communes
        $vars['raison_sociale']     = $record->raison_sociale ?? $record->nom ?? '';
        $vars['email']              = $record->email ?? '';
        $vars['telephone']          = $record->telephone ?? '';
        $vars['teleprospecteur_prenom'] = auth()->user()?->prenom ?? '';

        // Artisan
        if (isset($record->nom_complet)) {
            $vars['artisan_prenom_nom'] = $record->nom_complet;
        }
        if (isset($record->metier_label)) {
            $vars['metier'] = $record->metier_label;
        }
        if (isset($record->date_activation)) {
            $vars['date_activation'] = $record->date_activation?->format('d/m/Y') ?? '';
        }

        // Contact / Interlocuteur
        $vars['contact_prenom_nom']  = $record->interlocuteur_nom ?? $record->nom_complet ?? $record->nom ?? '';
        $vars['cse_prenom']          = $record->interlocuteur_nom ?? '';
        $vars['cse_prenom_nom']      = $record->interlocuteur_nom ?? '';
        $vars['cse_fonction']        = $record->interlocuteur_fonction ?? '';
        $vars['cse_email']           = $record->interlocuteur_email ?? '';
        $vars['cse_telephone_direct'] = $record->interlocuteur_telephone ?? '';

        // Ticket
        if (isset($record->reference)) {
            $vars['ticket_reference'] = $record->reference ?? '#' . $record->id;
        }
        if (isset($record->objet) || isset($record->titre)) {
            $vars['ticket_objet'] = $record->objet ?? $record->titre ?? '';
        }
        if (isset($record->priorite_label)) {
            $vars['ticket_priorite'] = $record->priorite_label ?? '';
        }
        $vars['operateur_nom'] = $record->operateur?->nom_complet
            ?? ($record->operateur ? "{$record->operateur->prenom} {$record->operateur->nom}" : '');

        // Conseiller
        $vars['conseiller_nom'] = $record->conseiller?->nom_complet
            ?? ($record->conseiller ? "{$record->conseiller->prenom} {$record->conseiller->nom}" : '');

        // Partenaire
        $vars['type_partenaire'] = $record->type?->label() ?? $record->type ?? '';

        return $vars;
    }
}
