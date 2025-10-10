<?php

namespace App\Notifications;

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
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'icon' => $this->icon ?? 'heroicon-o-information-circle',
            'color' => $this->color ?? 'primary',
            'action_url' => $this->actionUrl,
            'action_label' => $this->actionLabel ?? 'View',
        ];
    }
}
