<section class="content">
  <div class="content-header">
    <div class="container-fluid">
      <div class="text-center mb-3">
          <h1 class="m-0 pb-2 border-bottom" style="font-size:2.50rem;">Reportes</h1>
      </div>

      {{-- Filtros --}}
      <div class="card card-outline card-secondary mb-3">
        <div class="card-body py-3">

          <div class="d-flex flex-column flex-md-row flex-wrap align-items-md-center gap-4">

            {{-- 🌟 Rubro General --}}
            <div class="d-flex flex-column" style="min-width:220px;">
              <label class="text-muted small mb-1">Rubro general</label>
              <select class="form-control form-control-sm shadow-sm"
                      wire:model.live="rubroGeneral">
                  <option value="">-- Todos los rubros --</option>
                  <option value="ALOJAMIENTO DE ALQUILER TURISTICO">Alojamiento de alquiler turistico</option>
                  <option value="GASTRONOMIA">Gastronomía</option>
                  <option value="CENTRO DE ESTETICA Y SPA">Centro de esterica y spa</option>
                  <option value="LAVADEROS DE AUTOS">Lavaderos de autos</option>
                  <option value="LUBRICENTROS">Lubricentros</option>
                  <option value="TALLER DEL AUTOMOTOR">Taller del automotor</option>
                  <option value="SALUD">Salud</option>
                  <option value="GIMNASIOS">Gimnasios</option>
                  <option value="ALQUILER DE CANCHAS">Alquiler de canchas</option>
                  <option value="VENTA DE ARTESANIAS Y PRODUCTOS REGIONALES">Venta de artesanias y productos regionales</option>
                  <option value="SALA DE ELABORACION">Sala de elaboracion</option>
                  <option value="COCINA DOMICILIARIA">Cocina domiciliaria</option>
                  <option value="SERVICIOS">Servicios</option>
                  <option value="COMERCIO">Comercio</option>
                  <option value="AGRO / PRODUCCION">Agro/Produccion</option>
                  <option value="OTROS">Otros</option>
              </select>
            </div>

            {{-- Rubro específico con TomSelect --}}
            <div class="d-flex flex-column" style="min-width:250px;" wire:ignore>
              <label class="text-muted small mb-1">Rubro (específico)</label>
              <select id="select-rubro-filtro" class="form-control form-control-sm shadow-sm">
                <option value="">-- Todos --</option>
                @foreach($rubroOpts as $op)
                  <option value="{{ $op['id'] }}">{{ $op['subrubro'] }}</option>
                @endforeach
              </select>
            </div>

            {{-- Estado --}}
            <div class="d-flex flex-column" style="min-width:180px;">
              <label class="text-muted small mb-1">Estado</label>
              <select class="form-control form-control-sm shadow-sm" wire:model.live="estado">
                <option value="">-- Todos --</option>
                <option value="entramite">021/90</option>
                <option value="irregular">032/01</option>
                <option value="040">040/25</option>
                <option value="baja">Baja</option>
                <option value="baja_oficio">Baja de oficio</option>
                <option value="sin_efecto">Expediente sin efecto</option>
              </select>
            </div>

            {{-- Desde --}}
            <div class="d-flex flex-column" style="min-width:160px;">
              <label class="text-muted small mb-1">Desde</label>
              <input type="date" class="form-control form-control-sm shadow-sm" wire:model.live="desde">
            </div>

            {{-- Hasta --}}
            <div class="d-flex flex-column" style="min-width:160px;">
              <label class="text-muted small mb-1">Hasta</label>
              <input type="date" class="form-control form-control-sm shadow-sm" wire:model.live="hasta">
            </div>

            {{-- Próximos a vencer --}}
            <div class="d-flex flex-column" style="min-width:180px;">
              <label class="text-muted small mb-1">Próx. a vencer (días)</label>
              <select class="form-control form-control-sm shadow-sm" wire:model.live="proximos_vtos">
                <option value="">-- Todos --</option>
                <option value="30">30</option>
                <option value="60">60</option>
                <option value="90">90</option>
              </select>
            </div>

            {{-- Solo clausurados --}}
            <div class="d-flex flex-column" style="min-width:160px;">
              <label class="text-muted small mb-1 invisible">-</label>
              <div class="form-check">
                <input id="chk-claus" type="checkbox" class="form-check-input" wire:model.live="solo_clausurados">
                <label for="chk-claus" class="form-check-label">Sólo clausurados</label>
              </div>
            </div>

          </div>
        </br>
          <hr>
          </br>
          <a class="btn btn-outline-danger btn-sm shadow-sm"
             href="{{ route('reportes.pdf', [
                'rubro_id' => $rubro_id,
                'rubroGeneral' => $rubroGeneral,
                'estado' => $estado,
                'desde' => $desde,
                'hasta' => $hasta,
                'proximos_vtos' => $proximos_vtos,
                'solo_clausurados' => $solo_clausurados ? 1 : 0,
             ]) }}">
            <i class="fas fa-file-pdf mr-1"></i> Descargar PDF
          </a>

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
                  <small>021/90 ({{ $e['entramite']['pct'] ?? 0 }}%)</small>
                </div>
                <div class="col-6 col-md-2 mt-3 mt-md-0">
                  <h4 class="mb-0">{{ $e['irregular']['n'] ?? 0 }}</h4>
                  <small>032/01 ({{ $e['irregular']['pct'] ?? 0 }}%)</small>
                </div>
                <div class="col-6 col-md-2 mt-3 mt-md-0">
                  <h4 class="mb-0">{{ $e['040']['n'] ?? 0 }}</h4>
                  <small>040/25 ({{ $e['040']['pct'] ?? 0 }}%)</small>
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

  /* ---------- General ---------- */
  .card {
    border-radius: 0.7rem !important;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #e2e2e2 !important;
  }

  .card-header {
    font-weight: 600;
    font-size: 0.95rem;
    background: #f7f9fb !important;
    border-bottom: 1px solid #e5e5e5 !important;
  }

  .card-body {
    background: #ffffff;
    padding-top: 1.15rem !important;
  }

  .titulo-comercio {
    font-size: 1.9rem !important;
    font-weight: 800 !important;
    letter-spacing: -0.5px;
  }

  /* ---------- Etiquetas / Categorías ---------- */
  .badge {
    padding: 0.45em 0.65em !important;
    font-size: 0.75rem !important;
    font-weight: 600 !important;
    border-radius: 0.35rem !important;
  }

  .badge-light { 
    background: #f2f2f2 !important; 
    color: #555 !important; 
  }

  .badge-success { background-color: #2ecc71 !important; }
  .badge-info    { background-color: #3498db !important; }
  .badge-warning { background-color: #f1c40f !important; color:#333 !important; }
  .badge-danger  { background-color: #e74c3c !important; }

  /* ---------- Títulos pequeños ---------- */
  .text-muted.small {
    font-size: 0.72rem !important;
    letter-spacing: 0.3px;
    text-transform: uppercase;
  }

  .font-weight-bold {
    font-size: 0.92rem;
  }

  /* ---------- Encabezado general ---------- */
  .content-header {
    border-bottom: 1px solid #e5e5e5;
    background: linear-gradient(to right, #ffffff, #fafafa);
    padding-bottom: 1rem;
    padding-top: 0.5rem;
  }

  /* ---------- Botonera derecha ---------- */
  .btn-group .btn {
    border-radius: 0.4rem !important;
    font-size: 0.78rem;
  }

  .btn-primary {
    background: #4a6cf7 !important;
    border-color: #4a6cf7 !important;
  }

  .btn-danger {
    background: #e74c3c !important;
    border-color: #e74c3c !important;
  }

  .btn-secondary {
    background: #bdc3c7 !important;
    border-color: #bdc3c7 !important;
  }

  /* ---------- Separadores ---------- */
  hr.my-2 {
    border-top: 1px solid #ddd !important;
  }

  /* ---------- Tablas ---------- */
  table.table {
    border-radius: 0.5rem !important;
    overflow: hidden;
  }

  .table thead th {
    background: #f7f9fb !important;
    font-weight: 600 !important;
  }

  .table tbody tr td {
    font-size: 0.82rem !important;
  }

  /* ---------- Badges de documentación ---------- */
  .docs-box {
    transition: 0.2s;
  }

  .docs-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 6px rgba(0,0,0,0.12);
  }

</style>
@endpush
