<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MesaEntradaNotification extends Notification
{
    use Queueable;

    public function __construct(public array $payload) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'tipo' => 'mesa',
            'receiver_id' => $notifiable->id,
        ] + $this->payload;
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
