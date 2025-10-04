<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MesaEntradaNotification extends Notification
{
    use Queueable;

    public function __construct(
        public array $payload // ['fecha'=>..., 'nro_ingreso'=>..., 'docs'=>[], 'titular'=>..., 'hc'=>...]
    ) {}

    public function via($notifiable): array
    {
        return ['database']; // sólo BD
    }

    public function toDatabase($notifiable): array
    {
        return $this->payload + [
            'sender_id' => auth()->id(),
            'sender_name' => auth()->user()?->name,
        ];
    }
}