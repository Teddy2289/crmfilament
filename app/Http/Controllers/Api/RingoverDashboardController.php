<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appel;
use App\Models\User;
use App\Services\RingoverService;
use App\Services\RingoverTagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RingoverDashboardController extends Controller
{
    public function getDashboardData(Request $request, RingoverService $ringoverService, RingoverTagService $tagService): JsonResponse
    {
        $user = $request->user();
        $isTelepro = $user && method_exists($user, 'hasRoleCache')
            ? $user->hasRoleCache('teleprospecteur')
            : ($user && ($user->role_cache ?? null) === 'teleprospecteur');
        $isManager = $user && in_array(($user->role_cache ?? null), ['admin', 'administrateur', 'superviseur', 'super_admin'], true);
        $userId = $request->integer('userId') ?: null;

        if ($isTelepro && ! $isManager) {
            $userId = (int) $user->getAuthIdentifier();
        }

        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        return response()->json([
            'connexionOk' => $ringoverService->testConnection(),
            'diagnostic' => $tagService->diagnostic($startDate, $endDate, $userId),
            'calls' => $this->localCalls($startDate, $endDate, $userId),
            'users' => $this->ringoverUsers($user, $isTelepro, $isManager),
            'config' => [
                'hasApiToken' => filled(config('ringover.api_token')),
                'hasWebhookSecret' => filled(config('ringover.webhook_secret')),
            ],
            'webhookUrl' => url('/api/ringover/webhook'),
        ]);
    }

    private function ringoverUsers(?User $user, bool $isTelepro, bool $isManager)
    {
        $query = User::query()->whereNotNull('ringover_user_id')->orderBy('nom')->orderBy('prenom');

        if ($isTelepro && ! $isManager && $user) {
            $query->whereKey($user->getAuthIdentifier());
        }

        return $query->get(['id', 'nom', 'prenom'])
            ->map(fn (User $item): array => [
                'id' => $item->id,
                'name' => trim($item->prenom.' '.$item->nom),
            ])->values();
    }

    private function localCalls(?string $startDate, ?string $endDate, ?int $userId): array
    {
        $query = Appel::query()
            ->with(['appelable', 'user'])
            ->whereNotNull('ringover_call_id')
            ->latest('date_heure')
            ->limit(100);

        if (filled($startDate)) {
            $query->whereDate('date_heure', '>=', $startDate);
        }
        if (filled($endDate)) {
            $query->whereDate('date_heure', '<=', $endDate);
        }
        if ($userId) {
            $query->where(function ($q) use ($userId): void {
                $q->where('user_id', $userId)->orWhere('phoning_agent_id', $userId);
            });
        }

        return $query->get()->map(fn (Appel $appel): array => $this->serializeCall($appel))->values()->all();
    }

    private function serializeCall(Appel $appel): array
    {
        $target = $appel->appelable;
        $targetType = match (true) {
            $target instanceof \App\Models\Prospect => 'prospect',
            $target instanceof \App\Models\Partenaire => 'partenaire',
            $target instanceof \App\Models\Client => 'client',
            default => null,
        };

        $targetName = $target
            ? trim((string) ($target->raison_sociale ?? $target->nom ?? $target->nom_tiers ?? $target->name ?? 'Fiche CRM'))
            : null;

        $targetUrl = match ($targetType) {
            'prospect' => route('filament.ns-conseil.resources.prospects.view', ['record' => $target->getKey()]),
            'partenaire' => route('filament.ns-conseil.resources.partenaires.view', ['record' => $target->getKey()]),
            'client' => route('filament.ns-conseil.resources.clients.view', ['record' => $target->getKey()]),
            default => null,
        };

        $phoningUrl = $targetType && $target
            ? route('filament.ns-conseil.pages.phoning-workflow', [
                'contact_id' => $target->getKey(),
                'contact_type' => $targetType,
            ])
            : null;

        return [
            'id' => $appel->getKey(),
            'ringover_call_id' => $appel->ringover_call_id,
            'date' => optional($appel->date_heure)->format('d/m/Y H:i'),
            'timestamp' => optional($appel->date_heure)->toIso8601String(),
            'direction' => $appel->direction,
            'status' => $appel->phoning_result ?: $appel->phoning_status ?: $appel->resultat_label ?: $appel->ringover_status_tag ?: 'Non défini',
            'status_code' => $appel->phoning_status,
            'ringover_status' => $appel->ringover_status_tag,
            'duration' => $appel->duree_formatee,
            'phone' => $this->formatPhone($appel->numero_appelant),
            'phone_normalized' => $this->normalizePhone($appel->numero_appelant),
            'agent' => ($appel->user ? trim(($appel->user->prenom ?? '').' '.($appel->user->nom ?? '')) : null) ?: ($appel->ringover_agent_nom ?? 'Non attribué'),
            'target' => $target ? [
                'type' => $targetType,
                'name' => $targetName ?: 'Fiche CRM',
                'id' => $target->getKey(),
                'url' => $targetUrl,
                'phoning_url' => $phoningUrl,
            ] : null,
        ];
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (! filled($phone)) {
            return null;
        }

        $digits = preg_replace('/\\D+/', '', (string) $phone) ?: '';
        if ($digits === '') {
            return null;
        }

        return str_starts_with($digits, '33') && strlen($digits) === 11
            ? '0'.substr($digits, 2)
            : $digits;
    }

    private function formatPhone(?string $phone): ?string
    {
        $raw = trim((string) $phone);
        if ($raw === '') {
            return null;
        }

        $digits = preg_replace('/\\D+/', '', $raw) ?: '';
        if ($digits === '') {
            return $raw;
        }

        if (str_starts_with($digits, '33') && strlen($digits) === 11) {
            $national = '0'.substr($digits, 2);
            return '+33 '.implode(' ', str_split(substr($national, 1), 2));
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            return implode(' ', str_split($digits, 2));
        }

        return $raw;
    }
}
