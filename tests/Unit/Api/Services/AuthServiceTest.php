<?php

namespace Tests\Unit\Api\Services;

use App\Models\User;
use App\Services\Api\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Tests unitaires pour AuthService.
 *
 * Couvre : issueTokenPair, revokeAccessToken, revokeAllTokens, refreshAccessToken
 * Requirements : 2.1, 2.6, 2.7, 15.1, 15.4
 */
class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AuthService::class);
    }

    // -------------------------------------------------------------------------
    // issueTokenPair
    // -------------------------------------------------------------------------

    public function test_issue_token_pair_returns_access_and_refresh_tokens(): void
    {
        $user = User::factory()->create();

        $result = $this->service->issueTokenPair($user);

        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('refresh_token', $result);
        $this->assertArrayHasKey('expires_in', $result);
        $this->assertIsString($result['access_token']);
        $this->assertIsString($result['refresh_token']);
        $this->assertIsInt($result['expires_in']);
    }

    public function test_issue_token_pair_creates_two_tokens_in_database(): void
    {
        $user = User::factory()->create();

        $this->service->issueTokenPair($user);

        $this->assertCount(2, $user->tokens()->get());
    }

    public function test_issue_token_pair_access_token_has_wildcard_ability(): void
    {
        $user = User::factory()->create();

        $this->service->issueTokenPair($user);

        $accessToken = $user->tokens()->where('name', 'access')->first();
        $this->assertNotNull($accessToken);
        $this->assertContains('*', $accessToken->abilities);
    }

    public function test_issue_token_pair_refresh_token_has_refresh_ability(): void
    {
        $user = User::factory()->create();

        $this->service->issueTokenPair($user);

        $refreshToken = $user->tokens()->where('name', 'refresh')->first();
        $this->assertNotNull($refreshToken);
        $this->assertContains('refresh', $refreshToken->abilities);
    }

    public function test_issue_token_pair_access_token_expires_in_24_hours(): void
    {
        Carbon::setTestNow('2024-01-01 12:00:00');
        $user = User::factory()->create();

        $this->service->issueTokenPair($user);

        $accessToken = $user->tokens()->where('name', 'access')->first();
        $this->assertNotNull($accessToken->expires_at);
        // Access token doit expirer en 24h (±1 seconde pour les arrondis)
        $this->assertEqualsWithDelta(
            Carbon::now()->addMinutes(60 * 24)->timestamp,
            Carbon::parse($accessToken->expires_at)->timestamp,
            1,
        );

        Carbon::setTestNow(null);
    }

    public function test_issue_token_pair_refresh_token_expires_in_30_days(): void
    {
        Carbon::setTestNow('2024-01-01 12:00:00');
        $user = User::factory()->create();

        $this->service->issueTokenPair($user);

        $refreshToken = $user->tokens()->where('name', 'refresh')->first();
        $this->assertNotNull($refreshToken->expires_at);
        // Refresh token doit expirer en 30 jours (±1 seconde pour les arrondis)
        $this->assertEqualsWithDelta(
            Carbon::now()->addMinutes(60 * 24 * 30)->timestamp,
            Carbon::parse($refreshToken->expires_at)->timestamp,
            1,
        );

        Carbon::setTestNow(null);
    }

    public function test_issue_token_pair_expires_in_is_86400_seconds(): void
    {
        $user = User::factory()->create();

        $result = $this->service->issueTokenPair($user);

        // expires_in = access TTL en secondes = 24h * 60min * 60s
        $this->assertEquals(86400, $result['expires_in']);
    }

    public function test_issue_token_pair_logs_emission(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('api.token.issued', \Mockery::on(function (array $context) use (&$user) {
                return isset($context['user_id'], $context['ip'], $context['timestamp']);
            }));

        $user = User::factory()->create();
        $this->service->issueTokenPair($user);
    }

    // -------------------------------------------------------------------------
    // revokeAccessToken
    // -------------------------------------------------------------------------

    public function test_revoke_access_token_deletes_current_token(): void
    {
        $user = User::factory()->create();
        $tokenResult = $user->createToken('access', ['*']);

        // Simuler l'authentification avec ce token
        $this->actingAs($user, 'sanctum');

        // Forcer le currentAccessToken à pointer sur notre token
        $token = $tokenResult->accessToken;
        $user->withAccessToken($token);

        $this->service->revokeAccessToken($user);

        $this->assertNull($user->tokens()->where('id', $token->id)->first());
    }

    public function test_revoke_access_token_logs_revocation(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('api.token.revoked', \Mockery::on(function (array $context) {
                return isset($context['user_id'], $context['ip'], $context['timestamp']);
            }));

        $user = User::factory()->create();
        $tokenResult = $user->createToken('access', ['*']);
        $user->withAccessToken($tokenResult->accessToken);

        $this->service->revokeAccessToken($user);
    }

    // -------------------------------------------------------------------------
    // revokeAllTokens
    // -------------------------------------------------------------------------

    public function test_revoke_all_tokens_deletes_all_user_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('access', ['*']);
        $user->createToken('refresh', ['refresh']);
        $user->createToken('access', ['*']); // second access token

        $this->assertCount(3, $user->tokens()->get());

        $this->service->revokeAllTokens($user);

        $this->assertCount(0, $user->tokens()->get());
    }

    public function test_revoke_all_tokens_only_affects_targeted_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $userA->createToken('access', ['*']);
        $userB->createToken('access', ['*']);
        $userB->createToken('refresh', ['refresh']);

        $this->service->revokeAllTokens($userA);

        $this->assertCount(0, $userA->tokens()->get());
        $this->assertCount(2, $userB->tokens()->get());
    }

    public function test_revoke_all_tokens_logs_revocation(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('api.token.revoked_all', \Mockery::on(function (array $context) {
                return isset($context['user_id'], $context['ip'], $context['timestamp']);
            }));

        $user = User::factory()->create();
        $user->createToken('access', ['*']);

        $this->service->revokeAllTokens($user);
    }

    // -------------------------------------------------------------------------
    // refreshAccessToken
    // -------------------------------------------------------------------------

    public function test_refresh_access_token_returns_new_access_token(): void
    {
        $user = User::factory()->create();
        $user->createToken('access', ['*']);

        $result = $this->service->refreshAccessToken($user);

        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('expires_in', $result);
        $this->assertIsString($result['access_token']);
        $this->assertEquals(86400, $result['expires_in']);
    }

    public function test_refresh_access_token_revokes_old_access_tokens(): void
    {
        $user = User::factory()->create();
        $oldToken = $user->createToken('access', ['*'])->accessToken;

        $this->service->refreshAccessToken($user);

        // L'ancien token d'accès doit être supprimé
        $this->assertNull($user->tokens()->where('id', $oldToken->id)->first());
        // Un nouveau token d'accès doit exister
        $this->assertCount(1, $user->tokens()->where('name', 'access')->get());
    }

    public function test_refresh_access_token_preserves_refresh_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('access', ['*']);
        $refreshToken = $user->createToken('refresh', ['refresh'])->accessToken;

        $this->service->refreshAccessToken($user);

        // Le refresh token ne doit pas être supprimé
        $this->assertNotNull($user->tokens()->where('id', $refreshToken->id)->first());
    }

    public function test_refresh_access_token_logs_refresh(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('api.token.refreshed', \Mockery::on(function (array $context) {
                return isset($context['user_id'], $context['ip'], $context['timestamp']);
            }));

        $user = User::factory()->create();
        $user->createToken('access', ['*']);

        $this->service->refreshAccessToken($user);
    }

    public function test_refresh_access_token_new_token_has_wildcard_ability(): void
    {
        $user = User::factory()->create();
        $user->createToken('access', ['*']);

        $this->service->refreshAccessToken($user);

        $newAccess = $user->tokens()->where('name', 'access')->first();
        $this->assertNotNull($newAccess);
        $this->assertContains('*', $newAccess->abilities);
    }
}
