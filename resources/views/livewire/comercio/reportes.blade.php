<section class="content" data-autosave="off">
<div class="modern-admin-page">
  <div class="content-header">
    <div class="container-fluid">
      <div class="text-center mb-3">
          <h1 class="m-0">Reportes</h1>
      </div>
    </div>
  </div>

  <div class="container-fluid">

      {{-- Filtros --}}
      <div class="card card-outline card-secondary mb-3">
        <div class="card-body py-3">

          <div class="d-flex flex-column flex-md-row flex-wrap align-items-md-center gap-4">

            {{-- 🌟 Rubro General --}}
            <div class="d-flex flex-column" style="min-width:220px;" wire:ignore>
              <label class="text-muted small mb-1">Rubro general</label>
              <select id="select-report-rubro-general" class="form-control form-control-sm shadow-sm">
                  <option value="">-- Todos los rubros --</option>
                  @foreach($rubroGenerales as $general)
                    <option value="{{ $general }}">{{ $general }}</option>
                  @endforeach
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
            <div class="d-flex flex-column" style="min-width:180px;" wire:ignore>
              <label class="text-muted small mb-1">Estado</label>
              <select id="select-report-estado" class="form-control form-control-sm shadow-sm">
                <option value="">-- Todos --</option>
                <option value="todas_bajas">Todas las bajas</option>
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
            <div class="d-flex flex-column" style="min-width:180px;" wire:ignore>
              <label class="text-muted small mb-1">Próx. a vencer (días)</label>
              <select id="select-report-proximos" class="form-control form-control-sm shadow-sm">
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

            <div class="d-flex flex-column" style="min-width:190px;">
              <label class="text-muted small mb-1 invisible">-</label>
              <div class="form-check">
                <input id="chk-baja-temporaria" type="checkbox" class="form-check-input" wire:model.live="solo_baja_temporaria">
                <label for="chk-baja-temporaria" class="form-check-label">Sólo baja temporaria</label>
              </div>
            </div>

          </div>
          <hr>
          @php
            $exportParams = [
              'rubro_id' => $rubro_id,
              'rubroGeneral' => $rubroGeneral,
              'estado' => $estado,
              'desde' => $desde,
              'hasta' => $hasta,
              'proximos_vtos' => $proximosVtos,
              'solo_clausurados' => $solo_clausurados ? 1 : 0,
              'solo_baja_temporaria' => $solo_baja_temporaria ? 1 : 0,
            ];
          @endphp
          <a class="btn btn-outline-danger btn-sm shadow-sm"
             href="{{ route('reportes.pdf', $exportParams) }}">
            <i class="fas fa-file-pdf mr-1"></i> Descargar PDF
          </a>
          <a class="btn btn-outline-success btn-sm shadow-sm ml-2"
             href="{{ route('reportes.excel', $exportParams) }}">
            <i class="fas fa-file-excel mr-1"></i> Descargar Excel
          </a>
          <button type="button" class="btn btn-outline-secondary btn-sm shadow-sm ml-2" wire:click="limpiarFiltros">
            <i class="fas fa-eraser mr-1"></i> Limpiar filtros
          </button>
          <div class="text-muted small mt-2">Las fechas y próximos vencimientos sólo filtran cuando se seleccionan.</div>

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
                        <th class="text-right">Unidades</th>
                        <th class="text-right">Plazas</th>
                        <th>Vto</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($this->listadoGeneral as $u)
                        <tr>
                          <td>{{ $u->nombre_comercial ?? '-' }}</td>
                          <td>{{ $u->estadoModel->nombre ?? $u->estado }}</td>
                          <td>{{ $u->rubro->subrubro ?? '-' }}</td>
                          <td class="text-right">{{ $u->alojamiento_unidades ?? '-' }}</td>
                          <td class="text-right">{{ $u->alojamiento_plazas ?? '-' }}</td>
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

        {{-- Gráfico de comercios por rubro --}}
        <div class="col-lg-6">
          <div class="card border-secondary">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
              <div><strong>Gráficos</strong><small class="d-block text-muted">Distribución de comercios por rubro</small></div>
              <button type="button" class="btn btn-outline-primary btn-sm" onclick="descargarGraficoRubros()"><i class="fas fa-image mr-1"></i> Descargar imagen</button>
            </div>
            <div class="card-body">
              @include('comercio.partials.rubros-pie', [
                'chartItems' => $this->porRubro['items'],
                'chartTotal' => $this->porRubro['total'],
              ])
            </div>
          </div>
        </div>
      </div> {{-- row mt-3 --}}
    </div>
  </div>
</div>
</section>

