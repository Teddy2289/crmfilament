<div>
    @if ($show && !empty($eventData))
        <div
            x-data
            x-on:keydown.escape.window="$wire.close()"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
        >
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="relative z-10 w-full max-w-lg rounded-3xl bg-white dark:bg-gray-900 shadow-2xl ring-1 ring-black/10 overflow-hidden"
            >
                {{-- Header avec dégradé --}}
                <div class="relative px-6 pt-6 pb-4">
                    <div class="absolute inset-0 opacity-10" style="background: linear-gradient(135deg, {{ $eventData['calendar_color'] }} 0%, transparent 100%);"></div>
                    
                    <div class="relative flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            @if ($eventData['calendar_name'])
                                <div class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold mb-3 ring-1 ring-inset"
                                    style="background-color: {{ $eventData['calendar_color'] }}15; color: {{ $eventData['calendar_color'] }}; ring-color: {{ $eventData['calendar_color'] }}30;">
                                    <span class="h-1.5 w-1.5 rounded-full animate-pulse" style="background-color: {{ $eventData['calendar_color'] }}"></span>
                                    {{ $eventData['calendar_name'] }}
                                </div>
                            @endif
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">
                                {{ $eventData['title'] }}
                            </h2>
                        </div>
                        <button wire:click="close" class="shrink-0 rounded-full p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 transition-all hover:rotate-90">
                            <x-heroicon-o-x-mark class="h-6 w-6" />
                        </button>
                    </div>
                </div>

                {{-- Détails --}}
                <div class="px-6 pb-6 space-y-4">
                    @if ($eventData['start'])
                        @php
                            $evStart = \Carbon\Carbon::parse($eventData['start'])->locale('fr');
                            $evEnd = $eventData['end'] ? \Carbon\Carbon::parse($eventData['end'])->locale('fr') : null;
                            $isAllDay = $eventData['allDay'] ?? false;
                        @endphp
                        <div class="flex items-center gap-4 text-sm">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 shadow-lg shadow-blue-500/25">
                                <x-heroicon-o-clock class="h-5 w-5 text-white" />
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 dark:text-white capitalize">
                                    {{ $evStart->isoFormat('dddd D MMMM YYYY') }}
                                </p>
                                @if (!$isAllDay)
                                    <p class="text-gray-500 dark:text-gray-400">
                                        {{ $evStart->format('H:i') }} @if ($evEnd) – {{ $evEnd->format('H:i') }} @endif
                                    </p>
                                @else
                                    <p class="text-gray-500 dark:text-gray-400">Toute la journée</p>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if ($eventData['location'])
                        <div class="flex items-center gap-4 text-sm">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 shadow-lg shadow-emerald-500/25">
                                <x-heroicon-o-map-pin class="h-5 w-5 text-white" />
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ $eventData['location'] }}
                                </p>
                            </div>
                        </div>
                    @endif

                    @if ($eventData['description'])
                        <div class="flex items-start gap-4 text-sm">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 shadow-lg shadow-purple-500/25">
                                <x-heroicon-o-document-text class="h-5 w-5 text-white" />
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 dark:text-white mb-1">Description</p>
                                <p class="text-gray-600 dark:text-gray-300 whitespace-pre-line leading-relaxed">{{ $eventData['description'] }}</p>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-between gap-3 px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-800">
                    <button wire:click="close" class="flex-1 rounded-xl px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-all">
                        Fermer
                    </button>
                    @if ($eventData['google_id'])
                        <a href="https://calendar.google.com/calendar/r/eventedit/{{ $eventData['google_id'] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex-1 flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 shadow-lg shadow-blue-500/25 transition-all hover:shadow-blue-500/40">
                            <x-heroicon-o-arrow-top-right-on-square class="h-4 w-4" />
                            Ouvrir dans Google
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

