<div class="container-fluid px-3">
  <div class="content-header px-0">
    <h1 class="mb-3">Seguimiento de actas</h1>
    <input type="search" class="form-control" style="max-width:420px"
           wire:model.live.debounce.300ms="search" placeholder="Buscar comercio o acta…">
  </div>
  <div class="card">
    <div class="card-body p-0 table-responsive">
      <table class="table table-striped table-hover mb-0">
        <thead class="thead-light"><tr><th>Prioridad</th><th>Vencimiento</th><th>Comercio</th><th>Acta</th><th>Tipo</th><th>Estado</th><th></th></tr></thead>
        <tbody>
          @forelse($actas as $acta)
            @php
              $dias = today()->diffInDays($acta->fecha_vencimiento, false);
              [$badge, $prioridad] = match(true) {
                $dias < 0 => ['danger', 'Vencida hace '.abs($dias).' día(s)'],
                $dias === 0 => ['danger', 'Vence hoy'],
                $dias <= 3 => ['warning', 'Urgente: '.$dias.' día(s)'],
                $dias <= 7 => ['info', 'Próxima: '.$dias.' día(s)'],
                default => ['secondary', $dias.' día(s)'],
              };
              $nombre = $acta->ubicacion?->nombre_comercial ?: ($acta->ubicacion?->razon_social ?: 'Comercio #'.$acta->ubicacion_id);
            @endphp
            <tr>
              <td><span class="badge badge-{{ $badge }}">{{ $prioridad }}</span></td>
              <td>{{ $acta->fecha_vencimiento?->format('d/m/Y') }}</td><td>{{ $nombre }}</td><td>{{ $acta->titulo }}</td>
              <td>{{ $acta->tipo_acta ? ucfirst($acta->tipo_acta) : '—' }}</td><td>{{ $acta->estado ?: '—' }}</td>
              <td><a class="btn btn-sm btn-outline-primary" href="{{ route('comercio.data', $acta->ubicacion_id) }}">Ver comercio</a></td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted py-4">No hay actas con vencimiento.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($actas->hasPages())<div class="card-footer">{{ $actas->links() }}</div>@endif
  </div>
</div>
