<?php 

namespace App\Livewire\Notifications;

use Livewire\Component;
use App\Notifications\ProxVtoNotification;

class BellProxVto extends Component
{
    public int $unread = 0;
    public function refreshCount(): void
    {
        $this->unread = auth()->user()?->unreadNotifications()
            ->where('type', ProxVtoNotification::class)
            ->count() ?? 0;
    }
    public function mount(): void { $this->refreshCount(); }
    public function render() { return view('livewire.notifications.bell'); }
}