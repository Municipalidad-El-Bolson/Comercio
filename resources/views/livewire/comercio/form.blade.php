<!-- Modal -->
{{-- <x-confirmation-alert /> --}}
<div class="modal fade" id="form" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true"
    wire:ignore.self>
    <div class="modal-dialog modal-lg" role="document"
        style="margin-top: 150px; margin-left: auto; margin-right: auto; max-width: 800px;">
        <form autocomplete="off" wire:submit.prevent="{{ $showEditModal ? 'updateComercio' : 'createCliente' }}">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title mb-0" id="exampleModalLabel">
                    {{ $showEditModal ? 'Editar Comercio' : 'Nuevo Comercio' }}
                    </h6>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body p-2">
                    {{-- Persona: Física o Jurídica --}}
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
                        <label class="mb-1" for="nombre_comercial">Nombre Comercial</label>
                        <input type="text" id="nombre_comercial" wire:model.defer="state.nombre_comercial"
                        class="form-control form-control-sm text-capitalize @error('state.nombre_comercial') is-invalid @enderror"
                        placeholder="Nombre comercial">
                        @error('state.nombre_comercial') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    </div>

                    {{-- Identificación: o Apellido+Nombre (física) o Razón Social (jurídica) --}}
                    <div class="form-row">
                    @if(($state['persona_tipo'] ?? 'fisica') === 'fisica')
                        <div class="form-group col-md-6 mb-2">
                        <label class="mb-1" for="apellido">Apellido</label>
                        <input type="text" id="apellido" wire:model.defer="state.apellido"
                            class="form-control form-control-sm text-capitalize @error('state.apellido') is-invalid @enderror"
                            placeholder="Apellido">
                        @error('state.apellido') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group col-md-6 mb-2">
                        <label class="mb-1" for="nombres">Nombres</label>
                        <input type="text" id="nombres" wire:model.defer="state.nombres"
                            class="form-control form-control-sm text-capitalize @error('state.nombres') is-invalid @enderror"
                            placeholder="Nombres">
                        @error('state.nombres') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @else
                        <div class="form-group col-md-12 mb-2">
                        <label class="mb-1" for="razon_social">Razón Social</label>
                        <input type="text" id="razon_social" wire:model.defer="state.razon_social"
                            class="form-control form-control-sm text-capitalize @error('state.razon_social') is-invalid @enderror"
                            placeholder="Razón Social">
                        @error('state.razon_social') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif
                    </div>

                    {{-- Contacto y domicilios --}}
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

                    {{-- Rubro + Estado del trámite --}}
                    <div class="form-row">
                    <div class="form-group col-md-6 mb-2">
                        <label class="mb-1" for="rubro_id">Rubro</label>
                        <select id="rubro_id" wire:model.defer="state.rubro_id"
                        class="form-control form-control-sm @error('state.rubro_id') is-invalid @enderror">
                        <option value="">-- Seleccione Rubro --</option>
                        @foreach ($rubros as $rubro)
                            <option value="{{ $rubro->id }}">
                            {{ $rubro->rubro_madre }} — {{ $rubro->subrubro }}
                            </option>
                        @endforeach
                        </select>
                        @error('state.rubro_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-group col-md-6 mb-2">
                        <label class="mb-1" for="estado">Estado</label>
                        <select id="estado" wire:model.defer="state.estado"
                        class="form-control form-control-sm @error('state.estado') is-invalid @enderror">
                        <option value="vigente">Vigente</option>
                        <option value="irregular">Irregular</option>
                        <option value="entramite">En Trámite</option>
                        </select>
                        @error('state.estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    </div>

                    {{-- Situación (alta/baja) + fechas --}}
                    <div class="form-row">
                    <div class="form-group col-md-4 mb-2">
                        <label class="mb-1" for="situacion">Situación</label>
                        <select id="situacion" wire:model.defer="state.situacion"
                        class="form-control form-control-sm @error('state.situacion') is-invalid @enderror">
                        <option value="alta">Alta</option>
                        <option value="baja">Baja</option>
                        </select>
                        @error('state.situacion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @if(($state['situacion'] ?? 'alta') === 'baja')
                        <div class="form-group col-md-4 mb-2">
                        <label class="mb-1" for="fecha_baja">Fecha de baja</label>
                        <input type="date" id="fecha_baja" wire:model.defer="state.fecha_baja"
                            class="form-control form-control-sm @error('state.fecha_baja') is-invalid @enderror">
                        @error('state.fecha_baja') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @else
                        <div class="form-group col-md-4 mb-2">
                        <label class="mb-1" for="fecha_alta">Fecha de alta</label>
                        <input type="date" id="fecha_alta" wire:model.defer="state.fecha_alta"
                            class="form-control form-control-sm @error('state.fecha_alta') is-invalid @enderror">
                        @error('state.fecha_alta') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    <div class="form-group col-md-4 mb-2">
                        <label class="mb-1" for="monto_pagar">Monto a pagar (opcional)</label>
                        <input type="number" step="0.01" id="monto_pagar" wire:model.defer="state.monto_pagar"
                        class="form-control form-control-sm @error('state.monto_pagar') is-invalid @enderror"
                        placeholder="0.00">
                        @error('state.monto_pagar') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

                    {{-- Documentación (checkboxes) --}}
                    <div class="border rounded p-2 mt-2">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="mb-0">Documentación</h6>
                        <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-success"
                                wire:click="marcarTodosLosDocs(true)">
                            Presentó toda la documentación
                        </button>
                        <button type="button" class="btn btn-outline-secondary"
                                wire:click="marcarTodosLosDocs(false)">
                            Limpiar
                        </button>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Generales --}}
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
                            <span class="form-check-label">Constancia de inscripción emitida por Agencia de Recaudación Tributaria de Río Negro</span>
                        </label>

                        <label class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" wire:model="state.documentos.doc_fotocopia_dni">
                            <span class="form-check-label">Fotocopia del DNI</span>
                        </label>

                        <label class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" wire:model="state.documentos.doc_comprobante_uso_inmueble">
                            <span class="form-check-label">Comprobante que acredite el uso del inmueble a destinar a comercio</span>
                        </label>

                        <label class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" wire:model="state.documentos.doc_libre_deuda_tasas_inmueble">
                            <span class="form-check-label">Libre deuda de tasas municipales de la propiedad</span>
                        </label>

                        <label class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" wire:model="state.documentos.doc_aptitud_tecnica_local">
                            <span class="form-check-label">Certificado de aptitud técnica del local a habilitar</span>
                        </label>

                        <label class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" wire:model="state.documentos.doc_cocap_rhi">
                            <span class="form-check-label">Certificado de CO.CA.P.R.HI</span>
                        </label>

                        <label class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" wire:model="state.documentos.doc_nota_carteleria_obras">
                            <span class="form-check-label">Nota a Obras Públicas declarando cartelería usada como publicidad</span>
                        </label>

                        <label class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" wire:model="state.documentos.doc_libro_actas_100">
                            <span class="form-check-label">Libro de actas de 100 hojas</span>
                        </label>
                        </div>

                        {{-- Documentación adicional para Jurídicas --}}
                        @if(($state['persona_tipo'] ?? 'fisica') === 'juridica')
                        <div class="col-md-6">
                            <strong class="d-block mb-1">Personas Jurídicas</strong>

                            <label class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" wire:model="state.documentos.doc_acta_constitucion">
                            <span class="form-check-label">Acta de constitución de sociedad u organización</span>
                            </label>

                            <label class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" wire:model="state.documentos.doc_contrato_societario">
                            <span class="form-check-label">Contrato societario</span>
                            </label>

                            <label class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" wire:model="state.documentos.doc_docs_representantes">
                            <span class="form-check-label">Documentación de sus representantes</span>
                            </label>
                        </div>
                        @endif
                    </div>
                    </div>

                </div>

                <div class="modal-footer py-2 px-3">
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">
                    <i class="fa fa-times mr-1"></i> Cerrar
                    </button>
                    <button type="submit" class="btn btn-sm btn-primary">
                    <i class="fa fa-save mr-1"></i>
                    {{ $showEditModal ? 'Guardar Cambios' : 'Grabar' }}
                    </button>
                </div>
                </div>

        </form>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('form');
        if (modal) {
            $('#form').on('shown.bs.modal', function() {
                const input = document.getElementById('razon_social');
                if (input) {
                    input.focus();
                    input.select();
                }
            });
        }
    });
    document.addEventListener('livewire:init', () => {
        Livewire.on('show-form', () => {
            $('#form').modal('show');
        });
        Livewire.on('hide-form', () => {
            $('#form').modal('hide');
        });
    });
</script>
