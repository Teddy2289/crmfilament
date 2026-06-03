{{-- resources/views/filament/super-admin/pages/dashboard.blade.php --}}
<x-filament-panels::page>

@php
    $stats  = $this->getStats();
    $roles  = $this->getRolesDistribution();
    $recent = $this->getRecentUsers();

    $cards = [
        ['icon' => 'heroicon-o-users',        'label' => 'Utilisateurs', 'value' => $stats['users'],   'sub' => $stats['actifs'].' actifs',          'color' => 'violet', 'trend' => '+12%', 'up' => true],
        ['icon' => 'heroicon-o-circle-stack',  'label' => 'Tables BDD',   'value' => $stats['tables'],  'sub' => $stats['db_size'],                   'color' => 'blue',   'trend' => null,   'up' => null],
        ['icon' => 'heroicon-o-shield-check',  'label' => 'Rôles',        'value' => $stats['roles'],   'sub' => $stats['permissions'].' permissions','color' => 'emerald','trend' => null,   'up' => null],
        ['icon' => 'heroicon-o-arrow-up-tray', 'label' => 'Imports',      'value' => $stats['imports'], 'sub' => "logs d'import",                     'color' => 'amber',  'trend' => null,   'up' => null],
    ];

    $links = [
        ['href' => '/super-admin/users/create',     'icon' => 'heroicon-o-user-plus',    'label' => 'Nouvel utilisateur', 'desc' => 'Créer un compte',      'color' => 'violet'],
        ['href' => '/super-admin/roles/create',     'icon' => 'heroicon-o-shield-check', 'label' => 'Nouveau rôle',       'desc' => 'Définir les accès',    'color' => 'blue'],
        ['href' => '/super-admin/database-manager', 'icon' => 'heroicon-o-circle-stack', 'label' => 'Gestionnaire BDD',   'desc' => 'Parcourir les tables', 'color' => 'emerald'],
        ['href' => '/super-admin/import-logs',      'icon' => 'heroicon-o-arrow-up-tray','label' => "Logs d'import",      'desc' => "Voir l'historique",    'color' => 'amber'],
    ];
@endphp

{{-- ── En-tête ─────────────────────────────────────────── --}}
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.75rem; flex-wrap:wrap; gap:.75rem;">
    <div>
        <h1 style="font-size:1.25rem; font-weight:700; color:var(--fi-color-gray-950, #030712); margin:0; line-height:1.4;">
            Vue d'ensemble
        </h1>
        <p style="font-size:.8125rem; color:#6b7280; margin:.2rem 0 0;">
            Supervision générale &mdash; {{ now()->translatedFormat('l d F Y') }}
        </p>
    </div>
    <span style="display:inline-flex; align-items:center; gap:.4rem; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:999px; padding:.3rem .75rem; font-size:.75rem; font-weight:600; color:#15803d;">
        <span style="position:relative; display:flex; width:.5rem; height:.5rem;">
            <span style="position:absolute; inset:0; border-radius:999px; background:#4ade80; opacity:.75; animation:ping 1s cubic-bezier(0,0,.2,1) infinite;"></span>
            <span style="position:relative; width:.5rem; height:.5rem; border-radius:999px; background:#22c55e; display:block;"></span>
        </span>
        Système opérationnel
    </span>
</div>

<style>
@keyframes ping { 75%,100%{transform:scale(2);opacity:0} }
</style>

