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
                            // Última habilitacion (viene eager-loaded con limit(1))
                            $hab = $ubicacion->relationLoaded('habilitaciones')
                                        ? optional($ubicacion->habilitaciones->first())->numero
                                        : null;

                            // Subtítulo: razón social o Apellido + Nombres
                            $subtitulo = $ubicacion->razon_social
                                ?: trim(($ubicacion->apellido ?? '') . ' ' . ($ubicacion->nombres ?? ''));
                            $subtitulo = $subtitulo !== '' ? $subtitulo : '—';

                            $estado = strtolower($ubicacion->estado ?? '');
                            $badgeEstado = $estado === 'vigente' ? 'success' : ($estado === 'irregular' ? 'danger' : 'warning');
                            @endphp

                            <tr onclick="window.location='{{ route('comercio.data', $ubicacion) }}'"
                                style="cursor:pointer;"
                                @if($ubicacion->situacion === 'clausurado') class="table-secondary text-muted" @endif
                            >
                            {{-- Comercio (título + subtítulo) --}}
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
                            @php $hab = data_get($ubicacion, 'habilitacionActual.numero'); @endphp
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

                            {{-- Estado --}}
                            <td class="text-sm text-center">
                                <span class="badge badge-{{ $badgeEstado }}">
                                {{ ucfirst($ubicacion->estado ?? '—') }}
                                </span>
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


