<x-filament-panels::page>
    @if (empty($this->getHeaderWidgets()) && empty($this->getFooterWidgets()))
        <x-filament-panels::empty-state
            icon="heroicon-o-chart-pie"
            heading="Aucun widget disponible"
            description="Vous n'avez pas accès aux widgets de ce dashboard."
        />
    @else
        <x-filament-panels::grid :default="$this->getColumns()" class="gap-6">
            @foreach ($this->getHeaderWidgets() as $widget)
                @if ($widget::canView())
                    <x-filament-panels::widget 
                        :widget="$widget" 
                        :id="$widget::getId()" 
                    />
                @endif
            @endforeach
        </x-filament-panels::grid>

        @if (!empty($this->getFooterWidgets()))
            <x-filament-panels::grid :default="$this->getColumns()" class="gap-6 mt-6">
                @foreach ($this->getFooterWidgets() as $widget)
                    @if ($widget::canView())
                        <x-filament-panels::widget 
                            :widget="$widget" 
                            :id="$widget::getId()" 
                        />
                    @endif
                @endforeach
            </x-filament-panels::grid>
        @endif
    @endif
</x-filament-panels::page>
