<!-- resources/views/livewire/mesa-entrada/form.blade.php -->
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

            {{-- Documentación (TomSelect múltiple + búsqueda + chips + scroll) --}}
{{-- Documentación (TomSelect múltiple + checkboxes + búsqueda + chips + scroll) --}}
<div class="col-12" wire:ignore>
  <label class="form-label mb-1">Seleccioná Documentación</label>

  <select id="select-documentacion"
          multiple
          class="form-control form-control-sm"
          autocomplete="off">
    @foreach($opsDocs as $op)
      <option value="{{ $op->id }}"
        @selected(in_array($op->id, $documentacion_ids, true))>
        {{ $op->nombre }}
      </option>
    @endforeach
  </select>

  @error('documentacion_ids')
    <div class="invalid-feedback d-block">{{ $message }}</div>
  @enderror
</div>

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
<style>
  /* Altura y scroll del dropdown */
  .ts-dropdown, .ts-dropdown .dropdown-content {
    max-height: 320px !important;
    overflow-y: auto !important;
    -webkit-overflow-scrolling: touch;
    z-index: 1055;
  }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('livewire:init', () => {
  let ts;

  function initTS(){
    const el = document.getElementById('select-documentacion');
    if(!el) return;

    // Si ya existe una instancia previa, destruirla para reinit correcto
    if(el.tomselect){
      el.tomselect.destroy();
    }

    ts = new TomSelect(el, {
      // 👇 forzamos MULTI
      mode: 'multi',
      maxItems: 9999,

      plugins: ['remove_button','checkbox_options','dropdown_input'],
      dropdownParent: 'body',
      persist: false,
      create: false,
      maxOptions: 5000,

      onChange(values){
        // values puede venir string o array; normalizamos a array
        const arr = Array.isArray(values) ? values : (values ? [values] : []);
        const ints = arr.map(v => parseInt(v,10)).filter(v => !isNaN(v));
        @this.set('documentacion_ids', ints);
      }
    });

    // Set inicial desde Livewire
    const initial = @json($documentacion_ids ?? []);
    if(initial.length){
      ts.setValue(initial.map(String), false);
    }
  }

  // Inicializar y re-sincronizar tras cada render de Livewire
  Livewire.hook('message.processed', () => {
    initTS();
    const el = document.getElementById('select-documentacion');
    if(el && el.tomselect){
      const liveVals = (@json($documentacion_ids ?? []) || []).map(String);
      el.tomselect.setValue(liveVals, false);
    }
  });

  // Primera vez
  initTS();
});
</script>
@endpush

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
