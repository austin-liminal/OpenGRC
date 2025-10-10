<div class="relative" x-data="{ open: @entangle('isOpen') }">
    <!-- Bell Icon Button -->
    <button
        type="button"
        @click="open = !open"
        class="relative flex items-center justify-center w-10 h-10 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-lg transition"
        aria-label="Notifications"
    >
        <x-filament::icon
            icon="heroicon-o-bell"
            class="w-6 h-6"
        />

        @if($unreadCount > 0)
            <span class="absolute -top-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold leading-none text-white rounded-full" style="background-color: #dc2626; right: -0.25rem;">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <!-- Dropdown Menu -->
    <div
        x-show="open"
        @click.away="open = false"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 z-50 mt-2 w-96 origin-top-right rounded-lg bg-white shadow-lg dark:bg-gray-800"
        style="right: 0; left: auto; display: none; width: 24rem;"
        role="menu"
        aria-orientation="vertical"
    >
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                Notifications
            </h3>

            @if($unreadCount > 0)
                <button
                    wire:click="markAllAsRead"
                    class="text-xs text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
                >
                    Mark all as read
                </button>
            @endif
        </div>

        <!-- Notifications List -->
        <div class="max-h-96 overflow-y-auto">
            @forelse($notifications as $notification)
                <div
                    class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition {{ $notification->read_at ? 'opacity-60' : '' }}"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <!-- Notification Icon (if available) -->
                            @if(isset($notification->data['icon']))
                                <div class="flex items-center gap-2 mb-1">
                                    <x-filament::icon
                                        :icon="$notification->data['icon']"
                                        class="w-5 h-5 text-{{ $notification->data['color'] ?? 'gray' }}-500"
                                    />
                                </div>
                            @endif

                            <!-- Title -->
                            @if(isset($notification->data['title']))
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $notification->data['title'] }}
                                </p>
                            @endif

                            <!-- Body -->
                            @if(isset($notification->data['body']))
                                <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                    {{ $notification->data['body'] }}
                                </p>
                            @endif

                            <!-- Action Link -->
                            @if(isset($notification->data['action_url']) && $notification->data['action_url'])
                                <a
                                    href="{{ $notification->data['action_url'] }}"
                                    class="mt-2 inline-block text-xs font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
                                >
                                    {{ $notification->data['action_label'] ?? 'View' }} →
                                </a>
                            @endif

                            <!-- Timestamp -->
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">
                                {{ $notification->created_at->diffForHumans() }}
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-1">
                            @if(!$notification->read_at)
                                <button
                                    wire:click="markAsRead('{{ $notification->id }}')"
                                    class="p-1 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400"
                                    title="Mark as read"
                                >
                                    <x-filament::icon
                                        icon="heroicon-o-check"
                                        class="w-4 h-4"
                                    />
                                </button>
                            @endif

                            <button
                                wire:click="deleteNotification('{{ $notification->id }}')"
                                class="p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-400"
                                title="Delete"
                            >
                                <x-filament::icon
                                    icon="heroicon-o-trash"
                                    class="w-4 h-4"
                                />
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-4 py-8 text-center">
                    <x-filament::icon
                        icon="heroicon-o-bell-slash"
                        class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600"
                    />
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        No notifications yet
                    </p>
                </div>
            @endforelse
        </div>

        <!-- Footer (optional - view all link) -->
        @if($notifications->isNotEmpty())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                <a
                    href="#"
                    class="block text-center text-xs text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
                >
                    View all notifications
                </a>
            </div>
        @endif
    </div>
</div>
