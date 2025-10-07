<?php 

namespace App\Livewire\Notifications;

use Livewire\Component;
use App\Notifications\VencidoNotification;

class BellVencidos extends Component
{
    public int $unread = 0;

    public function refreshCount(): void
    {
        $this->unread = auth()->user()?->unreadNotifications()
            ->where('type', VencidoNotification::class)
            ->count() ?? 0;
    }

    public function mount(): void { $this->refreshCount(); }

    public function render() { return view('livewire.notifications.bell'); }
}
