<div wire:ignore.self class="modal fade" id="modalMovimientos" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content"><!-- ← modal-content DEBE ser un div -->
      <form wire:submit.prevent="guardarMovimiento" enctype="multipart/form-data">
        <div class="modal-header bg-secondary text-white py-2">
          <h6 class="modal-title mb-0">Movimientos de {{ $ubicacion->razon_social ?? '' }}</h6>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>

        <div class="modal-body p-2"><!-- ← FALTABA ESTA APERTURA -->
          {{-- Form --}}
          <div class="form-group mb-2">
            <label class="mb-1">Título</label>
            <input type="text" id="titulo" wire:model.defer="titulo"
                   class="form-control form-control-sm text-capitalize">
            @error('titulo') <small class="text-danger">{{ $message }}</small> @enderror
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
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

            <div class="form-group col-md-6">
              <label class="mb-1" for="tipo_acta">Tipo de acta</label>
              <select id="tipo_acta" wire:model.defer="tipo_acta"
                      class="form-control form-control-sm @error('tipo_acta') is-invalid @enderror">
                <option value="">-- Seleccioná --</option>
                <option value="asesoramiento">Asesoramiento</option>
                <option value="notificacion">Notificación</option>
                <option value="inspeccion">Inspección</option>
                <option value="infraccion">Infracción</option>
              </select>
              @error('tipo_acta') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          <div class="form-group mb-2">
            <label class="mb-1">Descripción</label>
            <textarea wire:model.defer="descripcion" class="form-control form-control-sm text-capitalize" rows="2"></textarea>
            @error('descripcion') <small class="text-danger">{{ $message }}</small> @enderror
          </div>

          {{-- Archivo actual en edición --}}
          @if ($movimientoIdEdit && $archivoActual)
            <div class="mb-2">
                <small class="text-muted">Archivo actual:</small>
                <div>
                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($archivoActual) }}" 
                    target="_blank" rel="noopener">
                    {{ basename($archivoActual) }}
                </a>
                </div>
            </div>
            @endif

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

          {{-- Tabla --}}
          <h6 class="mb-2">Historial de movimientos</h6>
          <table class="table table-sm table-bordered mb-0">
            <thead class="thead-light">
              <tr>
                <th class="text-sm">Título</th>
                <th class="text-sm">Estado</th>
                <th class="text-sm">Descripción</th>
                <th class="text-sm">Archivo</th>
                <th class="text-sm">Fecha</th>
                <th class="text-sm text-center">Acciones</th>
              </tr>
            </thead>
            <tbody>
              @forelse($movimientos as $mov)
                <tr wire:key="mov-{{ $mov->id }}">
                  <td class="text-sm">{{ $mov->titulo }}</td>
                  <td class="text-sm">{{ $mov->estado ?? '—' }}</td>
                  <td class="text-sm">{{ $mov->descripcion ?? '—' }}</td>
                  <td class="text-sm">
                    @php $url = $mov->archivo ? Storage::disk('public')->url($mov->archivo) : null; @endphp
                    @if ($mov->archivo && $url)
                      <a href="{{ $url }}" target="_blank" rel="noopener">Ver</a>
                    @else
                      —
                    @endif
                  </td>
                  <td class="text-sm">{{ optional($mov->fecha)->format('d/m/Y H:i') }}</td>
                  <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-primary"
                            wire:click="editarMovimiento({{ $mov->id }})">
                      Editar
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger"
                            wire:click="eliminarMovimiento({{ $mov->id }})">
                      Borrar
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
        </div><!-- ← CIERRE modal-body -->

        <div class="modal-footer py-2 px-3">
          <button type="submit" class="btn btn-sm btn-primary" wire:loading.attr="disabled">
            {{ $movimientoIdEdit ? 'Actualizar' : 'Guardar' }}
          </button>

          @if ($movimientoIdEdit)
            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="cancelarEdicion">
              Cancelar edición
            </button>
          @endif

          <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cerrar</button>
        </div>
      </form>
    </div><!-- /modal-content -->
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


