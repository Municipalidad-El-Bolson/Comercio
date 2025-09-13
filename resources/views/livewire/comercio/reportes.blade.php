<section class="content">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">Reportes de Habilitaciones Comerciales</h1></div>
      </div>

      {{-- Filtros --}}
      <div class="card card-outline card-secondary mb-3">
        <div class="card-body">
          <div class="form-row">
            {{-- Búsqueda por rubro (sin JS, con datalist) --}}
            <div class="form-group col-md-4">
              <label>Rubro (buscar)</label>
              <input type="text"
                     class="form-control"
                     list="rubros-list"
                     placeholder="Escribí para filtrar rubros…"
                     wire:model.live="rubro_search">
              <datalist id="rubros-list">
                @foreach($rubrosOpts as $r)
                  <option value="{{ $r['subrubro'] }}"></option>
                @endforeach
              </datalist>
              <small class="text-muted">Tipiá para acotar la lista de rubros.</small>
            </div>

            {{-- Selección exacta de rubro principal --}}
            <div class="form-group col-md-4">
              <label>Rubro (principal)</label>
              <select class="form-control" wire:model.live="rubro_id">
                <option value="">-- Todos --</option>
                @foreach($rubrosOpts as $r)
                  <option value="{{ $r['id'] }}">{{ $r['subrubro'] }}</option>
                @endforeach
              </select>
              <small class="text-muted">Lista filtrada por lo que escribas arriba.</small>
            </div>

            <div class="form-group col-md-4">
              <label>Estado</label>
              <select class="form-control" wire:model.live="estado">
                <option value="">-- Todos --</option>
                <option value="entramite">En trámite</option>
                <option value="vigente">Vigente</option>
                <option value="irregular">Clausurado</option>
                <option value="baja">Baja</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-3">
              <label>Desde (rangos mensuales)</label>
              <input type="date" class="form-control" wire:model.live="desde">
            </div>

            <div class="form-group col-md-3">
              <label>Hasta</label>
              <input type="date" class="form-control" wire:model.live="hasta">
            </div>

            <div class="form-group col-md-3">
              <label>Próx. a vencer (días)</label>
              <select class="form-control" wire:model.live="proximos_vtos">
                <option value="30">30</option>
                <option value="60">60</option>
                <option value="90">90</option>
              </select>
            </div>
          </div>

          <button class="btn btn-outline-danger ml-2" wire:click="exportarPdf">
            <i class="fas fa-file-pdf mr-1"></i> Descargar PDF
          </button>
        </div>
      </div>

      <div class="row">
        {{-- Listado general --}}
        <div class="col-lg-6">
          <div class="card border-secondary h-100">
            <div class="card-header">Listado general</div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                  <thead class="thead-light">
                    <tr>
                      <th>Nombre</th>
                      <th>Estado</th>
                      <th>Rubro (principal)</th>
                      <th>Anexos</th>
                      <th>Vto</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($this->listadoGeneral as $u)
                      @php
                        $anexos = $u->rubros
                          ->when($u->rubro_id, fn($c) => $c->where('id', '!=', $u->rubro_id))
                          ->pluck('subrubro')->filter()->values()->all();
                      @endphp
                      <tr>
                        <td>{{ $u->nombre_comercial ?? '-' }}</td>
                        <td>{{ $u->estadoModel->descripcion ?? $u->estado }}</td>
                        <td>{{ $u->rubro->subrubro ?? '-' }}</td>
                        <td>
                          @forelse($anexos as $a)
                            <span class="badge badge-secondary mr-1 mb-1">{{ $a }}</span>
                          @empty
                            —
                          @endforelse
                        </td>
                        <td>{{ $u->fecha_vto ? \Illuminate\Support\Carbon::parse($u->fecha_vto)->format('Y-m-d') : '—' }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
            <div class="card-footer">
              {{ $this->listadoGeneral->links() }}
            </div>
          </div>
        </div>

        {{-- Comercios por rubro (principal) --}}
        <div class="col-lg-6">
          <div class="card border-secondary h-100">
            <div class="card-header">Comercios por rubro (principal)</div>
            <div class="card-body p-0">
              <div class="p-2">
                <small class="text-muted">Total considerado: {{ $this->porRubro['total'] }}</small>
              </div>
              <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                  <thead class="thead-light">
                    <tr>
                      <th>Rubro</th>
                      <th class="text-right">Cantidad</th>
                      <th class="text-right">% del total</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($this->porRubro['items'] as $r)
                      <tr>
                        <td>{{ $r->subrubro }}</td>
                        <td class="text-right">{{ $r->cantidad }}</td>
                        <td class="text-right">{{ $r->porcentaje }}%</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        {{-- (Se eliminan tarjetas por Mega rubro y Rubro madre) --}}

        {{-- Comercios por estado --}}
        <div class="col-lg-6 mt-3">
          <div class="card border-secondary">
            <div class="card-header">Comercios por estado</div>
            <div class="card-body">
              @php $e = $this->porEstado; @endphp
              <div class="row text-center">
                <div class="col-6 col-md-3">
                  <h4 class="mb-0">{{ $e['vigentes']['n'] }}</h4>
                  <small>Vigentes ({{ $e['vigentes']['pct'] }}%)</small>
                </div>
                <div class="col-6 col-md-3">
                  <h4 class="mb-0">{{ $e['vencidos']['n'] }}</h4>
                  <small>Vencidos ({{ $e['vencidos']['pct'] }}%)</small>
                </div>
                <div class="col-6 col-md-3 mt-3 mt-md-0">
                  <h4 class="mb-0">{{ $e['tramite']['n'] }}</h4>
                  <small>En trámite ({{ $e['tramite']['pct'] }}%)</small>
                </div>
                <div class="col-6 col-md-3 mt-3 mt-md-0">
                  <h4 class="mb-0">{{ $e['claus']['n'] }}</h4>
                  <small>Clausurados ({{ $e['claus']['pct'] }}%)</small>
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- Nuevos habilitados --}}
        <div class="col-lg-6 mt-3">
          <div class="card border-secondary">
            <div class="card-header">Nuevos comercios habilitados ({{ $this->desde }} a {{ $this->hasta }})</div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                  <thead class="thead-light">
                    <tr>
                      <th>Año</th>
                      <th>Mes</th>
                      <th class="text-right">Cantidad</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($this->habilitadosPorMes as $r)
                      <tr>
                        <td>{{ $r->anio }}</td>
                        <td>{{ str_pad($r->mes,2,'0',STR_PAD_LEFT) }}</td>
                        <td class="text-right">{{ $r->cantidad }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        {{-- Bajas --}}
        <div class="col-lg-6 mt-3">
          <div class="card border-secondary">
            <div class="card-header">Comercios dados de baja ({{ $this->desde }} a {{ $this->hasta }})</div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                  <thead class="thead-light">
                    <tr>
                      <th>Año</th>
                      <th>Mes</th>
                      <th class="text-right">Cantidad</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($this->bajasPorMes as $r)
                      <tr>
                        <td>{{ $r->anio }}</td>
                        <td>{{ str_pad($r->mes,2,'0',STR_PAD_LEFT) }}</td>
                        <td class="text-right">{{ $r->cantidad }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        {{-- Próximos a vencer --}}
        <div class="col-lg-6 mt-3">
          <div class="card border-secondary">
            <div class="card-header">Habilitaciones próximas a vencer ({{ $this->proximos_vtos }} días)</div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                  <thead class="thead-light">
                    <tr>
                      <th>Nombre</th>
                      <th>Rubro (principal)</th>
                      <th>Vencimiento</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($this->proximosAVencer as $u)
                      <tr>
                        <td>{{ $u->nombre_comercial ?? '-' }}</td>
                        <td>{{ $u->rubro->subrubro ?? '-' }}</td>
                        <td>{{ $u->fecha_vto ? \Illuminate\Support\Carbon::parse($u->fecha_vto)->format('Y-m-d') : '—' }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

      </div> {{-- row --}}
    </div>
  </div>
</section>
