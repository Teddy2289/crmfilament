<?php

namespace Tests\Feature;

use App\Filament\NsConseil\Pages\RingoverDashboard;
use App\Models\Prospect;
use App\Models\StatutPhoning;
use App\Models\User;
use App\Services\RingoverCallSyncService;
use App\Services\RingoverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RingoverAdvancedIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        StatutPhoning::create([
            'model_type' => 'prospect',
            'code' => 'rdv',
            'label' => 'RDV',
            'pipeline_statut' => 'RPC',
            'actif' => true,
        ]);

        StatutPhoning::create([
            'model_type' => 'prospect',
            'code' => 'cse_ni',
            'label' => 'CSE-NI',
            'pipeline_statut' => 'RP',
            'actif' => true,
        ]);
    }

    #[Test]
    public function ringover_sync_maps_user_tags_and_local_prospect(): void
    {
        $user = User::factory()->create([
            'prenom' => 'Aline',
            'nom' => 'Agent',
            'email' => 'agent@example.test',
            'ringover_user_id' => null,
            'ringover_email' => null,
        ]);

        $prospect = Prospect::create([
            'nom' => 'CSE Demo',
            'telephone' => '06 12 34 56 78',
            'departement' => '45',
        ]);

        $result = app(RingoverCallSyncService::class)->sync([
            'id' => 'call-123',
            'started_at' => now()->timestamp,
            'duration' => 95,
            'direction' => 'outbound',
            'status' => 'answered',
            'raw_digits' => '+33612345678',
            'recording' => 'https://recording.example.test/call-123.mp3',
            'user' => [
                'id' => 'ring-user-1',
                'email' => 'agent@example.test',
                'name' => 'Aline Agent',
            ],
            'tags' => [
                ['name' => 'DEP_45'],
                ['name' => 'RDV'],
            ],
        ]);

        $appel = $result['appel'];

        $this->assertTrue($result['created']);
        $this->assertSame($user->id, $appel->user_id);
        $this->assertSame(Prospect::class, $appel->appelable_type);
        $this->assertSame($prospect->id, $appel->appelable_id);
        $this->assertSame('DEP_45', $appel->ringover_department_tag);
        $this->assertSame('RDV', $appel->ringover_status_tag);
        $this->assertTrue($appel->ringover_tag_is_complete);
        $this->assertSame('rdv', $appel->phoning_status);
        $this->assertSame('RDV', $appel->phoning_result);
        $this->assertSame('RPC', $prospect->refresh()->statut->value);
        $this->assertSame('ring-user-1', $user->refresh()->ringover_user_id);
        $this->assertSame('agent@example.test', $user->ringover_email);
    }

    #[Test]
    public function ringover_sync_extracts_summary_note_and_qualifications_from_call_payload(): void
    {
        $result = app(RingoverCallSyncService::class)->sync([
            'id' => 'call-qualif-1',
            'started_at' => now()->timestamp,
            'duration' => 140,
            'direction' => 'outbound',
            'status' => 'answered',
            'summary' => 'Client intéressé. RDV confirmé le 15/09 à 10h.',
            'comments' => [
                ['content' => 'Note agent : dossier très favorable et client motivé.'],
            ],
            'qualifications' => [
                ['name' => 'budget', 'label' => 'Budget', 'value' => '3000'],
                ['name' => 'interet', 'label' => 'Intérêt', 'value' => 'Très élevé'],
            ],
            'tags' => [
                ['name' => 'DEP_45'],
                ['name' => 'RDV'],
            ],
        ]);

        $appel = $result['appel'];

        $this->assertSame('Client intéressé. RDV confirmé le 15/09 à 10h.', $appel->ringover_summary);
        $this->assertSame('Note agent : dossier très favorable et client motivé.', $appel->ringover_note);
        $this->assertSame([
            ['name' => 'budget', 'label' => 'Budget', 'value' => '3000'],
            ['name' => 'interet', 'label' => 'Intérêt', 'value' => 'Très élevé'],
        ], $appel->ringover_qualifications);
    }

    #[Test]
    public function ringover_dashboard_is_accessible_for_mapped_agents_and_filters_their_calls(): void
    {
        Cache::flush();

        $agent = User::factory()->create([
            'prenom' => 'Aline',
            'nom' => 'Agent',
            'email' => 'agent@example.test',
            'ringover_user_id' => 'ring-user-1',
            'ringover_email' => 'agent@example.test',
        ]);

        $this->actingAs($agent);

        Http::fake([
            'https://public-api.ringover.com/v2/calls*' => Http::response([
                'call_list' => [
                    [
                        'id' => 'call-1',
                        'direction' => 'out',
                        'is_answered' => true,
                        'total_duration' => 90,
                        'start_time' => '2026-08-03T10:00:00+00:00',
                        'user' => [
                            'id' => 'ring-user-1',
                            'email' => 'agent@example.test',
                            'concat_name' => 'Aline Agent',
                        ],
                    ],
                    [
                        'id' => 'call-2',
                        'direction' => 'in',
                        'is_answered' => false,
                        'total_duration' => 45,
                        'start_time' => '2026-08-03T11:00:00+00:00',
                        'user' => [
                            'id' => 'ring-user-2',
                            'email' => 'other@example.test',
                            'concat_name' => 'Other Agent',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->assertTrue(RingoverDashboard::canAccess());

        $calls = app(RingoverService::class)->getCalls();
        $stats = app(RingoverService::class)->getStats();

        $this->assertCount(1, $calls);
        $this->assertSame('call-1', $calls[0]['id']);
        $this->assertSame(1, $stats['total']);
        $this->assertSame(1, $stats['sortants']);
        $this->assertSame(1, $stats['repondus']);
    }

    #[Test]
    public function ringover_webhook_requires_secret_when_configured_and_is_idempotent(): void
    {
        config(['ringover.webhook_secret' => 'secret-test']);

        $payload = [
            'event' => 'call.completed',
            'call' => [
                'id' => 'webhook-call-1',
                'started_at' => now()->timestamp,
                'duration' => 30,
                'direction' => 'outbound',
                'status' => 'done',
                'tags' => ['DEP_75', 'CSE-NI'],
            ],
        ];

        $this->postJson('/api/ringover/webhook', $payload)
            ->assertForbidden();

        $this->postJson('/api/ringover/webhook', $payload, [
            'X-Ringover-Webhook-Secret' => 'secret-test',
        ])
            ->assertOk()
            ->assertJson([
                'status' => 'ok',
                'created' => true,
                'ringover_call_id' => 'webhook-call-1',
                'tags_complete' => true,
            ]);

        $this->postJson('/api/ringover/webhook', $payload, [
            'X-Ringover-Webhook-Secret' => 'secret-test',
        ])
            ->assertOk()
            ->assertJson([
                'status' => 'ok',
                'created' => false,
                'ringover_call_id' => 'webhook-call-1',
                'tags_complete' => true,
            ]);

        $this->assertDatabaseCount('appels', 1);
        $this->assertDatabaseHas('appels', [
            'ringover_call_id' => 'webhook-call-1',
            'ringover_department_tag' => 'DEP_75',
            'ringover_status_tag' => 'CSE-NI',
            'phoning_status' => 'cse_ni',
            'ringover_tag_is_complete' => true,
            'ringover_sync_source' => 'webhook',
        ]);
    }
}
