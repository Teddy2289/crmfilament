<?php

namespace Database\Seeders;

use App\Models\CustomField;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestCustomFieldsSeeder extends Seeder
{
    public function run(): void
    {
        // Créer des champs de test pour EntiteCommerciale
        CustomField::create([
            'name' => 'Budget annuel',
            'slug' => 'budget_annuel',
            'type' => 'number',
            'required' => false,
            'target_model' => 'App\Models\EntiteCommerciale',
            'placeholder' => 'Montant en euros',
            'helper_text' => 'Budget annuel de l\'entité commerciale',
            'order' => 1,
            'active' => true,
        ]);

        CustomField::create([
            'name' => 'Date de création',
            'slug' => 'date_creation',
            'type' => 'date',
            'required' => false,
            'target_model' => 'App\Models\EntiteCommerciale',
            'placeholder' => 'JJ/MM/AAAA',
            'helper_text' => 'Date de création de l\'entité',
            'order' => 2,
            'active' => true,
        ]);

        CustomField::create([
            'name' => 'Type de structure',
            'slug' => 'type_structure',
            'type' => 'select',
            'required' => true,
            'target_model' => 'App\Models\EntiteCommerciale',
            'options' => ['SARL', 'SAS', 'SA', 'Association', 'Auto-entrepreneur'],
            'placeholder' => 'Sélectionnez le type',
            'helper_text' => 'Forme juridique de l\'entité',
            'order' => 3,
            'active' => true,
        ]);

        // Créer des champs de test pour Partenaire
        CustomField::create([
            'name' => 'Secteur d\'activité spécifique',
            'slug' => 'secteur_specifique',
            'type' => 'text',
            'required' => false,
            'target_model' => 'App\Models\Partenaire',
            'placeholder' => 'ex: Informatique, BTP, Santé...',
            'helper_text' => 'Secteur d\'activité principal du partenaire',
            'order' => 1,
            'active' => true,
        ]);

        // Créer des champs de test pour Prospect
        CustomField::create([
            'name' => 'Source du prospect',
            'slug' => 'source_prospect',
            'type' => 'select',
            'required' => false,
            'target_model' => 'App\Models\Prospect',
            'options' => ['LinkedIn', 'Salon professionnel', 'Recommandation', 'Site web', 'Autre'],
            'placeholder' => 'Comment avez-vous découvert ce prospect ?',
            'helper_text' => 'Origine du contact',
            'order' => 1,
            'active' => true,
        ]);

        // Créer des champs de test pour Client
        CustomField::create([
            'name' => 'Préférences de contact',
            'slug' => 'preferences_contact',
            'type' => 'checkbox',
            'required' => false,
            'target_model' => 'App\Models\Client',
            'placeholder' => 'Accepte les communications',
            'helper_text' => 'Le client accepte d\'être contacté par email',
            'order' => 1,
            'active' => true,
        ]);

        $this->command->info('Champs personnalisés de test créés avec succès.');
    }
}
