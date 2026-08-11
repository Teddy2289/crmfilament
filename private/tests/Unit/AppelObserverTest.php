<?php

namespace Tests\Unit;

use App\Models\Appel;
use App\Models\Prospect;
use App\Models\StatutPhoning;
use App\Observers\AppelObserver;
use App\Enums\ProspectStatut;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppelObserverTest extends TestCase
{
    use RefreshDatabase;

    private $userId;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Désactiver les contraintes de clés étrangères pour les tests
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        
        // Créer un utilisateur pour les tests
        $user = \App\Models\User::create([
            'nom' => 'Test',
            'prenom' => 'User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'actif' => 1,
        ]);
        $this->userId = $user->id;
        
        // Créer les statuts phoning nécessaires pour les tests
        StatutPhoning::create([
            'model_type' => 'prospect',
            'code' => 'contacte',
            'label' => 'Contacté',
            'pipeline_statut' => 'STD_Joint', // Utiliser une valeur enum valide
            'actif' => true,
        ]);

        StatutPhoning::create([
            'model_type' => 'prospect',
            'code' => 'std_nr',
            'label' => 'STD NR',
            'pipeline_statut' => 'STD_NR',
            'actif' => true,
        ]);

        StatutPhoning::create([
            'model_type' => 'prospect',
            'code' => 'ko',
            'label' => 'KO',
            'pipeline_statut' => 'KO',
            'actif' => true,
        ]);
        
        // Réactiver les contraintes de clés étrangères
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
    }

    public function test_created_appel_updates_prospect_statut_to_contacte()
    {
        $prospect = Prospect::create([
            'nom' => 'Test Prospect',
            'statut' => 'AC',
            'teleprospecteur_id' => $this->userId,
        ]);
        $appel = Appel::create([
            'appelable_type' => Prospect::class,
            'appelable_id' => $prospect->id,
            'phoning_result' => 'contacte',
            'user_id' => $this->userId,
        ]);

        $observer = new AppelObserver();
        $observer->created($appel);

        $prospect->refresh();
        $this->assertEquals('STD_Joint', $prospect->statut->value);
    }

    public function test_created_appel_updates_prospect_statut_to_std_nr()
    {
        $prospect = Prospect::create([
            'nom' => 'Test Prospect',
            'statut' => 'AC',
            'teleprospecteur_id' => $this->userId,
        ]);
        $appel = Appel::create([
            'appelable_type' => Prospect::class,
            'appelable_id' => $prospect->id,
            'phoning_result' => 'std_nr',
            'user_id' => $this->userId,
        ]);

        $observer = new AppelObserver();
        $observer->created($appel);

        $prospect->refresh();
        $this->assertEquals('STD_NR', $prospect->statut->value);
    }

    public function test_created_appel_updates_prospect_statut_to_ko()
    {
        $prospect = Prospect::create([
            'nom' => 'Test Prospect',
            'statut' => 'AC',
            'teleprospecteur_id' => $this->userId,
        ]);
        $appel = Appel::create([
            'appelable_type' => Prospect::class,
            'appelable_id' => $prospect->id,
            'phoning_result' => 'ko',
            'user_id' => $this->userId,
        ]);

        $observer = new AppelObserver();
        $observer->created($appel);

        $prospect->refresh();
        $this->assertEquals('KO', $prospect->statut->value);
    }

    public function test_updated_appel_with_phoning_result_change_updates_prospect_statut()
    {
        $prospect = Prospect::create([
            'nom' => 'Test Prospect',
            'statut' => 'AC',
            'teleprospecteur_id' => $this->userId,
        ]);
        $appel = Appel::create([
            'appelable_type' => Prospect::class,
            'appelable_id' => $prospect->id,
            'phoning_result' => 'std_nr',
            'user_id' => $this->userId,
        ]);

        $observer = new AppelObserver();
        $observer->created($appel);

        $prospect->refresh();
        $this->assertEquals('STD_NR', $prospect->statut->value);

        // Modifier le résultat de l'appel
        $appel->phoning_result = 'ko';
        $appel->save();

        $observer->updated($appel);

        $prospect->refresh();
        $this->assertEquals('KO', $prospect->statut->value);
    }

    public function test_updated_appel_without_phoning_result_change_does_not_update_prospect()
    {
        $prospect = Prospect::create([
            'nom' => 'Test Prospect',
            'statut' => 'AC',
            'teleprospecteur_id' => $this->userId,
        ]);
        $appel = Appel::create([
            'appelable_type' => Prospect::class,
            'appelable_id' => $prospect->id,
            'phoning_result' => 'contacte',
            'user_id' => $this->userId,
        ]);

        $observer = new AppelObserver();
        $observer->created($appel);

        $prospect->refresh();
        $this->assertEquals('STD_Joint', $prospect->statut->value);

        // Modifier un autre champ sans changer phoning_result
        $appel->date_heure = now()->addHour();
        $appel->save();

        $observer->updated($appel);

        $prospect->refresh();
        $this->assertEquals('STD_Joint', $prospect->statut->value);
    }

    public function test_appel_not_linked_to_prospect_does_not_update_statut()
    {
        $prospect = Prospect::create([
            'nom' => 'Test Prospect',
            'statut' => 'AC',
            'teleprospecteur_id' => $this->userId,
        ]);
        $appel = Appel::create([
            'appelable_type' => 'OtherModel',
            'appelable_id' => 999,
            'phoning_result' => 'contacte',
            'user_id' => $this->userId,
        ]);

        $observer = new AppelObserver();
        $observer->created($appel);

        $prospect->refresh();
        $this->assertEquals('AC', $prospect->statut->value);
    }

    public function test_appel_without_phoning_result_does_not_update_statut()
    {
        $prospect = Prospect::create([
            'nom' => 'Test Prospect',
            'statut' => 'AC',
            'teleprospecteur_id' => $this->userId,
        ]);
        $appel = Appel::create([
            'appelable_type' => Prospect::class,
            'appelable_id' => $prospect->id,
            'phoning_result' => null,
            'user_id' => $this->userId,
        ]);

        $observer = new AppelObserver();
        $observer->created($appel);

        $prospect->refresh();
        $this->assertEquals('AC', $prospect->statut->value);
    }

    public function test_appel_with_invalid_statut_phoning_does_not_update_statut()
    {
        $prospect = Prospect::create([
            'nom' => 'Test Prospect',
            'statut' => 'AC',
            'teleprospecteur_id' => $this->userId,
        ]);
        $appel = Appel::create([
            'appelable_type' => Prospect::class,
            'appelable_id' => $prospect->id,
            'phoning_result' => 'invalid_result',
            'user_id' => $this->userId,
        ]);

        $observer = new AppelObserver();
        $observer->created($appel);

        $prospect->refresh();
        $this->assertEquals('AC', $prospect->statut->value);
    }

    public function test_appel_with_inactive_statut_phoning_does_not_update_statut()
    {
        // Créer un statut phoning inactif
        StatutPhoning::create([
            'model_type' => 'prospect',
            'code' => 'inactive_result',
            'label' => 'Inactif',
            'pipeline_statut' => 'Contacté',
            'actif' => false,
        ]);

        $prospect = Prospect::create([
            'nom' => 'Test Prospect',
            'statut' => 'AC',
            'teleprospecteur_id' => $this->userId,
        ]);
        $appel = Appel::create([
            'appelable_type' => Prospect::class,
            'appelable_id' => $prospect->id,
            'phoning_result' => 'inactive_result',
            'user_id' => $this->userId,
        ]);

        $observer = new AppelObserver();
        $observer->created($appel);

        $prospect->refresh();
        $this->assertEquals('AC', $prospect->statut->value);
    }
}
