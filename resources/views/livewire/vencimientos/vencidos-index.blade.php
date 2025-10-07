<div class="container-fluid pt-4">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-10">
      <div class="content-header py-0 mb-3 d-flex align-items-center justify-content-between">
        <h1 class="m-0 pb-2 border-bottom" style="font-size:2.50rem;">Vencidos</h1>
      </div>

      <div class="card shadow-sm">
        <div class="list-group list-group-flush">
          @forelse ($items as $it)
            <div class="list-group-item d-flex flex-column flex-md-row justify-content-between">
              <div>
                <div class="fw-semibold">{{ $it['nombre'] }}</div>
                <div class="text-muted small">
                  Venció: {{ $it['fecha_vto'] }} · Estado: {{ $it['estado'] }} · Cambio: {{ $it['fecha_cambio'] }}
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
              No hay vencidos.
            </div>
          @endforelse
        </div>
      </div>
    </div>
  </div>
</div>