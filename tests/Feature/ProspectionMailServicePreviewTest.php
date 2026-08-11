<?php

namespace Tests\Feature;

use App\Mail\ContactSansCSEMail;
use App\Models\Prospect;
use App\Services\ProspectionMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ProspectionMailServicePreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_context_sends_email_directly_without_queue(): void
    {
        Mail::fake();

        $prospect = new Prospect([
            'nom' => 'Test Wizi',
            'interlocuteur_nom' => 'Jean Test',
            'interlocuteur_fonction' => 'Responsable',
            'interlocuteur_email' => 'test@wizi-learn.com',
            'interlocuteur_telephone' => '0123456789',
            'nb_salaries' => 12,
            'departement' => '75',
            'ville' => 'Paris',
        ]);

        app(ProspectionMailService::class)->envoyerPourStatut('ncse_50', $prospect, [
            'email_preview_to' => 'test@wizi-learn.com',
            'email_preview_subject' => 'Objet test NCSE-50',
            'email_preview_body' => '<p>Corps test</p>',
        ]);

        Mail::assertSent(ContactSansCSEMail::class, function (ContactSansCSEMail $mail) {
            return $mail->hasTo('test@wizi-learn.com');
        });
    }
}
