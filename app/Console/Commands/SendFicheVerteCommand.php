<?php

namespace App\Console\Commands;

use App\Mail\FicheVerteCommercialMail;
use App\Models\Appel;
use App\Models\Prospect;
use App\Models\TemplateFiche;
use App\Services\Crm\FicheWordService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendFicheVerteCommand extends Command
{
    protected $signature = 'fiche:send-verte {prospect_id} {email}';

    protected $description = 'Envoie une fiche verte pour un prospect à une adresse email';

    public function handle(FicheWordService $ficheService): int
    {
        $prospectId = $this->argument('prospect_id');
        $email = $this->argument('email');

        $prospect = Prospect::find($prospectId);
        if (!$prospect) {
            $this->error("Prospect #{$prospectId} introuvable");
            return 1;
        }

        $this->info("Prospect: {$prospect->nom}");

        // Chercher un appel existant avec fiche verte
        $appel = Appel::where('appelable_type', Prospect::class)
            ->where('appelable_id', $prospectId)
            ->where('fiche_type', 'verte')
            ->first();

        if (!$appel) {
            // Créer un appel test avec fiche verte
            $this->info("Aucun appel avec fiche verte trouvé, création d'un appel test...");
            
            // Charger le commercial assigné
            $commercial = $prospect->commercial;

            $ficheData = [
                // Informations entreprise
                'raison_sociale' => $prospect->raison_sociale ?? $prospect->nom,
                'secteur_activite' => $prospect->secteur_activite ?? '',
                'effectif_total' => $prospect->nb_salaries ?? '',
                'presence_cse' => $this->getPresenceCse($prospect->statut),
                
                // Adresse
                'adresse' => $prospect->adresse ?? '',
                'code_postal' => $prospect->code_postal ?? '',
                'ville' => $prospect->ville ?? '',
                
                // Interlocuteur
                'interlocuteur_nom' => $prospect->interlocuteur_nom ?? '',
                'interlocuteur_prenom' => $prospect->interlocuteur_prenom ?? '',
                'interlocuteur_fonction' => $prospect->interlocuteur_fonction ?? '',
                'interlocuteur_telephone' => $prospect->interlocuteur_telephone ?? '',
                'interlocuteur_email' => $prospect->interlocuteur_email ?? '',
                'jour_dispo_appel' => $prospect->creneaux_permanence_cse ?? '',
                
                // Responsable de secteur
                'commercial_nom' => $commercial ? ($commercial->name ?? '') : '',
                
                // Dates et commentaires (vides par défaut)
                'date_rdv_a_prendre' => '',
                'heure_rdv_a_prendre' => '',
                'commentaires' => $prospect->description ?? '',
                'date_appel' => now()->format('d/m/Y'),
                'teleprospecteur_nom' => auth()->user()?->name ?? '',
            ];
            
            $appel = Appel::create([
                'appelable_type' => Prospect::class,
                'appelable_id' => $prospectId,
                'fiche_type' => 'verte',
                'phoning_status' => 'bloc2',
                'date_heure' => now(),
                'fiche_data' => $ficheData,
            ]);

            $this->info("Appel créé: #{$appel->id}");
        }

        // Générer la fiche si elle n'existe pas
        if (!$appel->fiche_word_path) {
            $this->info("Génération de la fiche verte...");
            
            $template = TemplateFiche::actifs()->parType('verte')->first();
            if (!$template) {
                $this->error("Aucun template 'verte' actif trouvé");
                return 1;
            }

            $localPath = $ficheService->generer($template, $appel->fiche_data);
            $publicUrl = $ficheService->stocker($localPath, now()->format('Y/m'));

            $appel->update([
                'fiche_word_path' => $publicUrl,
                'fiche_word_generated_at' => now(),
            ]);

            $this->info("Fiche générée: {$publicUrl}");
        }

        // Envoyer l'email
        $this->info("Envoi de l'email à {$email}...");
        
        try {
            Mail::to($email)->queue(new FicheVerteCommercialMail($appel));
            $appel->update(['fiche_verte_envoyee_at' => now()]);
            $this->info("✅ Email envoyé avec succès");
            return 0;
        } catch (\Exception $e) {
            $this->error("Erreur lors de l'envoi: " . $e->getMessage());
            return 1;
        }
    }
}
