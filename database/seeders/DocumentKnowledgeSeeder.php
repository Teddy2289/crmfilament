<?php

namespace Database\Seeders;

use App\Models\DocumentKnowledge;
use Illuminate\Database\Seeder;

class DocumentKnowledgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $documents = [
            [
                'titre' => 'Procédure de qualification CSE',
                'description' => 'Guide complet pour qualifier les CSE et déterminer leur éligibilité aux partenariats.',
                'type' => 'procedure',
                'categorie' => 'commercial',
                'est_publique' => true,
                'ordre' => 1,
            ],
            [
                'titre' => 'Script d\'appels téléphoniques',
                'description' => 'Script standardisé pour les appels de prospection auprès des CSE.',
                'type' => 'script',
                'categorie' => 'commercial',
                'est_publique' => true,
                'ordre' => 2,
            ],
            [
                'titre' => 'Checklist conversion Partenaire',
                'description' => 'Liste des étapes à suivre pour convertir un prospect en partenaire.',
                'type' => 'checklist',
                'categorie' => 'commercial',
                'est_publique' => true,
                'ordre' => 3,
            ],
            [
                'titre' => 'Politique de tarification',
                'description' => 'Grille tarifaire et conditions commerciales pour les partenariats.',
                'type' => 'politique',
                'categorie' => 'commercial',
                'est_publique' => false,
                'ordre' => 4,
            ],
            [
                'titre' => 'Guide d\'onboarding Artisan',
                'description' => 'Procédure d\'intégration des nouveaux artisans dans le réseau.',
                'type' => 'procedure',
                'categorie' => 'operationnel',
                'est_publique' => true,
                'ordre' => 5,
            ],
            [
                'titre' => 'Processus de gestion des réclamations',
                'description' => 'Procédure de traitement des réclamations clients et artisans.',
                'type' => 'procedure',
                'categorie' => 'operationnel',
                'est_publique' => true,
                'ordre' => 6,
            ],
            [
                'titre' => 'Template fiche intervention',
                'description' => 'Modèle de fiche d\'intervention pour les artisans.',
                'type' => 'template',
                'categorie' => 'operationnel',
                'est_publique' => true,
                'ordre' => 7,
            ],
            [
                'titre' => 'Politique RH interne',
                'description' => 'Règles et procédures internes de gestion des ressources humaines.',
                'type' => 'politique',
                'categorie' => 'rh',
                'est_publique' => false,
                'ordre' => 8,
            ],
            [
                'titre' => 'Guide d\'utilisation CRM',
                'description' => 'Manuel utilisateur pour le système de gestion de la relation client.',
                'type' => 'guide',
                'categorie' => 'it',
                'est_publique' => true,
                'ordre' => 9,
            ],
            [
                'titre' => 'Procédure de sauvegarde des données',
                'description' => 'Processus de backup et de restauration des données système.',
                'type' => 'procedure',
                'categorie' => 'it',
                'est_publique' => false,
                'ordre' => 10,
            ],
        ];

        foreach ($documents as $document) {
            DocumentKnowledge::firstOrCreate(
                ['titre' => $document['titre']],
                $document
            );
        }

        $this->command->info('✓ 10 documents de base de connaissances créés/mis à jour');
    }
}
