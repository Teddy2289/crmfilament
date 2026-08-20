@php
    $mailboxes = $this->getActiveMailboxes();
    $envFrom = $this->getEnvFromAddress();
@endphp

<x-filament-widgets::widget>
    <div class="rounded-xl border border-primary-200 bg-primary-50 px-5 py-4 dark:border-primary-800 dark:bg-primary-950/40">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300">
                    <x-heroicon-o-envelope class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-950 dark:text-white">Boîte mail utilisée pour les envois</p>
                    @if (count($mailboxes) === 1)
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                            {{ $mailboxes[0]['email'] }}
                            @if (filled($mailboxes[0]['name']))
                                <span class="text-gray-500 dark:text-gray-400">· {{ $mailboxes[0]['name'] }}</span>
                            @endif
                        </p>
                    @elseif (count($mailboxes) > 1)
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ count($mailboxes) }} boîtes actives</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($mailboxes as $mailbox)
                                <span class="rounded-md bg-white px-2 py-1 text-xs font-medium text-gray-700 ring-1 ring-gray-200 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-700">
                                    {{ $mailbox['email'] }}
                                </span>
                            @endforeach
                        </div>
                    @elseif ($envFrom)
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">Configuration générale : {{ $envFrom }}</p>
                    @else
                        <p class="mt-1 text-sm text-warning-700 dark:text-warning-300">Aucune boîte active configurée.</p>
                    @endif
                </div>
            </div>
            <span class="inline-flex w-fit items-center rounded-full bg-success-100 px-2.5 py-1 text-xs font-medium text-success-700 dark:bg-success-900/40 dark:text-success-300">
                {{ count($mailboxes) === 1 ? 'Sélection automatique' : (count($mailboxes) > 1 ? 'Boîtes disponibles' : 'À configurer') }}
            </span>
        </div>
    </div>
</x-filament-widgets::widget>
