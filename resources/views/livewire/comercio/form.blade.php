@php
  $__formKey = 'form-'.md5(json_encode([
      'mode'  => $showEditModal ? 'edit' : 'new',
      'rubros'=> count($state['rubros'] ?? []),
      'tels'  => count($state['telefonos'] ?? []),
  ]));
@endphp

<div class="modal fade" id="form" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable" role="document">
    <form autocomplete="off"
          wire:submit.prevent="{{ $showEditModal ? 'updateComercio' : 'createCliente' }}"
          class="modal-content"
          wire:key="{{ $__formKey }}">

      <div class="modal-header bg-primary text-white py-2">
        <h6 class="modal-title mb-0">{{ $showEditModal ? 'Editar Comercio' : 'Nuevo Comercio' }}</h6>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>

      @if ($errors->any())
        <div class="alert alert-danger py-2 mb-0">
          <ul class="mb-0">
            @foreach ($errors->all() as $err)
              <li>{{ $err }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="modal-body p-2">
        {{-- Tipo de Persona + DNI/CUIT + Fantasía --}}
        <div class="form-row">
          <div class="form-group col-md-4 mb-2">
            <label class="mb-1" for="persona_tipo">Tipo de Persona</label>
            <select id="persona_tipo" wire:model.defer="state.persona_tipo"
              class="form-control form-control-sm @error('state.persona_tipo') is-invalid @enderror">
              <option value="fisica">Física</option>
              <option value="juridica">Jurídica</option>
            </select>
            @error('state.persona_tipo') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="form-group col-md-4 mb-2">
            <label class="mb-1" for="dni_cuit">DNI / CUIT</label>
            <input type="text" id="dni_cuit" wire:model.defer="state.dni_cuit"
              class="form-control form-control-sm @error('state.dni_cuit') is-invalid @enderror"
              placeholder="DNI o CUIT">
            @error('state.dni_cuit') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="form-group col-md-4 mb-2">
            <label class="mb-1" for="nombre_comercial">Nombre de Fantasía</label>
            <input type="text" id="nombre_comercial" wire:model.defer="state.nombre_comercial"
              class="form-control form-control-sm text-capitalize @error('state.nombre_comercial') is-invalid @enderror"
              placeholder="Nombre comercial">
            @error('state.nombre_comercial') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>

        {{-- Identificación --}}
        <div class="form-row">
          <div class="form-group col-md-6 mb-2" id="bloque-fisica-apellido">
            <label class="mb-1" for="apellido">Apellido</label>
            <input type="text" id="apellido" wire:model.defer="state.apellido"
              class="form-control form-control-sm text-capitalize @error('state.apellido') is-invalid @enderror"
              placeholder="Apellido">
            @error('state.apellido') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="form-group col-md-6 mb-2" id="bloque-fisica-nombres">
            <label class="mb-1" for="nombres">Nombres</label>
            <input type="text" id="nombres" wire:model.defer="state.nombres"
              class="form-control form-control-sm text-capitalize @error('state.nombres') is-invalid @enderror"
              placeholder="Nombres">
            @error('state.nombres') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="form-group col-md-12 mb-2 d-none" id="bloque-juridica-razon">
            <label class="mb-1" for="razon_social">Razón Social</label>
            <input type="text" id="razon_social" wire:model.defer="state.razon_social"
              class="form-control form-control-sm text-capitalize @error('state.razon_social') is-invalid @enderror"
              placeholder="Razón Social">
            @error('state.razon_social') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>

        {{-- Domicilio / Correo / Teléfonos (repeater) --}}
        <div class="form-row">
          <div class="form-group col-md-4 mb-2">
            <label class="mb-1" for="domicilio_comercio">Domicilio del Comercio</label>
            <input type="text" id="domicilio_comercio" wire:model.defer="state.domicilio_comercio"
              class="form-control form-control-sm text-capitalize @error('state.domicilio_comercio') is-invalid @enderror"
              placeholder="Domicilio del comercio">
            @error('state.domicilio_comercio') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="form-group col-md-4 mb-2">
            <label class="mb-1" for="correo">Correo electrónico</label>
            <input type="email" id="correo" wire:model.defer="state.correo"
              class="form-control form-control-sm @error('state.correo') is-invalid @enderror"
              placeholder="correo@ejemplo.com">
            @error('state.correo') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="form-group col-md-4 mb-2">
            <label class="mb-1 d-flex align-items-center justify-content-between">
              <span>Teléfonos</span>
              <button type="button" class="btn btn-sm btn-outline-primary" wire:click="addTelefono">
                <i class="fa fa-plus"></i>
              </button>
            </label>
            @foreach(($state['telefonos'] ?? ['']) as $i => $tel)
              <div class="input-group input-group-sm mb-1" wire:key="tel-{{ $i }}">
                <input type="text"
                       class="form-control @error('state.telefonos.'.$i) is-invalid @enderror"
                       placeholder="Teléfono"
                       wire:model.defer="state.telefonos.{{ $i }}">
                <div class="input-group-append">
                  <button type="button" class="btn btn-outline-danger"
                          wire:click="removeTelefono({{ $i }})"
                          @disabled($i===0 && count($state['telefonos'] ?? [])<=1)>
                    <i class="fa fa-trash"></i>
                  </button>
                </div>
                @error('state.telefonos.'.$i) <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
              </div>
            @endforeach
          </div>
        </div>

        {{-- Rubros (repeater: mega/madre/sub) --}}
        <div class="col-12 px-0">
          <label class="mb-1 d-flex align-items-center justify-content-between">
            <span>Rubros</span>
            <button type="button" class="btn btn-sm btn-outline-primary" wire:click="addRubroRow">
              <i class="fa fa-plus"></i>
            </button>
          </label>

          @foreach(($state['rubros'] ?? [['mega'=>'','madre'=>'','sub_id'=>null]]) as $i => $row)
            <div class="form-row align-items-end mb-2 border rounded p-2" wire:key="rubro-row-{{ $i }}">
              <div class="form-group col-md-4 mb-1">
                <label class="mb-1">Mega rubro</label>
                <select class="form-control form-control-sm"
                        wire:model.live="state.rubros.{{ $i }}.mega">
                  <option value="">-- Seleccione Mega rubro --</option>
                  @foreach (($megas ?? []) as $mega)
                    <option value="{{ $mega }}">{{ $mega }}</option>
                  @endforeach
                </select>
              </div>

              <div class="form-group col-md-4 mb-1">
                <label class="mb-1">Rubro madre</label>
                <select class="form-control form-control-sm"
                        @disabled(empty($state['rubros'][$i]['mega'] ?? ''))
                        wire:model.live="state.rubros.{{ $i }}.madre">
                  <option value="">-- Seleccione Rubro madre --</option>
                  @foreach (($madresOptions[$i] ?? []) as $madre)
                    <option value="{{ $madre }}">{{ $madre }}</option>
                  @endforeach
                </select>
              </div>

              <div class="form-group col-md-3 mb-1">
                <label class="mb-1">Subrubro</label>
                <select class="form-control form-control-sm @error('state.rubros.'.$i.'.sub_id') is-invalid @enderror"
                        @disabled(empty($state['rubros'][$i]['madre'] ?? ''))
                        wire:model.live="state.rubros.{{ $i }}.sub_id">
                  <option value="">-- Seleccione Subrubro --</option>
                  @foreach (($subsOptions[$i] ?? []) as $op)
                    <option value="{{ $op['id'] }}">{{ $op['sub'] }}</option>
                  @endforeach
                </select>
                @error('state.rubros.'.$i.'.sub_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="form-group col-md-1 mb-1 text-right">
                <button type="button" class="btn btn-sm btn-outline-danger"
                        wire:click="removeRubroRow({{ $i }})" 
                        @disabled($i===0 && count($state['rubros'] ?? []) <= 1) 
                        title="Eliminar fila">
                  <i class="fa fa-trash"></i>
                </button>
              </div>
            </div>
          @endforeach
        </div>

        {{-- ÚNICOS: N° de disposición y N° de habilitación (sin fecha, sin múltiples) --}}
        <div class="form-row">
          <div class="form-group col-md-6 mb-2">
            <label class="mb-1" for="numero_disposicion">N° de disposición</label>
            <input type="text" id="numero_disposicion"
                   wire:model.defer="state.numero_disposicion"
                   class="form-control form-control-sm @error('state.numero_disposicion') is-invalid @enderror"
                   placeholder="Ej: 1234/2025">
            @error('state.numero_disposicion') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="form-group col-md-6 mb-2">
            <label class="mb-1" for="numero_habilitacion">N° de habilitación comercial</label>
            <input type="text" id="numero_habilitacion"
                   wire:model.defer="state.numero_habilitacion"
                   class="form-control form-control-sm @error('state.numero_habilitacion') is-invalid @enderror"
                   placeholder="Ej: HC-000123">
            @error('state.numero_habilitacion') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>

        @php $estadoActual = data_get($state, 'estado'); @endphp

        <div class="form-row">
          {{-- Estado --}}
          <div class="form-group col-md-4 mb-2">
            <label class="mb-1" for="estado">Estado</label>
            <select id="estado" wire:model.live="state.estado"
                    class="form-control form-control-sm @error('state.estado') is-invalid @enderror">
              <option value="">-- Seleccioná estado --</option>
              <option value="entramite">En trámite</option>
              <option value="vigente">Vigente</option>
              <option value="irregular">Irregular</option>
              <option value="baja">Baja</option>
            </select>
            @error('state.estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          {{-- Tipo de habilitación --}}
          <div class="form-group col-md-4 mb-2">
            <label class="mb-1" for="tipo_hab">Tipo de habilitación</label>
            <select id="tipo_hab" wire:model.live="state.tipo_hab"
                    class="form-control form-control-sm @error('state.tipo_hab') is-invalid @enderror">
              <option value="definitiva">Definitiva</option>
              <option value="prev">Provisoria (6 meses)</option>
            </select>
            @error('state.tipo_hab') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          {{-- Monto --}}
          <div class="form-group col-md-4 mb-2">
            <label class="mb-1" for="monto_pagar">Monto a pagar (opcional)</label>
            <input type="number" step="0.01" id="monto_pagar" wire:model.defer="state.monto_pagar"
              class="form-control form-control-sm @error('state.monto_pagar') is-invalid @enderror" placeholder="0.00">
            @error('state.monto_pagar') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>

        <div class="form-row">
          {{-- Fecha de alta --}}
          @if($estadoActual && $estadoActual !== 'entramite')
            <div class="form-group col-md-4 mb-2" id="grp-fecha-alta">
              <label class="mb-1" for="fecha_alta">Fecha de alta</label>
              <input type="date" id="fecha_alta"
                     wire:model.defer="state.fecha_alta"
                     class="form-control form-control-sm @error('state.fecha_alta') is-invalid @enderror">
              @error('state.fecha_alta') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          @endif

          {{-- Fecha de vencimiento --}}
          @if(in_array($estadoActual, ['vigente','irregular']))
            <div class="form-group col-md-4 mb-2" id="grp-fecha-vto">
              <label class="mb-1" for="fecha_vto">Fecha de vencimiento (manual)</label>
              <input type="date" id="fecha_vto"
                     wire:model.defer="state.fecha_vto"
                     class="form-control form-control-sm @error('state.fecha_vto') is-invalid @enderror">
              @error('state.fecha_vto') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          @endif

          {{-- Fecha de baja --}}
          @if($estadoActual === 'baja')
            <div class="form-group col-md-4 mb-2" id="grp-fecha-baja">
              <label class="mb-1" for="fecha_baja">Fecha de baja</label>
              <input type="date" id="fecha_baja"
                     wire:model.defer="state.fecha_baja"
                     class="form-control form-control-sm @error('state.fecha_baja') is-invalid @enderror">
              @error('state.fecha_baja') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          @endif
        </div>

        <div class="form-group mb-2">
          <label class="mb-1" for="observaciones">Observaciones</label>
          <textarea id="observaciones" wire:model.defer="state.observaciones"
            class="form-control form-control-sm @error('state.observaciones') is-invalid @enderror" rows="2"
            placeholder="Observaciones (opcional)"></textarea>
          @error('state.observaciones') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- Documentación --}}
        <div class="border rounded p-2 mt-2">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="mb-0">Documentación</h6>
            <div class="btn-group btn-group-sm">
              <button type="button" class="btn btn-success" wire:click="marcarTodosLosDocs(true)">
                Presentó toda la documentación
              </button>
              <button type="button" class="btn btn-outline-secondary" wire:click="marcarTodosLosDocs(false)">
                Limpiar
              </button>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <strong class="d-block mb-1">General</strong>

              <label class="form-check mb-1">
                <input class="form-check-input" type="checkbox" wire:model="state.documentos.doc_libre_deuda_municipal">
                <span class="form-check-label">Certificado de libre deuda municipal</span>
              </label>
              <label class="form-check mb-1">
                <input class="form-check-input" type="checkbox" wire:model="state.documentos.doc_planeamiento_urbano">
                <span class="form-check-label">Dirección de Planeamiento Urbano</span>
              </label>
              <label class="form-check mb-1">
                <input class="form-check-input" type="checkbox" wire:model="state.documentos.doc_solicitud_habilitacion_pago">
                <span class="form-check-label">Solicitud de habilitación + pago</span>
              </label>
              <label class="form-check mb-1">
                <input class="form-check-input" type="checkbox" wire:model="state.documentos.doc_comprobante_uso_local">
                <span class="form-check-label">Comprobante de uso del local</span>
              </label>
              <label class="form-check mb-1">
                <input class="form-check-input" type="checkbox" wire:model="state.documentos.doc_afip_constancia">
                <span class="form-check-label">Constancia de inscripción emitida por AFIP</span>
              </label>
              <label class="form-check mb-1">
                <input class="form-check-input" type="checkbox" wire:model="state.documentos.doc_recaudacion_rn">
                <span class="form-check-label">Constancia de inscripción de Agencia de Recaudación Río Negro</span>
              </label>
              <label class="form-check mb-1">
                <input class="form-check-input" type="checkbox" wire:model="state.documentos.doc_fotocopia_dni">
                <span class="form-check-label">Fotocopia del DNI</span>
              </label>
              <label class="form-check mb-1">
                <input class="form-check-input" type="checkbox" wire:model="state.documentos.doc_comprobante_uso_inmueble">
                <span class="form-check-label">Comprobante de uso del inmueble</span>
              </label>
              <label class="form-check mb-1">
                <input class="form-check-input" type="checkbox" wire:model="state.documentos.doc_libre_deuda_tasas_inmueble">
                <span class="form-check-label">Libre deuda de tasas municipales</span>
              </label>
              <label class="form-check mb-1">
                <input class="form-check-input" type="checkbox" wire:model="state.documentos.doc_aptitud_tecnica_local">
                <span class="form-check-label">Certificado de aptitud técnica del local</span>
              </label>
              <label class="form-check mb-1">
                <input class="form-check-input" type="checkbox" wire:model="state.documentos.doc_cocap_rhi">
                <span class="form-check-label">Certificado de CO.CA.P.R.HI</span>
              </label>
              <label class="form-check mb-1">
                <input class="form-check-input" type="checkbox" wire:model="state.documentos.doc_nota_carteleria_obras">
                <span class="form-check-label">Nota a Obras Públicas declarando cartelería</span>
              </label>
              <label class="form-check mb-1">
                <input class="form-check-input" type="checkbox" wire:model="state.documentos.doc_libro_actas_100">
                <span class="form-check-label">Libro de actas de 100 hojas</span>
              </label>
            </div>

            <div class="col-md-6 d-none" id="docs-juridica">
              <strong class="d-block mb-1">Personas Jurídicas</strong>
              <label class="form-check mb-1">
                <input class="form-check-input" type="checkbox" wire:model="state.documentos.doc_acta_constitucion">
                <span class="form-check-label">Acta de constitución</span>
              </label>
              <label class="form-check mb-1">
                <input class="form-check-input" type="checkbox" wire:model="state.documentos.doc_contrato_societario">
                <span class="form-check-label">Contrato societario</span>
              </label>
              <label class="form-check mb-1">
                <input class="form-check-input" type="checkbox" wire:model="state.documentos.doc_docs_representantes">
                <span class="form-check-label">Documentación de representantes</span>
              </label>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer py-2 px-3">
        <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">
          <i class="fa fa-times mr-1"></i> Cerrar
        </button>
        <button type="submit" class="btn btn-primary"
          wire:loading.attr="disabled" wire:target="createCliente,updateComercio">
          <span wire:loading.remove wire:target="createCliente,updateComercio">Guardar</span>
          <span wire:loading wire:target="createCliente,updateComercio">Guardando…</span>
        </button>
      </div>
    </form>
  </div>
</div>

{{-- JS: Modal + Persona + Confirmación de cambio de estado --}}
<script>
  function leerTipoPersona() {
    const sel = document.getElementById('persona_tipo');
    return sel ? sel.value : 'fisica';
  }
  function aplicarModoPersona(tipo) {
    const esJ = (tipo === 'juridica');
    const bApe = document.getElementById('bloque-fisica-apellido');
    const bNom = document.getElementById('bloque-fisica-nombres');
    const bRaz = document.getElementById('bloque-juridica-razon');
    const docsJ = document.getElementById('docs-juridica');
    const ape = document.getElementById('apellido');
    const nom = document.getElementById('nombres');
    const raz = document.getElementById('razon_social');

    if (bApe) bApe.classList.toggle('d-none', esJ);
    if (bNom) bNom.classList.toggle('d-none', esJ);
    if (bRaz) bRaz.classList.toggle('d-none', !esJ);
    if (docsJ) docsJ.classList.toggle('d-none', !esJ);

    if (ape) ape.disabled = esJ;
    if (nom) nom.disabled = esJ;
    if (raz) raz.disabled = !esJ;
  }

  document.addEventListener('livewire:init', () => {
    Livewire.on('show-form', () => $('#form').modal('show'));
    Livewire.on('hide-form', () => $('#form').modal('hide'));

    Livewire.hook('message.processed', () => {
      aplicarModoPersona(leerTipoPersona());
    });

    Livewire.on('confirm-baja', ({ message }) => {
      if (confirm(message)) {
        Livewire.dispatch('confirmarBajaHoy');
      } else {
        Livewire.dispatch('cancelarCambioBaja');
      }
    });
  });

  document.addEventListener('DOMContentLoaded', () => {
    $('#form').on('shown.bs.modal', function () {
      const persona = leerTipoPersona();
      aplicarModoPersona(persona);
      const input = persona === 'juridica'
        ? document.getElementById('razon_social')
        : document.getElementById('apellido');
      if (input) { input.focus(); input.select(); }
    });

    const selPersona = document.getElementById('persona_tipo');
    if (selPersona) {
      aplicarModoPersona(selPersona.value);
      selPersona.addEventListener('change', () => aplicarModoPersona(selPersona.value));
    }

    const selEstado = document.getElementById('estado');
    if (selEstado) {
      let prev = selEstado.value || '';
      selEstado.addEventListener('change', (e) => {
        const nuevo = e.target.value;
        if (nuevo === prev) return;
        const ok = confirm('Vas a cambiar el estado del comercio. ¿Confirmás este cambio?');
        if (!ok) {
          selEstado.value = prev;
          selEstado.dispatchEvent(new Event('input', { bubbles: true }));
          selEstado.dispatchEvent(new Event('change', { bubbles: true }));
          return;
        }
        prev = nuevo;
      });
    }
  });
</script>

<style>
  @media (max-width: 576px) {
    .modal-dialog { max-width: 98vw !important; margin: 1.75rem auto; }
    .modal-content { padding: 0.5rem; }
  }
  .modal.show .modal-dialog { margin-top: 3.5rem; }
</style>