{{-- ── KPI Cards — grille forcée en inline-grid ────────── --}}
<div style="display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1.75rem;">
    @foreach($cards as $card)
    @php
        $colors = [
            'violet' => ['bg'=>'#f5f3ff','icon_bg'=>'#ede9fe','icon_c'=>'#7c3aed','halo'=>'#ddd6fe','num'=>'#5b21b6'],
            'blue'   => ['bg'=>'#eff6ff','icon_bg'=>'#dbeafe','icon_c'=>'#2563eb','halo'=>'#bfdbfe','num'=>'#1d4ed8'],
            'emerald'=> ['bg'=>'#f0fdf4','icon_bg'=>'#dcfce7','icon_c'=>'#16a34a','halo'=>'#bbf7d0','num'=>'#15803d'],
            'amber'  => ['bg'=>'#fffbeb','icon_bg'=>'#fef3c7','icon_c'=>'#d97706','halo'=>'#fde68a','num'=>'#b45309'],
        ][$card['color']];
    @endphp
    <div style="position:relative; overflow:hidden; background:#fff; border-radius:1rem; border:1px solid #f3f4f6; box-shadow:0 1px 3px rgba(0,0,0,.06); padding:1.25rem; transition:box-shadow .15s, transform .15s;"
         onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,.1)';this.style.transform='translateY(-2px)'"
         onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,.06)';this.style.transform='none'">

        {{-- halo --}}
        <div style="position:absolute; top:-1.5rem; right:-1.5rem; width:5rem; height:5rem; border-radius:999px; background:{{ $colors['halo'] }}; opacity:.4; filter:blur(1.5rem); pointer-events:none;"></div>

        <div style="display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:1rem;">
            <div style="display:flex; width:2.5rem; height:2.5rem; align-items:center; justify-content:center; border-radius:.75rem; background:{{ $colors['icon_bg'] }};">
                <x-dynamic-component :component="$card['icon']" style="width:1.25rem; height:1.25rem; color:{{ $colors['icon_c'] }};"/>
            </div>
            @if($card['trend'])
            <span style="display:inline-flex; align-items:center; gap:.2rem; background:#f0fdf4; border-radius:999px; padding:.15rem .5rem; font-size:.7rem; font-weight:600; color:#16a34a;">
                <x-heroicon-m-arrow-trending-up style="width:.7rem; height:.7rem;"/>
                {{ $card['trend'] }}
            </span>
            @endif
        </div>

        <div style="font-size:1.875rem; font-weight:800; color:{{ $colors['num'] }}; line-height:1; font-variant-numeric:tabular-nums;">{{ $card['value'] }}</div>
        <div style="margin-top:.4rem; font-size:.875rem; font-weight:600; color:#374151;">{{ $card['label'] }}</div>
        <div style="margin-top:.2rem; font-size:.75rem; color:#9ca3af;">{{ $card['sub'] }}</div>
    </div>
    @endforeach
</div>

{{-- ── Ligne du milieu : Rôles + Utilisateurs ──────────── --}}
<div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.75rem;">

    {{-- Distribution des rôles --}}
    <div style="background:#fff; border-radius:1rem; border:1px solid #f3f4f6; box-shadow:0 1px 3px rgba(0,0,0,.06); padding:1.25rem;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.1rem;">
            <div style="display:flex; align-items:center; gap:.5rem;">
                <div style="display:flex; width:1.5rem; height:1.5rem; align-items:center; justify-content:center; border-radius:.375rem; background:#ede9fe;">
                    <x-heroicon-o-shield-check style="width:.875rem; height:.875rem; color:#7c3aed;"/>
                </div>
                <span style="font-size:.875rem; font-weight:600; color:#111827;">Distribution des rôles</span>
            </div>
            <span style="font-size:.75rem; color:#9ca3af;">{{ $roles->sum('users_count') }} total</span>
        </div>

        <div style="display:flex; flex-direction:column; gap:.6rem;">
            @foreach($roles as $role)
            @php
                $max   = $roles->max('users_count') ?: 1;
                $pct   = round($role->users_count / $max * 100);
                $total = $roles->sum('users_count') ?: 1;
                $share = round($role->users_count / $total * 100);
                $rmap  = [
                    'super_admin'    => ['bar'=>'#7c3aed','bg'=>'#ede9fe','tc'=>'#5b21b6'],
                    'administrateur' => ['bar'=>'#d97706','bg'=>'#fef3c7','tc'=>'#92400e'],
                ];
                $rc = $rmap[$role->name] ?? ['bar'=>'#60a5fa','bg'=>'#dbeafe','tc'=>'#1e40af'];
            @endphp
            <div>
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:.35rem;">
                    <span style="display:inline-block; padding:.15rem .5rem; border-radius:.375rem; background:{{ $rc['bg'] }}; font-size:.7rem; font-weight:600; font-family:monospace; color:{{ $rc['tc'] }};">{{ $role->name }}</span>
                    <div style="display:flex; align-items:center; gap:.5rem;">
                        <span style="font-size:.7rem; color:#9ca3af;">{{ $share }}%</span>
                        <span style="font-size:.75rem; font-weight:700; color:#374151; min-width:1rem; text-align:right;">{{ $role->users_count }}</span>
                    </div>
                </div>
                <div style="height:.375rem; background:#f3f4f6; border-radius:999px; overflow:hidden;">
                    <div style="height:100%; width:{{ $pct }}%; background:{{ $rc['bar'] }}; border-radius:999px; transition:width .5s ease;"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Derniers utilisateurs --}}
    <div style="background:#fff; border-radius:1rem; border:1px solid #f3f4f6; box-shadow:0 1px 3px rgba(0,0,0,.06); padding:1.25rem;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.1rem;">
            <div style="display:flex; align-items:center; gap:.5rem;">
                <div style="display:flex; width:1.5rem; height:1.5rem; align-items:center; justify-content:center; border-radius:.375rem; background:#dcfce7;">
                    <x-heroicon-o-user-plus style="width:.875rem; height:.875rem; color:#16a34a;"/>
                </div>
                <span style="font-size:.875rem; font-weight:600; color:#111827;">Derniers utilisateurs</span>
            </div>
            <a href="/super-admin/users" style="font-size:.75rem; font-weight:500; color:#7c3aed; text-decoration:none;">Voir tout →</a>
        </div>

        <div style="display:flex; flex-direction:column; gap:.25rem;">
            @foreach($recent as $i => $user)
            <div style="display:flex; align-items:center; gap:.75rem; padding:.5rem .6rem; border-radius:.625rem; transition:background .12s;"
                 onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
                <div style="position:relative; flex-shrink:0;">
                    <div style="display:flex; width:2.25rem; height:2.25rem; align-items:center; justify-content:center; border-radius:999px; background:linear-gradient(135deg,#a78bfa,#7c3aed); color:#fff; font-size:.7rem; font-weight:700;">
                        {{ strtoupper(substr($user->prenom??'?',0,1)) }}{{ strtoupper(substr($user->nom??'?',0,1)) }}
                    </div>
                    @if($i===0)
                    <span style="position:absolute; top:0; right:0; width:.625rem; height:.625rem; background:#22c55e; border-radius:999px; border:2px solid #fff;"></span>
                    @endif
                </div>
                <div style="flex:1; min-width:0;">
                    <div style="font-size:.8125rem; font-weight:600; color:#111827; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ trim($user->prenom.' '.$user->nom) }}</div>
                    <div style="font-size:.7rem; color:#9ca3af; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $user->email }}</div>
                </div>
                <div style="font-size:.7rem; color:#d1d5db; flex-shrink:0;">{{ $user->created_at->diffForHumans() }}</div>
            </div>
            @endforeach
        </div>
    </div>

