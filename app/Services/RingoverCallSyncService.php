<?php

namespace App\Services;

use App\Enums\EventResult;
use App\Enums\EventType;
use App\Models\Appel;
use App\Models\Client;
use App\Models\Opportunite;
use App\Models\Partenaire;
use App\Models\Prospect;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class RingoverCallSyncService
{
    public function __construct(
        private readonly RingoverTagService $tags,
        private readonly RingoverUserMapper $users,
    ) {}

    /**
     * @return array{appel: Appel, created: bool, tag_validation: array<string, mixed>}
     */
    public function sync(array $call, ?Collection $ringoverUsers = null, string $source = 'sync'): array
    {
        $call = $this->unwrapCallPayload($call);
        $callId = $this->extractCallId($call);

        if (! $callId) {
            throw new InvalidArgumentException('Identifiant appel Ringover manquant.');
        }

        $tagValidation = $this->tags->analyze($call);
        $ringoverUser = $this->extractRingoverUser($call, $ringoverUsers);
        $ringoverUserId = $this->extractRingoverUserId($call, $ringoverUser);
        $ringoverEmail = $this->extractRingoverUserEmail($call, $ringoverUser);
        $localUser = $this->users->resolve($ringoverUserId, $ringoverEmail, $ringoverUser);
        $localTarget = $this->resolveLocalTarget($call);

        $appel = Appel::query()->firstOrNew(['ringover_call_id' => $callId]);
        $created = ! $appel->exists;

        if ($created && $localTarget) {
            $appel->appelable_type = get_class($localTarget);
            $appel->appelable_id = $localTarget->id;
        }

        $appel->fill([
            'ringover_user_id' => $ringoverUserId,
            'ringover_number_id' => $this->stringValue(data_get($call, 'number.id') ?? data_get($call, 'number_id')),
            'ringover_agent_nom' => $this->agentName($localUser, $ringoverUser, $ringoverUserId),
            'user_id' => $localUser?->id,
            'type' => $this->eventType($call),
            'resultat' => $this->mapStatus((string) (data_get($call, 'status') ?? data_get($call, 'state') ?? '')),
            'date_heure' => $this->startedAt($call),
            'duree_secondes' => $this->integerValue(data_get($call, 'duration') ?? data_get($call, 'duration_in_seconds')),
            'direction' => $this->stringValue(data_get($call, 'direction') ?? data_get($call, 'type')),
            'numero_appelant' => $this->extractPhoneNumber($call),
            'enregistrement_audio' => $this->stringValue(
                data_get($call, 'recording')
                    ?? data_get($call, 'recording_url')
                    ?? data_get($call, 'record.url')
            ),
            'commentaire' => $this->extractComment($call),
            'ringover_tags' => $tagValidation['tags'],
            'ringover_department_tag' => $tagValidation['department_tag'], 
            'ringover_status_tag' => $tagValidation['status_tag'],
            'ringover_tag_validation' => $tagValidation,
            'ringover_tag_is_complete' => $tagValidation['complete'],
            'ringover_payload' => $call,
            'ringover_synced_at' => now(),
            'ringover_webhook_received_at' => $source === 'webhook' ? now() : $appel->ringover_webhook_received_at,
            'ringover_sync_source' => $source,
        ]);

        if ($tagValidation['status_code'] && ! $appel->phoning_completed_at) {
            // On ne pose le statut phoning que si aucun statut n'a déjà été
            // finalisé (par un agent via PhoningWorkflow ou par un webhook
            // Ringover précédent). Une fois phoning_completed_at posé, plus
            // aucun webhook ultérieur ne doit pouvoir l'écraser.
            $appel->phoning_status = $tagValidation['status_code'];
            $appel->phoning_result = $tagValidation['status_label'] ?? $tagValidation['status_code'];
            $appel->phoning_completed_at = now();
            $appel->phoning_agent_id = $localUser?->id ?? $appel->phoning_agent_id;
            $appel->phoning_notes = $appel->phoning_notes ?? $appel->commentaire;
        }

        $appel->save();

        return [
            'appel' => $appel->refresh(),
            'created' => $created,
            'tag_validation' => $tagValidation,
        ];
    }

    public function unwrapCallPayload(array $payload): array
    {
        foreach (['call', 'data.call', 'data', 'resource.call', 'resource'] as $path) {
            $candidate = data_get($payload, $path);

            if (is_array($candidate) && $this->extractCallId($candidate)) {
                return $candidate;
            }
        }

        return $payload;
    }

    public function extractCallId(array $call): ?string
    {
        foreach (['id', 'call_id', 'uuid', 'uuid_call', 'ringover_call_id'] as $path) {
            $value = data_get($call, $path);

            if (filled($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    private function extractRingoverUser(array $call, ?Collection $ringoverUsers): ?array
    {
        $user = data_get($call, 'user') ?? data_get($call, 'agent') ?? data_get($call, 'owner');

        if (is_array($user)) {
            return $user;
        }

        $userId = $this->extractRingoverUserId($call);

        if ($userId && $ringoverUsers) {
            $ringoverUser = $ringoverUsers->get($userId) ?? $ringoverUsers->get((int) $userId);

            return is_array($ringoverUser) ? $ringoverUser : null;
        }

        return null;
    }

    private function extractRingoverUserId(array $call, ?array $ringoverUser = null): ?string
    {
        foreach (['user.id', 'user_id', 'agent.id', 'agent_id', 'owner.id'] as $path) {
            $value = data_get($call, $path);

            if (filled($value)) {
                return (string) $value;
            }
        }

        return $ringoverUser ? $this->users->extractUserId($ringoverUser) : null;
    }

    private function extractRingoverUserEmail(array $call, ?array $ringoverUser): ?string
    {
        foreach (['user.email', 'user.mail', 'agent.email', 'agent.mail', 'owner.email'] as $path) {
            $value = data_get($call, $path);

            if (filled($value)) {
                return strtolower((string) $value);
            }
        }

        return $ringoverUser ? $this->users->extractUserEmail($ringoverUser) : null;
    }

    private function agentName(?\App\Models\User $localUser, ?array $ringoverUser, ?string $ringoverUserId): ?string
    {
        if ($localUser) {
            return trim("{$localUser->prenom} {$localUser->nom}");
        }

        if ($ringoverUser) {
            return $this->users->extractUserName($ringoverUser) ?? ($ringoverUserId ? "Agent #{$ringoverUserId}" : null);
        }

        return $ringoverUserId ? "Agent #{$ringoverUserId}" : null;
    }

    private function eventType(array $call): EventType
    {
        return (data_get($call, 'direction') ?? data_get($call, 'type')) === 'inbound'
            ? EventType::Permanence
            : EventType::Appel;
    }

    private function mapStatus(string $ringoverStatus): ?EventResult
    {
        return match ($ringoverStatus) {
            'answered', 'done' => EventResult::Realise,
            'missed_customer', 'missed' => EventResult::NonAbouti,
            'voicemail' => EventResult::Rappel,
            'blocked', 'abandoned' => EventResult::Annule,
            default => null,
        };
    }

    private function startedAt(array $call): Carbon
    {
        $value = data_get($call, 'started_at')
            ?? data_get($call, 'start_time')
            ?? data_get($call, 'start')
            ?? data_get($call, 'date')
            ?? data_get($call, 'created_at');

        if (is_numeric($value)) {
            return Carbon::createFromTimestamp((int) $value);
        }

        if (filled($value)) {
            return Carbon::parse((string) $value);
        }

        return now();
    }

    private function extractComment(array $call): ?string
    {
        $comment = data_get($call, 'comments.0.content')
            ?? data_get($call, 'comments.0.text')
            ?? data_get($call, 'comment')
            ?? data_get($call, 'notes');

        return $this->stringValue($comment);
    }

    private function extractPhoneNumber(array $call): ?string
    {
        $direction = data_get($call, 'direction') ?? data_get($call, 'type');

        // Le numéro du contact dépend du sens de l'appel :
        // outbound → le contact est le destinataire (to_number)
        // inbound  → le contact est l'appelant (from_number)
        $directionalPaths = $direction === 'inbound'
            ? ['from_number', 'from', 'caller.number']
            : ['to_number', 'to', 'callee.number'];

        $fallbackPaths = $direction === 'inbound'
            ? ['to_number', 'to', 'callee.number']
            : ['from_number', 'from', 'caller.number'];

        foreach (
            [
                'raw_digits',
                'phone_number',
                'contact_number',
                ...$directionalPaths,
                'contact.phone',
                ...$fallbackPaths,
            ] as $path
        ) {
            $value = data_get($call, $path);

            if (filled($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    private function resolveLocalTarget(array $call): ?Model
    {
        $phone = $this->normalizePhone($this->extractPhoneNumber($call));

        if (! $phone) {
            return null;
        }

        foreach ($this->phoneFieldsByModel() as $modelClass => $fields) {
            /** @var class-string<Model> $modelClass */
            $records = $modelClass::query()
                ->where(function ($query) use ($fields): void {
                    foreach ($fields as $field) {
                        $query->orWhereNotNull($field);
                    }
                })
                ->latest('updated_at')
                ->limit(1000)
                ->get();

            foreach ($records as $record) {
                foreach ($fields as $field) {
                    if ($this->normalizePhone($record->{$field} ?? null) === $phone) {
                        return $record;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return array<class-string<Model>, list<string>>
     */
    private function phoneFieldsByModel(): array
    {
        return [
            Prospect::class => [
                'telephone',
                'telephone_alt',
                'interlocuteur_telephone',
                'cse_secretaire_tel_direct',
                'cse_secretaire_tel_perso',
                'cse_tresorier_tel_direct',
                'cse_tresorier_tel_perso',
                'syndicat_tel_direct',
                'syndicat_tel_perso',
                'dirigeant_telephone',
            ],
            Partenaire::class => [
                'telephone',
                'cse_secretaire_tel_direct',
                'cse_secretaire_tel_perso',
                'cse_tresorier_tel_direct',
                'cse_tresorier_tel_perso',
                'syndicat_tel_direct',
                'syndicat_tel_perso',
                'dirigeant_telephone',
            ],
            Client::class => ['telephone'],
            Opportunite::class => ['telephone', 'interlocuteur_telephone'],
        ];
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?: '';

        if (str_starts_with($digits, '33') && strlen($digits) === 11) {
            $digits = '0' . substr($digits, 2);
        }

        return $digits !== '' ? $digits : null;
    }

    private function stringValue(mixed $value): ?string
    {
        return filled($value) ? (string) $value : null;
    }

    private function integerValue(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
