@php
if (function_exists('app') && isset($getRecord) && is_callable($getRecord)) {
    $campagne = $getRecord();
} else {
    $campagne = $record ?? null;
}

if (isset($getState) && is_callable($getState)) {
    $statut = $getState();
} else {
    $statut = $state ?? null;
}
@endphp

<div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
    @if($campagne && $statut)
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-sm font-semibold text-slate-900">Appels - {{ $campagne->statutLabel($statut) ?? $statut }}</h3>
                <p class="mt-1 text-xs text-slate-500">Liste des appels enregistrés pour ce statut.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.15em] text-slate-600">{{ strtoupper($statut) }}</span>
        </div>

        @livewire(\App\Http\Livewire\CampagneAppelsTable::class, ['campagneId' => $campagne->id, 'statut' => $statut], key('campagne-appels-table-'.$statut))
    @else
        <div class="rounded-3xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            Impossible d'afficher la table d'appels pour ce statut.
        </div>
    @endif
</div>
