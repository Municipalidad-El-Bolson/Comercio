<div class="modal fade" id="form" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable" role="document">
    <form autocomplete="off"
          wire:submit.prevent="{{ $showEditModal ? 'updateComercio' : 'createCliente' }}"
          class="modal-content"
          wire:key="form-{{ $formKey ?? 'x' }}">

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
            <select id="persona_tipo" wire:model.live="state.persona_tipo"
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

        {{-- Identificación (condicional sin JS) --}}
        @if( data_get($state, 'persona_tipo', 'fisica') === 'fisica' )
          <div class="form-row">
            <div class="form-group col-md-4 mb-2">
              <label class="mb-1" for="apellido">Apellido</label>
              <input type="text" id="apellido" wire:model.defer="state.apellido"
                class="form-control form-control-sm text-capitalize @error('state.apellido') is-invalid @enderror"
                placeholder="Apellido">
              @error('state.apellido') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group col-md-4 mb-2">
              <label class="mb-1" for="nombres">Nombres</label>
              <input type="text" id="nombres" wire:model.defer="state.nombres"
                class="form-control form-control-sm text-capitalize @error('state.nombres') is-invalid @enderror"
                placeholder="Nombres">
              @error('state.nombres') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group col-md-4 mb-2">
              <label class="mb-1" for="nomenclatura">Nomenclatura catastral</label>
              <input type="text" id="nomenclatura"
                wire:model.defer="state.nomenclatura"
                class="form-control form-control-sm @error('state.nomenclatura') is-invalid @enderror"
                placeholder="Ej: J749 052F000">
              @error('state.nomenclatura') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>
        @else
          <div class="form-row">
            <div class="form-group col-md-6 mb-2">
              <label class="mb-1" for="razon_social">Razón Social</label>
              <input type="text" id="razon_social" wire:model.defer="state.razon_social"
                class="form-control form-control-sm text-capitalize @error('state.razon_social') is-invalid @enderror"
                placeholder="Razón Social">
              @error('state.razon_social') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group col-md-6 mb-2">
              <label class="mb-1" for="nomenclatura">Nomenclatura catastral</label>
              <input type="text" id="nomenclatura"
                    wire:model.defer="state.nomenclatura"
                    class="form-control form-control-sm @error('state.nomenclatura') is-invalid @enderror"
                    placeholder="Ej: J749 052F000">
              @error('state.nomenclatura') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>
        @endif

        @php
          $opsRubro = $rubroOpts ?? [];
          $opsAnexo = $anexoOpts ?? [];
        @endphp
        <div class="form-group col-md-12 mb-1" wire:ignore>
          <label class="mb-1">Seleccioná el Rubro Principal</label>
          <select id="select-rubro-principal"
                  class="form-control form-control-sm @error('state.rubro_id') is-invalid @enderror">
            <option value="">-- Seleccione Rubro --</option>
            @foreach($opsRubro as $op)
              @php
                // Soporta colección Eloquent ($rubros) o array plano ($rubroOpts)
                $id  = is_array($op) ? $op['id'] : $op->id;
                $txt = is_array($op) ? $op['subrubro'] : $op->subrubro;
              @endphp
              <option value="{{ $id }}">{{ $txt }}</option>
            @endforeach
          </select>
          @error('state.rubro_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

          <label class="mb-1">Seleccioná  Rubro Anexo</label>
          <select multiple id="select-rubros-anexos"
                  class="form-control form-control-sm @error('state.rubros_anexos') is-invalid @enderror" size="6">
                  <option value="">-- Seleccione Anexo --</option>
            @foreach($opsAnexo as $op)
              @php
                $id  = is_array($op) ? $op['id'] : $op->id;
                $txt = is_array($op) ? $op['subrubro'] : $op->subrubro;
              @endphp
              <option value="{{ $id }}">{{ $txt }}</option>
            @endforeach
          </select>
          @error('state.rubros_anexos') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        {{-- Domicilio / Correo / Teléfonos --}}
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

        @php
          // Estado BASE seguro desde el state (por defecto '021')
          $base = (string) ($state['estado'] ?? '021');
          $base = trim(mb_strtolower($base));

          // Normalización mínima en la vista (por si viene con legacy):
          $map = [
            'entramite' => '021', 'en tramite' => '021', 'en trámite' => '021', 'alta' => '021', 'vigente' => '021',
            'irregular' => '032',
            'sin_efecto' => 'exp_sin_efecto',
          ];
          $base = $map[$base] ?? $base;

          // Validar conjunto permitido
          $permitidos = ['021','032','040','baja','baja_oficio','exp_sin_efecto'];
          if (!in_array($base, $permitidos, true)) $base = '021';

          // Opciones de “Cambios” por estado base (sólo 021 y 032)
          $cambiosOpts = match ($base) {
            '021' => [
              '' => 'Ninguno',
              'cambio_domicilio' => 'Cambio de Domicilio',
              'adicion_anexo'    => 'Adición de Rubro Anexo',
              'cambio_razon'     => 'Cambio de Razón Social',
            ],
            '032' => [
              '' => 'Ninguno',
              'cambio_rubro'     => 'Cambio de Rubro',
              'adicion_anexo'    => 'Adeción de Rubro Anexo',
              'cambio_fantasia'  => 'Cambio de Nombre de Fantasía',
              'baja_alojamiento' => 'Baja de Unidad de Alojamiento',
              'cambio_razon'     => 'Cambio de Razón Social',
            ],
            default => [],
          };
        @endphp

        <div class="form-row">
          {{-- Estado (usa CÓDIGOS BASE) --}}
          <div class="form-group col-md-4 mb-2">
            <label class="mb-1" for="estado">Estado</label>
            <select id="estado" wire:model.live="state.estado"
                    class="form-control form-control-sm @error('state.estado') is-invalid @enderror">
              <option value="">-- Seleccioná estado --</option>
              <option value="021">021/90</option>
              <option value="032">032/01</option>
              <option value="040">040/25</option>
              <option value="baja">Baja</option>
              <option value="baja_oficio">Baja de oficio</option>
              <option value="exp_sin_efecto">Expediente sin efecto</option>
            </select>
            @error('state.estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          {{-- Cambios (sólo 021 y 032) --}}
          @if(in_array($base, ['021','032'], true))
            <div class="form-group col-md-4 mb-2">
              <label class="mb-1" for="cambio_tipo">Cambios:</label>
              <select id="cambio_tipo" wire:model.live="state.cambio_tipo" class="form-control form-control-sm">
                @foreach($cambiosOpts as $key => $txt)
                  <option value="{{ $key }}">{{ $txt }}</option>
                @endforeach
              </select>
            </div>
          @endif

          {{-- Tipo de habilitación --}}
          <div class="form-group col-md-4 mb-2">
            <label class="mb-1" for="tipo_hab">Tipo de habilitación</label>
            <select id="tipo_hab" wire:model.live="state.tipo_hab"
                    class="form-control form-control-sm @error('state.tipo_hab') is-invalid @enderror">
              <option value="definitiva">Definitiva</option>
              <option value="prev">Provisoria</option>
            </select>
            @error('state.tipo_hab') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>

        {{-- Fechas por estado base --}}
        <div class="form-row">
          @if($base === '021')
            {{-- 021: alta + vto (ambas requeridas por validación del componente) --}}
            <div class="form-group col-md-4 mb-2">
              <label class="mb-1" for="fecha_alta">Fecha de alta</label>
              <input type="date" id="fecha_alta" wire:model.defer="state.fecha_alta"
                    class="form-control form-control-sm @error('state.fecha_alta') is-invalid @enderror">
              @error('state.fecha_alta') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="form-group col-md-4 mb-2">
              <label class="mb-1" for="fecha_vto">Fecha de vencimiento</label>
              <input type="date" id="fecha_vto" wire:model.defer="state.fecha_vto"
                    class="form-control form-control-sm @error('state.fecha_vto') is-invalid @enderror">
              @error('state.fecha_vto') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          @elseif($base === '032' || $base === '040')
            {{-- 032/040: alta requerida, vto opcional --}}
            <div class="form-group col-md-4 mb-2">
              <label class="mb-1" for="fecha_alta">Fecha de alta</label>
              <input type="date" id="fecha_alta" wire:model.defer="state.fecha_alta"
                    class="form-control form-control-sm @error('state.fecha_alta') is-invalid @enderror">
              @error('state.fecha_alta') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="form-group col-md-4 mb-2">
              <label class="mb-1" for="fecha_vto">Fecha de vencimiento (opcional)</label>
              <input type="date" id="fecha_vto" wire:model.defer="state.fecha_vto"
                    class="form-control form-control-sm @error('state.fecha_vto') is-invalid @enderror">
              @error('state.fecha_vto') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          @elseif(in_array($base, ['baja','baja_oficio','exp_sin_efecto'], true))
            {{-- Bajas: alta (requerida si no existía) + baja (requerida) --}}
            <div class="form-group col-md-4 mb-2">
              <label class="mb-1" for="fecha_alta">Fecha de alta</label>
              <input type="date" id="fecha_alta" wire:model.defer="state.fecha_alta"
                    class="form-control form-control-sm @error('state.fecha_alta') is-invalid @enderror">
              @error('state.fecha_alta') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="form-group col-md-4 mb-2">
              <label class="mb-1" for="fecha_baja">Fecha de baja</label>
              <input type="date" id="fecha_baja" wire:model.defer="state.fecha_baja"
                    class="form-control form-control-sm @error('state.fecha_baja') is-invalid @enderror">
              @error('state.fecha_baja') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          @endif

          <div class="form-group col-md-4 mb-3">
            <label class="mb-1 d-block">Situación</label>
            <div class="form-check mb-2">
              <input type="checkbox" class="form-check-input" id="chkClausurado" wire:model="state.es_clausurado">
              <label class="form-check-label" for="chkClausurado">Clausurado</label>
            </div>
          </div>
        </div>

        {{-- Observaciones --}}
        <div class="form-group mb-2">
          <label class="mb-1" for="observaciones">Observaciones</label>
          <textarea id="observaciones" wire:model.defer="state.observaciones"
            class="form-control form-control-sm @error('state.observaciones') is-invalid @enderror" rows="2"
            placeholder="Observaciones (opcional)"></textarea>
          @error('state.observaciones') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- Documentación (dinámica por estado base) --}}
        @php
          $docSchema = isset($docSchema) && is_array($docSchema)
              ? $docSchema
              : (method_exists($this, 'getDocSchemaProperty') ? $this->docSchema : ['items' => [], 'uso_inmueble' => ['show' => false]]);
        @endphp

        <div class="border rounded p-2 mt-2">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="mb-0">Documentación</h6>
            <div class="btn-group btn-group-sm">
              <button type="button" class="btn btn-success"
                      wire:click="marcarTodosLosDocs(true)"
                      @disabled(empty($docSchema['items']) && empty($docSchema['uso_inmueble']['show']))>
                Presentó toda la documentación
              </button>
              <button type="button" class="btn btn-outline-secondary" wire:click="marcarTodosLosDocs(false)">
                Limpiar
              </button>
            </div>
          </div>

          @if(empty($docSchema['items']) && empty($docSchema['uso_inmueble']['show']))
            <em>No hay documentos para este estado.</em>
          @else
            <div class="row">
              @foreach($docSchema['items'] as $i => $it)
                <div class="col-md-6">
                  <label class="form-check mb-1">
                    <input class="form-check-input" type="checkbox" wire:model="state.documentos.{{ $it['key'] }}">
                    <span class="form-check-label">{{ $it['label'] }}</span>
                  </label>
                </div>
              @endforeach
            </div>

            {{-- Uso de inmueble: checkbox + select (si aplica) --}}
            @if(data_get($docSchema,'uso_inmueble.show'))
              <hr class="my-2">
              <div class="form-row align-items-end">
                <div class="form-group col-md-4 mb-2">
                  <label class="mb-1 d-block">{{ data_get($docSchema,'uso_inmueble.label','Uso de inmueble') }}</label>
                  <label class="form-check m-0">
                    <input class="form-check-input" type="checkbox"
                          wire:model="state.documentos.{{ $docSchema['uso_inmueble']['checkboxKey'] }}">
                    <span class="form-check-label">Presenta comprobante</span>
                  </label>
                </div>
                <div class="form-group col-md-8 mb-2">
                  <label class="mb-1" for="uso_inmueble_tipo">Tipo</label>
                  <select id="uso_inmueble_tipo" class="form-control form-control-sm"
                          wire:model="state.documentos.{{ $docSchema['uso_inmueble']['selectKey'] }}">
                    <option value="">-- Seleccione uno --</option>
                    @foreach($docSchema['uso_inmueble']['options'] as $val => $txt)
                      <option value="{{ $val }}">{{ $txt }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
            @endif
          @endif
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

@push('scripts')
<script>
  function initTomSelectsOnce() {
    // Rubro principal
    const rp = document.getElementById('select-rubro-principal');
    if (rp && !rp.tomselect) {
      new TomSelect(rp, {
        allowEmptyOption: true,
        maxOptions: 4000,
        plugins: ['dropdown_input']
      });
    }

    // Rubros anexos (chips + ✖)
    const ra = document.getElementById('select-rubros-anexos');
    if (ra && !ra.tomselect) {
      new TomSelect(ra, {
        plugins: ['remove_button','checkbox_options','dropdown_input'],
        maxOptions: 8000,
        persist: false
      });
    }
  }

  function setTomSelectValues(payload = {}) {
    const { rubroId = null, anexos = [] } = payload;

    // Principal: setear primero el <select> nativo y luego TomSelect (si existe)
    const rp = document.getElementById('select-rubro-principal');
    if (rp) {
      const val = rubroId ? String(rubroId) : '';
      rp.value = val; // nativo
      if (rp.tomselect) rp.tomselect.setValue(val, false); // TomSelect
    }

    // Anexos: idem, nativo + TomSelect
    const ra = document.getElementById('select-rubros-anexos');
    if (ra) {
      const vals = (anexos || []).map(String);

      // nativo
      Array.from(ra.options).forEach(o => { o.selected = vals.includes(o.value); });

      // TomSelect
      if (ra.tomselect) {
        ra.tomselect.clear();
        if (vals.length) ra.tomselect.setValue(vals, false);
      }
    }
  }

  function bindTomSelectChangeToLivewire() {
    // Empujar cambios a Livewire
    const rp = document.getElementById('select-rubro-principal');
    if (rp && !rp.dataset._bound) {
      rp.addEventListener('change', e => {
        const val = e.target.value || null;
        @this.set('state.rubro_id', val ? parseInt(val) : null);
      });
      rp.dataset._bound = '1';
    }

    const ra = document.getElementById('select-rubros-anexos');
    if (ra && !ra.dataset._bound) {
      ra.addEventListener('change', e => {
        const arr = Array.from(e.target.selectedOptions).map(o => parseInt(o.value));
        @this.set('state.rubros_anexos', arr);
      });
      ra.dataset._bound = '1';
    }
  }

  document.addEventListener('livewire:init', () => {
    // Aseguramos init idempotente en cada render de Livewire
    Livewire.hook('message.processed', () => {
      initTomSelectsOnce();
      bindTomSelectChangeToLivewire();
    });

    // Al abrir el modal: inicializar y setear valores del payload SIEMPRE
    Livewire.on('show-form', (payload = {}) => {
      $('#form').modal('show');
      setTimeout(() => {
        initTomSelectsOnce();
        bindTomSelectChangeToLivewire();
        setTomSelectValues(payload);
      }, 50);
    });

    function initTomSelectsOnce() {
      const rp = document.getElementById('select-rubro-principal');
      if (rp && !rp.tomselect) {
        new TomSelect(rp, {
          allowEmptyOption: true,
          maxOptions: 4000,
          plugins: ['dropdown_input']
        });
      }
      const ra = document.getElementById('select-rubros-anexos');
      if (ra && !ra.tomselect) {
        new TomSelect(ra, {
          plugins: ['remove_button','checkbox_options','dropdown_input'],
          maxOptions: 8000,
          persist: false
        });
      }
    }

    function setTomSelectValues(payload = {}, attempt = 0) {
      const { rubroId = null, anexos = [] } = payload;
      const rp = document.getElementById('select-rubro-principal');
      const ra = document.getElementById('select-rubros-anexos');

      // Si todavía no están listos los TomSelect, reintentar un par de veces.
      const notReady = (rp && !rp.tomselect) || (ra && !ra.tomselect);
      if (notReady && attempt < 5) {
        setTimeout(() => setTomSelectValues(payload, attempt + 1), 60);
        return;
      }

      if (rp) {
        const val = rubroId ? String(rubroId) : '';
        rp.value = val;
        if (rp.tomselect) rp.tomselect.setValue(val, false);
      }

      if (ra) {
        const vals = (anexos || []).map(String);
        Array.from(ra.options).forEach(o => { o.selected = vals.includes(o.value); });
        if (ra.tomselect) {
          ra.tomselect.clear();
          if (vals.length) ra.tomselect.setValue(vals, false);
        }
      }
    }

    function bindTomSelectChangeToLivewire() {
      const rp = document.getElementById('select-rubro-principal');
      if (rp && !rp.dataset._bound) {
        rp.addEventListener('change', e => {
          const val = e.target.value || null;
          @this.set('state.rubro_id', val ? parseInt(val) : null);
        });
        rp.dataset._bound = '1';
      }

      const ra = document.getElementById('select-rubros-anexos');
      if (ra && !ra.dataset._bound) {
        ra.addEventListener('change', e => {
          const arr = Array.from(e.target.selectedOptions).map(o => parseInt(o.value));
          @this.set('state.rubros_anexos', arr);
        });
        ra.dataset._bound = '1';
      }
    }

    document.addEventListener('livewire:init', () => {
      Livewire.hook('message.processed', () => {
        initTomSelectsOnce();
        bindTomSelectChangeToLivewire();
      });

      // Abrir modal y setear valores (create o edit)
      Livewire.on('show-form', (payload = {}) => {
        $('#form').modal('show');
        setTimeout(() => {
          initTomSelectsOnce();
          bindTomSelectChangeToLivewire();
          setTomSelectValues(payload);
        }, 50);
      });

      // Cuando el lado PHP ya terminó de preparar el state en editar,
      // reenviamos sólo el seteo (sin reabrir modal)
      Livewire.on('refresh-selects', (payload = {}) => {
        setTomSelectValues(payload);
      });

      Livewire.on('hide-form', () => $('#form').modal('hide'));
    });

    Livewire.on('hide-form', () => $('#form').modal('hide'));
  });
</script>
@endpush



<style>
  @media (max-width: 576px) {
    .modal-dialog { max-width: 98vw !important; margin: 1.75rem auto; }
    .modal-content { padding: 0.5rem; }
  }
  .modal.show .modal-dialog { margin-top: 3.5rem; }
</style>
