<div class="container-fluid px-1 px-md-3">
    <x-confirmation-alert />
    <x-loading-indicator />
    @include('livewire.comercio.form')
    <livewire:comercio.movimiento-modal />

    <div class="content-header">
        <div class="container-fluid">

                <div class="text-center mb-3">
                    <h1 class="m-0 pb-2 border-bottom" style="font-size:2.50rem;">Lista de comercios</h1>

            </div>
        </div>
    </div>

    <div class="container-fluid px-0">
        <div class="card">
            <div class="card mb-3">
                <div class="card-body py-3">

                    <div class="d-flex flex-column flex-md-row align-items-md-center gap-3">

                        {{-- Select de Rubro --}}
                        <div class="d-flex flex-column" style="min-width:250px;">
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

                        {{-- Buscador --}}
                        <div class="flex-grow-1">
                            <label class="text-muted small mb-1">Buscar comercio</label>
                            <div class="input-group input-group-sm shadow-sm">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text"
                                    wire:model.live="searchTerm"
                                    class="form-control"
                                    autocomplete="off"
                                    placeholder="Nombre comercial, Nº Hab., Titular, DNI/CUIT, Rubro o Domicilio.">
                            </div>
                        </div>

                        {{-- Botón Nuevo comercio --}}
                        <div class="pt-1 pt-md-0 text-md-end">
                            <label class="invisible small">.</label> {{-- Alinea el botón con los labels --}}
                            <button
                                class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2 shadow-sm w-100 w-md-auto"
                                wire:click="nuevoComercio">
                                <i class="fas fa-user-plus"></i> Nuevo comercio
                            </button>
                        </div>

                    </div>

                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-bordered mb-0 align-middle">
                        <thead class="thead-light">
                        <tr class="text-center">
                            <th class="text-sm" >Comercio</th>
                            <th class="text-sm" style="width:120px">Nº Hab.</th>
                            <th class="text-sm" style="width:140px">DNI / CUIT</th>
                            <th class="text-sm" >Rubro</th>
                            <th class="text-sm" >Domicilio</th>
                            <th class="text-sm" style="width:120px">Estado</th>
                            <th class="text-sm text-center" style="width:120px">Subir Actas</th>
                        </tr>
                        </thead>

                        <tbody>
                            @forelse ($ubicaciones as $ubicacion)
                                @php
                                    // Nº habilitación (si la tenés con relación)
                                    $hab = data_get($ubicacion, 'habilitacionActual.numero');

                                    // Subtítulo (titular)
                                    $subtitulo = $ubicacion->razon_social
                                        ?: trim(($ubicacion->apellido ?? '') . ' ' . ($ubicacion->nombres ?? ''));
                                    $subtitulo = $subtitulo !== '' ? $subtitulo : '—';

                                    // Preferí columnas normalizadas si existen
                                    $baseRaw   = trim((string)($ubicacion->estado_base ?? ''));
                                    $labelRaw  = trim((string)($ubicacion->estado_label ?? ''));

                                    // Derivar base cuando no venga seteada
                                    if ($baseRaw === '') {
                                        $estadoRaw = mb_strtolower(trim((string)($ubicacion->estado ?? '')));
                                        $baseRaw = match ($estadoRaw) {
                                            'entramite','en tramite','en trámite','en_tramite','en-tramite','021' => '021',
                                            'irregular','032'                                                    => '032',
                                            '040'                                                                => '040',
                                            'baja'                                                               => 'baja',
                                            'baja_oficio','baja de oficio'                                       => 'baja_oficio',
                                            'sin_efecto','expediente sin efecto','exp_sin_efecto'                => 'exp_sin_efecto',
                                            default                                                              => '021',
                                        };
                                    }

                                    // Si no tenemos label, usar uno "lindo" según base
                                    if ($labelRaw === '') {
                                        $labelRaw = match ($baseRaw) {
                                            '021'            => '021',
                                            '032'            => '032',
                                            '040'            => '040',
                                            'baja'           => 'Baja',
                                            'baja_oficio'    => 'Baja de Oficio',
                                            'exp_sin_efecto' => 'Expediente sin Efecto',
                                            default          => strtoupper($baseRaw),
                                        };
                                    }

                                    // Extraer "cambio" cuando el label viene como "021 - Algo" / "032 - Algo" / "040 - Algo"
                                    $cambio = null;
                                    if (preg_match('/^\s*(021|032|040)\s*-\s*(.+)$/u', $labelRaw, $m)) {
                                        $labelBase = trim($m[1]);   // 021 / 032 / 040
                                        $cambio    = trim($m[2]);   // texto del cambio
                                    } else {
                                        $labelBase = $labelRaw;     // ya viene amigable (Baja / Baja de Oficio / etc.)
                                    }

                                    // Color del badge por base
                                    $badgeEstado = match ($baseRaw) {
                                        '021'            => 'success',   // 021
                                        '032'            => 'warning',   // 032
                                        '040'            => 'info',      // 040 
                                        'baja'           => 'danger',
                                        'baja_oficio',
                                        'exp_sin_efecto' => 'dark',
                                        default          => 'light',
                                    };

                                    $estadoVisual = match ($baseRaw) {
                                        '021' => '021/90',
                                        '032' => '032/01',
                                        '040' => '040/25',
                                        default => $labelRaw,
                                    };
                                @endphp

                                <tr onclick="window.location='{{ route('comercio.data', $ubicacion) }}'"
                                    style="cursor:pointer;"
                                    @if($ubicacion->situacion === 'clausurado') class="table-secondary text-muted" @endif
                                >
                                    {{-- Comercio --}}
                                    <td>
                                        <div class="d-flex align-items-start">
                                            <div class="mr-2 mt-1">
                                                <i class="fas fa-store text-muted"></i>
                                            </div>
                                            <div class="text-truncate" style="max-width: 560px;">
                                                <div class="font-weight-bold text-truncate">
                                                    {{ $ubicacion->nombre_comercial ?? '-' }}
                                                    @if($ubicacion->situacion === 'clausurado')
                                                        <span class="badge badge-danger ml-2">Clausurado</span>
                                                    @endif
                                                </div>
                                                <div class="small text-muted text-truncate">
                                                    {{ $subtitulo }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Nº Hab. --}}
                                    <td class="text-sm text-center">
                                        @if($hab)
                                            <span class="badge badge-light border" title="Última habilitación">
                                                <i class="fas fa-file-signature mr-1"></i>{{ $hab }}
                                            </span>
                                        @else
                                            —
                                        @endif
                                    </td>

                                    {{-- DNI/CUIT --}}
                                    <td class="text-sm text-center">
                                        {{ $ubicacion->dni_cuit ?: '—' }}
                                    </td>

                                    {{-- Rubro --}}
                                    <td class="text-sm">
                                        {{ data_get($ubicacion, 'rubro.subrubro', '—') }}
                                    </td>

                                    {{-- Domicilio --}}
                                    <td class="text-sm text-truncate">
                                        <i class="fas fa-map-marker-alt text-muted mr-1"></i>
                                        {{ $ubicacion->domicilio_comercio ?: '—' }}
                                    </td>

                                    {{-- Estado (muestra 021/032/040 o el label de baja…) --}}
                                    <td class="text-sm text-center">
                                        <span class="badge badge-{{ $badgeEstado }}">
                                            {{ $estadoVisual }}
                                        </span>
                                        @if($cambio)
                                            <div><small class="text-muted">{{ $cambio }}</small></div>
                                        @endif
                                    </td>

                                    {{-- Acciones --}}
                                    <td class="small text-center">
                                        <button type="button"
                                                class="btn btn-primary btn-sm"
                                                title="Ver Movimientos / Actas"
                                                onclick="event.stopPropagation();"
                                                wire:click="mostrarMovimientos({{ $ubicacion->id }})">
                                            Actas
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No hay registros</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>

                    <div class="d-flex justify-content-center mt-3">
                    {{ $ubicaciones->links() }}
                    </div>
            </div>
            @include('livewire.comercio.mapa-modal')
        </div>
    </div>
</div>

@push('scripts')
<script>
  window.addEventListener('mostrar-modal-movimientos', () => {
    $('#modalMovimientos').modal('show');
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



