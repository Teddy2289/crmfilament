<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmailTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'nom' => 'Confirmation RDV Prospect',
                'cle' => 'prospect.confirmation_rdv',
                'sujet' => 'Confirmation de votre rendez-vous - {{entreprise_nom}}',
                'corps' => 'Bonjour {{elu_prenom}} {{elu_nom}},

Nous confirmons notre rendez-vous prévu le {{rdv_jour}} {{rdv_date}} à {{rdv_heure}}.

Lieu : {{rdv_lieu}}

Notre commercial {{commercial_nom}} sera ravi de vous rencontrer pour discuter des opportunités de formation pour {{entreprise_nom}}.

Si vous ne pouvez pas honorer ce rendez-vous, merci de nous en informer au plus vite.

Cordialement,
{{teleprospecteur_nom}}
AOPIA Formation - NS Conseil',
                'description' => 'Email de confirmation de rendez-vous avec un prospect après acceptation (Cas 2 - RDV accepté)',
                'actif' => true,
            ],
            [
                'nom' => 'Prise de contact BLOC',
                'cle' => 'prospect.prise_contact_bloc',
                'sujet' => 'Prise de contact - {{entreprise_nom}}',
                'corps' => 'Bonjour {{elu_prenom}} {{elu_nom}},

Suite à notre appel téléphonique, je me permets de vous contacter par email pour convenir d\'un échange concernant les offres de formation d\'AOPIA Formation.

Nous souhaiterions vous présenter nos solutions adaptées aux besoins de {{entreprise_nom}}.

{{disponibilites ? "Vous avez indiqué les disponibilités suivantes : " . disponibilites : "Merci de nous indiquer vos disponibilités pour un échange téléphonique."}}

Je reste à votre disposition pour organiser ce rendez-vous.

Cordialement,
{{teleprospecteur_nom}}
AOPIA Formation - NS Conseil',
                'description' => 'Email de prise de contact quand le standard refuse de transférer (Cas 3 - BLOC)',
                'actif' => true,
            ],
            [
                'nom' => 'Contact sans CSE',
                'cle' => 'prospect.contact_sans_cse',
                'sujet' => 'Formation professionnelle - {{entreprise_nom}}',
                'corps' => 'Bonjour {{contact_prenom}} {{contact_nom}},

Suite à notre échange téléphonique, je vous contacte concernant les formations professionnelles proposées par AOPIA Formation.

Nous avons appris que {{entreprise_nom}} ({{nb_salaries}} salariés) ne dispose pas de CSE. Je m\'adresse donc à vous en tant que {{contact_fonction}} pour vous présenter nos solutions de formation.

Nos offres sont adaptées aux besoins des entreprises et de leurs salariés. Je serais ravi de vous en dire plus lors d\'un échange.

Merci de me faire part de vos disponibilités.

Cordialement,
{{teleprospecteur_nom}}
AOPIA Formation - NS Conseil',
                'description' => 'Email de contact quand il n\'y a pas de CSE dans l\'entreprise (Cas 4 - NCSE-50)',
                'actif' => true,
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(
                ['cle' => $template['cle']],
                $template
            );
        }

        $this->command->info('Templates email créés avec succès.');
        $this->command->info('Templates créés : ' . count($templates));
    }
}
