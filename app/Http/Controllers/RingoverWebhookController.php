<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessRingoverWebhook;
use App\Services\RingoverCallSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RingoverWebhookController extends Controller
{
    public function __invoke(Request $request, RingoverCallSyncService $sync): JsonResponse
    {
        if (! $this->isAuthorized($request)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $payload = $request->all();

        $result = $sync->sync($payload, source: 'webhook');

        return response()->json([
            'status' => 'ok',
            'created' => $result['created'],
            'ringover_call_id' => $result['appel']->ringover_call_id,
            'tags_complete' => $result['tag_validation']['complete'],
            'message' => 'Webhook processed successfully',
        ]);
    }

    private function isAuthorized(Request $request): bool
    {
        $secret = config('ringover.webhook_secret');

        if (blank($secret)) {
            return true;
        }

        $candidate = $request->header('X-Ringover-Webhook-Secret')
            ?? $request->header('X-Webhook-Secret')
            ?? $request->bearerToken()
            ?? $request->query('secret');

        return is_string($candidate) && hash_equals((string) $secret, $candidate);
    }
}
