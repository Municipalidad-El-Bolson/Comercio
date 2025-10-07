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
            <div class="list-group-item d-flex flex-column flex-md-row justify-content-between">
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
                  <button class="btn btn-sm btn-outline-success" wire:click="markAsRead('{{ $it['id'] }}')">
                    Marcar leída
                  </button>
                @endif
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
