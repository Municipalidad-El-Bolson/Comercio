<div class="container">
  {{-- encabezado --}}
  <div class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h1 class="m-0">Detalle del Comercio</h1>

        <div class="btn-group" x-data>
          <a wire:navigate href="{{ url()->previous() }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Volver
          </a>

          @isset($ubicacion->id)
            <a href="#" wire:click.prevent="editaComercio({{ $ubicacion->id }})" class="btn btn-primary btn-sm">
              <i class="fa fa-edit mr-1" title="Editar Registro"></i> Editar
            </a>
          @endisset

          @can('manage-ubicaciones')
            <button type="button"
                    class="btn btn-danger btn-sm"
                    x-on:click.prevent="if (confirm('¿Eliminar definitivamente este comercio? Esta acción no se puede deshacer.')) { $wire.deleteComercio() }">
              <i class="fa fa-trash mr-1"></i> Eliminar
            </button>
          @endcan
        </div>
      </div>
    </div>
  </div>

  <div class="container-fluid">
    {{-- Timeline arriba --}}
    @if($ubicacion->habilita_seguimiento)
      <livewire:comercio.timeline 
        :ubicacion-id="$ubicacion->id"
        :created-at="$ubicacion->created_at" />
    @endif

    {{-- Identificación --}}
    <div class="card mb-3">
      <div class="card-header bg-light"><strong>Identificación</strong></div>
      <div class="card-body">
        @php
          $tels = $ubicacion->telefonos->pluck('telefono')->filter()->implode(' / ');
        @endphp

        {{-- Fila 1: siempre visibles --}}
        <div class="row">
          <div class="col-md-3 mb-2">
            <div class="text-muted small">Tipo de persona</div>
            <div class="font-weight-bold">{{ ucfirst($ubicacion->persona_tipo) }}</div>
          </div>
          <div class="col-md-3 mb-2">
            <div class="text-muted small">DNI / CUIT</div>
            <div class="font-weight-bold">{{ $ubicacion->dni_cuit ?: '—' }}</div>
          </div>

          @if($esJuridica)
            <div class="col-md-3 mb-2">
              <div class="text-muted small">Razón social</div>
              <div class="font-weight-bold">{{ $ubicacion->razon_social ?: '—' }}</div>
            </div>
            <div class="col-md-3 mb-2">
              <div class="text-muted small">Nombre comercial</div>
              <div class="font-weight-bold">{{ $ubicacion->nombre_comercial ?: '—' }}</div>
            </div>
          @else
            <div class="col-md-3 mb-2">
              <div class="text-muted small">Apellido</div>
              <div class="font-weight-bold">{{ $ubicacion->apellido ?: '—' }}</div>
            </div>
            <div class="col-md-3 mb-2">
              <div class="text-muted small">Nombres</div>
              <div class="font-weight-bold">{{ $ubicacion->nombres ?: '—' }}</div>
            </div>
          @endif
        </div>

        {{-- Fila 2: correo/teléfonos (+ nombre comercial si es física) --}}
        <div class="row">
          @if(!$esJuridica)
            <div class="col-md-3 mb-2">
              <div class="text-muted small">Nombre comercial</div>
              <div class="font-weight-bold">{{ $ubicacion->nombre_comercial ?: '—' }}</div>
            </div>
          @endif

          <div class="col-md-{{ $esJuridica ? '6' : '3' }} mb-2">
            <div class="text-muted small">Correo</div>
            <div class="font-weight-bold">{{ $ubicacion->correo ?: '—' }}</div>
          </div>
          <div class="col-md-{{ $esJuridica ? '6' : '3' }} mb-2">
            <div class="text-muted small">Teléfono(s)</div>
            <div class="font-weight-bold">{{ $tels !== '' ? $tels : '—' }}</div>
          </div>
        </div>
      </div>
    </div>

    {{-- Rubro y estado (solo el bloque de anexos cambia) --}}
    <div class="card mb-3">
      <div class="card-header bg-light"><strong>Rubro y Estado</strong></div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4 mb-2">
            <div class="text-muted small">Rubro (principal)</div>
            <div class="font-weight-bold">{{ optional($ubicacion->rubro)->subrubro ?: '—' }}</div>
          </div>

          <div class="col-md-4 mb-2">
            <div class="text-muted small">Rubros anexos</div>
            @php
              $anexos = $ubicacion->rubros
                ->when($ubicacion->rubro_id, fn($c) => $c->where('id', '!=', $ubicacion->rubro_id))
                ->pluck('subrubro')
                ->filter()
                ->values()
                ->all();
            @endphp
            @if(empty($anexos))
              <div class="text-muted">—</div>
            @else
              <div>
                @foreach($anexos as $a)
                  <span class="badge badge-secondary mr-1 mb-1">{{ $a }}</span>
                @endforeach
              </div>
            @endif
          </div>

          <div class="col-md-4 mb-2">
            <div class="text-muted small">Estado</div>
            @php
              $estado = strtolower($ubicacion->estado ?? '');
              $badgeEstado = $estado === 'vigente' ? 'success' : ($estado === 'irregular' ? 'danger' : 'warning');
            @endphp
            <span class="badge badge-{{ $badgeEstado }}">{{ ucfirst($ubicacion->estado ?? '-') }}</span>
          </div>
        </div>

        @php
          $tieneAlta = !empty($ubicacion->fecha_alta);
          $tieneBaja = !empty($ubicacion->fecha_baja);
          $tieneVto  = !empty($ubicacion->fecha_vto);
        @endphp

        <div class="row">
          <div class="col-md-4 mb-2">
            <div class="text-muted small">Situación</div>
            <div class="font-weight-bold">{{ ucfirst($ubicacion->situacion) }}</div>
          </div>

          @if($tieneAlta)
            <div class="col-md-4 mb-2">
              <div class="text-muted small">Fecha de alta</div>
              <div class="font-weight-bold">
                {{ \Illuminate\Support\Carbon::parse($ubicacion->fecha_alta)->format('d/m/Y') }}
              </div>
            </div>
          @endif

          @if($tieneBaja)
            <div class="col-md-4 mb-2">
              <div class="text-muted small">Fecha de baja</div>
              <div class="font-weight-bold">
                {{ \Illuminate\Support\Carbon::parse($ubicacion->fecha_baja)->format('d/m/Y') }}
              </div>
            </div>
          @endif
        </div>

        {{-- Vencimiento solo si existe --}}
        @if($tieneVto)
          <div class="row">
            <div class="col-md-4 mb-2">
              <div class="text-muted small">Vencimiento</div>
              @php
                $vto = \Illuminate\Support\Carbon::parse($ubicacion->fecha_vto);
                $badgeVto = $vto->isPast()
                  ? 'danger'
                  : ($vto->diffInDays(now()) <= 30 ? 'warning' : 'success');
              @endphp
              <div class="font-weight-bold">
                <span class="badge badge-{{ $badgeVto }}" style="font-size:95%">
                  {{ $vto->format('d/m/Y') }}
                </span>
              </div>
            </div>
          </div>
        @endif
      </div>
    </div>

    {{-- Domicilios y otros --}}
    <div class="card mb-3">
      <div class="card-header bg-light"><strong>Domicilios y Otros</strong></div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-2">
            <div class="text-muted small">Domicilio del comercio</div>
            <div class="font-weight-bold">{{ $ubicacion->domicilio_comercio ?: '—' }}</div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-4 mb-2">
            <div class="text-muted small">Habilitado</div>
            @if ($ubicacion->habilitado)
              <span class="badge badge-success">Sí</span>
            @else
              <span class="badge badge-danger">No</span>
            @endif
          </div>
          <div class="col-md-4 mb-2">
            <div class="text-muted small">Observaciones</div>
            <div class="font-weight-bold">{{ $ubicacion->observaciones ?: '—' }}</div>
          </div>
        </div>
      </div>
    </div>

    {{-- Actas e inspecciones --}}
    @php
      $movs = $ubicacion->movimientos()->where('tipo','acta')->latest()->get();
      $totalMovs = $movs->count();
    @endphp

    <div class="card mb-4" x-data="{openMovs:true}">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <strong class="mr-3">Actas</strong>
          <span class="badge badge-info">{{ $totalMovs }}</span>
        </div>
        <div class="d-flex align-items-center">
          <button class="btn btn-sm btn-outline-secondary" type="button" @click="openMovs = !openMovs">
            <span class="mr-1" x-text="openMovs ? 'ocultar' : 'ver'"></span>
            <i :class="openMovs ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"></i>
          </button>
        </div>
      </div>

      <div x-show="openMovs" x-collapse>
        <div class="card-body p-2">
          @if($movs->isEmpty())
            <div class="text-center text-muted py-3">Sin movimientos aún.</div>
          @else
            <div class="table-responsive">
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
                  @foreach($movs as $mov)
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
                      <td class="text-sm">
                        @php
                          $base = $mov->fecha ?? $mov->created_at;
                          $dt = \Illuminate\Support\Carbon::parse($base);
                          if ($dt->format('H:i') === '00:00') $dt = \Illuminate\Support\Carbon::parse($mov->created_at);
                        @endphp
                        {{ $dt->format('d/m/Y H:i') }}
                      </td>
                      <td class="text-center">
                        <button type="button"
                                class="btn btn-sm btn-outline-danger"
                                wire:click.prevent="eliminarMovimiento({{ $mov->id }})">
                          Borrar
                        </button>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>

      @include('livewire.comercio.form')
    </div>

    {{-- Documentación --}}
    <div class="card mb-4" x-data="{open:false}">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <strong class="mr-3">Documentación presentada</strong>
          <span class="badge badge-primary">{{ $docsOK }}/{{ $docsTotal }}</span>
        </div>
        <button class="btn btn-sm btn-outline-secondary" type="button" @click="open=!open">
          <span class="mr-1 " x-text="open ? 'ocultar' : 'ver'"></span>
          <i :class="open ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"></i>
        </button>
      </div>

      <div x-show="open" x-collapse x-cloak>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <h6 class="mb-2">Generales</h6>
              @foreach($labelsGenerales as $key => $label)
                @php $ok = !empty($docs[$key] ?? false); @endphp
                <div class="mb-2 p-2 rounded border {{ $ok ? 'bg-success text-white border-success' : 'bg-light text-muted border-secondary' }}">
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="small">{{ $label }}</span>
                    <span class="badge {{ $ok ? 'badge-light' : 'badge-secondary' }}">{{ $ok ? 'Sí' : 'No' }}</span>
                  </div>
                </div>
              @endforeach
            </div>

            @if($esJuridica)
              <div class="col-md-6 mb-3">
                <h6 class="mb-2">Personas Jurídicas</h6>
                @foreach($labelsJuridicas as $key => $label)
                  @php $ok = !empty($docs[$key] ?? false); @endphp
                  <div class="mb-2 p-2 rounded border {{ $ok ? 'bg-success text-white border-success' : 'bg-light text-muted border-secondary' }}">
                    <div class="d-flex justify-content-between align-items-center">
                      <span class="small">{{ $label }}</span>
                      <span class="badge {{ $ok ? 'badge-light' : 'badge-secondary' }}">{{ $ok ? 'Sí' : 'No' }}</span>
                    </div>
                  </div>
                @endforeach
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
