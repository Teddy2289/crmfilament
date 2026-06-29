<?php

namespace Database\Seeders;

use App\Models\CustomField;
use App\Models\CustomFieldValue;
use App\Models\Partenaire;
use App\Models\Prospect;
use App\Models\Client;
use App\Models\EntiteCommerciale;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DemoCustomFieldValuesSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer les champs personnalisés créés par TestCustomFieldsSeeder
        $entiteFields = CustomField::where('target_model', 'App\Models\EntiteCommerciale')->active()->get();
        $partenaireFields = CustomField::where('target_model', 'App\Models\Partenaire')->active()->get();
        $prospectFields = CustomField::where('target_model', 'App\Models\Prospect')->active()->get();
        $clientFields = CustomField::where('target_model', 'App\Models\Client')->active()->get();

        // Créer ou récupérer une entité commerciale de démonstration
        $entite = EntiteCommerciale::firstOrCreate(
            ['code' => 'DEMO001'],
            [
                'nom' => 'Entreprise Demo SARL',
            ]
        );

        // Remplir les champs personnalisés pour l'entité commerciale
        foreach ($entiteFields as $field) {
            $value = match ($field->slug) {
                'budget_annuel' => '150000',
                'date_creation' => '2020-01-15',
                'type_structure' => 'SARL',
                default => null,
            };

            if ($value !== null) {
                CustomFieldValue::updateOrCreate(
                    [
                        'custom_field_id' => $field->id,
                        'model_type' => EntiteCommerciale::class,
                        'model_id' => $entite->id,
                    ],
                    ['value' => $value]
                );
            }
        }

        // Créer ou récupérer un partenaire de démonstration
        $partenaire = Partenaire::firstOrCreate(
            ['nom' => 'Partenaire Demo Informatique'],
            [
                'type' => 'Entreprise directe',
                'statut' => 'en_cours_prospection',
                'email' => 'contact@partenaire-demo.com',
                'telephone' => '0123456789',
            ]
        );

        // Remplir les champs personnalisés pour le partenaire
        foreach ($partenaireFields as $field) {
            $value = match ($field->slug) {
                'secteur_specifique' => 'Informatique - Solutions Cloud',
                default => null,
            };

            if ($value !== null) {
                CustomFieldValue::updateOrCreate(
                    [
                        'custom_field_id' => $field->id,
                        'model_type' => Partenaire::class,
                        'model_id' => $partenaire->id,
                    ],
                    ['value' => $value]
                );
            }
        }

        // Créer ou récupérer un prospect de démonstration
        $prospect = Prospect::firstOrCreate(
            ['nom' => 'Prospect Demo CSE'],
            [
                'type_pressenti' => 'CSE',
                'statut' => 'AC',
                'email' => 'contact@prospect-demo.com',
                'telephone' => '0234567890',
            ]
        );

        // Remplir les champs personnalisés pour le prospect
        foreach ($prospectFields as $field) {
            $value = match ($field->slug) {
                'source_prospect' => 'LinkedIn',
                default => null,
            };

            if ($value !== null) {
                CustomFieldValue::updateOrCreate(
                    [
                        'custom_field_id' => $field->id,
                        'model_type' => Prospect::class,
                        'model_id' => $prospect->id,
                    ],
                    ['value' => $value]
                );
            }
        }

        // Créer ou récupérer un client de démonstration
        $client = Client::firstOrCreate(
            ['nom_tiers' => 'Client Demo Formation'],
            [
                'prenom' => 'Jean',
                'email' => 'jean@client-demo.com',
                'telephone' => '0345678901',
                'etat' => 'en_cours',
            ]
        );

        // Remplir les champs personnalisés pour le client
        foreach ($clientFields as $field) {
            $value = match ($field->slug) {
                'preferences_contact' => '1',
                default => null,
            };

            if ($value !== null) {
                CustomFieldValue::updateOrCreate(
                    [
                        'custom_field_id' => $field->id,
                        'model_type' => Client::class,
                        'model_id' => $client->id,
                    ],
                    ['value' => $value]
                );
            }
        }

        $this->command->info('Données de démonstration créées avec succès.');
        $this->command->info('Entité commerciale: ' . $entite->nom);
        $this->command->info('Partenaire: ' . $partenaire->nom);
        $this->command->info('Prospect: ' . $prospect->nom);
        $this->command->info('Client: ' . $client->nom_tiers . ' ' . $client->prenom);
        $this->command->info('');
        $this->command->info('⚠️  Ce seeder est destiné à la démonstration uniquement.');
        $this->command->info('   Supprimez-le avant de passer en production.');
    }
}
