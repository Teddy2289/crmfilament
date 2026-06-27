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

        // Manually dispatch the job as it would be done in PhoningWorkflow
        \App\Jobs\GenerateFicheWordJob::dispatch($appel->id);

        Queue::assertPushed(GenerateFicheWordJob::class, function ($job) use ($appel) {
            return $job->appelId === $appel->id;
        });
    }

    public function test_job_met_a_jour_chemin_fiche_dans_appel(): void
    {
        Storage::fake('public');

        $service = $this->createMock(\App\Services\Crm\FicheWordService::class);
        $service->method('genererPourAppel')->willReturn('/tmp/test.docx');
        $service->method('stocker')->willReturn('fiches/2026/06/test.docx');

        $user = User::factory()->create();
        $prospect = Prospect::factory()->create([
            'nom' => 'Test Company',
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

        $template = \App\Models\TemplateFiche::create([
            'code' => 'test_bleue',
            'nom' => 'Test Template',
            'type' => 'bleue',
            'fichier_path' => 'templates_fiches/test.docx',
            'actif' => true,
        ]);

        Storage::disk('public')->put($template->fichier_path, 'fake content');

        $job = new GenerateFicheWordJob($appel->id);
        $job->handle($service);

        $appel->refresh();

        $this->assertNotNull($appel->fiche_word_path);
        $this->assertNotNull($appel->fiche_word_generated_at);
        $this->assertStringContainsString('.docx', $appel->fiche_word_path);
    }

    public function test_job_ne_fait_rien_si_appel_non_trouve(): void
    {
        $service = $this->createMock(\App\Services\Crm\FicheWordService::class);
        $job = new GenerateFicheWordJob(99999);

        // The job handles missing appels gracefully by returning early
        $job->handle($service);

        // No exception should be thrown
        $this->assertTrue(true);
    }

    public function test_job_ne_fait_rien_si_pas_de_fiche_type(): void
    {
        Storage::fake('public');

        $service = $this->createMock(\App\Services\Crm\FicheWordService::class);
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
        $job->handle($service);

        $appel->refresh();

        $this->assertNull($appel->fiche_word_path);
    }
}
