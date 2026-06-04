<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class VencidoNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $ubicacion_id,
        public string $nombre,
        public string $fecha_vto,
        public string $fecha_cambio,   // Y-m-d H:i
        public string $estado_anterior, // "021"
        public string $estado_nuevo     // "032"
    ) {}

    public function via($notifiable): array { return ['database']; }

    public function toDatabase($notifiable): array
    {
        return [
            'tipo'           => 'vencido',
            'ubicacion_id'   => $this->ubicacion_id,
            'nombre'         => $this->nombre,
            'fecha_vto'      => $this->fecha_vto,
            'fecha_cambio'   => $this->fecha_cambio,
            'estado_anterior'=> $this->estado_anterior,
            'estado_nuevo'   => $this->estado_nuevo,
        ];
    }
}