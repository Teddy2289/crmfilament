<?php

namespace Tests\Unit;

use App\Filament\NsConseil\Pages\PhoningWorkflow;
use Illuminate\Mail\Mailable;
use Tests\TestCase;

class PhoningWorkflowPreviewTest extends TestCase
{
    public function test_it_still_builds_preview_payload_when_no_recipient_is_predefined(): void
    {
        $workflow = new class extends PhoningWorkflow
        {
            protected function getPreviewMailableForStatut(string $statut): ?Mailable
            {
                return new class extends Mailable
                {
                };
            }

            protected function resolvePreviewRecipient(string $statut): ?string
            {
                return null;
            }

            protected function getMailableSubject(Mailable $mailable): string
            {
                return 'Sujet test';
            }

            protected function getMailableBody(Mailable $mailable): string
            {
                return '<p>Corps test</p>';
            }
        };

        $payload = (new \ReflectionMethod($workflow, 'getEmailPreviewPayload'));
        $payload->setAccessible(true);

        $result = $payload->invoke($workflow);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('recipient', $result);
        $this->assertSame('', $result['recipient']);
        $this->assertSame('Sujet test', $result['subject']);
        $this->assertSame('<p>Corps test</p>', $result['body']);
    }
}
