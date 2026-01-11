@php
    $sidebarCollapsible = filament()->isSidebarCollapsibleOnDesktop();

    $links = [
        [
            'label' => 'Settings',
            'url' => '/admin/settings',
            'icon' => 'heroicon-o-cog-6-tooth',
            'external' => false,
            'permissions' => ['Manage Users', 'View Audit Log', 'Manage Permissions', 'Configure Authentication'],
        ],
        [
            'label' => 'Help',
            'url' => 'https://docs.opengrc.com',
            'icon' => 'heroicon-o-question-mark-circle',
            'external' => true,
            'permissions' => null,
        ],
    ];
@endphp

<ul class="fi-sidebar-nav-groups -mx-2 flex flex-col gap-y-1 px-4 pb-4">
    <hr class="border-gray-600 mb-2 mx-2">
    @foreach ($links as $link)
        @if ($link['permissions'])
            @canany($link['permissions'])
                <x-filament-panels::sidebar.item
                    :icon="$link['icon']"
                    :url="$link['url']"
                    :should-open-url-in-new-tab="$link['external']"
                >
                    {{ $link['label'] }}
                </x-filament-panels::sidebar.item>
            @endcanany
        @else
            <x-filament-panels::sidebar.item
                :icon="$link['icon']"
                :url="$link['url']"
                :should-open-url-in-new-tab="$link['external']"
            >
                {{ $link['label'] }}
            </x-filament-panels::sidebar.item>
        @endif
    @endforeach
</ul> 