<aside id="crm-ns-sidebar" aria-hidden="true">
    <div style="display:flex;align-items:center;gap:10px;padding:4px 6px 16px;border-bottom:1px solid #e7ebef;">
        <div style="width:34px;height:34px;border-radius:4px;background:#337ab7;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;">
            NS
        </div>
        <div>
            <div style="font-size:13px;font-weight:700;color:#1f2a33;line-height:1.2;">NS CONSEIL</div>
            <div style="font-size:11px;color:#6e7b87;margin-top:2px;">CRM Partenaires</div>
        </div>
    </div>

    <nav style="padding:14px 0;display:flex;flex-direction:column;gap:2px;font-size:13px;">
        @foreach ([
            ['label' => 'Tableau de bord', 'active' => true],
            ['label' => 'Partenaires', 'active' => false],
            ['label' => 'Prospects', 'active' => false],
            ['label' => 'Opportunites', 'active' => false],
            ['label' => 'Clients', 'active' => false],
            ['label' => 'Rendez-vous', 'active' => false],
            ['label' => 'Rapports', 'active' => false],
        ] as $item)
            <div style="display:flex;align-items:center;gap:9px;min-height:32px;padding:6px 8px;border-radius:4px;color:{{ $item['active'] ? '#1f5f93' : '#2f3b45' }};background:{{ $item['active'] ? '#e8edf2' : 'transparent' }};border-left:{{ $item['active'] ? '3px solid #337ab7' : '3px solid transparent' }};">
                <span style="width:8px;height:8px;border-radius:2px;background:{{ $item['active'] ? '#337ab7' : '#c4ccd4' }};display:inline-block;"></span>
                <span>{{ $item['label'] }}</span>
            </div>
        @endforeach
    </nav>

    <div style="margin-top:auto;padding:12px 8px;border-top:1px solid #e7ebef;color:#6e7b87;font-size:11px;line-height:1.5;">
        <div style="font-weight:700;color:#566471;margin-bottom:4px;">Acces securise</div>
        <div>Connexion locale au CRM AOPIA / LIKE Formation.</div>
    </div>
</aside>
