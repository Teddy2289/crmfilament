<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\RingoverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RingoverServiceScopeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function agent_sees_only_his_own_ringover_calls_and_stats(): void
    {
        Cache::flush();

        $agent = User::make([
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
                        'cdr_id' => 'cdr-1',
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
                        'cdr_id' => 'cdr-2',
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

        $service = app(RingoverService::class);
        $calls = $service->getCalls();
        $stats = $service->getStats();

        $this->assertCount(1, $calls);
        $this->assertSame('call-1', $calls[0]['id']);
        $this->assertSame(1, $stats['total']);
        $this->assertSame(1, $stats['sortants']);
        $this->assertSame(1, $stats['repondus']);
    }
}
