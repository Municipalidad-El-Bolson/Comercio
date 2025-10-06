<section class="content">
  <div class="content-header">
    <div class="container-fluid">
      <div class="text-center mb-3">
          <h1 class="m-0 pb-2 border-bottom" style="font-size:2.50rem;">Auditoria</h1>
      </div>

      <form wire:submit.prevent="filtrar">
        <div class="row g-2 mb-3 align-items-end">
          {{-- 1) Nombre de Usuario (typeahead) --}}
          <div class="col-12 col-md-4">
            <label class="form-label mb-1">Nombre de Usuario</label>
            <input
              class="form-control"
              type="text"
              placeholder="Usuario"
              list="userHints"
              wire:model.live.debounce.300ms="userName"
            >
            <datalist id="userHints">
              @foreach(($userHints ?? []) as $hint)
                <option value="{{ $hint }}"></option>
              @endforeach
            </datalist>
          </div>

          {{-- 2) Objeto --}}
          <div class="col-6 col-md-2">
            <label class="form-label mb-1">Objeto</label>
            <select class="form-select" wire:model.live="objeto">
              <option value="">Todos</option>
              <option value="comercio">Comercio</option>
              <option value="acta">Acta</option>
            </select>
          </div>

          {{-- 3) Acción --}}
          <div class="col-6 col-md-2">
            <label class="form-label mb-1">Acción</label>
            <select class="form-select" wire:model.live="accion">
              <option value="">Todas</option>
              <option value="crear">Crear</option>
              <option value="editar">Editar</option>
              <option value="loguear">Loguear</option>
            </select>
          </div>

          {{-- 4) Desde / Hasta --}}
          <div class="col-6 col-md-2">
            <label class="form-label mb-1">Desde</label>
            <input class="form-control" type="date" wire:model.live="desde">
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label mb-1">Hasta</label>
            <input class="form-control" type="date" wire:model.live="hasta">
          </div>

          {{-- (Opcional) Limpiar 
          <div class="col-12 col-md-2 d-grid mt-2 mt-md-0">
            <button type="button" class="btn btn-outline-secondary" wire:click="clearFilters">
              Limpiar
            </button>
          </div>--}}
        </div>
      </form>

      <ol class="list-group list-group-numbered">
        @forelse($items as $log)
          <li class="list-group-item">
            <div class="row align-items-center">
              <div class="col-12 col-sm-10">
                <div class="fw-bold">
                  {{ $log->message }}
                </div>

                <div>{{ $log->subtitle }}</div>

                @if(!empty($log->diff_lines))
                  <ul class="mt-1 mb-0 small text-muted">
                    @foreach($log->diff_lines as $line)
                      <li>{{ $line }}</li>
                    @endforeach
                  </ul>
                @endif
              </div>

              <div class="col-12 col-sm-2 text-sm-end mt-2 mt-sm-0">
                <span class="badge text-bg-primary rounded-pill">
                {{ $log->created_at->timezone('America/Argentina/Buenos_Aires')->format('d/m/Y H:i') }}
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
