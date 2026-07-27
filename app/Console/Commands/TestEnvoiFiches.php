<?php

namespace App\Console\Commands;

use App\Mail\FicheJauneJ7Mail;
use App\Mail\FicheVerteCommercialMail;
use App\Models\Appel;
use App\Models\Prospect;
use App\Models\TemplateFiche;
use App\Services\Crm\FicheWordService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Vérifie de bout en bout le pipeline "fiche récap" (bleue / jaune / verte) :
 * génération du .docx avec les vraies données du prospect (et non plus le
 * template vierge) puis, en option, envoi réel de l'email avec pièce jointe.
 *
 * Les appels créés ici portent un phoning_status "test_*" qui ne correspond
 * à aucun code métier réel : ils n'affectent donc jamais le statut pipeline
 * du prospect (App\Observers\AppelObserver) ni les jobs planifiés
 * (SendFicheJauneJ7Job / SendFicheVerteCommercialJob), qui filtrent sur les
 * codes exacts cse_ni / bloc2.
 */
class TestEnvoiFiches extends Command
{
    protected $signature = 'crm:test-envoi-fiches
        {prospect : ID du prospect à utiliser comme jeu de données}
        {--to= : Adresse email qui recevra les envois réels (requis avec --send)}
        {--send : Envoie réellement les emails (sinon simple vérification du contenu généré)}
        {--cleanup : Supprime les appels de test précédemment créés pour ce prospect avant de continuer}';

    protected $description = "Teste la génération et l'envoi des fiches bleue/jaune/verte pour un prospect donné";

    private const TYPES = ['bleue', 'jaune', 'verte'];

    public function handle(FicheWordService $service): int
    {
        $prospect = Prospect::find((int) $this->argument('prospect'));

        if (! $prospect) {
            $this->error("Prospect #{$this->argument('prospect')} introuvable.");
            return self::FAILURE;
        }

        $send = (bool) $this->option('send');
        $to = $this->option('to');

        if ($send && ! $to) {
            $this->error('--send nécessite --to=adresse@test.com (aucun email réel n\'est envoyé sans destinataire explicite).');
            return self::FAILURE;
        }

        if ($this->option('cleanup')) {
            $supprimes = Appel::where('appelable_type', Prospect::class)
                ->where('appelable_id', $prospect->id)
                ->where('phoning_status', 'like', 'test_%')
                ->delete();
            $this->line("Nettoyage : {$supprimes} appel(s) de test précédent(s) supprimé(s).");
        }

        $this->info("Prospect utilisé : #{$prospect->id} — {$prospect->nom}");
        $this->line($send ? "Mode : ENVOI RÉEL vers {$to}" : 'Mode : vérification seule (dry-run, aucun email envoyé)');
        $this->newLine();

        $resultats = [];

        foreach (self::TYPES as $type) {
            $resultats[] = $this->testerType($type, $prospect, $service, $send, $to);
        }

        $this->newLine();
        $this->table(['Fiche', 'Template actif', 'Données réelles injectées', 'Email'], $resultats);

        if (! $send) {
            $this->newLine();
            $this->comment('Dry-run terminé. Relancez avec --send --to=votre@email.com pour recevoir les fiches jaune et verte par email.');
            $this->comment("La fiche bleue (invitation commercial) n'est jamais envoyée par cette commande : InvitationAgendaResponsableMail met en copie fixe bruno@ns-conseil.com et nirina@ns-conseil.com (règle R4), donc un test réel enverrait un email à ces deux adresses de production. Vérifiez le .docx généré (chemin affiché ci-dessus) puis testez cet envoi manuellement si besoin.");
        }

        return self::SUCCESS;
    }

