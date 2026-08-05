<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateWeeklyRecapsSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'cle' => 'recap.weekly_commercial',
                'nom' => 'Récapitulatif hebdomadaire - Commercial',
                'sujet' => 'Récapitulatif hebdomadaire - Commercial',
                'view_path' => resource_path('views/emails/weekly-commercial-recap.blade.php'),
                'description' => 'Modèle généré depuis la vue emails.weekly-commercial-recap',
            ],
            [
                'cle' => 'recap.weekly_teleprospecteur',
                'nom' => 'Récapitulatif hebdomadaire - Téléprospecteur',
                'sujet' => 'Récapitulif hebdomadaire - Téléprospecteur',
                'view_path' => resource_path('views/emails/weekly-teleprospecteur-recap.blade.php'),
                'description' => 'Modèle généré depuis la vue emails.weekly-teleprospecteur-recap',
            ],
        ];

        foreach ($items as $item) {
            $corps = '';

            if (file_exists($item['view_path'])) {
                $html = file_get_contents($item['view_path']);

                $search = [
                    "{{ \$user->prenom }}",
                    "{{ \$user->nom }}",
                    "{{ \$startDate->format('d/m/Y') }}",
                    "{{ \$endDate->format('d/m/Y') }}",
                ];

                $replace = [
                    '{{prenom}}',
                    '{{nom}}',
                    '{{start_date}}',
                    '{{end_date}}',
                ];

                // common replacements for stats keys (both templates)
                $search = array_merge($search, [
                    "{{ \$stats['rdv_realises'] }}",
                    "{{ \$stats['prospects_qf'] }}",
                    "{{ \$stats['conversions_partenaire'] }}",
                    "{{ \$stats['partenaires_actifs'] }}",
                    "{{ \$stats['opportunites_en_cours'] }}",
                    "{{ \$stats['appels_realises'] }}",
                    "{{ \$stats['prospects_contactes'] }}",
                    "{{ \$stats['rdv_planifies'] }}",
                    "{{ \$stats['conversions_qf'] }}",
                    "{{ \$stats['conversions_partenaire'] }}",
                ]);

                $replace = array_merge($replace, [
                    '{{rdv_realises}}',
                    '{{prospects_qf}}',
                    '{{conversions_partenaire}}',
                    '{{partenaires_actifs}}',
                    '{{opportunites_en_cours}}',
                    '{{appels_realises}}',
                    '{{prospects_contactes}}',
                    '{{rdv_planifies}}',
                    '{{conversions_qf}}',
                    '{{conversions_partenaire}}',
                ]);

                $corps = str_replace($search, $replace, $html);
            }

            EmailTemplate::updateOrCreate(
                ['cle' => $item['cle']],
                [
                    'nom' => $item['nom'],
                    'sujet' => $item['sujet'],
                    'corps' => $corps ?: $item['sujet'],
                    'description' => $item['description'],
                    'actif' => true,
                ]
            );
        }
    }
}
