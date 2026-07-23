<x-filament-panels::page>
    @push('styles')
    <style>
        /* Palette dérivée de OrganizationStatus::color() — CSS brut (pas de
           classes Tailwind) car le CSS Filament livré ici est précompilé et
           ne contient aucune classe utilitaire de couleur "brute" (bg-blue-500,
           border-orange-400...) : seul du CSS inline/custom est garanti de
           s'afficher, quel que soit le build front. */
        .pk-col-gray { background: rgb(243 244 246); border-color: rgb(229 231 235); }
        .dark .pk-col-gray { background: rgb(31 41 55 / .6); border-color: rgb(55 65 81); }
        .pk-col-blue { background: rgb(239 246 255); border-color: rgb(191 219 254); }
        .dark .pk-col-blue { background: rgb(30 58 138 / .18); border-color: rgb(30 64 175 / .4); }
        .pk-col-orange { background: rgb(255 247 237); border-color: rgb(254 215 170); }
        .dark .pk-col-orange { background: rgb(124 45 18 / .18); border-color: rgb(154 52 18 / .4); }
        .pk-col-green { background: rgb(240 253 244); border-color: rgb(187 247 208); }
        .dark .pk-col-green { background: rgb(5 46 22 / .25); border-color: rgb(22 101 52 / .4); }
        .pk-col-indigo { background: rgb(238 242 255); border-color: rgb(199 210 254); }
        .dark .pk-col-indigo { background: rgb(49 46 129 / .25); border-color: rgb(67 56 202 / .4); }
        .pk-col-red { background: rgb(254 242 242); border-color: rgb(254 202 202); }
        .dark .pk-col-red { background: rgb(127 29 29 / .25); border-color: rgb(153 27 27 / .4); }

        .pk-dot-gray { background: rgb(156 163 175); }
        .pk-dot-blue { background: rgb(59 130 246); }
        .pk-dot-orange { background: rgb(249 115 22); }
        .pk-dot-green { background: rgb(34 197 94); }
        .pk-dot-indigo { background: rgb(99 102 241); }
        .pk-dot-red { background: rgb(239 68 68); }

        .pk-badge-gray { background: rgb(229 231 235); color: rgb(55 65 81); }
        .dark .pk-badge-gray { background: rgb(55 65 81); color: rgb(209 213 219); }
        .pk-badge-blue { background: rgb(219 234 254); color: rgb(29 78 216); }
        .dark .pk-badge-blue { background: rgb(30 58 138 / .5); color: rgb(147 197 253); }
        .pk-badge-orange { background: rgb(255 237 213); color: rgb(154 52 18); }
        .dark .pk-badge-orange { background: rgb(124 45 18 / .5); color: rgb(253 186 116); }
        .pk-badge-green { background: rgb(220 252 231); color: rgb(22 101 52); }
        .dark .pk-badge-green { background: rgb(5 46 22 / .5); color: rgb(134 239 172); }
        .pk-badge-indigo { background: rgb(224 231 255); color: rgb(55 48 163); }
        .dark .pk-badge-indigo { background: rgb(49 46 129 / .5); color: rgb(165 180 252); }
        .pk-badge-red { background: rgb(254 226 226); color: rgb(153 27 27); }
        .dark .pk-badge-red { background: rgb(127 29 29 / .5); color: rgb(252 165 165); }

        .pk-card-gray { border-left-color: rgb(156 163 175); }
        .pk-card-blue { border-left-color: rgb(96 165 250); }
        .pk-card-orange { border-left-color: rgb(251 146 60); }
        .pk-card-green { border-left-color: rgb(74 222 128); }
        .pk-card-indigo { border-left-color: rgb(129 140 248); }
        .pk-card-red { border-left-color: rgb(248 113 113); }

        .pk-avatar { background: rgb(229 231 235); color: rgb(55 65 81); }
        .dark .pk-avatar { background: rgb(75 85 99); color: rgb(229 231 235); }
    </style>
    @endpush

    <div x-data wire:ignore.self class="md:flex overflow-x-auto overflow-y-hidden gap-4 pb-4">
        @foreach($statuses as $status)
            @include(static::$statusView)
        @endforeach

        <div wire:ignore>
            @include(static::$scriptsView)
        </div>
    </div>

    @unless($disableEditModal)
        <x-filament-kanban::edit-record-modal/>
    @endunless
</x-filament-panels::page>
