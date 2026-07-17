<div class="modal fade commerce-form-modal" id="form" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
  <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable" role="document">
    <form autocomplete="off"
          wire:submit.prevent="{{ $showEditModal ? 'updateComercio' : 'createCliente' }}"
          class="modal-content commerce-form-content"
          wire:key="form-{{ $formKey ?? 'x' }}">

      <div class="modal-header commerce-form-header">
        <div class="commerce-form-heading">
          <span class="commerce-form-heading-icon"><i class="fas {{ $showEditModal ? 'fa-pen' : 'fa-store' }}"></i></span>
          <div>
            <h5 class="modal-title mb-1">{{ $showEditModal ? 'Editar comercio' : 'Nuevo comercio' }}</h5>
            <div class="commerce-form-subtitle">{{ $showEditModal ? 'Actualizá los datos del expediente comercial.' : 'Completá la información para registrar el comercio.' }}</div>
          </div>
        </div>
        <button type="button" class="close commerce-form-close" data-dismiss="modal" aria-label="Cerrar"><span>&times;</span></button>
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

      <div class="modal-body commerce-form-body">
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

        {{-- ================= RUBRO PRINCIPAL (SIN wire:ignore) ================= --}}
        <div class="form-group col-md-12 mb-1">
        <label class="mb-1">Seleccioná el Rubro Principal</label>

        {{-- SELECT CONTROLADO POR TOMSELECT --}}
        <div wire:ignore>
          <select id="select-rubro-principal"
                  class="form-control form-control-sm @error('state.rubro_id') is-invalid @enderror">
              <option value="">-- Seleccione Rubro --</option>
              @foreach($opsRubro as $op)
                  @php
                      $id  = is_array($op) ? $op['id'] : $op->id;
                      $txt = is_array($op) ? $op['subrubro'] : $op->subrubro;
                  @endphp
                  <option value="{{ $id }}">{{ $txt }}</option>
              @endforeach
          </select>
        </div>
        {{-- INPUT REAL CONTROLADO POR LIVEWIRE --}}
        <input type="hidden" wire:model="state.rubro_id">

        @error('state.rubro_id')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror

        {{-- CAMPOS DINÁMICOS --}}
        @php
          $rubro   = optional(\App\Models\Rubro::find($state['rubro_id'] ?? null));
          $esAlojamientoTuristico = $rubro->esAlojamientoTuristico();
          $esCamping = $rubro->esCamping();
        @endphp

        @if($esAlojamientoTuristico && !$esCamping)
          <div class="row mt-3" wire:key="alojamiento-{{ $state['rubro_id'] ?? 'sin-rubro' }}">
            <div class="col-md-6 mb-2">
              <label class="mb-1">Cantidad de Unidades</label>
              <input type="number"
                    class="form-control form-control-sm"
                    wire:model="state.alojamiento_unidades">
            </div>

            <div class="col-md-6 mb-2">
              <label class="mb-1">Cantidad de Plazas</label>
              <input type="number"
                    class="form-control form-control-sm"
                    wire:model="state.alojamiento_plazas">
            </div>
          </div>
        @endif

        @if($esAlojamientoTuristico && $esCamping)
          <div class="row mt-3" wire:key="camping-{{ $state['rubro_id'] ?? 'sin-rubro' }}">

            <div class="col-md-4 mb-2">
              <label class="mb-1">Cantidad de Fogones</label>
              <input type="number"
                    class="form-control form-control-sm"
                    wire:model="state.camping_fogones">
            </div>

            <div class="col-md-4 mb-2">
              <label class="mb-1">Cantidad de Dormis</label>
              <input type="number"
                    class="form-control form-control-sm"
                    wire:model="state.camping_dormis">
            </div>

            <div class="col-md-4 mb-2">
              <label class="mb-1">Otros Servicios</label>
              <input type="text"
                    class="form-control form-control-sm"
                    wire:model="state.camping_otros_servicios"
                    placeholder="Quinchos, piscina, etc.">
            </div>

          </div>
        @endif


      </div>


        {{-- ================= RUBROS ANEXOS (CON wire:ignore) ================= --}}
        <div class="form-group col-md-12 mb-1" wire:ignore>
            <label class="mb-1">Seleccioná Rubro Anexo</label>

            <select multiple id="select-rubros-anexos"
                    class="form-control form-control-sm @error('state.rubros_anexos') is-invalid @enderror"
                    size="6">
                <option value="">-- Seleccione Anexo --</option>
                @foreach($opsAnexo as $op)
                    @php
                        $id  = is_array($op) ? $op['id'] : $op->id;
                        $txt = is_array($op) ? $op['subrubro'] : $op->subrubro;
                    @endphp
                    <option value="{{ $id }}">{{ $txt }}</option>
                @endforeach
            </select>

            @error('state.rubros_anexos')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
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
              'resolucion_482'   => 'Resolución 482/22',
              'permiso_habilitante' => 'Permiso Habilitante',
              'sala_de_elaboracion' => 'Sala de Elaboración',
              'cambio_fantasia'  => 'Cambio de Nombre de Fantasia',
            ],
            '032' => [
              '' => 'Ninguno',
              'cambio_rubro'     => 'Cambio de Rubro',
              'adicion_anexo'    => 'Adeción de Rubro Anexo',
              'cambio_fantasia'  => 'Cambio de Nombre de Fantasía',
              'baja_alojamiento' => 'Baja de Unidad de Alojamiento',
              'cambio_razon'     => 'Cambio de Razón Social',
              'permiso_habilitante' => 'Permiso Habilitante',
              'sala_de_elaboracion' => 'Sala de Elaboración',
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

        <div class="form-row">
          <div class="form-group col-md-6 mb-2">
            <label class="mb-1" for="suspension_tasas_desde">Suspensión de tasas desde</label>
            <input type="date" id="suspension_tasas_desde" wire:model.defer="state.suspension_tasas_desde"
                   class="form-control form-control-sm @error('state.suspension_tasas_desde') is-invalid @enderror">
            @error('state.suspension_tasas_desde') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="form-group col-md-6 mb-2">
            <label class="mb-1" for="suspension_tasas_hasta">Suspensión de tasas hasta</label>
            <input type="date" id="suspension_tasas_hasta" wire:model.defer="state.suspension_tasas_hasta"
                   class="form-control form-control-sm @error('state.suspension_tasas_hasta') is-invalid @enderror">
            @error('state.suspension_tasas_hasta') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                    class="form-control @error('fecha_vto') is-invalid @enderror">
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
              <label class="mb-1" for="fecha_vto">Fecha de vencimiento</label>
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
            <div class="form-check mb-2">
              <input type="checkbox" class="form-check-input" id="chkBajaTemporaria" wire:model="state.baja_temporaria">
              <label class="form-check-label" for="chkBajaTemporaria">Baja temporaria</label>
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

        <div class="commerce-documentation-panel mt-2">
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
                <div class="col-md-6" wire:key="documento-{{ $formKey ?? 'form' }}-{{ $it['key'] }}">
                  <label class="form-check mb-1" for="documento_{{ $formKey ?? 'form' }}_{{ $it['key'] }}">
                    <input id="documento_{{ $formKey ?? 'form' }}_{{ $it['key'] }}"
                           class="form-check-input" type="checkbox"
                           wire:model="state.documentos.{{ $it['key'] }}">
                    <span class="form-check-label">{{ $it['label'] }}</span>
                  </label>
                </div>
              @endforeach
            </div>

            {{-- Uso de inmueble: checkbox + select (si aplica) --}}
            @if(data_get($docSchema,'uso_inmueble.show'))
              <hr class="my-2">
              <div class="form-row align-items-end"
                   wire:key="uso-inmueble-{{ $formKey ?? 'form' }}-{{ $docSchema['uso_inmueble']['checkboxKey'] }}">
                <div class="form-group col-md-4 mb-2">
                  <label class="mb-1 d-block">{{ data_get($docSchema,'uso_inmueble.label','Uso de inmueble') }}</label>
                  <label class="form-check m-0" for="uso_inmueble_{{ $formKey ?? 'form' }}">
                    <input id="uso_inmueble_{{ $formKey ?? 'form' }}"
                          class="form-check-input" type="checkbox"
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

      <div class="modal-footer commerce-form-footer">
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
document.addEventListener('livewire:init', () => {

    // Reinicia SIEMPRE TomSelect (destruye y crea)
    function resetTomSelect(selector, options = {}) {
        const el = document.querySelector(selector);
        if (!el) return;

        // Si TomSelect ya existe, destruirlo
        if (el.tomselect) {
            el.tomselect.destroy();
        }

        // Crear uno nuevo
        const ts = new TomSelect(el, options);

        return ts;
    }

    // Setear valores iniciales desde Livewire → JS
    function setValues(payload = {}) {
        const { rubroId, anexos = [] } = payload;

        const rp = document.getElementById('select-rubro-principal');
        if (rp?.tomselect) {
            rp.tomselect.setValue(rubroId ? String(rubroId) : '', false);
        }

        const ra = document.getElementById('select-rubros-anexos');
        if (ra?.tomselect) {
            ra.tomselect.clear();
            if (anexos.length) {
                ra.tomselect.setValue(anexos.map(String), false);
            }
        }
    }

    // Vincular eventos por ÚNICA VEZ
    function bindEvents() {

        const rp = document.getElementById('select-rubro-principal');
        if (rp && !rp.dataset.bound) {
            rp.addEventListener('change', e => {
                const v = e.target.value;
                @this.set('state.rubro_id', v ? parseInt(v) : null);
            });
            rp.dataset.bound = "1";
        }

        const ra = document.getElementById('select-rubros-anexos');
        if (ra && !ra.dataset.bound) {
            ra.addEventListener('change', e => {
                const values = Array.from(e.target.selectedOptions).map(o => parseInt(o.value));
                @this.set('state.rubros_anexos', values);
            });
            ra.dataset.bound = "1";
        }
    }

    // Al procesarse CUALQUIER mensaje Livewire
    Livewire.hook('message.processed', () => {

        // Inicializar ambos selects SIEMPRE
        resetTomSelect('#select-rubro-principal', {
            allowEmptyOption: true,
            maxOptions: 4000,
            plugins: ['dropdown_input']
        });

        resetTomSelect('#select-rubros-anexos', {
            plugins: ['remove_button','checkbox_options','dropdown_input'],
            maxOptions: 8000,
            persist: false
        });

        bindEvents();
    });

    // Abrir modal → inicializar estados
    Livewire.on('show-form', payload => {
        $('#form').modal('show');
        setTimeout(() => {
            resetTomSelect('#select-rubro-principal', {
                allowEmptyOption: true,
                maxOptions: 4000,
                plugins: ['dropdown_input']
            });

            resetTomSelect('#select-rubros-anexos', {
                plugins: ['remove_button','checkbox_options','dropdown_input'],
                maxOptions: 8000,
                persist: false
            });

            bindEvents();
            setValues(payload);

        }, 80);
    });

    Livewire.on('hide-form', () => $('#form').modal('hide'));

});
</script>

@endpush

<style>
  .commerce-form-modal {
    --form-navy: #17385f;
    --form-blue: #286da8;
    --form-green: #17845f;
    --form-ink: #243b53;
    --form-muted: #6b7d8f;
    --form-line: #dce6ef;
  }
  .commerce-form-modal .modal-dialog { max-width: 1120px; margin-top: 2.4rem; }
  .commerce-form-modal .commerce-form-content {
    overflow: hidden;
    padding: 0;
    border: 0;
    border-radius: 1rem;
    background: #f3f7fa;
    box-shadow: 0 24px 65px rgba(15,39,64,.28);
  }
  .commerce-form-modal .commerce-form-header {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 82px;
    padding: 1rem 1.35rem;
    border: 0;
    background: linear-gradient(125deg,#17385f 0%,#245f91 65%,#268b8b 125%);
    color: #fff;
  }
  .commerce-form-modal .commerce-form-header::after {
    content: '';
    position: absolute;
    width: 180px;
    height: 180px;
    right: -55px;
    top: -125px;
    border-radius: 50%;
    background: rgba(255,255,255,.08);
    pointer-events: none;
  }
  .commerce-form-modal .commerce-form-heading {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: .85rem;
  }
  .commerce-form-modal .commerce-form-heading-icon {
    width: 44px;
    height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 44px;
    border: 1px solid rgba(255,255,255,.2);
    border-radius: .8rem;
    background: rgba(255,255,255,.12);
    font-size: 1.05rem;
  }
  .commerce-form-modal .modal-title {
    color: #fff;
    font-size: 1.15rem;
    font-weight: 800;
    letter-spacing: -.015em;
  }
  .commerce-form-modal .commerce-form-subtitle { color: rgba(255,255,255,.73); font-size: .78rem; }
  .commerce-form-modal .commerce-form-close {
    position: relative;
    z-index: 2;
    width: 36px;
    height: 36px;
    margin: 0;
    padding: 0;
    border: 1px solid rgba(255,255,255,.25);
    border-radius: .65rem;
    color: #fff;
    background: rgba(255,255,255,.1);
    opacity: 1;
    text-shadow: none;
  }
  .commerce-form-modal .commerce-form-close:hover { background: #fff; color: var(--form-navy); }
  .commerce-form-modal .commerce-form-body {
    padding: 1rem 1.1rem !important;
    background: #f3f7fa;
  }
  .commerce-form-modal .commerce-form-body > .form-row,
  .commerce-form-modal .commerce-form-body > .form-group {
    margin-right: 0;
    margin-bottom: .75rem !important;
    margin-left: 0;
    padding: .75rem .65rem .15rem;
    border: 1px solid var(--form-line);
    border-radius: .8rem;
    background: #fff;
    box-shadow: 0 3px 11px rgba(30,58,86,.035);
  }
  .commerce-form-modal .commerce-form-body > .form-group.col-md-12 { max-width: 100%; }
  .commerce-form-modal .form-group > label,
  .commerce-form-modal .form-group > label > span:first-child {
    color: #587087;
    font-size: .7rem;
    font-weight: 800;
    letter-spacing: .035em;
    text-transform: uppercase;
  }
  .commerce-form-modal .form-control,
  .commerce-form-modal .ts-control {
    min-height: 38px;
    border: 1px solid #cad8e4;
    border-radius: .55rem;
    background: #fbfdff;
    color: var(--form-ink);
    font-size: .86rem;
  }
  .commerce-form-modal textarea.form-control { min-height: 70px; resize: vertical; }
  .commerce-form-modal .form-control:focus,
  .commerce-form-modal .ts-control.focus {
    border-color: #70a7d1;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(40,109,168,.1);
  }
  .commerce-form-modal .form-check {
    min-height: 30px;
    display: flex;
    align-items: center;
    padding: .35rem .45rem .35rem 1.75rem;
    border-radius: .45rem;
  }
  .commerce-form-modal .form-check:hover { background: #eef5fa; }
  .commerce-form-modal .form-check-input { margin-top: 0; }
  .commerce-form-modal .form-check-label { color: #3d5368; font-size: .82rem; }
  .commerce-form-modal .commerce-documentation-panel {
    padding: .9rem;
    border: 1px solid #cddde9;
    border-radius: .85rem;
    background: #fff;
    box-shadow: 0 5px 16px rgba(30,58,86,.055);
  }
  .commerce-form-modal .commerce-documentation-panel h6 {
    color: var(--form-navy);
    font-size: .95rem;
    font-weight: 800;
  }
  .commerce-form-modal .btn { border-radius: .55rem; font-weight: 700; }
  .commerce-form-modal .btn-primary { border-color: var(--form-blue); background: var(--form-blue); }
  .commerce-form-modal .btn-success { border-color: var(--form-green); background: var(--form-green); }
  .commerce-form-modal .commerce-form-footer {
    position: sticky;
    bottom: 0;
    z-index: 3;
    min-height: 66px;
    padding: .75rem 1.1rem;
    border-top: 1px solid var(--form-line);
    background: rgba(255,255,255,.96);
    box-shadow: 0 -6px 18px rgba(30,58,86,.06);
    backdrop-filter: blur(7px);
  }
  .commerce-form-modal .commerce-form-footer .btn { min-width: 112px; min-height: 39px; }
  .commerce-form-modal .alert-danger {
    margin: .8rem 1rem 0;
    border: 1px solid #f1b7bb;
    border-radius: .7rem;
    font-size: .82rem;
  }
  @media (max-width: 767.98px) {
    .commerce-form-modal .modal-dialog { max-width: calc(100vw - 1rem) !important; margin: .5rem auto; }
    .commerce-form-modal .commerce-form-content { max-height: calc(100vh - 1rem); border-radius: .8rem; }
    .commerce-form-modal .commerce-form-header { min-height: 72px; padding: .85rem 1rem; }
    .commerce-form-modal .commerce-form-heading-icon { display: none; }
    .commerce-form-modal .commerce-form-subtitle { max-width: 230px; }
    .commerce-form-modal .commerce-form-body { padding: .7rem !important; }
    .commerce-form-modal .commerce-form-body > .form-row,
    .commerce-form-modal .commerce-form-body > .form-group { padding: .65rem .5rem .05rem; }
    .commerce-form-modal .commerce-form-footer { justify-content: stretch; }
    .commerce-form-modal .commerce-form-footer .btn { flex: 1 1 50%; min-width: 0; }
  }
</style>
