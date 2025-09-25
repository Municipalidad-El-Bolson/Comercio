<div class="container-fluid px-1 px-md-3">
    <x-confirmation-alert />
    <x-loading-indicator />
    @include('livewire.comercio.form')
    <livewire:comercio.movimiento-modal />

    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-12 col-md-6">
                    <h1 class="m-0">Lista de comercios</h1>
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
                                <th class="text-sm">Nombre de Fantasía</th>
                                <th class="text-sm">DNI / CUIT</th>
                                <th class="text-sm">Rubro</th>
                                <th class="text-sm">Domicilio Comercio</th>
                                <th class="text-sm">Estado</th>
                                <th class="text-sm text-bold text-center">Subir Actas</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($ubicaciones as $ubicacion)
                            <tr onclick="window.location='{{ route('comercio.data', $ubicacion) }}'"
                                style="cursor:pointer;"
                                @if($ubicacion->situacion === 'clausurado')
                                    class="table-secondary text-muted"
                                @endif
                            >
                                <td>
                                    <span class="font-weight-bold">
                                        {{ $ubicacion->nombre_comercial  ?? '-' }}
                                    </span>
                                    @if($ubicacion->situacion === 'clausurado')
                                        <span class="badge badge-danger ml-2">Clausurado</span>
                                    @endif
                                    <br>
                                    <small class="text-muted">
                                        {{-- Si tiene razón social la mostramos, si no Apellido + Nombre --}}
                                        {{ $ubicacion->razon_social 
                                            ?: trim(($ubicacion->apellido ?? '') . ' ' . ($ubicacion->nombres ?? '')) 
                                            ?: '—' }}
                                    </small>
                                </td>
                                <td class="text-sm">{{ $ubicacion->dni_cuit }}</td>
                                <td class="text-sm">
                                    {{ data_get($ubicacion, 'rubro.subrubro', '') }}
                                </td>
                                <td class="text-sm">{{ $ubicacion->domicilio_comercio }}</td>
                                <td class="text-sm text-center">
                                    <span class="badge badge-{{ $ubicacion->estado === 'vigente' ? 'success' : ($ubicacion->estado === 'irregular' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($ubicacion->estado) }}
                                    </span>
                                </td>
                                <td class="small text-center">
                                    <button type="button" class="btn btn-primary btn-sm" title="Ver Movimientos / Actas"
                                            onclick="event.stopPropagation();" 
                                            wire:click="mostrarMovimientos({{ $ubicacion->id }})">
                                        Actas
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted">No hay registros</td>
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

