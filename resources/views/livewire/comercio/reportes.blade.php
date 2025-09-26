<section class="content">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">Reportes de Habilitaciones Comerciales</h1></div>
      </div>

      {{-- Filtros --}}
      <div class="card card-outline card-secondary mb-3">
        <div class="card-body">
          {{-- Fila 1: RUBRO (TomSelect, igual que en el form) --}}
          <div class="form-row">
            <div class="form-group col-12" wire:ignore>
              <label class="mb-1">Rubro</label>
              <select id="select-rubro-filtro" class="form-control form-control-sm">
                <option value="">-- Todos --</option>
                @foreach($rubroOpts as $op)
                  <option value="{{ $op['id'] }}">{{ $op['subrubro'] }}</option>
                @endforeach
              </select>
            </div>
          </div>

          {{-- Fila 2: Estado / Desde / Hasta / Próx a vencer --}}
          <div class="form-row">
            <div class="form-group col-md-3">
              <label class="mb-1">Estado</label>
              <select class="form-control form-control-sm" wire:model.live="estado">
                <option value="">-- Todos --</option>
                <option value="entramite">021</option>
                <option value="vigente">Alta</option>
                <option value="irregular">032</option>
                <option value="baja">Baja</option>
                <option value="baja_oficio">Baja de oficio</option>
                <option value="sin_efecto">Expediente sin efecto</option>
              </select>
            </div>

            <div class="form-group col-md-3">
              <label class="mb-1">Desde</label>
              <input type="date" class="form-control form-control-sm" wire:model.live="desde">
            </div>

            <div class="form-group col-md-3">
              <label class="mb-1">Hasta</label>
              <input type="date" class="form-control form-control-sm" wire:model.live="hasta">
            </div>

            <div class="form-group col-md-3">
              <label class="mb-1">Próx. a vencer (días)</label>
              <select class="form-control form-control-sm" wire:model.live="proximos_vtos">
                <option value="30">30</option>
                <option value="60">60</option>
                <option value="90">90</option>
              </select>
            </div>
            <div class="form-group col-md-3 d-flex align-items-end">
              <div class="form-check">
                <input id="chk-claus" type="checkbox" class="form-check-input" wire:model.live="solo_clausurados">
                <label for="chk-claus" class="form-check-label">Sólo clausurados</label>
              </div>
            </div>
          </div>

          <button class="btn btn-outline-danger btn-sm" wire:click="exportarPdf">
            <i class="fas fa-file-pdf mr-1"></i> Descargar PDF
          </button>
        </div>
      </div>

      {{-- FILA: dos tarjetas minimizadas por defecto (comparten estado) --}}
      <div class="row" x-data="{ open: null }">
        {{-- Listado general --}}
        <div class="col-lg-6">
          <div class="card border-secondary">
            <div class="card-header d-flex justify-content-between align-items-center">
              <strong>Listado general</strong>
              <button class="btn btn-sm btn-outline-secondary" type="button"
                      @click="open = (open === 'listado' ? null : 'listado')">
                <span class="mr-1" x-text="open === 'listado' ? 'ocultar' : 'ver'"></span>
                <i :class="open === 'listado' ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"></i>
              </button>
            </div>

            <div x-show="open === 'listado'" x-collapse x-cloak>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-sm table-striped mb-0">
                    <thead class="thead-light">
                      <tr>
                        <th>Nombre</th>
                        <th>Estado</th>
                        <th>Rubro</th>
                        <th>Anexos</th>
                        <th>Vto</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($this->listadoGeneral as $u)
                        @php
                          $anexos = $u->rubros
                            ->when($u->rubro_id, fn($c) => $c->where('id', '!=', $u->rubro_id))
                            ->pluck('subrubro')->filter()->values()->all();
                        @endphp
                        <tr>
                          <td>{{ $u->nombre_comercial ?? '-' }}</td>
                          <td>{{ $u->estadoModel->nombre ?? $u->estado }}</td>
                          <td>{{ $u->rubro->subrubro ?? '-' }}</td>
                          <td>
                            @forelse($anexos as $a)
                              <span class="badge badge-secondary mr-1 mb-1">{{ $a }}</span>
                            @empty
                              —
                            @endforelse
                          </td>
                          <td>{{ $u->fecha_vto ? \Illuminate\Support\Carbon::parse($u->fecha_vto)->format('Y-m-d') : '—' }}</td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="card-footer">
                 <nav class="d-flex justify-content-center overflow-auto">
                    {{ $this->listadoGeneral->onEachSide(1)->links('pagination::bootstrap-4') }}
                  </nav>
              </div>
            </div>
          </div>
        </div>

        {{-- Comercios por rubro (principal) --}}
        <div class="col-lg-6">
          <div class="card border-secondary">
            <div class="card-header d-flex justify-content-between align-items-center">
              <strong>Comercios por rubro</strong>
              <button class="btn btn-sm btn-outline-secondary" type="button"
                      @click="open = (open === 'rubros' ? null : 'rubros')">
                <span class="mr-1" x-text="open === 'rubros' ? 'ocultar' : 'ver'"></span>
                <i :class="open === 'rubros' ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"></i>
              </button>
            </div>

            <div x-show="open === 'rubros'" x-collapse x-cloak>
              <div class="card-body p-0">
                <div class="p-2">
                  <small class="text-muted">Total considerado: {{ $this->porRubro['total'] }}</small>
                </div>
                <div class="table-responsive">
                  <table class="table table-sm table-striped mb-0">
                    <thead class="thead-light">
                      <tr>
                        <th>Rubro</th>
                        <th class="text-right">Cantidad</th>
                        <th class="text-right">% del total</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($this->porRubro['items'] as $r)
                        <tr>
                          <td>{{ $r->subrubro }}</td>
                          <td class="text-right">{{ $r->cantidad }}</td>
                          <td class="text-right">{{ $r->porcentaje }}%</td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>

      
      {{-- FILA: abajo de las listas, las dos tarjetas de estado y próximos vtos --}}
      <div class="row mt-3">
        {{-- Comercios por estado --}}
        <div class="col-lg-6">
          <div class="card border-secondary">
            <div class="card-header">Comercios por estado</div>
            <div class="card-body">
              @php $e = $this->porEstado; @endphp
              <div class="row text-center">
                <div class="col-6 col-md-2">
                  <h4 class="mb-0">{{ $e['entramite']['n'] ?? 0 }}</h4>
                  <small>021 ({{ $e['entramite']['pct'] ?? 0 }}%)</small>
                </div>
                <div class="col-6 col-md-2">
                  <h4 class="mb-0">{{ $e['vigente']['n'] ?? 0 }}</h4>
                  <small>Alta ({{ $e['vigente']['pct'] ?? 0 }}%)</small>
                </div>
                <div class="col-6 col-md-2 mt-3 mt-md-0">
                  <h4 class="mb-0">{{ $e['irregular']['n'] ?? 0 }}</h4>
                  <small>032 ({{ $e['irregular']['pct'] ?? 0 }}%)</small>
                </div>
                <div class="col-6 col-md-2 mt-3 mt-md-0">
                  <h4 class="mb-0">{{ $e['baja']['n'] ?? 0 }}</h4>
                  <small>Baja({{ $e['baja']['pct'] ?? 0 }}%)</small>
                </div>
                <div class="col-6 col-md-2 mt-3 mt-md-0">
                  <h4 class="mb-0">{{ $e['baja_oficio']['n'] ?? 0 }}</h4>
                  <small>Baja de oficio ({{ $e['baja_oficio']['pct'] ?? 0 }}%)</small>
                </div>
                <div class="col-6 col-md-2 mt-3 mt-md-0">
                  <h4 class="mb-0">{{ $e['sin_efecto']['n'] ?? 0 }}</h4>
                  <small>Expediente sin efecto ({{ $e['sin_efecto']['pct'] ?? 0 }}%)</small>
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- Próximos a vencer --}}
        <div class="col-lg-6">
          <div class="card border-secondary">
            <div class="card-header">Habilitaciones próximas a vencer ({{ $this->proximos_vtos }} días)</div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                  <thead class="thead-light">
                    <tr>
                      <th>Nombre</th>
                      <th>Rubro</th>
                      <th>Vencimiento</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($this->proximosAVencer as $u)
                      <tr>
                        <td>{{ $u->nombre_comercial ?? '-' }}</td>
                        <td>{{ $u->rubro->subrubro ?? '-' }}</td>
                        <td>{{ $u->fecha_vto ? \Illuminate\Support\Carbon::parse($u->fecha_vto)->format('Y-m-d') : '—' }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div> {{-- row mt-3 --}}
    </div>
  </div>
</section>

@push('scripts')
<script>
  // TomSelect para el filtro de Rubro
  function initRubroFiltroOnce() {
    const el = document.getElementById('select-rubro-filtro');
    if (!el || el.tomselect) return;
    new TomSelect(el, {
      allowEmptyOption: true,
      maxOptions: 8000,
      plugins: ['dropdown_input'],
      // sin persistencia; solo búsqueda local
    });
    // valor inicial desde Livewire (si hubiera)
    const initial = @json((string)($rubro_id ?? ''));
    if (initial && el.tomselect) el.tomselect.setValue(initial, false);

    el.addEventListener('change', (e) => {
      const val = e.target.value || null;
      @this.set('rubro_id', val ? parseInt(val) : null);
    });
  }

  document.addEventListener('livewire:init', () => {
    // Idempotente en cada render
    Livewire.hook('message.processed', () => {
      initRubroFiltroOnce();
      // Mantener el valor si Livewire lo cambia por fuera
      const el = document.getElementById('select-rubro-filtro');
      if (el && el.tomselect) {
        const current = @this.get('rubro_id');
        el.tomselect.setValue(current ? String(current) : '', false);
      }
    });
    initRubroFiltroOnce();
  });
</script>
@endpush
@push('styles')
<style>
  .card-footer nav { overflow-x: auto; }
  .card-footer .pagination { flex-wrap: nowrap; gap: .25rem; }
</style>
@endpush
