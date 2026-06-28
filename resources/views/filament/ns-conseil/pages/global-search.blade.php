<x-filament-panels::page>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900">Recherche globale</h2>
        <p class="text-gray-600">Trouvez des prospects, clients et partenaires par téléphone, email, nom ou ref_client</p>
    </x-slot>

    <div class="space-y-6">
        {{ $this->form }}

        @if(!empty($this->results))
            <div class="space-y-6">
                @if(!empty($this->results['prospects']))
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm">{{ count($this->results['prospects']) }}</span>
                            Prospects
                        </h3>
                        <div class="space-y-3">
                            @foreach($this->results['prospects'] as $prospect)
                                <div class="border rounded-lg p-4 hover:bg-gray-50 transition">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <a href="{{ $prospect['url'] }}" class="font-semibold text-blue-600 hover:underline">
                                                {{ $prospect['nom'] }}
                                            </a>
                                            <div class="text-sm text-gray-600 mt-1">
                                                @if($prospect['telephone'])
                                                    <span class="mr-3">📞 {{ $prospect['telephone'] }}</span>
                                                @endif
                                                @if($prospect['email'])
                                                    <span class="mr-3">✉️ {{ $prospect['email'] }}</span>
                                                @endif
                                                @if($prospect['ville'])
                                                    <span>📍 {{ $prospect['ville'] }}</span>
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-500 mt-2">
                                                Statut: {{ $prospect['statut'] }}
                                                @if($prospect['teleprospecteur'])
                                                    | Commercial: {{ $prospect['teleprospecteur'] }}
                                                @endif
                                            </div>
                                        </div>
                                        <a href="{{ $prospect['url'] }}" class="text-blue-600 hover:text-blue-800">
                                            Voir →
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(!empty($this->results['clients']))
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm">{{ count($this->results['clients']) }}</span>
                            Clients
                        </h3>
                        <div class="space-y-3">
                            @foreach($this->results['clients'] as $client)
                                <div class="border rounded-lg p-4 hover:bg-gray-50 transition">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <a href="{{ $client['url'] }}" class="font-semibold text-green-600 hover:underline">
                                                {{ $client['nom'] }}
                                            </a>
                                            <div class="text-sm text-gray-600 mt-1">
                                                @if($client['ref_client'])
                                                    <span class="mr-3 bg-gray-100 px-2 py-1 rounded text-xs">{{ $client['ref_client'] }}</span>
                                                @endif
                                                @if($client['telephone'])
                                                    <span class="mr-3">📞 {{ $client['telephone'] }}</span>
                                                @endif
                                                @if($client['email'])
                                                    <span class="mr-3">✉️ {{ $client['email'] }}</span>
                                                @endif
                                                @if($client['ville'])
                                                    <span>📍 {{ $client['ville'] }}</span>
                                                @endif
                                            </div>
                                            @if(!empty($client['ref_clients']))
                                                <div class="text-xs text-gray-500 mt-2">
                                                    Refs: {{ implode(', ', $client['ref_clients']) }}
                                                </div>
                                            @endif
                                            <div class="text-xs text-gray-500 mt-1">
                                                État: {{ $client['etat'] }}
                                                @if($client['commercial'])
                                                    | Commercial: {{ $client['commercial'] }}
                                                @endif
                                            </div>
                                        </div>
                                        <a href="{{ $client['url'] }}" class="text-green-600 hover:text-green-800">
                                            Voir →
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(!empty($this->results['partenaires']))
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <span class="bg-purple-100 text-purple-800 px-2 py-1 rounded text-sm">{{ count($this->results['partenaires']) }}</span>
                            Partenaires
                        </h3>
                        <div class="space-y-3">
                            @foreach($this->results['partenaires'] as $partenaire)
                                <div class="border rounded-lg p-4 hover:bg-gray-50 transition">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <a href="{{ $partenaire['url'] }}" class="font-semibold text-purple-600 hover:underline">
                                                {{ $partenaire['nom'] }}
                                            </a>
                                            <div class="text-sm text-gray-600 mt-1">
                                                @if($partenaire['telephone'])
                                                    <span class="mr-3">📞 {{ $partenaire['telephone'] }}</span>
                                                @endif
                                                @if($partenaire['email'])
                                                    <span class="mr-3">✉️ {{ $partenaire['email'] }}</span>
                                                @endif
                                                @if($partenaire['ville'])
                                                    <span>📍 {{ $partenaire['ville'] }}</span>
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-500 mt-2">
                                                Statut: {{ $partenaire['statut'] }}
                                                @if($partenaire['conseiller'])
                                                    | Conseiller: {{ $partenaire['conseiller'] }}
                                                @endif
                                            </div>
                                        </div>
                                        <a href="{{ $partenaire['url'] }}" class="text-purple-600 hover:text-purple-800">
                                            Voir →
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(!empty($this->results['entreprises']))
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <span class="bg-orange-100 text-orange-800 px-2 py-1 rounded text-sm">{{ count($this->results['entreprises']) }}</span>
                            Entreprises
                        </h3>
                        <div class="space-y-3">
                            @foreach($this->results['entreprises'] as $entreprise)
                                <div class="border rounded-lg p-4 hover:bg-gray-50 transition">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="font-semibold text-orange-600">
                                                {{ $entreprise['nom'] }}
                                            </div>
                                            <div class="text-sm text-gray-600 mt-1">
                                                @if($entreprise['siret'])
                                                    <span class="mr-3 bg-gray-100 px-2 py-1 rounded text-xs">{{ $entreprise['siret'] }}</span>
                                                @endif
                                                @if($entreprise['telephone'])
                                                    <span class="mr-3">📞 {{ $entreprise['telephone'] }}</span>
                                                @endif
                                                @if($entreprise['email'])
                                                    <span class="mr-3">✉️ {{ $entreprise['email'] }}</span>
                                                @endif
                                                @if($entreprise['ville'])
                                                    <span>📍 {{ $entreprise['ville'] }}</span>
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-500 mt-2">
                                                @if($entreprise['secteur'])
                                                    Secteur: {{ $entreprise['secteur'] }}
                                                @endif
                                                @if($entreprise['nb_partenaires'] > 0)
                                                    | {{ $entreprise['nb_partenaires'] }} partenaire(s)
                                                @endif
                                                @if($entreprise['nb_clients'] > 0)
                                                    | {{ $entreprise['nb_clients'] }} client(s)
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @elseif($this->searchQuery && strlen($this->searchQuery) >= 3)
            <div class="bg-white rounded-lg shadow p-6 text-center text-gray-500">
                Aucun résultat pour "{{ $this->searchQuery }}"
            </div>
        @endif
    </div>
</x-filament-panels::page>