{{-- Integración TomSelect retirada: el select nativo mantiene estable el filtro en Livewire 3.
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
--}}
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

  .rubros-pie-layout { display:grid; grid-template-columns:minmax(210px,.85fr) minmax(220px,1.15fr); gap:1rem; align-items:center; }
  .modern-admin-page select.form-control {
    min-height:42px !important; padding:.45rem 2.25rem .45rem .75rem !important;
    appearance:none; border:1px solid #c6d6e2 !important; border-radius:.68rem !important;
    background-color:#fbfdff !important;
    background-image:linear-gradient(45deg,transparent 50%,#52718a 50%),linear-gradient(135deg,#52718a 50%,transparent 50%) !important;
    background-position:calc(100% - 17px) 18px,calc(100% - 12px) 18px !important;
    background-size:5px 5px,5px 5px !important; background-repeat:no-repeat !important;
    color:#29445d; box-shadow:0 3px 10px rgba(30,58,86,.06) !important;
  }
  .modern-admin-page select.form-control:focus { border-color:#65a2ce !important; box-shadow:0 0 0 3px rgba(40,109,168,.12) !important; }
  .modern-admin-page select.form-control option { padding:.55rem; background:#fff; color:#29445d; }
  .modern-admin-page .card:has(.ts-wrapper) { overflow:visible !important; position:relative; z-index:20; }
  .modern-admin-page .ts-wrapper.dropdown-active { z-index:1002; }
  .modern-admin-page .ts-dropdown { z-index:1003 !important; }
  .rubros-pie-chart svg { display:block; width:100%; max-width:290px; margin:auto; filter:drop-shadow(0 8px 12px rgba(23,56,95,.12)); }
  .rubros-pie-chart .pie-total { fill:#17385f; font-size:22px; font-weight:800; }
  .rubros-pie-chart .pie-caption { fill:#718399; font-size:10px; }
  .rubros-pie-legend { display:block; max-height:300px; overflow:auto; padding-right:.25rem; }
  .pie-legend-row { display:grid; grid-template-columns:10px minmax(0,1fr) 34px 54px; gap:.45rem; align-items:center; padding:.38rem .2rem; border-bottom:1px solid #edf1f5; font-size:.75rem; }
  .pie-legend-color { width:10px; height:10px; border-radius:3px; }
  .pie-legend-label { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .pie-legend-row strong,.pie-legend-row small { text-align:right; }
  .pie-legend-row small { color:#718399; }
  @media(max-width:767.98px) { .rubros-pie-layout { grid-template-columns:1fr; } .rubros-pie-legend { max-height:230px; } }

</style>
@endpush

@script
<script>
  const initReportSelect = (id, property, numeric = false) => {
    const element = document.getElementById(id);
    if (!element || element.tomselect) return;
    new TomSelect(element, {
      allowEmptyOption: true,
      create: false,
      maxOptions: 8000,
      plugins: ['dropdown_input'],
      onChange(value) {
        $wire.set(property, value === '' ? null : (numeric ? parseInt(value, 10) : value));
      }
    });
  };
  initReportSelect('select-report-rubro-general', 'rubroGeneral');
  initReportSelect('select-rubro-filtro', 'rubro_id', true);
  initReportSelect('select-report-estado', 'estado');
  initReportSelect('select-report-proximos', 'proximosVtos', true);

  window.addEventListener('reportFiltersCleared', () => {
    ['select-report-rubro-general','select-rubro-filtro','select-report-estado','select-report-proximos'].forEach((id) => {
      const element = document.getElementById(id);
      if (element?.tomselect) element.tomselect.clear(true);
    });
  });

  window.descargarGraficoRubros = function () {
    const svg = document.querySelector('.rubros-pie-chart svg');
    if (!svg) return;
    const clone = svg.cloneNode(true);
    clone.setAttribute('width', '1200');
    clone.setAttribute('height', '1200');
    clone.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
    const source = new XMLSerializer().serializeToString(clone);
    const image = new Image();
    image.onload = function () {
      const canvas = document.createElement('canvas');
      canvas.width = 1200;
      canvas.height = 1200;
      const context = canvas.getContext('2d');
      context.fillStyle = '#ffffff';
      context.fillRect(0, 0, canvas.width, canvas.height);
      context.drawImage(image, 0, 0, canvas.width, canvas.height);
      URL.revokeObjectURL(image.src);
      const link = document.createElement('a');
      link.download = 'grafico-comercios-por-rubro.png';
      link.href = canvas.toDataURL('image/png');
      link.click();
    };
    image.src = URL.createObjectURL(new Blob([source], { type: 'image/svg+xml;charset=utf-8' }));
  };
</script>
@endscript
