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
                    {{-- Primera fila: Razon Social + DNI --}}
                    <div class="form-row">
                        <div class="form-group col-md-6 mb-2">
                            <label class="mb-1" for="razon_social">Razón Social</label>
                            <input type="text" wire:model.defer="state.razon_social" id="razon_social"
                                name="razon_social"
                                class="form-control form-control-sm text-capitalize @error('state.razon_social') is-invalid @enderror"
                                placeholder="Razón Social">
                            @error('state.razon_social')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group col-md-6 mb-2">
                            <label class="mb-1" for="dni">DNI</label>
                            <input type="number" wire:model.defer="state.dni" id="dni"
                                class="form-control form-control-sm @error('state.dni') is-invalid @enderror"
                                placeholder="DNI">
                            @error('state.dni')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Segunda fila: Apellido + Nombres --}}
                    <div class="form-row">
                        <div class="form-group col-md-6 mb-2">
                            <label class="mb-1" for="apellido">Apellido</label>
                            <input type="text" wire:model.defer="state.apellido" id="apellido"
                                class="form-control form-control-sm text-capitalize @error('state.apellido') is-invalid @enderror"
                                placeholder="Apellido">
                            @error('state.apellido')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group col-md-6 mb-2">
                            <label class="mb-1" for="nombres">Nombres</label>
                            <input type="text" wire:model.defer="state.nombres" id="nombres"
                                class="form-control form-control-sm text-capitalize @error('state.nombres') is-invalid @enderror"
                                placeholder="Nombres">
                            @error('state.nombres')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Tercera fila: Dirección + Rubro --}}
                    <div class="form-row">
                        <div class="form-group col-md-6 mb-2">
                            <label class="mb-1" for="direccion">Dirección</label>
                            <input type="text" wire:model.defer="state.direccion" id="direccion"
                                class="form-control form-control-sm text-capitalize @error('state.direccion') is-invalid @enderror"
                                placeholder="Dirección">
                            @error('state.direccion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group col-md-6 mb-2">
                            <label class="mb-1" for="direccion">Rubro</label>
                            <select id="rubro_id" wire:model.defer="state.rubro_id"
                                class="form-control form-control-sm @error('state.rubro_id') is-invalid @enderror">
                                <option value="">-- Seleccione Rubro --</option>
                                @foreach ($rubros as $rubro)
                                    <option value="{{ $rubro->id }}">{{ $rubro->rubro }}</option>
                                @endforeach
                            </select>
                            @error('state.rubro_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Cuarta fila: Tipo + Estado --}}
                    <div class="form-row">
                        <div class="form-group col-md-6 mb-2">
                            <label class="mb-1" for="tipo">Tipo</label>
                            <input type="text" wire:model.defer="state.tipo" id="tipo"
                                class="form-control form-control-sm text-capitalize @error('state.tipo') is-invalid @enderror"
                                placeholder="Tipo">
                            @error('state.tipo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group col-md-6 mb-2">
                            <label class="mb-1" for="estado">Estado</label>
                            <select wire:model.defer="state.estado" id="estado"
                                class="form-control form-control-sm @error('state.estado') is-invalid @enderror">
                                <option value="normal">Normal</option>
                                <option value="irregular">Irregular</option>
                                <option value="faltadoc">Falta Documentación</option>
                            </select>
                            @error('state.estado')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
</script>
