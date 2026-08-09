<x-filament-panels::modal>
    <x-slot name="heading">
        Détail de la modification
    </x-slot>

    <x-slot name="content">
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Date</p>
                    <p class="text-sm">{{ $record->date_modification->format('d/m/Y H:i') }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Type</p>
                    <p class="text-sm">
                        <span class="px-2 py-1 rounded-full text-xs font-bold
                            {{ $record->type_modification === 'Création' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $record->type_modification === 'Modification' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $record->type_modification === 'Suppression' ? 'bg-red-100 text-red-800' : '' }}
                            {{ $record->type_modification === 'Restauration' ? 'bg-blue-100 text-blue-800' : '' }}">
                            {{ $record->type_modification_label }}
                        </span>
                    </p>
                </div>
            </div>

            @if($record->champ)
                <div>
                    <p class="text-sm font-medium text-gray-500">Champ modifié</p>
                    <p class="text-sm font-semibold">{{ $record->champ_label }}</p>
                </div>
            @endif

            @if($record->user)
                <div>
                    <p class="text-sm font-medium text-gray-500">Utilisateur</p>
                    <p class="text-sm">{{ $record->user->name ?? 'Inconnu' }}</p>
                </div>
            @endif

            @if($record->ancienne_valeur)
                <div>
                    <p class="text-sm font-medium text-gray-500">Ancienne valeur</p>
                    <div class="bg-gray-50 p-3 rounded-md text-sm whitespace-pre-wrap">
                        {{ $record->ancienne_valeur_formatee }}
                    </div>
                </div>
            @endif

            @if($record->nouvelle_valeur)
                <div>
                    <p class="text-sm font-medium text-gray-500">Nouvelle valeur</p>
                    <div class="bg-green-50 p-3 rounded-md text-sm whitespace-pre-wrap">
                        {{ $record->nouvelle_valeur_formatee }}
                    </div>
                </div>
            @endif
        </div>
    </x-slot>
</x-filament-panels::modal>
