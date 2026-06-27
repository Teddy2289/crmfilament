<x-filament-panels::page>

    <x-filament::section>
        <x-slot name="heading">Diagnostic Ringover</x-slot>

        <div class="grid gap-4 md:grid-cols-3">
            <div>
                <div class="text-sm text-gray-500">Webhook</div>
                <div class="font-medium">{{ url('/api/ringover/webhook') }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Token API</div>
                <div class="font-medium">{{ config('ringover.api_token') ? 'Configure' : 'Non configure' }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Secret webhook</div>
                <div class="font-medium">{{ config('ringover.webhook_secret') ? 'Configure' : 'Non configure' }}</div>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-6">
            <div>
                <div class="text-sm text-gray-500">Appels</div>
                <div class="text-xl font-semibold">{{ $this->diagnostic['total_calls'] ?? 0 }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Tags complets</div>
                <div class="text-xl font-semibold">{{ $this->diagnostic['complete_tags'] ?? 0 }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Sans DEP</div>
                <div class="text-xl font-semibold">{{ $this->diagnostic['missing_department'] ?? 0 }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Sans statut</div>
                <div class="text-xl font-semibold">{{ $this->diagnostic['missing_status'] ?? 0 }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Users mappes</div>
                <div class="text-xl font-semibold">{{ $this->diagnostic['mapped_users'] ?? 0 }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Users non mappes</div>
                <div class="text-xl font-semibold">{{ $this->diagnostic['unmapped_users'] ?? 0 }}</div>
            </div>
        </div>

        @if (! ($this->diagnostic['schema_ready'] ?? false))
            <div class="mt-4 text-sm text-warning-600">
                Migration Ringover avancee non appliquee.
            </div>
        @endif
    </x-filament::section>

    @if (! $this->connexionOk)
        <x-filament::section>
            <div class="flex items-center gap-3 text-danger-600">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5" />
                <span class="font-medium">Connexion Ringover impossible.</span>
            </div>
        </x-filament::section>
    @endif

</x-filament-panels::page>
