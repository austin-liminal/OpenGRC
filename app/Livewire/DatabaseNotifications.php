<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class DatabaseNotifications extends Component
{
    public int $unreadCount = 0;

    public bool $isOpen = false;

    protected $listeners = [
        'notificationSent' => 'refreshNotifications',
    ];

    public function mount(): void
    {
        $this->refreshNotifications();
    }

    public function refreshNotifications(): void
    {
        $this->unreadCount = auth()->user()
            ->unreadNotifications()
            ->count();
    }

    public function toggleDropdown(): void
    {
        $this->isOpen = ! $this->isOpen;
    }

    public function markAsRead(string $notificationId): void
    {
        $notification = auth()->user()
            ->notifications()
            ->where('id', $notificationId)
            ->first();

        if ($notification) {
            $notification->markAsRead();
            $this->refreshNotifications();
        }
    }

    public function markAllAsRead(): void
    {
        auth()->user()
            ->unreadNotifications()
            ->update(['read_at' => now()]);

        $this->refreshNotifications();
    }

    public function deleteNotification(string $notificationId): void
    {
        auth()->user()
            ->notifications()
            ->where('id', $notificationId)
            ->delete();

        $this->refreshNotifications();
    }

    public function render(): View
    {
        $notifications = auth()->user()
            ->notifications()
            ->latest()
            ->limit(10)
            ->get();

        return view('livewire.database-notifications', [
            'notifications' => $notifications,
        ]);
    }
}
