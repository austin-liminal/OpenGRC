<?php

namespace App\Notifications;

use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DropdownNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $body,
        public ?string $icon = null,
        public ?string $color = null,
        public ?string $actionUrl = null,
        public ?string $actionLabel = null,
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $notification = FilamentNotification::make()
            ->title($this->title)
            ->body($this->body)
            ->icon($this->icon ?? 'heroicon-o-information-circle');

        // Set color/status
        if ($this->color === 'success') {
            $notification->success();
        } elseif ($this->color === 'danger') {
            $notification->danger();
        } elseif ($this->color === 'warning') {
            $notification->warning();
        }

        // Add action if URL provided
        if ($this->actionUrl) {
            $notification->actions([
                Action::make('view')
                    ->label($this->actionLabel ?? 'View')
                    ->url($this->actionUrl)
                    ->markAsRead(),
            ]);
        }

        return $notification->getDatabaseMessage();
    }
}
