<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class Ncse50CommercialEmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        EmailTemplate::updateOrCreate(
            ['cle' => 'interne.ncse_50_commercial'],
            [
                'nom' => 'Notification commercial - NCSE-50',
                'sujet' => '[NCSE-50] Prise en charge commerciale - {{entreprise_nom}}',
                'description' => 'Notification interne au commercial assigne pour une entreprise de moins de 50 salaries sans CSE. Des utilisateurs peuvent etre ajoutes en copie.',
                'corps' => "Bonjour {{commercial_prenom_nom}},\n\nLe prospect suivant a ete qualifie NCSE-50 (absence de CSE, moins de 50 salaries) et requiert votre prise en charge :\n\nEntreprise : {{entreprise_nom}}\nEffectif : {{nb_salaries}} salarie(s)\nContact identifie : {{contact_prenom}} {{contact_nom}}\nFonction : {{contact_fonction}}\nEmail : {{contact_email}}\nTelephone : {{contact_telephone}}\n\nMerci de prendre contact avec l'interlocuteur identifie afin de poursuivre la demarche commerciale.\n\nCordialement,\n{{teleprospecteur_nom}} - AOPIA LIKE Formation",
                'actif' => true,
            ],
        );
    }
}
