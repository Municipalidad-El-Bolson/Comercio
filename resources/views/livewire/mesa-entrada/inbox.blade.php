<div class="container-fluid pt-4">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-10">
      <div class="content-header py-0 mb-3 d-flex align-items-center justify-content-between">
        <h1 class="m-0 pb-2 border-bottom" style="font-size:2.50rem;">Mesa de entrada</h1>
        <button class="btn btn-outline-secondary btn-sm" wire:click="markAllAsRead">
          Marcar todas como leídas
        </button>
      </div>

      <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
          <h2 class="h5 mb-0"><i class="fas fa-history me-2"></i>Historial de documentación ingresada</h2>
        </div>
        <div class="card-body border-bottom">
          <div class="row g-2 align-items-end">
            <div class="col-12 col-lg-6">
              <label class="form-label small mb-1">Buscar</label>
              <input type="search" class="form-control" wire:model.live.debounce.350ms="search"
                     placeholder="Nº de ingreso, titular, HC, documento o usuario">
            </div>
            <div class="col-6 col-lg-2">
              <label class="form-label small mb-1">Desde</label>
              <input type="date" class="form-control" wire:model.live="fechaDesde">
            </div>
            <div class="col-6 col-lg-2">
              <label class="form-label small mb-1">Hasta</label>
              <input type="date" class="form-control" wire:model.live="fechaHasta">
            </div>
            <div class="col-12 col-lg-2">
              <button type="button" class="btn btn-outline-secondary w-100" wire:click="limpiarFiltros">Limpiar</button>
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Fecha</th>
                <th>Nº ingreso</th>
                <th>Titular / Razón social</th>
                <th>HC</th>
                <th>Documentación</th>
                <th>Ingresó</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($historial as $registro)
                <tr>
                  <td class="text-nowrap">{{ $registro->fecha?->format('d/m/Y') }}</td>
                  <td class="fw-semibold">#{{ $registro->nro_ingreso }}</td>
                  <td>{{ $registro->titular_razon }}</td>
                  <td>{{ $registro->hc ?: '—' }}</td>
                  <td style="min-width: 260px">
                    <div class="d-flex flex-wrap gap-1">
                      @foreach ($registro->documentos ?? [] as $documento)
                        <span class="badge bg-secondary">{{ $documento }}</span>
                      @endforeach
                    </div>
                  </td>
                  <td>
                    <div>{{ $registro->user?->name ?? $registro->sender_name ?? '—' }}</div>
                    <small class="text-muted">{{ $registro->created_at?->format('d/m/Y H:i') }}</small>
                  </td>
                </tr>
              @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No se encontraron ingresos.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @if ($historial->hasPages())
          <div class="card-footer bg-white">{{ $historial->links() }}</div>
        @endif
      </div>

      <h2 class="h5 mb-2">Notificaciones recibidas</h2>

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
