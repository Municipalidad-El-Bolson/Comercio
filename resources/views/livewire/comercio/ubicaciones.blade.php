<div class="container-fluid px-1 px-md-3">
    <x-confirmation-alert />
    <x-loading-indicator />
    @include('livewire.comercio.form')
    <livewire:comercio.movimiento-modal />

    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-12 col-md-6">
                    <h1 class="m-0">Panel Principal</h1>
                </div>
                <div class="col-12 col-md-6">
                    <ol class="breadcrumb float-md-right">
                        <li class="breadcrumb-item active">Home</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid px-0">
        <div class="card">
            <div class="d-flex flex-column flex-md-row justify-content-between mb-2 p-2">
                <div></div>
                <button class="btn btn-primary btn-sm mt-2 mt-md-0" wire:click="nuevoComercio">
                    <i class="fa fa-plus mr-1"></i> Nuevo
                </button>
            </div>

            <x-search-input wire:model.live="searchTerm" />

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-bordered mb-0">
                        <thead>
                            <tr class="text-center">
                                <th class="text-sm">N° HC</th>
                                <th class="text-sm">Persona</th>
                                <th class="text-sm">Razón Social</th>
                                <th class="text-sm">Apellido</th>
                                <th class="text-sm">Nombres</th>
                                <th class="text-sm">DNI / CUIT</th>
                                <th class="text-sm">Rubro</th>
                                <th class="text-sm">Domicilio Comercio</th>
                                <th class="text-sm">Estado</th>
                                <th class="text-sm">Situación</th>
                                <th class="text-sm">Habilitado</th>
                                <th class="text-sm text-bold text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ubicaciones as $ubicacion)
                                <tr onclick="window.location='{{ route('comercio.data', $ubicacion) }}'"
                                    style="cursor:pointer;">
                                    <td class="text-sm text-center">{{ $ubicacion->hc }}</td>
                                    <td class="text-sm text-center">{{ ucfirst($ubicacion->persona_tipo) }}</td>
                                    <td class="text-sm">{{ $ubicacion->razon_social }}</td>
                                    <td class="text-sm">{{ $ubicacion->apellido }}</td>
                                    <td class="text-sm">{{ $ubicacion->nombres }}</td>
                                    <td class="text-sm">{{ $ubicacion->dni_cuit }}</td>
                                    <td class="text-sm">
                                        {{ $ubicacion->rubro->rubro_madre ?? '' }}
                                        @if ($ubicacion->rubro->subrubro)
                                            - {{ $ubicacion->rubro->subrubro }}
                                        @endif
                                    </td>
                                    <td class="text-sm">{{ $ubicacion->domicilio_comercio }}</td>
                                    <td class="text-sm text-center">
                                        <span
                                            class="badge badge-{{ $ubicacion->estado === 'vigente' ? 'success' : ($ubicacion->estado === 'irregular' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($ubicacion->estado) }}
                                        </span>
                                    </td>
                                    <td class="text-sm text-center">{{ ucfirst($ubicacion->situacion) }}</td>
                                    <td class="text-sm text-center">
                                        @if ($ubicacion->habilitado)
                                            <span class="badge badge-success">Sí</span>
                                        @else
                                            <span class="badge badge-danger">No</span>
                                        @endif
                                    </td>
                                    <td class="small text-center">
                                        <button type="button" class="btn btn-primary btn-sm"
                                            title="Ver Movimientos / Actas" onclick="event.stopPropagation();"
                                            wire:click="mostrarMovimientos({{ $ubicacion->id }})">
                                            Actas
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center text-muted">No hay registros</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-center mt-3">
                    {{ $ubicaciones->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.addEventListener('mostrar-modal-movimientos', () => {
        $('#modalMovimientos').modal('show');
    });
</script>
