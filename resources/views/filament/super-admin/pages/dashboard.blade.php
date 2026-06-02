{{-- resources/views/filament/super-admin/pages/dashboard.blade.php --}}
<x-filament-panels::page>

@php
    $stats = $this->getStats();
    $roles = $this->getRolesDistribution();
    $recent = $this->getRecentUsers();
@endphp

{{-- ── KPI Cards ── --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['icon' => 'heroicon-o-users',         'label' => 'Utilisateurs',   'value' => $stats['users'],       'sub' => $stats['actifs'] . ' actifs',       'color' => 'violet'],
        ['icon' => 'heroicon-o-circle-stack',   'label' => 'Tables BDD',     'value' => $stats['tables'],      'sub' => $stats['db_size'],                  'color' => 'blue'],
        ['icon' => 'heroicon-o-shield-check',   'label' => 'Rôles',          'value' => $stats['roles'],       'sub' => $stats['permissions'] . ' permissions', 'color' => 'emerald'],
        ['icon' => 'heroicon-o-arrow-up-tray',  'label' => 'Imports',        'value' => $stats['imports'],     'sub' => 'logs d\'import',                   'color' => 'amber'],
    ] as $card)
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow ring-1 ring-gray-950/5 dark:ring-white/10 p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $card['value'] }}</div>
                <div class="p-2 rounded-lg bg-{{ $card['color'] }}-50 dark:bg-{{ $card['color'] }}-950">
                    <x-dynamic-component :component="$card['icon']" class="w-5 h-5 text-{{ $card['color'] }}-500" />
                </div>
            </div>
            <div class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $card['label'] }}</div>
            <div class="text-xs text-gray-400 mt-0.5">{{ $card['sub'] }}</div>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    {{-- ── Distribution des rôles ── --}}
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow ring-1 ring-gray-950/5 dark:ring-white/10 p-5">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <x-heroicon-o-shield-check class="w-4 h-4 text-violet-500" />
            Distribution des rôles
        </h3>
        <div class="space-y-2">
            @foreach($roles as $role)
                @php
                    $max = $roles->max('users_count') ?: 1;
                    $pct = $max > 0 ? round($role->users_count / $max * 100) : 0;
                    $color = match($role->name) {
                        'super_admin'    => 'bg-violet-500',
                        'administrateur' => 'bg-amber-500',
                        default          => 'bg-blue-400',
                    };
                @endphp
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-500 w-32 truncate font-mono">{{ $role->name }}</span>
                    <div class="flex-1 bg-gray-100 dark:bg-gray-800 rounded-full h-2 overflow-hidden">
                        <div class="{{ $color }} h-2 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                    </div>
                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 w-6 text-right">{{ $role->users_count }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ── Derniers utilisateurs ── --}}
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow ring-1 ring-gray-950/5 dark:ring-white/10 p-5">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <x-heroicon-o-user-plus class="w-4 h-4 text-emerald-500" />
            Derniers utilisateurs créés
        </h3>
        <div class="space-y-3">
            @foreach($recent as $user)
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-violet-100 dark:bg-violet-950 flex items-center justify-center text-violet-600 dark:text-violet-400 text-xs font-bold shrink-0">
                        {{ strtoupper(substr($user->prenom ?? '?', 0, 1)) }}{{ strtoupper(substr($user->nom ?? '?', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ trim($user->prenom . ' ' . $user->nom) }}
                        </div>
                        <div class="text-xs text-gray-400 truncate">{{ $user->email }}</div>
                    </div>
                    <div class="text-xs text-gray-400 shrink-0">{{ $user->created_at->diffForHumans() }}</div>
                </div>
            @endforeach
        </div>
    </div>

</div>

{{-- ── Accès rapides ── --}}
<div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-3">
    @foreach([
        ['href' => '/super-admin/users/create',    'icon' => 'heroicon-o-user-plus',    'label' => 'Nouvel utilisateur',   'color' => 'violet'],
        ['href' => '/super-admin/roles/create',    'icon' => 'heroicon-o-shield-check', 'label' => 'Nouveau rôle',         'color' => 'blue'],
        ['href' => '/super-admin/database-manager','icon' => 'heroicon-o-circle-stack', 'label' => 'Gestionnaire BDD',     'color' => 'emerald'],
        ['href' => '/super-admin/import-logs',     'icon' => 'heroicon-o-arrow-up-tray','label' => 'Logs d\'import',       'color' => 'amber'],
    ] as $link)
        <a href="{{ $link['href'] }}"
           class="flex items-center gap-3 p-4 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-{{ $link['color'] }}-300 hover:bg-{{ $link['color'] }}-50 dark:hover:bg-{{ $link['color'] }}-950/30 transition-colors group">
            <x-dynamic-component :component="$link['icon']" class="w-5 h-5 text-{{ $link['color'] }}-500 group-hover:scale-110 transition-transform" />
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $link['label'] }}</span>
        </a>
    @endforeach
</div>

</x-filament-panels::page>
