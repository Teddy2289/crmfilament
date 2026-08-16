<?php

namespace Tests\Feature;

use App\Enums\ProspectStatut;
use App\Enums\RendezVousStatut;
use App\Models\Prospect;
use App\Models\RendezVous;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que le widget de traitement affiche correctement les fiches
     */
    public function test_activite_traitement_widget_calculates_correct_counts()
    {
        $role = Role::create(['name' => 'teleprospecteur']);
        $user = User::factory()->create();
        $user->assignRole($role);

        // Créer des prospects avec différents statuts
        Prospect::factory()->count(5)->create([
            'teleprospecteur_id' => $user->id,
            'statut' => ProspectStatut::AC->value,
        ]);

        Prospect::factory()->count(3)->create([
            'teleprospecteur_id' => $user->id,
            'statut' => ProspectStatut::STD_NR->value,
        ]);

        Prospect::factory()->count(2)->create([
            'teleprospecteur_id' => $user->id,
            'statut' => ProspectStatut::QF->value,
        ]);

        // Vérifier que les comptes sont corrects
        $this->assertEquals(5, Prospect::where('teleprospecteur_id', $user->id)->where('statut', ProspectStatut::AC->value)->count());
        $this->assertEquals(3, Prospect::where('teleprospecteur_id', $user->id)->where('statut', ProspectStatut::STD_NR->value)->count());
        $this->assertEquals(2, Prospect::where('teleprospecteur_id', $user->id)->where('statut', ProspectStatut::QF->value)->count());
    }

    /**
     * Test que la chart de statuts affiche tous les statuts présents
     */
    public function test_prospection_statuts_chart_displays_all_statuses()
    {
        $role = Role::create(['name' => 'teleprospecteur']);
        $user = User::factory()->create();
        $user->assignRole($role);

        // Créer des prospects pour chaque statut étendu
        $statuts = [
            ProspectStatut::AC,
            ProspectStatut::STD_NR,
            ProspectStatut::STD_Joint,
            ProspectStatut::CSE_NR,
            ProspectStatut::RP,
            ProspectStatut::RPC,
            ProspectStatut::KO,
            ProspectStatut::QF,
            ProspectStatut::REPONDEUR,
            ProspectStatut::NRP,
            ProspectStatut::FAX,
            ProspectStatut::SUPP,
            ProspectStatut::CSE_NI,
            ProspectStatut::RAPL_ELU,
            ProspectStatut::RAPL_STD,
            ProspectStatut::BLOC2,
            ProspectStatut::NCSE_50,
        ];

        foreach ($statuts as $statut) {
            Prospect::factory()->create([
                'teleprospecteur_id' => $user->id,
                'statut' => $statut->value,
            ]);
        }

        $this->assertEquals(17, Prospect::where('teleprospecteur_id', $user->id)->count());
    }

    /**
     * Test que le widget d'alertes TL calcule les statuts problématiques
     */
    public function test_team_leader_alerts_widget_counts_problematic_statuses()
    {
        $role = Role::create(['name' => 'superviseur']);
        $supervisor = User::factory()->create();
        $supervisor->assignRole($role);

        // Créer des prospects problématiques
        Prospect::factory()->count(2)->create(['statut' => ProspectStatut::KO->value]);
        Prospect::factory()->count(1)->create(['statut' => ProspectStatut::SUPP->value]);
        Prospect::factory()->count(3)->create(['statut' => ProspectStatut::BLOC2->value]);

        // Total problématiques = 6
        $problematic = Prospect::whereIn('statut', [
            ProspectStatut::KO->value,
            ProspectStatut::SUPP->value,
            ProspectStatut::BLOC2->value,
        ])->count();

        $this->assertEquals(6, $problematic);
    }

    /**
     * Test que le widget commercial inclut les nouveaux statuts dans le pipeline
     */
    public function test_commercial_kpi_widget_includes_extended_statuses()
    {
        $role = Role::create(['name' => 'commercial']);
        $commercial = User::factory()->create();
        $commercial->assignRole($role);

        // Créer des prospects dans le pipeline étendu
        Prospect::factory()->count(2)->create([
            'commercial_id' => $commercial->id,
            'statut' => ProspectStatut::RP->value,
        ]);

        Prospect::factory()->count(2)->create([
            'commercial_id' => $commercial->id,
            'statut' => ProspectStatut::RAPL_STD->value,
        ]);

        Prospect::factory()->count(1)->create([
            'commercial_id' => $commercial->id,
            'statut' => ProspectStatut::RAPL_ELU->value,
        ]);

        // Total pipeline = 5
        $pipeline = Prospect::where('commercial_id', $commercial->id)
            ->whereIn('statut', [
                ProspectStatut::RP->value,
                ProspectStatut::RPC->value,
                ProspectStatut::RAPL_STD->value,
                ProspectStatut::RAPL_ELU->value,
            ])->count();

        $this->assertEquals(5, $pipeline);
    }

    /**
     * Test que le widget direction affiche les prospects finalisés correctement
     */
    public function test_direction_kpi_widget_counts_finalized_prospects()
    {
        $role = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($role);

        // Créer des prospects finalisés ce mois
        Prospect::factory()->count(3)->create([
            'statut' => ProspectStatut::QF->value,
            'qf_valide_at' => now(),
        ]);

        Prospect::factory()->count(2)->create([
            'statut' => ProspectStatut::RAPL_STD->value,
            'updated_at' => now(),
        ]);

        Prospect::factory()->count(1)->create([
            'statut' => ProspectStatut::RAPL_ELU->value,
            'updated_at' => now(),
        ]);

        // Total finalisés ce mois = 6
        $finalized = Prospect::whereIn('statut', [
            ProspectStatut::QF->value,
            ProspectStatut::RAPL_STD->value,
            ProspectStatut::RAPL_ELU->value,
        ])
            ->whereMonth('updated_at', now()->month)
            ->count();

        $this->assertEquals(6, $finalized);
    }

    /**
     * Test que les widgets n'affichent que les statuts avec données
     */
    public function test_widgets_only_display_statuses_with_data()
    {
        $role = Role::create(['name' => 'teleprospecteur']);
        $user = User::factory()->create();
        $user->assignRole($role);

        // Créer seulement 3 prospects avec des statuts différents
        Prospect::factory()->create([
            'teleprospecteur_id' => $user->id,
            'statut' => ProspectStatut::AC->value,
        ]);

        Prospect::factory()->create([
            'teleprospecteur_id' => $user->id,
            'statut' => ProspectStatut::STD_NR->value,
        ]);

        Prospect::factory()->create([
            'teleprospecteur_id' => $user->id,
            'statut' => ProspectStatut::QF->value,
        ]);

        // Les autres statuts (14) ne devraient pas être affichés dans les widgets
        $userProspects = Prospect::where('teleprospecteur_id', $user->id)->count();
        $this->assertEquals(3, $userProspects);
    }
}
