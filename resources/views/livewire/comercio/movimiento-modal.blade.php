<div wire:ignore.self class="modal fade" id="modalMovimientos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form wire:submit.prevent="guardarMovimiento" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header bg-secondary text-white py-2">
                <h6 class="modal-title mb-0">Movimientos de {{ $ubicacion->razon_social ?? '' }}</h6>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body p-2">
                {{-- Formulario de carga --}}
                <div class="form-row">
                    <div class="form-group col-md-6 mb-2">
                        <label class="mb-1">Título</label>
                        <input type="text" id="titulo" wire:model.defer="titulo"
                               class="form-control form-control-sm text-capitalize">
                        @error('titulo') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="form-group col-md-6 mb-2">
                        <label class="mb-1">Estado</label>
                        <select wire:model.defer="estado" class="form-control form-control-sm">
                            <option value="En Proceso">En Proceso</option>
                            <option value="Observado">Observado</option>
                            <option value="Completo">Completo</option>
                            <option value="Rechazado">Rechazado</option>
                            <option value="Archivado">Archivado</option>
                            <option value="Cancelado">Cancelado</option>
                        </select>
                        @error('estado') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                </div>

                <div class="form-group mb-2">
                    <label class="mb-1">Descripción</label>
                    <textarea wire:model.defer="descripcion" class="form-control form-control-sm text-capitalize" rows="2"></textarea>
                </div>

                <div class="form-group mb-2">
                    <label class="mb-1 d-flex align-items-center">
                        Archivo (opcional)
                        <span class="ml-2" wire:loading wire:target="archivo">Subiendo…</span>
                    </label>
                    <input type="file" wire:model="archivo" accept=".jpg,.jpeg,.png,.webp,.gif,.bmp,.pdf,.doc,.docx,.xls,.xlsx,.txt"
                           class="form-control-file form-control-sm">
                    @error('archivo') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <hr class="my-2">

                {{-- Tabla de historial --}}
                <h6 class="mb-2">Historial de movimientos</h6>
                <table class="table table-sm table-bordered mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th class="text-sm">Título</th>
                            <th class="text-sm">Estado</th>
                            <th class="text-sm">Descripción</th>
                            <th class="text-sm">Archivo</th>
                            <th class="text-sm">Fecha</th>
                            <th class="text-sm text-center">Acciones</th> {{-- <== CAMBIADO --}}
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movimientos as $mov)
                            <tr wire:key="mov-{{ $mov->id }}">
                                <td class="text-sm">{{ $mov->titulo }}</td>
                                <td class="text-sm">{{ $mov->estado ?? '—' }}</td>
                                <td class="text-sm">{{ $mov->descripcion ?? '—' }}</td>
                                <td class="text-sm">
                                    @if ($mov->archivo_existe && $mov->archivo_url)
                                        @if ($mov->archivo_es_imagen)
                                            <a href="{{ $mov->archivo_url }}" target="_blank" rel="noopener">
                                                <img src="{{ $mov->archivo_url }}" alt="archivo" style="max-width:80px;max-height:60px;object-fit:cover;">
                                            </a>
                                        @else
                                            <a href="{{ $mov->archivo_url }}" target="_blank" rel="noopener">Ver</a>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-sm">{{ $mov->fecha_mostrar }}</td>
                                <td class="text-center">
                                    <button type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        wire:click="$dispatch('editarMovimiento', {{ $mov->id }})">
                                        Editar
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-sm">Sin movimientos aún.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="modal-footer py-2 px-3">
                <button type="submit" class="btn btn-sm btn-primary" wire:loading.attr="disabled">Guardar</button>
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $('#modalMovimientos').on('shown.bs.modal', function() {
        const input = document.getElementById('titulo');
        if (input) { input.focus(); input.select(); }
    });
});
</script>

