
<div class="container-fluid pt-4">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-10">

      <div class="content-header py-0 mb-3">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <h1 class="m-0 pb-1 border-bottom" style="font-size:2.50rem;">Próximos a vencer</h1>
            <small class="text-muted">
              Los comercios aparecen aquí <strong>10 días antes</strong> de su vencimiento.
              Se notifican automáticamente cuando faltan ≤10 días.
            </small>
          </div>
          <button class="btn btn-outline-secondary btn-sm" wire:click="markAllAsRead">
            Marcar todas como leídas
          </button>
        </div>
      </div>

      <div class="card shadow-sm">
        <div class="list-group list-group-flush">
          @forelse ($items as $it)
            <div class="list-group-item d-flex flex-column flex-md-row justify-content-between
            @if($it['nuevo']) noti-flash @endif">
            <div>
                <div class="fw-semibold">
                    {{ $it['nombre'] }}
                    @if($it['dias_restantes'] <= 10 && $it['dias_restantes'] > 0)
                        <span class="badge bg-primary">{{ $it['dias_restantes'] }} días</span>
                    @endif
                </div>

                <div class="text-muted small">
                    Vence: {{ $it['fecha_vto'] }}
                    · Días restantes: {{ $it['dias_restantes'] }}
                    · Estado: {{ $it['estado'] }}

                    @if(!empty($it['direccion']))
                        · {{ $it['direccion'] }}
                    @endif
                </div>
            </div>

            <div class="mt-2 mt-md-0 text-md-end d-flex gap-2">

                <a href="{{ route('comercio.data', $it['id']) }}"
                  class="btn btn-sm btn-outline-primary">
                    Ver comercio
                </a>

                <button class="btn btn-sm btn-outline-danger"
                        wire:click="deleteItem({{ $it['id'] }})">
                    Borrar
                </button>

            </div>
        </div>

          @empty
            <div class="list-group-item text-center text-muted py-4">
              No hay vencimientos próximos.
            </div>
          @endforelse
        </div>
      </div>

    </div>
  </div>
</div>
@push('styles')
<style>
  @keyframes flash {
    0%   { background-color: #fff3cd; }
    50%  { background-color: #ffe8a1; }
    100% { background-color: #fff3cd; }
  }


  /* ===== BOTONES MODERNOS ===== */

  .btn {
    border-radius: 0.45rem !important;
    padding: 0.35rem 0.75rem !important;
    font-size: 0.78rem !important;
    font-weight: 600 !important;
    transition: all 0.20s ease-in-out !important;
  }

  /* Primary */
  .btn-outline-primary {
    color: #4a6cf7 !important;
    border-color: #4a6cf7 !important;
  }

  .btn-outline-primary:hover {
    background: #4a6cf7 !important;
    color: white !important;
    box-shadow: 0 2px 6px rgba(74,108,247,0.45) !important;
  }

  /* Danger */
  .btn-outline-danger {
    color: #e74c3c !important;
    border-color: #e74c3c !important;
  }

  .btn-outline-danger:hover {
    background: #e74c3c !important;
    color: white !important;
    box-shadow: 0 2px 6px rgba(231,76,60,0.45) !important;
  }

  /* Secondary */
  .btn-outline-secondary {
    color: #6c757d !important;
    border-color: #6c757d !important;
  }

  .btn-outline-secondary:hover {
    background: #6c757d !important;
    color: white !important;
    box-shadow: 0 2px 6px rgba(108,117,125,0.45) !important;
  }

  .noti-flash {
    animation: flash 1s ease-in-out 2;
  }

  /* Opcional: mejorar el list-group */
  .list-group-item {
    border-left: 4px solid transparent !important;
    transition: 0.2s ease;
  }

  .list-group-item:hover {
    background: #f9fafb !important;
    border-left-color: #4a6cf7 !important;
  }

</style>
@endpush
