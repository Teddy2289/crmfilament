<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallRinging implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly array $callData,
        public readonly ?string $userId = null
    ) {}

    public function broadcastOn(): array
    {
        $channels = [
            new Channel('ringover.calls'),
        ];

        if ($this->userId) {
            $channels[] = new PrivateChannel('user.'.$this->userId);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'call.ringing';
    }

    public function broadcastWith(): array
    {
        return [
            'call_id' => $this->callData['id'] ?? null,
            'from_number' => $this->callData['from_number'] ?? $this->callData['raw_digits'] ?? null,
            'to_number' => $this->callData['to_number'] ?? null,
            'direction' => $this->callData['direction'] ?? null,
            'user_id' => $this->callData['user']['id'] ?? $this->callData['user_id'] ?? null,
            'user_name' => $this->callData['user']['name'] ?? null,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
