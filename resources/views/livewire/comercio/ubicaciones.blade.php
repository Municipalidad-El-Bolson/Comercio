<div class="container">
    <x-confirmation-alert />
    <x-loading-indicator />
    @include('livewire.comercio.form')
    <livewire:comercio.movimiento-modal />

    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Panel Principal</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item active">Home</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="d-flex justify-content-between mb-2 p-2">
                <div></div>
                <button class="btn btn-primary btn-sm" wire:click="nuevoComercio">
                    <i class="fa fa-plus mr-1"></i> Nuevo
                </button>
            </div>

            <x-search-input wire:model.live="searchTerm" />

            <div class="card-body">
                <table class="table table-sm table-hover table-bordered">
                    <thead>
                        <tr class="text-center">
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
                            <th colspan="3" class="small text-bold text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ubicaciones as $ubicacion)
                            <tr class="position-relative">
                                    
                                </td>
                                <td class="text-sm text-center">
                                    {{ ucfirst($ubicacion->persona_tipo) }}
                                    <a wire:navigate
                                        href="{{ route('comercio.data', $ubicacion) }}"
                                        class="stretched-link">
                                    </a>
                                </td>
                                <td class="text-sm">
                                    {{ $ubicacion->razon_social }}
                                </td>
                                <td class="text-sm">
                                    {{ $ubicacion->apellido }}
                                </td>
                                <td class="text-sm">
                                    {{ $ubicacion->nombres }}
                                </td>
                                <td class="text-sm">
                                    {{ $ubicacion->dni_cuit }}
                                </td>
                                <td class="text-sm">
                                    {{ $ubicacion->rubro->rubro_madre ?? '' }} 
                                    @if($ubicacion->rubro->subrubro)
                                        - {{ $ubicacion->rubro->subrubro }}
                                    @endif
                                </td>
                                <td class="text-sm">
                                    {{ $ubicacion->domicilio_comercio }}
                                </td>
                                <td class="text-sm text-center">
                                    <span class="badge badge-{{ $ubicacion->estado === 'vigente' ? 'success' : ($ubicacion->estado === 'irregular' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($ubicacion->estado) }}
                                    </span>
                                </td>
                                <td class="text-sm text-center">
                                    {{ ucfirst($ubicacion->situacion) }}
                                </td>
                                <td class="text-sm text-center">
                                    @if ($ubicacion->habilitado)
                                        <span class="badge badge-success">Sí</span>
                                    @else
                                        <span class="badge badge-danger">No</span>
                                    @endif
                                </td>
                                <td class="small text-center">
                                    <a href="#" wire:click.prevent="editaComercio({{ $ubicacion->id }})">
                                        <i class="fa fa-edit text-info mr-2" data-toggle="tooltip" title="Editar Registro"></i>
                                    </a>
                                    <a href="#" wire:click="mostrarMovimientos({{ $ubicacion->id }})">
                                        <i class="fas fa-clipboard-list text-success" data-toggle="tooltip" title="Ver Movimientos"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center text-muted">No hay registros</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="d-flex justify-content-center">
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
