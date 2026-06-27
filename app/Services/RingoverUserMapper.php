<?php

namespace App\Services;

use App\Models\User;

class RingoverUserMapper
{
    public function resolve(?string $ringoverUserId, ?string $ringoverEmail = null, ?array $ringoverUser = null): ?User
    {
        $ringoverUserId = filled($ringoverUserId) ? (string) $ringoverUserId : null;
        $ringoverEmail = filled($ringoverEmail) ? strtolower((string) $ringoverEmail) : null;

        $user = null;

        if ($ringoverUserId) {
            $user = User::query()->where('ringover_user_id', $ringoverUserId)->first();
        }

        if (! $user && $ringoverEmail) {
            $user = User::query()
                ->where('ringover_email', $ringoverEmail)
                ->orWhere('email', $ringoverEmail)
                ->first();
        }

        if (! $user) {
            return null;
        }

        $updates = [];

        if ($ringoverUserId && $user->ringover_user_id !== $ringoverUserId) {
            $updates['ringover_user_id'] = $ringoverUserId;
        }

        if ($ringoverEmail && $user->ringover_email !== $ringoverEmail) {
            $updates['ringover_email'] = $ringoverEmail;
        }

        if ($updates !== []) {
            $user->forceFill($updates)->save();
        }

        return $user->refresh();
    }

    /**
     * @return array{mapped: int, updated: int, unmatched: int}
     */
    public function syncFromRingoverUsers(array $ringoverUsers): array
    {
        $mapped = 0;
        $updated = 0;
        $unmatched = 0;

        foreach ($ringoverUsers as $ringoverUser) {
            if (! is_array($ringoverUser)) {
                continue;
            }

            $ringoverUserId = $this->extractUserId($ringoverUser);
            $ringoverEmail = $this->extractUserEmail($ringoverUser);

            $before = $ringoverUserId
                ? User::query()->where('ringover_user_id', $ringoverUserId)->first()
                : null;

            $user = $this->resolve($ringoverUserId, $ringoverEmail, $ringoverUser);

            if (! $user) {
                $unmatched++;
                continue;
            }

            $mapped++;

            if (! $before || $before->ringover_user_id !== $user->ringover_user_id || $before->ringover_email !== $user->ringover_email) {
                $updated++;
            }
        }

        return compact('mapped', 'updated', 'unmatched');
    }

    public function extractUserId(array $data): ?string
    {
        foreach (['id', 'user_id', 'ringover_user_id', 'uuid'] as $path) {
            $value = data_get($data, $path);

            if (filled($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    public function extractUserEmail(array $data): ?string
    {
        foreach (['email', 'mail', 'user_email', 'ringover_email'] as $path) {
            $value = data_get($data, $path);

            if (filled($value)) {
                return strtolower((string) $value);
            }
        }

        return null;
    }

    public function extractUserName(array $data): ?string
    {
        foreach (['name', 'display_name', 'full_name'] as $path) {
            $value = data_get($data, $path);

            if (filled($value)) {
                return (string) $value;
            }
        }

        $firstName = data_get($data, 'first_name') ?? data_get($data, 'firstname');
        $lastName = data_get($data, 'last_name') ?? data_get($data, 'lastname');
        $name = trim((string) $firstName.' '.(string) $lastName);

        return $name !== '' ? $name : null;
    }
}
