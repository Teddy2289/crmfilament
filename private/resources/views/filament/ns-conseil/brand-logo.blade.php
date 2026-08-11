@php
    // $logoPath peut être injecté depuis le PanelProvider :
    // ->brandLogo(fn () => view('filament.ns-conseil.brand-logo', ['logoPath' => $theme?->brand_logo_path]))
    $logoUrl = $logoPath ?? asset('images/NS.png');
@endphp

<div style="display: flex; align-items: center; gap: 0.6rem;">
    <img
        src="{{ $logoUrl }}"
        alt="NS Conseil"
        style="height: 3.5rem; width: auto;"
    />
    <span style="font-weight: 700; font-size: 1rem; line-height: 1.1; color: #ffffff; letter-spacing: -0.01em;">
        NS CONSEIL
        <span style="display: block; font-size: 0.65rem; font-weight: 500; color: rgba(255,255,255,0.6); letter-spacing: 0.05em;">
            CRM PARTENAIRES
        </span>
    </span>
</div>