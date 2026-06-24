<?php

namespace Tests\Feature;

use App\Jobs\GenerateFicheWordJob;
use App\Models\Appel;
use App\Models\Prospect;
use App\Models\TemplateFiche;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GenerateFicheWordJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_est_dispatche_apres_creation_appel(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $prospect = Prospect::factory()->create();

        $appel = Appel::create([
            'appelable_type' => Prospect::class,
            'appelable_id' => $prospect->id,
            'user_id' => $user->id,
            'type' => \App\Enums\EventType::Appel,
            'date_heure' => now(),
            'resultat' => \App\Enums\EventResult::Realise,
            'fiche_type' => 'bleue',
            'fiche_data' => ['raison_sociale' => 'Test Company'],
        ]);

        Queue::assertPushed(GenerateFicheWordJob::class, function ($job) use ($appel) {
            return $job->appelId === $appel->id;
        });
    }

    public function test_job_met_a_jour_chemin_fiche_dans_appel(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $prospect = Prospect::factory()->create([
            'raison_sociale' => 'Test Company',
        ]);

        $appel = Appel::create([
            'appelable_type' => Prospect::class,
            'appelable_id' => $prospect->id,
            'user_id' => $user->id,
            'type' => \App\Enums\EventType::Appel,
            'date_heure' => now(),
            'resultat' => \App\Enums\EventResult::Realise,
            'fiche_type' => 'bleue',
            'fiche_data' => ['raison_sociale' => 'Test Company'],
        ]);

        $template = TemplateFiche::factory()->create([
            'type' => 'bleue',
            'fichier_path' => 'templates_fiches/test.docx',
        ]);

        Storage::disk('public')->put($template->fichier_path, 'fake content');

        $job = new GenerateFicheWordJob($appel->id);
        $job->handle();

        $appel->refresh();

        $this->assertNotNull($appel->fiche_word_path);
        $this->assertNotNull($appel->fiche_word_generated_at);
        $this->assertStringContainsString('.docx', $appel->fiche_word_path);
    }

    public function test_job_ne_fait_rien_si_appel_non_trouve(): void
    {
        $job = new GenerateFicheWordJob(99999);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $job->handle();
    }

    public function test_job_ne_fait_rien_si_pas_de_fiche_type(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $prospect = Prospect::factory()->create();

        $appel = Appel::create([
            'appelable_type' => Prospect::class,
            'appelable_id' => $prospect->id,
            'user_id' => $user->id,
            'type' => \App\Enums\EventType::Appel,
            'date_heure' => now(),
            'resultat' => \App\Enums\EventResult::Realise,
            'fiche_type' => null,
        ]);

        $job = new GenerateFicheWordJob($appel->id);
        $job->handle();

        $appel->refresh();

        $this->assertNull($appel->fiche_word_path);
    }
}
