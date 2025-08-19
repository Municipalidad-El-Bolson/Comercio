<!-- Modal -->
<div class="modal fade" id="form" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
  <div class="modal-dialog modal-lg" role="document" style="margin-top:150px;max-width:800px;">
    <form autocomplete="off" wire:submit.prevent="{{ $showEditModal ? 'updateComercio' : 'createCliente' }}" class="modal-content">
      <div class="modal-header bg-primary text-white py-2">
        <h6 class="modal-title mb-0">{{ $showEditModal ? 'Editar Comercio' : 'Nuevo Comercio' }}</h6>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>

      <div class="modal-body p-2">
        {{-- Tipo de Persona + DNI/CUIT + Fantasía--}}
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

        {{-- Identificación (Ambos sets visibles; JS habilita/oculta)--}}
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

        {{-- Contacto y domicilios--}}
        <div class="form-row">
          <div class="form-group col-md-6 mb-2">
            <label class="mb-1" for="domicilio_responsable">Domicilio Responsable</label>
            <input type="text" id="domicilio_responsable" wire:model.defer="state.domicilio_responsable"
                   class="form-control form-control-sm text-capitalize @error('state.domicilio_responsable') is-invalid @enderror"
                   placeholder="Domicilio del responsable">
            @error('state.domicilio_responsable') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="form-group col-md-6 mb-2">
            <label class="mb-1" for="domicilio_comercio">Domicilio del Comercio</label>
            <input type="text" id="domicilio_comercio" wire:model.defer="state.domicilio_comercio"
                   class="form-control form-control-sm text-capitalize @error('state.domicilio_comercio') is-invalid @enderror"
                   placeholder="Domicilio del comercio">
            @error('state.domicilio_comercio') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>

        <div class="form-row">
          <div class="form-group col-md-4 mb-2">
            <label class="mb-1" for="correo">Correo electrónico</label>
            <input type="email" id="correo" wire:model.defer="state.correo"
                   class="form-control form-control-sm @error('state.correo') is-invalid @enderror"
                   placeholder="correo@ejemplo.com">
            @error('state.correo') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="form-group col-md-4 mb-2">
            <label class="mb-1" for="telefono">Teléfono</label>
            <input type="text" id="telefono" wire:model.defer="state.telefono"
                   class="form-control form-control-sm @error('state.telefono') is-invalid @enderror"
                   placeholder="Teléfono">
            @error('state.telefono') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="form-group col-md-4 mb-2">
            <label class="mb-1" for="nomenclatura">Nomenclatura (opcional)</label>
            <input type="text" id="nomenclatura" wire:model.defer="state.nomenclatura"
                   class="form-control form-control-sm @error('state.nomenclatura') is-invalid @enderror"
                   placeholder="Nomenclatura">
            @error('state.nomenclatura') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>

        {{--Rubro Madre -> Subrubro dependiente--}}
        @php
          $rubros = $rubros ?? collect(); // por si no viene
          $madres = $rubros->pluck('rubro_madre')->filter()->unique()->sort()->values();
          $mapaSub = $rubros->groupBy(fn($r) => strtolower($r->rubro_madre))
                           ->map(fn($g) => $g->map(fn($r)=> ['id'=>$r->id,'sub'=>$r->subrubro])->values());
          $rubroIdActual = (int)($state['rubro_id'] ?? 0);
        @endphp

        <div class="form-row">
          <div class="form-group col-md-6 mb-2">
            <label class="mb-1">Rubro</label>
            <select id="rubro-madre" class="form-control form-control-sm">
              <option value="">-- Seleccione Rubro Madre --</option>
              @foreach($madres as $madre)
                <option value="{{ strtolower($madre) }}">{{ $madre }}</option>
              @endforeach
            </select>
          </div>

          <div class="form-group col-md-6 mb-2">
            <label class="mb-1">Subrubro</label>
            <select id="rubro-sub"
                    class="form-control form-control-sm @error('state.rubro_id') is-invalid @enderror"
                    wire:model.defer="state.rubro_id" disabled>
              <option value="">-- Seleccione Subrubro --</option>
            </select>
            @error('state.rubro_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>

        {{-- Estado + Fechas + Monto + Observaciones --}}
        <div class="form-row">
          <div class="form-group col-md-4 mb-2">
            <label class="mb-1" for="estado">Estado</label>
            <select id="estado" wire:model.defer="state.estado"
                    class="form-control form-control-sm @error('state.estado') is-invalid @enderror">
              <option value="vigente">Vigente</option>
              <option value="irregular">Irregular</option>
              <option value="entramite">En Trámite</option>
            </select>
            @error('state.estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="form-group col-md-4 mb-2">
            <label class="mb-1" for="fecha_alta">Fecha de alta</label>
            <input type="date" id="fecha_alta" wire:model.defer="state.fecha_alta"
                   class="form-control form-control-sm @error('state.fecha_alta') is-invalid @enderror">
            @error('state.fecha_alta') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="form-group col-md-4 mb-2">
            <label class="mb-1" for="monto_pagar">Monto a pagar (opcional)</label>
            <input type="number" step="0.01" id="monto_pagar" wire:model.defer="state.monto_pagar"
                   class="form-control form-control-sm @error('state.monto_pagar') is-invalid @enderror"
                   placeholder="0.00">
            @error('state.monto_pagar') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>

        <div class="form-group mb-2">
          <label class="mb-1" for="observaciones">Observaciones</label>
          <textarea id="observaciones" wire:model.defer="state.observaciones"
                    class="form-control form-control-sm @error('state.observaciones') is-invalid @enderror"
                    rows="2" placeholder="Observaciones (opcional)"></textarea>
          @error('state.observaciones') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{--Documentación (Generales + Jurídicas)--}}
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
            {{-- Generales --}}
            <div class="col-md-6">
              <strong class="d-block mb-1">General</strong>

              @php
                $g = fn($k,$t) => '<label class="form-check mb-1">
                  <input class="form-check-input" type="checkbox" wire:model="state.documentos.'.$k.'">
                  <span class="form-check-label">'.$t.'</span></label>';
              @endphp

              {!! $g('doc_libre_deuda_municipal','Certificado de libre deuda municipal') !!}
              {!! $g('doc_planeamiento_urbano','Dirección de Planeamiento Urbano') !!}
              {!! $g('doc_solicitud_habilitacion_pago','Solicitud de habilitación + pago') !!}
              {!! $g('doc_comprobante_uso_local','Comprobante de uso del local') !!}
              {!! $g('doc_afip_constancia','Constancia de inscripción emitida por AFIP') !!}
              {!! $g('doc_recaudacion_rn','Constancia de inscripción emitida por Agencia de Recaudación Tributaria de Río Negro') !!}
              {!! $g('doc_fotocopia_dni','Fotocopia del DNI') !!}
              {!! $g('doc_comprobante_uso_inmueble','Comprobante que acredite el uso del inmueble a destinar a comercio') !!}
              {!! $g('doc_libre_deuda_tasas_inmueble','Libre deuda de tasas municipales de la propiedad') !!}
              {!! $g('doc_aptitud_tecnica_local','Certificado de aptitud técnica del local a habilitar') !!}
              {!! $g('doc_cocap_rhi','Certificado de CO.CA.P.R.HI') !!}
              {!! $g('doc_nota_carteleria_obras','Nota a Obras Públicas declarando cartelería usada como publicidad') !!}
              {!! $g('doc_libro_actas_100','Libro de actas de 100 hojas') !!}
            </div>

            {{-- Jurídicas (si es Jurídica → se muestra via JS) --}}
            <div class="col-md-6 d-none" id="docs-juridica">
              <strong class="d-block mb-1">Personas Jurídicas</strong>

              {!! $g('doc_acta_constitucion','Acta de constitución de sociedad u organización') !!}
              {!! $g('doc_contrato_societario','Contrato societario') !!}
              {!! $g('doc_docs_representantes','Documentación de sus representantes') !!}
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer py-2 px-3">
        <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">
          <i class="fa fa-times mr-1"></i> Cerrar
        </button>
        <button type="submit" class="btn btn-sm btn-primary">
          <i class="fa fa-save mr-1"></i> {{ $showEditModal ? 'Guardar Cambios' : 'Grabar' }}
        </button>
      </div>
    </form>
  </div>
</div>

{{--JS: Modal + Persona + Rubros--}}
<script>
  // Mostrar/ocultar modal por eventos Livewire
  document.addEventListener('livewire:init', () => {
    Livewire.on('show-form', () => $('#form').modal('show'));
    Livewire.on('hide-form', () => $('#form').modal('hide'));
    // re-aplicar modos luego de cada render
    Livewire.hook('message.processed', () => {
      aplicarModoPersona(leerTipoPersona());
    });
  });

  // Foco inicial
  document.addEventListener('DOMContentLoaded', function () {
    $('#form').on('shown.bs.modal', function () {
      const persona = leerTipoPersona();
      aplicarModoPersona(persona);
      const input = persona === 'juridica' ? document.getElementById('razon_social')
                                           : document.getElementById('apellido');
      if (input) { input.focus(); input.select(); }
    });
  });

  // --- Persona: Física/Jurídica (sin recargar) ---
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

  document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('persona_tipo');
    if (sel) {
      aplicarModoPersona(sel.value);
      sel.addEventListener('change', () => aplicarModoPersona(sel.value));
    }
  });

  // --- Rubro Madre/Subrubro ---
  document.addEventListener('DOMContentLoaded', function () {
    const mapa    = @json($mapaSub ?? [], JSON_UNESCAPED_UNICODE);
    const madreEl = document.getElementById('rubro-madre');
    const subEl   = document.getElementById('rubro-sub');
    const currentRubroId = {{ $rubroIdActual ?? 0 }};

    if (!madreEl || !subEl) return;

    // id => madreKey (para precarga)
    const idToMadre = {};
    Object.keys(mapa).forEach(key => (mapa[key] || []).forEach(it => idToMadre[it.id] = key));

    function poblarSubrubros(madreKey) {
      subEl.innerHTML = '<option value="">-- Seleccione Subrubro --</option>';
      const lista = mapa[madreKey] || [];
      if (!madreKey || !lista.length) { subEl.disabled = true; return; }
      lista.forEach(it => {
        const opt = document.createElement('option');
        opt.value = it.id;
        opt.textContent = it.sub || '(Sin subrubro)';
        subEl.appendChild(opt);
      });
      subEl.disabled = false;
    }

    madreEl.addEventListener('change', () => {
      poblarSubrubros(madreEl.value);
      subEl.value = '';
    });

    // Precarga en edición
    if (currentRubroId) {
      const mk = idToMadre[currentRubroId];
      if (mk) {
        madreEl.value = mk;
        poblarSubrubros(mk);
        subEl.value = String(currentRubroId);
      }
    }
  });
</script>
