<x-filament-panels::page>
@php
    $stats = $this->getStats();

    $kpis = [
        ['label' => 'Tickets', 'value' => $stats['tickets'], 'sub' => $stats['tickets_actifs'].' actifs', 'href' => '/allopro/tickets'],
        ['label' => 'Urgences', 'value' => $stats['tickets_urgents'], 'sub' => $stats['tickets_retard'].' en retard', 'href' => '/allopro/tickets'],
        ['label' => 'Artisans', 'value' => $stats['artisans'], 'sub' => 'reseau disponible', 'href' => '/allopro/artisans'],
        ['label' => 'Contacts', 'value' => $stats['contacts'], 'sub' => $stats['contacts_particuliers'].' particuliers', 'href' => '/allopro/contact-particuliers'],
        ['label' => 'Devis', 'value' => $stats['devis'], 'sub' => $stats['devis_attente'].' en attente', 'href' => '/allopro/devis'],
        ['label' => 'Factures', 'value' => $stats['factures'], 'sub' => $stats['factures_attente'].' non payees', 'href' => '/allopro/factures'],
    ];

    $workQueues = [
        ['href' => '/allopro/tickets', 'icon' => 'heroicon-o-ticket', 'label' => 'Tickets actifs', 'value' => $stats['tickets_actifs'], 'meta' => 'Qualification, RDV et suivi client'],
        ['href' => '/allopro/artisans', 'icon' => 'heroicon-o-wrench-screwdriver', 'label' => 'Artisans', 'value' => $stats['artisans'], 'meta' => 'Disponibilites et metiers'],
        ['href' => '/allopro/affaire-interventions', 'icon' => 'heroicon-o-briefcase', 'label' => 'Interventions', 'value' => $stats['interventions'], 'meta' => 'Affaires et interventions en cours'],
        ['href' => '/allopro/contact-particuliers', 'icon' => 'heroicon-o-user', 'label' => 'Contacts particuliers', 'value' => $stats['contacts_particuliers'], 'meta' => 'Clients et demandes entrantes'],
    ];

    $salesQueues = [
        ['href' => '/allopro/devis', 'icon' => 'heroicon-o-document-text', 'label' => 'Devis a suivre', 'value' => $stats['devis_attente'], 'meta' => $stats['devis_relancer'].' relances'],
        ['href' => '/allopro/bon-de-commandes', 'icon' => 'heroicon-o-clipboard-document-check', 'label' => 'Bons de commande', 'value' => $stats['bons_de_commande_actifs'], 'meta' => $stats['bons_de_commande'].' au total'],
        ['href' => '/allopro/factures', 'icon' => 'heroicon-o-banknotes', 'label' => 'Factures ouvertes', 'value' => $stats['factures_attente'], 'meta' => $stats['factures_retard'].' en retard'],
        ['href' => '/allopro/contact-partenaires', 'icon' => 'heroicon-o-building-office-2', 'label' => 'Contacts partenaires', 'value' => $stats['contacts_partenaires'], 'meta' => 'Prescripteurs et relais'],
    ];

    $qualityQueues = [
        ['href' => '/allopro/reclamation-p8s', 'icon' => 'heroicon-o-exclamation-triangle', 'label' => 'Reclamations P8', 'value' => $stats['reclamations'], 'meta' => 'Traitement qualite'],
        ['href' => '/allopro/rapport-satisfaction-p6s', 'icon' => 'heroicon-o-face-smile', 'label' => 'Satisfaction P6', 'value' => $stats['satisfactions'], 'meta' => 'Retours et NPS'],
    ];
@endphp

<div class="espo-dashboard">
    <div class="espo-dashboard-header">
        <div>
            <h2 class="espo-dashboard-title">Tableau de bord AlloPro</h2>
            <p class="espo-dashboard-meta">Pilotage appels, artisans, interventions et facturation - {{ now()->format('d/m/Y H:i') }}</p>
        </div>
        <div class="espo-dashboard-actions">
            <a class="espo-badge-soft" href="/allopro/tickets">Tickets</a>
            <a class="espo-badge-soft" href="/allopro/artisans">Artisans</a>
            <a class="espo-badge-soft" href="/allopro/factures">Factures</a>
        </div>
    </div>

    <div class="espo-kpi-grid">
        @foreach ($kpis as $kpi)
            <a class="espo-kpi" href="{{ $kpi['href'] }}">
                <span class="espo-kpi-label">{{ $kpi['label'] }}</span>
                <span class="espo-kpi-value">{{ number_format((float) $kpi['value'], 0, ',', ' ') }}</span>
                <span class="espo-kpi-sub">{{ $kpi['sub'] }}</span>
            </a>
        @endforeach
    </div>

    <div class="espo-dashlet-grid espo-dashlet-grid-3">
        <section class="espo-dashlet">
            <div class="espo-dashlet-header">
                <h3 class="espo-dashlet-title">Files de travail</h3>
                <span class="espo-badge-soft espo-badge-soft-warning">{{ $stats['tickets_actifs'] }} actifs</span>
            </div>
            <div class="espo-dashlet-body">
                <div class="espo-action-list">
                    @foreach ($workQueues as $queue)
                        <a class="espo-action-row" href="{{ $queue['href'] }}">
                            <span class="espo-action-icon">
                                <x-dynamic-component :component="$queue['icon']" />
                            </span>
                            <span class="espo-list-main">
                                <span class="espo-list-title">{{ $queue['label'] }}</span>
                                <span class="espo-list-sub">{{ $queue['meta'] }}</span>
                            </span>
                            <span class="espo-list-value">{{ number_format((float) $queue['value'], 0, ',', ' ') }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="espo-dashlet">
            <div class="espo-dashlet-header">
                <h3 class="espo-dashlet-title">Commercial & documents</h3>
                <span class="espo-badge-soft">{{ $stats['devis'] }} devis</span>
            </div>
            <div class="espo-dashlet-body">
                <div class="espo-action-list">
                    @foreach ($salesQueues as $queue)
                        <a class="espo-action-row" href="{{ $queue['href'] }}">
                            <span class="espo-action-icon">
                                <x-dynamic-component :component="$queue['icon']" />
                            </span>
                            <span class="espo-list-main">
                                <span class="espo-list-title">{{ $queue['label'] }}</span>
                                <span class="espo-list-sub">{{ $queue['meta'] }}</span>
                            </span>
                            <span class="espo-list-value">{{ number_format((float) $queue['value'], 0, ',', ' ') }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="espo-dashlet">
            <div class="espo-dashlet-header">
                <h3 class="espo-dashlet-title">Qualite</h3>
                <span class="espo-badge-soft">{{ $stats['satisfactions'] }} retours</span>
            </div>
            <div class="espo-dashlet-body">
                <div class="espo-action-list">
                    @foreach ($qualityQueues as $queue)
                        <a class="espo-action-row" href="{{ $queue['href'] }}">
                            <span class="espo-action-icon">
                                <x-dynamic-component :component="$queue['icon']" />
                            </span>
                            <span class="espo-list-main">
                                <span class="espo-list-title">{{ $queue['label'] }}</span>
                                <span class="espo-list-sub">{{ $queue['meta'] }}</span>
                            </span>
                            <span class="espo-list-value">{{ number_format((float) $queue['value'], 0, ',', ' ') }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    </div>
</div>
</x-filament-panels::page>
