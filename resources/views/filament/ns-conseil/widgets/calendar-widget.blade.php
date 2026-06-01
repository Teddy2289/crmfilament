{{-- Modal détail événement Google --}}
@if ($showEventModal && !empty($selectedEvent))
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
        x-data
        x-on:keydown.escape.window="$wire.closeEventModal()">
        <div class="absolute inset-0 bg-black/50" wire:click="closeEventModal"></div>
        <div class="relative z-10 w-full max-w-md rounded-2xl bg-white dark:bg-gray-900 shadow-2xl overflow-hidden">
            <div class="h-2 w-full" style="background-color: {{ $selectedEvent['calendar_color'] }}"></div>
            <div class="flex items-start justify-between gap-4 px-5 pt-5 pb-3">
                <div class="flex items-start gap-3 min-w-0">
                    <span class="mt-1 h-3 w-3 rounded-full shrink-0" style="background-color: {{ $selectedEvent['calendar_color'] }}"></span>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white leading-snug">{{ $selectedEvent['title'] }}</h2>
                </div>
                <button wire:click="closeEventModal" class="shrink-0 rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                    <x-heroicon-o-x-mark class="h-4 w-4" />
                </button>
            </div>
            <div class="px-5 pb-5 space-y-3">
                @if ($selectedEvent['start'])
                    @php
                        $evStart = \Carbon\Carbon::parse($selectedEvent['start'])->locale('fr');
                        $evEnd = $selectedEvent['end'] ? \Carbon\Carbon::parse($selectedEvent['end'])->locale('fr') : null;
                        $isAllDay = $selectedEvent['allDay'] ?? false;
                    @endphp
                    <div class="flex items-center gap-2.5 text-sm text-gray-600 dark:text-gray-300">
                        <x-heroicon-o-clock class="h-4 w-4 shrink-0 text-gray-400" />
                        <span>
                            {{ $evStart->isoFormat('dddd D MMMM') }}
                            @if (!$isAllDay)
                                · {{ $evStart->format('H:i') }}
                                @if ($evEnd) – {{ $evEnd->format('H:i') }} @endif
                            @endif
                        </span>
                    </div>
                @endif
                @if ($selectedEvent['calendar_name'])
                    <div class="flex items-center gap-2.5 text-sm text-gray-600 dark:text-gray-300">
                        <x-heroicon-o-calendar-days class="h-4 w-4 shrink-0 text-gray-400" />
                        <span>{{ $selectedEvent['calendar_name'] }}</span>
                    </div>
                @endif
                @if ($selectedEvent['location'])
                    <div class="flex items-start gap-2.5 text-sm text-gray-600 dark:text-gray-300">
                        <x-heroicon-o-map-pin class="h-4 w-4 shrink-0 text-gray-400 mt-0.5" />
                        <span>{{ $selectedEvent['location'] }}</span>
                    </div>
                @endif
                @if ($selectedEvent['description'])
                    <div class="flex items-start gap-2.5 text-sm text-gray-600 dark:text-gray-300">
                        <x-heroicon-o-document-text class="h-4 w-4 shrink-0 text-gray-400 mt-0.5" />
                        <p class="whitespace-pre-line leading-relaxed">{{ $selectedEvent['description'] }}</p>
                    </div>
                @endif
            </div>
            <div class="flex justify-end gap-2 px-5 py-3 border-t border-gray-100 dark:border-gray-800">
                <button wire:click="closeEventModal" class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                    Fermer
                </button>
                @if ($selectedEvent['google_id'])
                    <a href="https://calendar.google.com/calendar/r/eventedit/{{ $selectedEvent['google_id'] }}"
                        target="_blank"
                        class="rounded-lg px-4 py-2 text-sm font-medium bg-primary-600 text-white hover:bg-primary-500 transition">
                        Ouvrir dans Google
                    </a>
                @endif
            </div>
        </div>
    </div>
@endif