</div>

{{-- ── Accès rapides — 4 colonnes forcées ─────────────── --}}
<div style="background:#fff; border-radius:1rem; border:1px solid #f3f4f6; box-shadow:0 1px 3px rgba(0,0,0,.06); padding:1.25rem;">
    <p style="font-size:.6875rem; font-weight:600; text-transform:uppercase; letter-spacing:.08em; color:#9ca3af; margin:0 0 .875rem;">Actions rapides</p>

    <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:.75rem;">
        @foreach($links as $link)
        @php
            $lc = [
                'violet' => ['icon_bg'=>'#ede9fe','icon_c'=>'#7c3aed','hover_bg'=>'#f5f3ff','hover_b'=>'#c4b5fd'],
                'blue'   => ['icon_bg'=>'#dbeafe','icon_c'=>'#2563eb','hover_bg'=>'#eff6ff','hover_b'=>'#93c5fd'],
                'emerald'=> ['icon_bg'=>'#dcfce7','icon_c'=>'#16a34a','hover_bg'=>'#f0fdf4','hover_b'=>'#86efac'],
                'amber'  => ['icon_bg'=>'#fef3c7','icon_c'=>'#d97706','hover_bg'=>'#fffbeb','hover_b'=>'#fcd34d'],
            ][$link['color']];
        @endphp
        <a href="{{ $link['href'] }}"
           style="display:flex; flex-direction:column; gap:.75rem; padding:1rem; border-radius:.75rem; border:1px solid #e5e7eb; text-decoration:none; transition:all .15s; background:#fff;"
           onmouseover="this.style.background='{{ $lc['hover_bg'] }}';this.style.borderColor='{{ $lc['hover_b'] }}';this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 10px rgba(0,0,0,.08)'"
           onmouseout="this.style.background='#fff';this.style.borderColor='#e5e7eb';this.style.transform='none';this.style.boxShadow='none'">
            <div style="display:flex; width:2.25rem; height:2.25rem; align-items:center; justify-content:center; border-radius:.625rem; background:{{ $lc['icon_bg'] }};">
                <x-dynamic-component :component="$link['icon']" style="width:1.125rem; height:1.125rem; color:{{ $lc['icon_c'] }};"/>
            </div>
            <div>
                <div style="font-size:.8125rem; font-weight:600; color:#1f2937;">{{ $link['label'] }}</div>
                <div style="font-size:.7rem; color:#9ca3af; margin-top:.15rem;">{{ $link['desc'] }}</div>
            </div>
        </a>
        @endforeach
    </div>
</div>

</x-filament-panels::page>
