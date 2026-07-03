<?php

namespace App\Jobs;

use App\Events\CallAnswered;
use App\Events\CallHangup;
use App\Events\CallRinging;
use App\Services\RingoverCallSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ProcessRingoverWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array|int $backoff = [5, 10, 30];

    public function __construct(
        public readonly array $payload,
        public readonly string $source = 'webhook'
    ) {}

    public function handle(RingoverCallSyncService $sync): void
    {
        try {
            $result = $sync->sync($this->payload, source: $this->source);
            
            $this->broadcastEvent($this->payload, $result['appel']?->user_id);
            
            Log::info('Ringover webhook processed', [
                'call_id' => $result['appel']->ringover_call_id,
                'created' => $result['created'],
                'tags_complete' => $result['tag_validation']['complete'],
            ]);
        } catch (InvalidArgumentException $e) {
            Log::error('Ringover webhook validation error', [
                'error' => $e->getMessage(),
                'payload' => $this->payload,
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Ringover webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $this->payload,
            ]);
            throw $e;
        }
    }

    private function broadcastEvent(array $callData, ?string $userId): void
    {
        $status = $callData['status'] ?? $callData['state'] ?? null;
        
        match ($status) {
            'ringing' => broadcast(new CallRinging($callData, $userId))->toOthers(),
            'answered', 'done' => broadcast(new CallAnswered($callData, $userId))->toOthers(),
            'hangup', 'ended', 'completed' => broadcast(new CallHangup($callData, $userId))->toOthers(),
            default => null,
        };
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Ringover webhook job failed', [
            'error' => $exception->getMessage(),
            'payload' => $this->payload,
        ]);
    }
}
