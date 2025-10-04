<?php

namespace App\Livewire\Notifications;

use Livewire\Component;

class Bell extends Component
{
    public int $unread = 0;

    public function mount(): void
    {
        $this->refreshCount();
    }

    public function refreshCount(): void
    {
        $this->unread = auth()->user()?->unreadNotifications()->count() ?? 0;
    }

    public function render()
    {
        return view('livewire.notifications.bell');
    }
}