    private function testerType(string $type, Prospect $prospect, FicheWordService $service, bool $send, ?string $to): array
    {
        $template = TemplateFiche::actifs()->parType($type)->first();

        if (! $template) {
            $this->warn("[{$type}] Aucun TemplateFiche actif de type '{$type}' — avez-vous lancé php artisan db:seed --class=TemplateFicheSeeder ?");
            return [$type, 'MANQUANT', '-', '-'];
        }

        $data = $this->buildDonneesTest($type, $prospect);

        $localPath = $service->generer($template, $data);
        $xml = $this->extraireDocumentXml($localPath);

        $balisesRestantes = str_contains($xml, '{{');
        $donneesPresentes = str_contains($xml, $prospect->nom ?? '___')
            || str_contains($xml, (string) ($data['interlocuteur_nom'] ?? '___'));

        $ok = ! $balisesRestantes && $donneesPresentes;
        $this->line(($ok ? '  OK ' : '  KO ')."[{$type}] fichier généré : {$localPath}");

        if ($balisesRestantes) {
            $this->error("  -> Des balises {{...}} n'ont pas été remplacées, vérifiez le template '{$template->fichier_path}'.");
        }
        if (! $donneesPresentes) {
            $this->error('  -> Le nom du prospect / interlocuteur est absent du document généré.');
        }

        $emailStatut = '-';

        if ($send) {
            $publicUrl = $service->stocker($localPath, 'test');

            $appel = Appel::create([
                'appelable_type' => Prospect::class,
                'appelable_id' => $prospect->id,
                'type' => \App\Enums\EventType::Appel,
                'date_heure' => now(),
                'resultat' => \App\Enums\EventResult::Realise,
                'phoning_status' => "test_{$type}",
                'phoning_result' => "[TEST ENVOI] Fiche {$type}",
                'phoning_notes' => '[TEST ENVOI] Généré par crm:test-envoi-fiches, sans effet sur le pipeline du prospect.',
                'fiche_type' => $type,
                'fiche_data' => $data,
                'fiche_word_path' => $publicUrl,
                'fiche_word_generated_at' => now(),
            ]);

            $mailable = match ($type) {
                'jaune' => new FicheJauneJ7Mail($appel, $prospect->commercial ?: $prospect->teleprospecteur),
                'verte' => new FicheVerteCommercialMail($appel),
                default => null,
            };

            if ($mailable) {
                Mail::to($to)->send($mailable);
                $emailStatut = "envoyé à {$to} (appel #{$appel->id})";
                $this->line("  -> Email envoyé à {$to} avec la fiche {$type} en pièce jointe.");
            } else {
                $emailStatut = 'non envoyé (voir avertissement ci-dessous)';
            }
        }

        return [$type, $template->nom, $ok ? 'oui' : 'NON', $emailStatut];
    }

    private function buildDonneesTest(string $type, Prospect $prospect): array
    {
        $teleprospecteur = $prospect->teleprospecteur;
        $commercial = $prospect->commercial;

        $base = [
            'raison_sociale' => $prospect->raison_sociale ?: $prospect->nom,
            'secteur_activite' => $prospect->secteur_activite ?: 'Non renseigné',
            'effectif_total' => $prospect->nb_salaries ?: 'Non renseigné',
            'adresse' => $prospect->adresse_complete ?: 'Non renseignée',
            'interlocuteur_nom' => $prospect->interlocuteur_nom ?: 'Jean Test',
            'interlocuteur_fonction' => $prospect->interlocuteur_fonction ?: 'Secrétaire CSE',
            'interlocuteur_telephone' => $prospect->interlocuteur_telephone ?: '0000000000',
            'interlocuteur_email' => $prospect->interlocuteur_email ?: 'test@example.com',
            'teleprospecteur_nom' => $teleprospecteur ? trim("{$teleprospecteur->prenom} {$teleprospecteur->nom}") : 'Téléprospecteur Test',
            'commercial_nom' => $commercial ? trim("{$commercial->prenom} {$commercial->nom}") : 'Commercial Test',
            'date_appel' => now()->format('d/m/Y'),
        ];

        return match ($type) {
            'bleue' => array_merge($base, [
                'date_rdv' => now()->addDays(7)->format('d/m/Y'),
                'heure_rdv' => '14:30',
                'lieu_rdv' => 'Visio',
                'invitation_agenda_envoyee' => 'Oui',
                'enregistrement_appel_joint' => 'Non',
                'enregistrement_raison' => 'Test automatisé',
                'besoins_exprimes' => "[TEST] Besoin de formation CPF pour les élus",
                'objections_soulevees' => '[TEST] Aucune',
                'points_attention_rdv' => "[TEST] Ceci est un envoi de test, aucune action réelle requise",
                'notes_interlocuteur' => '[TEST]',
            ]),
            'jaune' => array_merge($base, [
                'commentaires' => '[TEST] Commentaire de test',
                'date_rappel' => now()->addDays(7)->format('d/m/Y'),
                'heure_rappel' => '10:00',
            ]),
            'verte' => array_merge($base, [
                'presence_cse' => 'À confirmer',
                'jour_dispo_appel' => 'Mardi matin',
                'commentaires' => '[TEST] Commentaire de test',
                'date_rdv_a_prendre' => now()->addDays(2)->format('d/m/Y'),
                'heure_rdv_a_prendre' => '11:00',
            ]),
            default => $base,
        };
    }

    private function extraireDocumentXml(string $docxPath): string
    {
        $zip = new \ZipArchive();
        $zip->open($docxPath);
        $xml = $zip->getFromName('word/document.xml') ?: '';
        $zip->close();

        return $xml;
    }
}
