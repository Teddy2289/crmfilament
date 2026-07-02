<x-filament-panels::page>
    @if(! $isGoogleConnected)
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950 mb-4">
            <div class="flex items-center gap-3">
                <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-amber-500 shrink-0" />
                <div>
                    <p class="text-sm font-medium text-amber-800 dark:text-amber-200">Google Calendar non connecté</p>
                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">Connectez votre compte Google pour synchroniser automatiquement vos rendez-vous.</p>
                </div>
            </div>
        </div>
    @else
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-800 dark:bg-emerald-950 mb-4">
            <div class="flex items-center gap-2">
                <x-heroicon-o-check-circle class="h-4 w-4 text-emerald-500 shrink-0" />
                <p class="text-sm text-emerald-700 dark:text-emerald-300">Google Calendar connecté — les RDV sont synchronisés automatiquement.</p>
            </div>
        </div>
    @endif

    {{-- Légende RDV CRM --}}
    <div class="mb-2">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-wide">RDV CRM</p>
        <div class="flex flex-wrap gap-3">
            <span class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400"><span class="h-3 w-3 rounded-full bg-[#0ea5e9]"></span> Appel</span>
            <span class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400"><span class="h-3 w-3 rounded-full bg-[#10b981]"></span> Permanence</span>
            <span class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400"><span class="h-3 w-3 rounded-full bg-[#6366f1]"></span> Présentation</span>
            <span class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400"><span class="h-3 w-3 rounded-full bg-[#f97316]"></span> Intervention</span>
            <span class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400"><span class="h-3 w-3 rounded-full bg-[#9ca3af]"></span> Annulé</span>
        </div>
    </div>

    {{-- Légende Calendriers Google --}}
    <div class="mb-6">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-wide">Calendriers Google</p>
        <div class="flex flex-wrap gap-3">
            <span class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400"><span class="h-3 w-3 rounded-full" style="background:#b99aff"></span> Maxence DURAT</span>
            <span class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400"><span class="h-3 w-3 rounded-full" style="background:#f83a22"></span> Ghislaine GAUVILLE</span>
            <span class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400"><span class="h-3 w-3 rounded-full" style="background:#9a9cff"></span> Francis Thibaud</span>
            <span class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400"><span class="h-3 w-3 rounded-full" style="background:#9fc6e7"></span> Séverine Boutin</span>
            <span class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400"><span class="h-3 w-3 rounded-full" style="background:#16a765"></span> Jours fériés</span>
        </div>
    </div>

    {{-- Widget calendrier — rendu par Filament via getFooterWidgets() --}}
        @livewire('google-event-modal')

</x-filament-panels::page>
