<div class="acta-editor-component">
<div wire:ignore.self class="modal fade acta-editor-modal" id="modalMovimientos" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content"><!-- ← modal-content DEBE ser un div -->
      <form wire:submit.prevent="guardarMovimiento" enctype="multipart/form-data">
        <div class="modal-header acta-editor-header">
          <div>
            <div class="acta-editor-eyebrow">Inspecciones y documentación</div>
            <h5 class="modal-title mb-0">{{ $movimientoIdEdit ? 'Editar acta' : 'Actas del comercio' }}</h5>
            <div class="acta-editor-commerce">{{ $ubicacion?->nombre_comercial ?: ($ubicacion?->razon_social ?? 'Comercio') }}</div>
          </div>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>

        <div class="modal-body p-3"><!-- ← FALTABA ESTA APERTURA -->
          <div class="acta-editor-section-title">
            <span class="acta-editor-section-icon"><i class="fas fa-file-signature"></i></span>
            <div><strong>{{ $movimientoIdEdit ? 'Datos del acta seleccionada' : 'Cargar una nueva acta' }}</strong><small>Completá la información y adjuntá el archivo si corresponde.</small></div>
          </div>
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

          <div class="form-group mb-3 acta-deadline-box">
            <label class="mb-1 font-weight-bold">
              <i class="fas fa-calendar-alt mr-1 text-warning"></i>Vencimiento del acta (opcional)
            </label>
            <div class="input-group input-group-sm">
              <input type="number" min="1" max="3650" wire:model.live.debounce.300ms="dias_vencimiento"
                   class="form-control form-control-sm @error('dias_vencimiento') is-invalid @enderror"
                   placeholder="Ej.: 15">
              <div class="input-group-append"><span class="input-group-text">días</span></div>
            </div>
            @if($this->fechaVencimientoEstimada)
              <div class="mt-1 font-weight-bold text-danger">
                Fecha de vencimiento: {{ $this->fechaVencimientoEstimada }}
              </div>
            @endif
            <small class="form-text text-muted">Al completar el plazo aparecerá en Seguimiento de actas.</small>
            @error('dias_vencimiento') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

          <hr class="my-3">

          {{-- Tabla --}}
          <div class="acta-editor-section-title mb-2">
            <span class="acta-editor-section-icon"><i class="fas fa-history"></i></span>
            <div><strong>Historial de actas</strong><small>Actas registradas anteriormente para este comercio.</small></div>
          </div>
          <div class="table-responsive acta-history-table">
          <table class="table table-sm table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th class="text-sm">Título</th>
                <th class="text-sm">Estado</th>
                <th class="text-sm">Descripción</th>
                <th class="text-sm">Archivo</th>
                <th class="text-sm">Fecha</th>
                <th class="text-sm">Vencimiento</th>
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
                    @php
                          $raw  = $mov->archivo ?? '';
                          $path = ltrim(preg_replace('#^storage/#i', '', $raw), '/');
                          $disk = \Illuminate\Support\Facades\Storage::disk('public');
                          $ok   = $path !== '' && $disk->exists($path);
                          $url  = $ok ? route('files.show', ['path' => $path]) : null;
                          $isImg= $ok && preg_match('/\.(jpe?g|png|gif|webp|bmp)$/i', $path);
                        @endphp
                        @if ($ok && $url)
                          @if ($isImg)
                            <a href="{{ $url }}" target="_blank" rel="noopener">
                              <img src="{{ $url }}" alt="archivo" style="max-width:80px;max-height:60px;object-fit:cover;">
                            </a>
                          @else
                            <a href="{{ $url }}" target="_blank" rel="noopener">Ver</a>
                          @endif
                        @else
                          —
                        @endif
                  </td>
                  <td class="text-sm">{{ optional($mov->fecha)->format('d/m/Y H:i') }}</td>
                  <td class="text-sm">{{ $mov->fecha_vencimiento?->format('d/m/Y') ?? '—' }}</td>
                  <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-primary"
                            wire:click="editarMovimiento({{ $mov->id }})">
                      Editar
                    </button>
                    <button type="button"
                                class="btn btn-sm btn-outline-danger"
                                onclick="if(!confirm('¿Eliminar este movimiento?')) return;"
                                wire:click.prevent="eliminarMovimiento({{ $mov->id }})">
                          Borrar
                        </button>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-sm">Sin movimientos aún.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
          </div>
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
window.addEventListener('mostrar-modal-movimientos', () => {
  $('#modalMovimientos').modal('show');
});
</script>

<style>
  .acta-editor-modal .modal-content { overflow:hidden; border:0; border-radius:16px; box-shadow:0 24px 70px rgba(14,39,64,.3); }
  .acta-editor-modal .acta-editor-header { align-items:flex-start; padding:1rem 1.25rem; border:0; background:linear-gradient(125deg,#17385f,#245f91 70%,#268b8b); color:#fff; }
  .acta-editor-modal .acta-editor-eyebrow { margin-bottom:.2rem; color:rgba(255,255,255,.72); font-size:.67rem; font-weight:800; letter-spacing:.1em; text-transform:uppercase; }
  .acta-editor-modal .modal-title { font-weight:800; letter-spacing:-.02em; }
  .acta-editor-modal .acta-editor-commerce { margin-top:.2rem; color:rgba(255,255,255,.82); font-size:.82rem; }
  .acta-editor-modal .acta-editor-section-title { display:flex; align-items:center; gap:.65rem; margin-bottom:1rem; color:#17385f; }
  .acta-editor-modal .acta-editor-section-title small { display:block; margin-top:.1rem; color:#738498; font-size:.72rem; font-weight:500; }
  .acta-editor-modal .acta-editor-section-icon { display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; flex:0 0 34px; border-radius:9px; background:#eaf3f9; color:#286da8; }
  .acta-editor-modal label { color:#60758a; font-size:.72rem; font-weight:800; letter-spacing:.03em; }
  .acta-editor-modal .form-control { min-height:38px; border-color:#cad8e4; border-radius:8px; background:#fbfdff; }
  .acta-editor-modal textarea.form-control { min-height:72px; }
  .acta-editor-modal .form-control:focus { border-color:#70a7d1; box-shadow:0 0 0 3px rgba(40,109,168,.1); }
  .acta-editor-modal .acta-deadline-box { padding:.8rem 1rem; border:1px solid #efd69b; border-radius:10px; background:#fffaf0; }
  .acta-editor-modal .acta-history-table { border:1px solid #dce6ef; border-radius:10px; }
  .acta-editor-modal .acta-history-table thead th { border-top:0; border-bottom:1px solid #dce6ef; background:#edf4f9; color:#526a80; font-size:.68rem; font-weight:800; text-transform:uppercase; }
  .acta-editor-modal .modal-footer { border-top:1px solid #dce6ef; background:#f7fafc; }
  .acta-editor-modal .btn { border-radius:8px; font-weight:700; }
  @media (max-width:767.98px) { .acta-editor-modal .modal-dialog { margin:.5rem; } .acta-editor-modal .modal-body { padding:1rem !important; } }
</style>
</div>
