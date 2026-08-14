<?php

namespace Tests\Feature;

use App\Models\Prospect;
use App\Models\User;
use App\Services\Phoning\FicheLockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class FicheLockServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FicheLockService $lockService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lockService = app(FicheLockService::class);
    }

    public function test_can_acquire_lock_on_fiche()
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create();

        $this->actingAs($user);

        $result = $this->lockService->acquireLock($prospect);

        $this->assertTrue($result['success']);
        $this->assertNull($result['locked_by']);
    }

    public function test_cannot_acquire_lock_if_already_locked_by_another_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $prospect = Prospect::factory()->create();

        // User 1 acquires the lock
        $this->actingAs($user1);
        $this->lockService->acquireLock($prospect);

        // User 2 tries to acquire the same lock
        $this->actingAs($user2);
        $result = $this->lockService->acquireLock($prospect);

        $this->assertFalse($result['success']);
        $this->assertNotNull($result['locked_by']);
        $this->assertEquals($user1->id, $result['locked_by']->id);
    }

    public function test_can_renew_own_lock()
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create();

        $this->actingAs($user);

        // First lock
        $result1 = $this->lockService->acquireLock($prospect);
        $this->assertTrue($result1['success']);

        // Try to lock again (should renew heartbeat)
        $result2 = $this->lockService->acquireLock($prospect);
        $this->assertTrue($result2['success']);
    }

    public function test_can_release_lock()
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create();

        $this->actingAs($user);

        $this->lockService->acquireLock($prospect);
        $this->lockService->releaseLock($prospect);

        // Another user should now be able to lock
        $user2 = User::factory()->create();
        $this->actingAs($user2);
        $result = $this->lockService->acquireLock($prospect);
        $this->assertTrue($result['success']);
    }

    public function test_get_lock_info()
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create();

        $this->actingAs($user);

        // No lock yet
        $info = $this->lockService->getLockInfo($prospect);
        $this->assertFalse($info['is_locked']);

        // Acquire lock
        $this->lockService->acquireLock($prospect);
        $info = $this->lockService->getLockInfo($prospect);
        $this->assertTrue($info['is_locked']);
        $this->assertTrue($info['is_own_lock']);
    }

    public function test_expired_locks_are_released()
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create();

        $this->actingAs($user);
        $this->lockService->acquireLock($prospect);

        // Simulate expired heartbeat (manually update the database)
        \DB::table('phoning_fiche_locks')
            ->where('lockable_type', Prospect::class)
            ->where('lockable_id', $prospect->id)
            ->update(['heartbeat_at' => now()->subMinutes(20)]);

        // Release expired locks
        $this->lockService->releaseExpiredLocks();

        // Another user should now be able to lock
        $user2 = User::factory()->create();
        $this->actingAs($user2);
        $result = $this->lockService->acquireLock($prospect);
        $this->assertTrue($result['success']);
    }
}
