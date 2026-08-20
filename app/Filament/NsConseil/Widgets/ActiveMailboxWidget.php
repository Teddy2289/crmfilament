<?php

namespace App\Filament\NsConseil\Widgets;

use App\Models\EmailConfiguration;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class ActiveMailboxWidget extends Widget
{
    protected static string $view = 'filament.ns-conseil.widgets.active-mailbox';

    protected int|string|array $columnSpan = 'full';

    /** @return array<int, array{email:string, name:?string, global:bool}> */
    public function getActiveMailboxes(): array
    {
        $user = Auth::user();
        if (! $user) {
            return [];
        }

        return EmailConfiguration::query()
            ->forUser($user->id)
            ->active()
            ->get(['email', 'from_name', 'is_global'])
            ->map(fn (EmailConfiguration $config): array => [
                'email' => (string) $config->email,
                'name' => $config->from_name,
                'global' => (bool) $config->is_global,
            ])
            ->values()
            ->all();
    }

    public function getEnvFromAddress(): ?string
    {
        $address = config('mail.from.address');
        return is_string($address) && filter_var($address, FILTER_VALIDATE_EMAIL)
            ? $address
            : null;
    }
}
