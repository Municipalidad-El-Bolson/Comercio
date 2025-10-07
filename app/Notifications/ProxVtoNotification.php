<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProxVtoNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $ubicacion_id,
        public string $nombre,         // fantasia/razon_social
        public string $fecha_vto,      // Y-m-d
        public int $dias_restantes     // ej. 10
    ) {}

    public function via($notifiable): array { return ['database']; }

    public function toDatabase($notifiable): array
    {
        return [
            'tipo'          => 'prox_vto',
            'ubicacion_id'  => $this->ubicacion_id,
            'nombre'        => $this->nombre,
            'fecha_vto'     => $this->fecha_vto,
            'dias_restantes'=> $this->dias_restantes,
        ];
    }
}