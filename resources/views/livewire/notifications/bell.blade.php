<!-- resources/views/livewire/notifications/bell.blade.php -->
<span wire:poll.30s="refreshCount" class="sidebar-notification" aria-label="{{ $unread }} notificaciones sin leer">
  <i class="fas fa-bell" aria-hidden="true"></i>
  @if($unread > 0)
    <span class="sidebar-notification-count">
      {{ $unread > 99 ? '99+' : $unread }}
    </span>
  @endif
</span>
