<div class="container-fluid px-1 px-md-3">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-7">
                    <h1 class="m-0">Reportes de Habilitaciones Comerciales</h1>
                </div>
                <div class="col-sm-5">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Home</a></li>
                        <li class="breadcrumb-item active">Reportes</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-md-4 mb-3">
            <button type="button"
                class="btn btn-block text-left h-100 {{ $reporteActivo === 'proximas-avencer' ? 'btn-primary' : 'btn-outline-primary' }}"
                wire:click="mostrarReporte('proximas-avencer')">
                <span class="d-block font-weight-bold">Habilitaciones proximas a vencer</span>
                <span class="small">{{ $proximasAVencer->count() }} comercios en los proximos {{ $dias }} dias</span>
            </button>
        </div>
        <div class="col-12 col-md-4 mb-3">
            <button type="button"
                class="btn btn-block text-left h-100 {{ $reporteActivo === 'vencidas' ? 'btn-danger' : 'btn-outline-danger' }}"
                wire:click="mostrarReporte('vencidas')">
                <span class="d-block font-weight-bold">Habilitaciones vencidas</span>
                <span class="small">{{ $vencidas->count() }} comercios con vencimiento anterior a hoy</span>
            </button>
        </div>
        <div class="col-12 col-md-4 mb-3">
            <button type="button"
                class="btn btn-block text-left h-100 {{ $reporteActivo === 'estados' ? 'btn-secondary' : 'btn-outline-secondary' }}"
                wire:click="mostrarReporte('estados')">
                <span class="d-block font-weight-bold">Resumen por estado</span>
                <span class="small">{{ $porEstado->sum() }} comercios registrados</span>
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            @if ($reporteActivo === 'proximas-avencer')
                <h3 class="card-title mb-0">Habilitaciones proximas a vencer ({{ $dias }} dias)</h3>
                <span class="badge badge-primary">{{ $proximasAVencer->count() }}</span>
            @elseif ($reporteActivo === 'vencidas')
                <h3 class="card-title mb-0">Habilitaciones vencidas</h3>
                <span class="badge badge-danger">{{ $vencidas->count() }}</span>
            @else
                <h3 class="card-title mb-0">Resumen por estado</h3>
                <span class="badge badge-secondary">{{ $porEstado->sum() }}</span>
            @endif
        </div>

        <div class="card-body p-0">
            @if ($reporteActivo === 'estados')
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Estado</th>
                                <th class="text-right">Cantidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($porEstado as $estado => $total)
                                <tr>
                                    <td>{{ ucfirst($estado ?: 'Sin estado') }}</td>
                                    <td class="text-right">{{ $total }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted">No hay registros</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                @php
                    $filas = $reporteActivo === 'vencidas' ? $vencidas : $proximasAVencer;
                @endphp
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover mb-0">
                        <thead>
                            <tr>
                                <th>N° HC</th>
                                <th>Comercio</th>
                                <th>Responsable</th>
                                <th>DNI / CUIT</th>
                                <th>Rubro</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Vencimiento</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($filas as $ubicacion)
                                <tr>
                                    <td>{{ $ubicacion->hc }}</td>
                                    <td>
                                        <a href="{{ route('comercio.data', $ubicacion) }}">
                                            {{ $ubicacion->nombre_comercial ?: $ubicacion->razon_social ?: 'Sin nombre comercial' }}
                                        </a>
                                    </td>
                                    <td>
                                        {{ $ubicacion->persona_tipo === 'juridica'
                                            ? $ubicacion->razon_social
                                            : trim(($ubicacion->apellido ?? '') . ' ' . ($ubicacion->nombres ?? '')) }}
                                    </td>
                                    <td>{{ $ubicacion->dni_cuit }}</td>
                                    <td>
                                        {{ $ubicacion->rubro->rubro_madre ?? '' }}
                                        @if (optional($ubicacion->rubro)->subrubro)
                                            - {{ $ubicacion->rubro->subrubro }}
                                        @endif
                                    </td>
                                    <td>{{ ucfirst($ubicacion->tipo_habilitacion ?? 'definitiva') }}</td>
                                    <td>{{ ucfirst($ubicacion->estado) }}</td>
                                    <td>{{ optional($ubicacion->fecha_vencimiento_calculada)->format('d/m/Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        No hay habilitaciones para este reporte.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
