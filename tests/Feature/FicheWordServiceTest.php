<?php

namespace Tests\Feature;

use App\Models\Appel;
use App\Models\Prospect;
use App\Models\TemplateFiche;
use App\Models\User;
use App\Services\Crm\FicheWordService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FicheWordServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FicheWordService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FicheWordService::class);
        Storage::fake('public');
    }

    public function test_peut_charger_un_template_word(): void
    {
        $template = TemplateFiche::factory()->create([
            'fichier_path' => 'templates_fiches/test.docx',
        ]);

        Storage::disk('public')->put($template->fichier_path, 'fake content');

        $result = $this->service->chargerTemplate($template);

        $this->assertNotNull($result);
    }

    public function test_peut_remplacer_les_variables_dans_un_template(): void
    {
        $variables = [
            '{{NOM_CLIENT}}' => 'Test Company',
            '{{TELEPHONE_CLIENT}}' => '0123456789',
            '{{DATE_RDV}}' => '25/06/2026',
        ];

        $result = $this->service->remplacerVariables($variables);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('NOM_CLIENT', $result);
        $this->assertEquals('Test Company', $result['NOM_CLIENT']);
    }

    public function test_peut_stocker_un_fichier_word(): void
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempPath, 'fake word content');

        $path = $this->service->stocker($tempPath, '2026/06');

        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        unlink($tempPath);
    }

    public function test_peut_generer_une_fiche_pour_un_appel(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create([
            'raison_sociale' => 'Test Company',
            'telephone' => '0123456789',
        ]);

        $appel = Appel::create([
            'appelable_type' => Prospect::class,
            'appelable_id' => $prospect->id,
            'user_id' => $user->id,
            'type' => \App\Enums\EventType::Appel,
            'date_heure' => now(),
            'resultat' => \App\Enums\EventResult::Realise,
            'fiche_type' => 'bleue',
            'fiche_data' => [
                'raison_sociale' => 'Test Company',
                'date_rdv' => '25/06/2026',
            ],
        ]);

        $template = TemplateFiche::factory()->create([
            'type' => 'bleue',
            'fichier_path' => 'templates_fiches/test.docx',
        ]);

        Storage::disk('public')->put($template->fichier_path, 'fake content');

        $result = $this->service->genererPourAppel($appel);

        $this->assertNotNull($result);
        $this->assertStringContainsString('.docx', $result);
    }

    public function test_retourne_null_si_template_non_trouve(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create();

        $appel = Appel::create([
            'appelable_type' => Prospect::class,
            'appelable_id' => $prospect->id,
            'user_id' => $user->id,
            'type' => \App\Enums\EventType::Appel,
            'date_heure' => now(),
            'resultat' => \App\Enums\EventResult::Realise,
            'fiche_type' => 'inconnu',
        ]);

        $result = $this->service->genererPourAppel($appel);

        $this->assertNull($result);
    }
}
