<section class="content">
  <div class="content-header">
    <div class="container-fluid">

      <h1 class="mb-3">Reportes de Habilitaciones Comerciales</h1>

      {{-- Filtros --}}
      <div class="card card-outline card-secondary mb-3">
        <div class="card-body">
          <div class="form-row">
            <div class="form-group col-md-3">
              <label>Subrubro</label>
              <select class="form-control" wire:model.live="rubro_id">
                <option value="">-- Todos --</option>
              </select>
            </div>

            <div class="form-group col-md-3">
              <label>Estado</label>
              <select class="form-control" wire:model.live="estado">
                <option value="">-- Todos --</option>
                <option value="entramite">En trámite</option>
                <option value="vigente">Vigente</option>
                <option value="irregular">Clausurado</option>
                <option value="baja">Baja</option>
              </select>
            </div>

            <div class="form-group col-md-3">
              <label>Desde</label>
              <input type="date" class="form-control" wire:model.live="desde">
            </div>

            <div class="form-group col-md-3">
              <label>Hasta</label>
              <input type="date" class="form-control" wire:model.live="hasta">
            </div>
          </div>

          <div class="form-row mt-3">
            <div class="col-md-12">
                <button class="btn btn-outline-danger ml-2" wire:click="exportarPdf">
                    <i class="fas fa-file-pdf mr-1"></i> Descargar PDF
                </button>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>
