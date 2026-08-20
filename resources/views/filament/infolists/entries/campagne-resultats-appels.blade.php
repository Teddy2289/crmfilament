@php
    $campagne = (isset($getRecord) && is_callable($getRecord)) ? $getRecord() : ($record ?? null);
@endphp
@if ($campagne)
    @livewire(\App\Http\Livewire\CampagneResultatsAppels::class, ['campagneId' => $campagne->id], key('campagne-resultats-appels-'.$campagne->id))
@else
    <p class="text-sm text-gray-500">Aucune campagne sélectionnée.</p>
@endif
