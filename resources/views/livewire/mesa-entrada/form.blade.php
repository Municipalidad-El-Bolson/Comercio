<div class="container-fluid pt-4">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">
      <div class="content-header py-0 mb-3 text-center">
        <h1 class="m-0 pb-2 border-bottom">Notificar</h1>
      </div>

      @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
      @endif

      <div class="card shadow-sm">
        <div class="card-body">
          <form wire:submit.prevent="submit" class="row g-3">

            <div class="col-md-4">
              <label class="form-label">Fecha</label>
              <input type="date" class="form-control @error('fecha') is-invalid @enderror"
                     wire:model="fecha">
              @error('fecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
              <label class="form-label">Nº ingreso</label>
              <input type="number" class="form-control @error('nro_ingreso') is-invalid @enderror"
                     wire:model="nro_ingreso" min="1">
              @error('nro_ingreso') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-8">
              <label class="form-label">Titular / Razón social</label>
              <input type="text" class="form-control @error('titular_razon') is-invalid @enderror"
                     wire:model.defer="titular_razon">
              @error('titular_razon') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
              <label class="form-label">HC (opcional)</label>
              <input type="text" class="form-control" wire:model.defer="hc">
            </div>

            {{-- Documentación (checkboxes con scroll + chips con X) --}}
            <div class="col-12">
              <label class="form-label mb-1">Seleccioná Documentación</label>

              <div class="d-flex gap-2 mb-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="selectAll">Tildar todo</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="clearAll">Destildar todo</button>
              </div>

              <div class="border rounded p-2" style="max-height: 280px; overflow-y: auto;">
                <div class="row g-2">
                  @foreach($opsDocs as $op)
                    <div class="col-12 col-md-6">
                      <div class="form-check">
                        <input class="form-check-input"
                              type="checkbox"
                              id="doc-{{ $op->id }}"
                              value="{{ $op->id }}"
                              wire:model.live="documentacion_ids"> {{-- 👈 aquí el cambio --}}
                        <label class="form-check-label" for="doc-{{ $op->id }}">
                          {{ $op->nombre }}
                        </label>
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>

              @error('documentacion_ids')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror

              {{-- Chips azules con ✕ (aparecen al instante) --}}
              @if(!empty($selectedDocsMap))
                <div class="mt-3 d-flex flex-wrap gap-2">
                  @foreach($selectedDocsMap as $id => $name)
                    <span class="badge bg-primary text-white d-inline-flex align-items-center fade-in" style="transition:all .2s;">
                      <span class="me-1">{{ $name }}</span>
                      <button type="button"
                              class="btn btn-sm btn-light py-0 px-1 ms-1 rounded-circle border-0"
                              style="font-size:.8rem; line-height:1; color:#0d6efd;"
                              aria-label="Quitar {{ $name }}"
                              wire:click="removeDoc({{ $id }})">×</button>
                    </span>
                  @endforeach
                </div>
              @endif
            </div>

            <style>
              .fade-in{opacity:0;transform:scale(.95);animation:fadeIn .25s forwards}
              @keyframes fadeIn{from{opacity:0;transform:scale(.95)}to{opacity:1;transform:scale(1)}}
            </style>


            <div class="col-12 d-flex justify-content-end">
              <button class="btn btn-primary">
                <i class="fas fa-paper-plane me-1"></i> Notificar
              </button>
            </div>

          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://unpkg.com/alpinejs@3.x.x" defer></script>
