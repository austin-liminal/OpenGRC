<x-filament-panels::page>
    @if (count($tabs) > 0)
        <x-filament::tabs>
            @foreach ($tabs as $key => $tab)
                <x-filament::tabs.item
                    :active="$activeTab === $key"
                    wire:click="setActiveTab('{{ $key }}')"
                    :icon="$tab['icon'] ?? null"
                    class="cursor-pointer"
                >
                    {{ $tab['label'] }}
                </x-filament::tabs.item>
            @endforeach
        </x-filament::tabs>
    @endif
</x-filament-panels::page>
