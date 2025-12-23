<x-filament-panels::page>
    <x-filament::tabs>
        @foreach ($tabs as $key => $tab)
            <x-filament::tabs.item
                :active="$activeTab === $key"
                wire:click="setActiveTab('{{ $key }}')"
                :icon="$tab['icon']"
            >
                {{ $tab['label'] }}
            </x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>
</x-filament-panels::page>
