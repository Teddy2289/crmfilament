@php
if (function_exists('app') && isset($getRecord) && is_callable($getRecord)) {
    $campagne = $getRecord();
} else {
    $campagne = $record ?? null;
}

$stats = $campagne?->getStats() ?? [];
$contactsByContact = $stats['contacts_par_statut'] ?? [];
$callsByStatus = $stats['par_statut'] ?? [];

// Merge: prefer distinct-contact counts when available, otherwise fall back to call counts.
$counts = $callsByStatus;
foreach ($contactsByContact as $code => $count) {
    $counts[$code] = $count;
}

$totalTreated = $stats['contacts_traites'] ?? 0;
@endphp

@if(empty($counts))
    <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">
        Aucun contact traité par statut pour l'instant.
    </div>
@else
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        @foreach($counts as $code => $count)
            @php
                $label = $campagne->statutLabel($code);
                $percent = $totalTreated > 0 ? round(($count / $totalTreated) * 100, 1) : 0;
            @endphp
            <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $label }}</div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-2xl font-semibold text-slate-900">{{ $count }}</span>
                            <span class="text-sm text-slate-500">({{ $percent }}%)</span>
                        </div>
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-2 rounded-full bg-slate-600" style="width: {{ $percent }}%;"></div>
                        </div>
                    </div>
                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-slate-700">{{ strtoupper($code) }}</span>
                </div>
            </div>
        @endforeach
    </div>
@endif
