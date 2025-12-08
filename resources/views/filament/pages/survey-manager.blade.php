<x-filament-panels::page>
    <x-filament-widgets::widgets
        :columns="1"
        :data="$this->getWidgetData()"
        :widgets="$this->getInfoWidgets()"
    />

    <x-filament::tabs>
        <x-filament::tabs.item
            :active="$activeTab === 'surveys'"
            wire:click="setActiveTab('surveys')"
            icon="heroicon-o-paper-airplane"
        >
            {{ __('survey.manager.tabs.surveys') }}
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'templates'"
            wire:click="setActiveTab('templates')"
            icon="heroicon-o-clipboard-document-list"
        >
            {{ __('survey.manager.tabs.templates') }}
        </x-filament::tabs.item>
    </x-filament::tabs>

    <x-filament-widgets::widgets
        :columns="1"
        :data="$this->getWidgetData()"
        :widgets="$this->getTableWidgets()"
    />
</x-filament-panels::page>
