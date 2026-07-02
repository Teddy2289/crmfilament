<div>
    @if ($show && !empty($eventData))
        <div
            x-data
            x-on:keydown.escape.window="$wire.close()"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div class="absolute inset-0 bg-black/50" wire:click="close"></div>

            <div
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative z-10 w-full max-w-md rounded-2xl bg-white dark:bg-gray-900 shadow-2xl ring-1 ring-black/5 overflow-hidden"
            >
                {{-- Bandeau couleur --}}
                <div class="h-1.5 w-full" style="background-color: {{ $eventData['calendar_color'] }}"></div>

                {{-- Header --}}
                <div class="flex items-start justify-between gap-4 px-6 pt-5 pb-4 mt-6">
                    <div class="min-w-0">
                        @if ($eventData['calendar_name'])
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium mb-2"
                                style="background-color: {{ $eventData['calendar_color'] }}22; color: {{ $eventData['calendar_color'] }};">
                                <span class="h-1.5 w-1.5 rounded-full" style="background-color: {{ $eventData['calendar_color'] }}"></span>
                                {{ $eventData['calendar_name'] }}
                            </span>
                        @endif
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white leading-snug">
                            {{ $eventData['title'] }}
                        </h2>
                    </div>
                    <button wire:click="close" class="shrink-0 rounded-full p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 transition">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                {{-- Détails --}}
                <div class="px-6 pb-6 space-y-3 border-t border-gray-100 dark:border-gray-800 pt-4">
                    @if ($eventData['start'])
                        @php
                            $evStart = \Carbon\Carbon::parse($eventData['start'])->locale('fr');
                            $evEnd = $eventData['end'] ? \Carbon\Carbon::parse($eventData['end'])->locale('fr') : null;
                            $isAllDay = $eventData['allDay'] ?? false;
                        @endphp
                        <div class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-300">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#2891e7]/10">
                                <x-heroicon-o-clock class="h-4 w-4 text-[#2891e7]" />
                            </span>
                            <span class="capitalize">
                                {{ $evStart->isoFormat('dddd D MMMM') }}
                                @if (!$isAllDay)
                                    <span class="text-gray-400">·</span> {{ $evStart->format('H:i') }}
                                    @if ($evEnd) – {{ $evEnd->format('H:i') }} @endif
                                @endif
                            </span>
                        </div>
                    @endif

                    @if ($eventData['location'])
                        <div class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-300">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#2891e7]/10">
                                <x-heroicon-o-map-pin class="h-4 w-4 text-[#2891e7]" />
                            </span>
                            <span>{{ $eventData['location'] }}</span>
                        </div>
                    @endif

                    @if ($eventData['description'])
                        <div class="flex items-start gap-3 text-sm text-gray-700 dark:text-gray-300">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#2891e7]/10">
                                <x-heroicon-o-document-text class="h-4 w-4 text-[#2891e7]" />
                            </span>
                            <p class="whitespace-pre-line leading-relaxed pt-1.5">{{ $eventData['description'] }}</p>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="flex justify-end gap-2 px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-800">
                    <button wire:click="close" class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                        Fermer
                    </button>
                    @if ($eventData['google_id'])
                        <a href="https://calendar.google.com/calendar/r/eventedit/{{ $eventData['google_id'] }}"
                            target="_blank"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-white transition"
                            style="background-color: #2891e7;"
                            onmouseover="this.style.backgroundColor='#1f7bc9'"
                            onmouseout="this.style.backgroundColor='#2891e7'">
                            Ouvrir dans Google
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

