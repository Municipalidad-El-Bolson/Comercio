<div class="container-fluid pt-4">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-10">
      <div class="content-header py-0 mb-3 d-flex align-items-center justify-content-between">
        <h1 class="m-0 pb-2 border-bottom" style="font-size:2.50rem;">Notificaciones</h1>
        <button class="btn btn-outline-secondary btn-sm" wire:click="markAllAsRead">
          Marcar todas como leídas
        </button>
      </div>

      <div class="card shadow-sm">
        <div class="list-group list-group-flush">
          @forelse ($items as $it)
            <div class="list-group-item d-flex flex-column flex-md-row justify-content-between 
              @if($it['nuevo']) noti-flash @endif">

              <div>
                <div class="fw-semibold">
                  #{{ $it['nro_ingreso'] }} — {{ $it['titular'] }}
                  @if(!$it['read_at']) <span class="badge bg-primary">nuevo</span> @endif
                </div>
                <div class="text-muted small">
                  Fecha {{ $it['fecha'] }} · HC: {{ $it['hc'] ?? '—' }} · De: {{ $it['sender_name'] ?? 'Mesa' }} · {{ $it['created_at'] }}
                </div>
                <div class="mt-2 d-flex flex-wrap gap-2">
                  @foreach ($it['docs'] as $d)
                    <span class="badge bg-secondary">{{ $d }}</span>
                  @endforeach
                </div>
              </div>
              <div class="mt-2 mt-md-0">
                @if(!$it['read_at'])
                  <button class="btn btn-sm btn-outline-success me-2"
                          wire:click="markAsRead('{{ $it['id'] }}')">
                    Marcar leída
                  </button>
                @endif

                <button class="btn btn-sm btn-outline-danger"
                        wire:click="deleteItem('{{ $it['id'] }}')">
                  Borrar
                </button>

              </div>
            </div>
          @empty
            <div class="list-group-item text-center text-muted py-4">
              No hay notificaciones.
            </div>
          @endforelse
        </div>
      </div>
    </div>
  </div>
</div>
@push('styles')
<style>

  /* ===== Botones modernizados ===== */
  .btn {
    border-radius: 0.45rem !important;
    padding: 0.35rem 0.75rem !important;
    font-size: 0.78rem !important;
    font-weight: 600 !important;
    transition: all 0.20s ease-in-out !important;
  }

  .btn-outline-success {
    color: #27ae60 !important;
    border-color: #27ae60 !important;
  }
  .btn-outline-success:hover {
    background:#27ae60 !important;
    color:white !important;
    box-shadow:0 2px 6px rgba(39,174,96,0.45) !important;
  }

  .btn-outline-primary {
    color: #4a6cf7 !important;
    border-color: #4a6cf7 !important;
  }
  .btn-outline-primary:hover {
    background:#4a6cf7 !important;
    color:white !important;
    box-shadow:0 2px 6px rgba(74,108,247,0.45) !important;
  }

  .btn-outline-danger {
    color:#e74c3c !important;
    border-color:#e74c3c !important;
  }
  .btn-outline-danger:hover {
    background:#e74c3c !important;
    color:white !important;
    box-shadow:0 2px 6px rgba(231,76,60,0.45) !important;
  }

  /* ===== List item hover ===== */
  .list-group-item {
    border-left: 4px solid transparent !important;
    transition: .25s ease;
  }
  .list-group-item:hover {
    background:#f9fafb !important;
    border-left-color:#4a6cf7 !important;
  }

  /* ===== Badge “nuevo” ===== */
  .badge.bg-primary {
    background:#4a6cf7 !important;
    font-size: .7rem !important;
    padding: .25em .55em !important;
    border-radius: .3rem !important;
  }

  /* ===== Nuevo titilando ===== */
  @keyframes flash {
    0%   { background-color: #fff3cd; }
    50%  { background-color: #ffe8a1; }
    100% { background-color: #fff3cd; }
  }
  .noti-flash {
    animation: flash 1s ease-in-out 2;
  }

</style>
@endpush
