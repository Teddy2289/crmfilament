<?php

namespace Tests\Feature;

use App\Jobs\SendFicheJauneJ7Job;
use App\Models\Appel;
use App\Models\Prospect;
use App\Models\TemplateFiche;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SendFicheJauneJ7JobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_trouve_appels_de_j_moins_7_avec_statut_cse_ni(): void
    {
        Storage::fake('public');
        Mail::fake();

        $service = $this->createMock(\App\Services\Crm\FicheWordService::class);
        $user = User::factory()->create(['email' => 'test@example.com']);
        $prospect = Prospect::factory()->create();

        $appel = Appel::create([
            'appelable_type' => Prospect::class,
            'appelable_id' => $prospect->id,
            'user_id' => $user->id,
            'type' => \App\Enums\EventType::Appel,
            'date_heure' => now()->subDays(7),
            'resultat' => \App\Enums\EventResult::Realise,
            'phoning_status' => 'CSE-NI',
            'fiche_type' => 'jaune',
            'fiche_word_path' => 'fiches/test.docx',
            'fiche_word_generated_at' => now()->subDays(7),
        ]);

        Storage::disk('public')->put('fiches/test.docx', 'fake content');

        $job = new SendFicheJauneJ7Job();
        $job->handle($service);

        Mail::assertQueued(\App\Mail\FicheJauneJ7Mail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_job_ne_renvoie_pas_si_deja_envoye(): void
    {
        Storage::fake('public');
        Mail::fake();

        $service = $this->createMock(\App\Services\Crm\FicheWordService::class);
        $user = User::factory()->create(['email' => 'test@example.com']);
        $prospect = Prospect::factory()->create();

        $appel = Appel::create([
            'appelable_type' => Prospect::class,
            'appelable_id' => $prospect->id,
            'user_id' => $user->id,
            'type' => \App\Enums\EventType::Appel,
            'date_heure' => now()->subDays(7),
            'resultat' => \App\Enums\EventResult::Realise,
            'phoning_status' => 'CSE-NI',
            'fiche_type' => 'jaune',
            'fiche_word_path' => 'fiches/test.docx',
            'fiche_word_generated_at' => now()->subDays(7),
            'fiche_jaune_j7_envoye_at' => now()->subDay(),
        ]);

        Storage::disk('public')->put('fiches/test.docx', 'fake content');

        $job = new SendFicheJauneJ7Job();
        $job->handle($service);

        Mail::assertNothingSent();
    }

    public function test_job_ignore_appels_avec_mauvais_statut(): void
    {
        Storage::fake('public');
        Mail::fake();

        $service = $this->createMock(\App\Services\Crm\FicheWordService::class);
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create();

        $appel = Appel::create([
            'appelable_type' => Prospect::class,
            'appelable_id' => $prospect->id,
            'user_id' => $user->id,
            'type' => \App\Enums\EventType::Appel,
            'date_heure' => now()->subDays(7),
            'resultat' => \App\Enums\EventResult::Realise,
            'phoning_status' => 'rdv',
            'fiche_type' => 'bleue',
        ]);

        $job = new SendFicheJauneJ7Job();
        $job->handle($service);

        Mail::assertNothingSent();
    }

    public function test_job_marque_appel_comme_envoye(): void
    {
        Storage::fake('public');
        Mail::fake();

        $service = $this->createMock(\App\Services\Crm\FicheWordService::class);
        $user = User::factory()->create(['email' => 'test@example.com']);
        $prospect = Prospect::factory()->create();

        $appel = Appel::create([
            'appelable_type' => Prospect::class,
            'appelable_id' => $prospect->id,
            'user_id' => $user->id,
            'type' => \App\Enums\EventType::Appel,
            'date_heure' => now()->subDays(7),
            'resultat' => \App\Enums\EventResult::Realise,
            'phoning_status' => 'CSE-NI',
            'fiche_type' => 'jaune',
            'fiche_word_path' => 'fiches/test.docx',
            'fiche_word_generated_at' => now()->subDays(7),
        ]);

        Storage::disk('public')->put('fiches/test.docx', 'fake content');

        $job = new SendFicheJauneJ7Job();
        $job->handle($service);

        $appel->refresh();

        $this->assertNotNull($appel->fiche_jaune_j7_envoye_at);
    }

    public function test_job_ne_fait_rien_si_aucun_appel_trouve(): void
    {
        Storage::fake('public');
        Mail::fake();

        $service = $this->createMock(\App\Services\Crm\FicheWordService::class);
        $job = new SendFicheJauneJ7Job();
        $job->handle($service);

        Mail::assertNothingSent();
    }
}
