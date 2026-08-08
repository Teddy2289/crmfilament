<?php

namespace Tests\Unit;

use App\Models\EmailTemplate;
use Tests\TestCase;

class EmailTemplateTest extends TestCase
{
    public function test_it_replaces_template_variables_case_insensitively(): void
    {
        $template = new EmailTemplate([
            'sujet' => 'Bonjour {{Nom}} {{prenom}}',
            'corps' => 'Entreprise {{RAISON_SOCIALE}} — {{telephone}}',
        ]);

        $variables = [
            'nom' => 'Martin',
            'prenom' => 'Claire',
            'raison_sociale' => 'AOPIA',
            'telephone' => '0123456789',
        ];

        $this->assertSame('Bonjour Martin Claire', $template->renderSujet($variables));
        $this->assertSame('Entreprise AOPIA — 0123456789', $template->renderCorps($variables));
    }
}
