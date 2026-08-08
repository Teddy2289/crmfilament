<x-filament::section heading="Historique complet" icon="heroicon-o-clock">
    @php($items = $this->getItems())

    @if (empty($items))
        <div class="text-sm text-gray-500">Aucun historique d’interaction ou de modification disponible pour ce prospect.</div>
    @else
        <div class="space-y-3">
            @foreach ($items as $item)
                <div class="rounded-lg border border-gray-200 bg-white p-3 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold text-gray-900">
                                {{ $item['title'] }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ $item['type_label'] }} · {{ $item['date'] }}
                            </div>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-medium uppercase tracking-wide text-slate-600">
                            {{ $item['type'] === 'interaction' ? 'Interaction' : 'Modification' }}
                        </span>
                    </div>

                    <div class="mt-2 text-sm text-gray-700">
                        {{ $item['body'] }}
                    </div>

                    @if (!empty($item['meta']))
                        <div class="mt-2 text-xs text-gray-500">
                            {{ $item['meta'] }}
                        </div>
                    @endif

                    @if (!empty($item['ancienne_valeur']) || !empty($item['nouvelle_valeur']))
                        <div class="mt-3 grid gap-2 md:grid-cols-2">
                            <div class="rounded-md border border-gray-200 bg-gray-50 p-2">
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Ancienne valeur</div>
                                <div class="mt-1 whitespace-pre-wrap text-sm text-gray-700">{{ $item['ancienne_valeur'] ?? '—' }}</div>
                            </div>
                            <div class="rounded-md border border-gray-200 bg-gray-50 p-2">
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Nouvelle valeur</div>
                                <div class="mt-1 whitespace-pre-wrap text-sm text-gray-700">{{ $item['nouvelle_valeur'] ?? '—' }}</div>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-filament::section>
