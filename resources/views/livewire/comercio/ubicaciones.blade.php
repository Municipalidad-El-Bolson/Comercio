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
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-2 mb-3 p-2">
                <div class="flex-wrap align-items-center gap-2">

                    {{-- Buscador --}}
                    <div class="input-group input-group-sm" style="min-width:260px;">
                    <span class="input-group-text bg-white">
                        <i class="fas fa-search"></i>
                    </span>
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Buscar comercio, DNI/CUIT o rubro…"
                        wire:model.debounce.300ms="searchTerm">
                    </div>

                </div>

                {{-- Crear nuevo comercio --}}
                <button
                    class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2"
                    wire:click="nuevoComercio">
                    <i class="fas fa-user-plus"></i>
                    <span>Nuevo comercio</span>
                </button>
            </div>


            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-bordered mb-0 align-middle">
                        <thead class="thead-light">
                        <tr class="text-center">
                            <th class="text-sm" style="min-width:240px">Comercio</th>
                            <th class="text-sm" style="width:120px">Nº Hab.</th>
                            <th class="text-sm" style="width:140px">DNI / CUIT</th>
                            <th class="text-sm" style="min-width:160px">Rubro</th>
                            <th class="text-sm" style="min-width:220px">Domicilio</th>
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
  .table td, .table th { vertical-align: middle; }
  .text-truncate { max-width: 100%; }
</style>
@endpush


