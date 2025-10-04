<!-- resources/views/livewire/notifications/bell.blade.php -->
<span wire:poll.30s="refreshCount" class="position-relative">
  <i class="fas fa-bell"></i>
  @if($unread > 0)
    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
      {{ $unread }}
    </span>
  @endif
</span>
