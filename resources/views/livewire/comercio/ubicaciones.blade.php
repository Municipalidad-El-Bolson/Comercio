<div class="container">
    <x-confirmation-alert />
    <x-loading-indicator />
    @include('livewire.comercio.form')
    <livewire:comercio.movimiento-modal />
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">HC - Panel Principal</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item active">Home</li>
                        <li class="breadcrumb-item active"><a href="/mapas">HC-Mapa</a></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            {{-- <div class="card-header bg-gradient-info">
                <h5 class="text-white font-weight-bolder ">Habilitaciones Comerciales</h5>
            </div> --}}
            <div class="d-flex justify-content-between mb-2">
                <div></div> {{-- espacio a la izquierda por si querés algo después --}}
                <button class="btn btn-primary btn-sm mr-2 mt-2" wire:click="nuevoComercio">
                    <i class="fa fa-plus mr-1"></i> Nuevo
                </button>
            </div>
            <x-search-input wire:model.live="searchTerm" />
            <div class="card-body">
                <table class="table table-sm table-hover table-bordered">
                    <thead>
                        <tr>
                            <th class="text-sm">Razón Social</th>
                            <th class="text-sm">Apellido</th>
                            <th class="text-sm">Nombres</th>
                            <th class="text-sm">DNI</th>
                            <th class="text-sm">Rubro</th>
                            <th class="text-sm">Dirección</th>
                            <th class="text-sm">Estado</th>
                            <th class="text-sm">Habilitado</th>
                            <th colspan="3" class="small text-bold text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ubicaciones as $ubicacion)
                            <tr>
                                <td class="text-sm">{{ $ubicacion->razon_social }}</td>
                                <td class="text-sm">{{ $ubicacion->apellido }}</td>
                                <td class="text-sm">{{ $ubicacion->nombres }}</td>
                                <td class="text-sm">{{ $ubicacion->dni }}</td>
                                <td class="text-sm">{{ $ubicacion->rubro->rubro }}</td>
                                <td class="text-sm">{{ $ubicacion->direccion }}</td>
                                <td class="text-sm">{{ ucfirst($ubicacion->estado) }}</td>
                                <td class="text-sm text-center">
                                    @if ($ubicacion->habilitado)
                                        <span class="badge badge-success">Sí</span>
                                    @else
                                        <span class="badge badge-danger">No</span>
                                    @endif
                                </td>
                                <td class="small text-center">
                                    <a href="#" wire:click.prevent="editaComercio({{ $ubicacion->id }})">
                                        <i class="fa fa-edit text-info mr-2" data-toggle="tooltip"
                                            title="Editar Registro"></i>
                                    </a>
                                    <a href="#" wire:click="mostrarMovimientos({{ $ubicacion->id }})">
                                        <i class="fas fa-clipboard-list text-success"></i>
                                    </a>
                                    {{-- <a href="#">
                                        <i class="fas fa-comment-dollar text-danger"
                                            wire:click.prevent="$emit('abrirModalMovimientos', {{ $ubicacion->id }})"
                                            title="Tramites"></i>
                                    </a> --}}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="d-flex justify-content-center">
                    {{ $ubicaciones->links() }}
                </div>


            </div>
        </div>
    </div>
</div>
{{-- Llama al modal --}}
<script>
    window.addEventListener('mostrar-modal-movimientos', () => {
        $('#modalMovimientos').modal('show');
    });
</script>
