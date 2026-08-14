<?php

namespace Tests\Feature;

use App\Models\Prospect;
use App\Models\User;
use App\Services\Phoning\FicheLockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhoningFicheLockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Créer la table des verrous
        \Schema::create('phoning_fiche_locks', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->morphs('lockable');
            $table->foreignId('locked_by_user_id')->constrained('users')->onDelete('cascade');
            $table->dateTime('locked_at')->useCurrent();
            $table->dateTime('heartbeat_at')->useCurrent();
            $table->timestamps();
            $table->index(['lockable_type', 'lockable_id']);
            $table->index('locked_by_user_id');
        });
    }

    public function test_user_can_acquire_lock_on_fiche()
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create();

        $this->actingAs($user);

        $service = app(FicheLockService::class);
        $result = $service->acquireLock($prospect);

        $this->assertTrue($result['success']);
        $this->assertNull($result['locked_by']);
        $this->assertDatabaseHas('phoning_fiche_locks', [
            'lockable_type' => Prospect::class,
            'lockable_id' => $prospect->id,
            'locked_by_user_id' => $user->id,
        ]);
    }

    public function test_second_user_cannot_acquire_lock()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $prospect = Prospect::factory()->create();

        // User 1 acquires lock
        $this->actingAs($user1);
        $service1 = app(FicheLockService::class);
        $result1 = $service1->acquireLock($prospect);
        $this->assertTrue($result1['success']);

        // User 2 tries to acquire lock
        $this->actingAs($user2);
        $service2 = app(FicheLockService::class);
        $result2 = $service2->acquireLock($prospect);

        $this->assertFalse($result2['success']);
        $this->assertNotNull($result2['locked_by']);
        $this->assertEquals($user1->id, $result2['locked_by']->id);
    }

    public function test_user_can_renew_own_lock()
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create();

        $this->actingAs($user);
        $service = app(FicheLockService::class);

        // Acquire lock
        $result1 = $service->acquireLock($prospect);
        $this->assertTrue($result1['success']);

        // Renew lock
        $result2 = $service->acquireLock($prospect);
        $this->assertTrue($result2['success']);

        // Verify only one lock exists
        $locks = \DB::table('phoning_fiche_locks')
            ->where('lockable_type', Prospect::class)
            ->where('lockable_id', $prospect->id)
            ->count();

        $this->assertEquals(1, $locks);
    }

    public function test_user_can_release_lock()
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create();

        $this->actingAs($user);
        $service = app(FicheLockService::class);

        // Acquire and release lock
        $service->acquireLock($prospect);
        $service->releaseLock($prospect);

        $this->assertDatabaseMissing('phoning_fiche_locks', [
            'lockable_type' => Prospect::class,
            'lockable_id' => $prospect->id,
        ]);
    }

    public function test_expired_locks_are_released()
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create();

        $this->actingAs($user);
        $service = app(FicheLockService::class);

        // Acquire lock
        $service->acquireLock($prospect);

        // Manually set heartbeat to past (16 minutes ago)
        \DB::table('phoning_fiche_locks')
            ->where('lockable_type', Prospect::class)
            ->where('lockable_id', $prospect->id)
            ->update(['heartbeat_at' => now()->subMinutes(16)]);

        // Release expired locks
        $service->releaseExpiredLocks();

        // Verify lock is gone
        $this->assertDatabaseMissing('phoning_fiche_locks', [
            'lockable_type' => Prospect::class,
            'lockable_id' => $prospect->id,
        ]);
    }

    public function test_get_lock_info()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $prospect = Prospect::factory()->create();

        // User1 acquires lock
        $this->actingAs($user1);
        $service = app(FicheLockService::class);
        $service->acquireLock($prospect);

        // User1 checks lock info
        $info1 = $service->getLockInfo($prospect);
        $this->assertTrue($info1['is_locked']);
        $this->assertTrue($info1['is_own_lock']);
        $this->assertEquals($user1->id, $info1['locked_by']->id);

        // User2 checks lock info
        $this->actingAs($user2);
        $service2 = app(FicheLockService::class);
        $info2 = $service2->getLockInfo($prospect);
        $this->assertTrue($info2['is_locked']);
        $this->assertFalse($info2['is_own_lock']);
        $this->assertEquals($user1->id, $info2['locked_by']->id);
    }
}
