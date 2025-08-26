<section class="content">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">Historial de movimientos</h1></div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item active"><a href="/">Home</a></li>
            <li class="breadcrumb-item">Historial</li>
          </ol>
        </div>
      </div>

      <form wire:submit.prevent="filtrar">
        <div class="row g-2 mb-3">
          <div class="col-sm-3">
            <input class="form-control" type="text" placeholder="Buscar acción/ruta/entidad" wire:model.defer="search">
          </div>
          <div class="col-sm-2">
            <input class="form-control" type="date" wire:model.defer="desde">
          </div>
          <div class="col-sm-2">
            <input class="form-control" type="date" wire:model.defer="hasta">
          </div>
          <div class="col-sm-3">
            <input class="form-control" type="text" placeholder="Nombre del usuario" wire:model.defer="adminName">
          </div>
          <div class="col-sm-2 d-grid">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-search"></i> Buscar
            </button>
          </div>
        </div>
      </form>


      <ol class="list-group list-group-numbered">
        @forelse($items as $log)
          <li class="list-group-item">
            <div class="row align-items-center">
              <div class="col-12 col-sm-10">
                <div class="fw-bold">
                  @if(!empty($log->action))
                    {{ $log->action }}
                  @elseif($log->entity_type)
                    {{ class_basename($log->entity_type) }} #{{ $log->entity_id }}
                  @else
                    —
                  @endif
                </div>

                <div>
                  {{ $log->user?->name ?? 'Invitado' }}
                  <small class="text-muted">• {{ $log->method }} {{ $log->path }}</small>
                  @if($log->ip)
                    <small class="text-muted">• {{ $log->ip }}</small>
                  @endif
                </div>
              </div>

              <div class="col-12 col-sm-2 text-sm-end mt-2 mt-sm-0">
                <span class="badge text-bg-primary rounded-pill">
                  {{ $log->created_at->format('d/m/Y H:i') }}
                </span>
              </div>
            </div>
          </li>

        @empty
          <li class="list-group-item text-muted">Sin registros.</li>
        @endforelse
      </ol>

      <div class="mt-3">
        {{ $items->links() }}
      </div>
    </div>
  </div>
</section>
