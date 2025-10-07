
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
            <div class="list-group-item d-flex flex-column flex-md-row justify-content-between">
              <div>
                <div class="fw-semibold">
                  {{ $it['nombre'] }}
                  @if($it['dias_restantes'] <= 10 && $it['dias_restantes'] > 0)
                    <span class="badge bg-primary">{{ $it['dias_restantes'] }} días</span>
                  @endif
                </div>
                <div class="text-muted small">
                  Vence: {{ $it['fecha_vto'] }} · Días restantes: {{ $it['dias_restantes'] }} · Estado: {{ $it['estado'] }}
                  @if(!empty($it['direccion'])) · {{ $it['direccion'] }} @endif
                </div>
              </div>
              <div class="mt-2 mt-md-0 text-md-end">
                <a href="{{ route('comercio.data', $it['id']) }}" class="btn btn-sm btn-outline-primary">
                  Ver comercio
                </a>
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
