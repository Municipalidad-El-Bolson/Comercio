<div class="container">
  {{-- HEADER / HERO --}}
  @php
    $esJuridica   = ($ubicacion->persona_tipo ?? 'fisica') === 'juridica';
    $titularBase  = trim(($ubicacion->apellido ?? '').' '.($ubicacion->nombres ?? ''));
    $titular      = $ubicacion->razon_social ?: ($titularBase !== '' ? $titularBase : '—');

    // === Estado / Cambio (usa estado_base + estado_label) ===
    $estadoBase  = $ubicacion->estado_base ?: null;               // '021','032','baja','baja_oficio','sin_efecto'...
    $estadoLabel = trim((string)($ubicacion->estado_label ?? '')); // ej: "021- Cambio de domicilio"

    // Fallbacks si no hay migrado aún
    if (!$estadoBase) {
      $raw = strtolower((string)($ubicacion->estado ?? ''));
      $estadoBase = match ($raw) {
        'entramite','en trámite','en tramite','021','alta','vigente' => '021',
        'irregular','032'                                            => '032',
        '040'                                                        => '040',
        'baja'                                                       => 'baja',
        'baja_oficio','baja de oficio'                               => 'baja_oficio',
        'sin_efecto','expediente sin efecto'                         => 'sin_efecto',
        default                                                      => '021',
      };
    }
    if ($estadoLabel === '') {
      $estadoLabel = match ($estadoBase) {
        '021'         => '021',
        '032'         => '032',
        '040'         => '040',
        'baja'        => 'Baja',
        'baja_oficio' => 'Baja de Oficio',
        'sin_efecto'  => 'Expediente sin Efecto',
        default       => strtoupper($estadoBase),
      };
    }

    // Parseo "BASE - Cambio"
    $cambioTxt = 'Ninguno';
    if (preg_match('/^\s*(021|032)\s*-\s*(.+)$/ui', $estadoLabel, $m)) {
      $estadoLabel = trim($m[1]);    
      $cambioTxt   = trim($m[2]);    
    }

    // Clases de badges
    $estadoClass = match ($estadoBase) {
      '021'                    => 'badge-success',
      '032'                    => 'badge-warning',
      '040'                    => 'badge-info',
      'baja','baja_oficio'     => 'badge-danger',
      'sin_efecto'             => 'badge-dark',
      default                  => 'badge-light',
    };
    $cambioClass = ($cambioTxt !== 'Ninguno') ? 'badge-info' : 'badge-light';

    // Disp/Hab
    $disp = optional($ubicacion->disposiciones->sortByDesc(fn($d) => $d->fecha ?? $d->created_at)->first());
    $hab  = optional($ubicacion->habilitaciones->sortByDesc(fn($h) => $h->fecha ?? $h->created_at)->first());
    $nroDisp = trim((string)($disp->numero ?? ''));
    $nroHab  = trim((string)($hab->numero  ?? ''));

    // Teléfonos / anexos
    $tels   = $ubicacion->telefonos->pluck('telefono')->filter()->implode(' / ');
    $anexos = $ubicacion->rubros
                ->when($ubicacion->rubro_id, fn($c) => $c->where('id', '!=', $ubicacion->rubro_id))
                ->pluck('subrubro')->filter()->values()->all();

    // Vencimiento
    $vto      = $ubicacion->fecha_vto ? \Illuminate\Support\Carbon::parse($ubicacion->fecha_vto) : null;
    $vtoBadge = $vto ? ($vto->isPast() ? 'danger' : ($vto->diffInDays(now()) <= 30 ? 'warning' : 'success')) : null;

    $estadoVisual = match ($estadoBase) {
        '021' => '021/90',
        '032' => '032/01',
        '040' => '040/25',
        default => $estadoLabel, // Baja, Baja de Oficio, etc.
    };
  @endphp

  <div class="container-fluid mt-3">
  <div class="card mb-4 border-secondary">
    <div class="card-body">

      <div class="d-flex align-items-start justify-content-between">
        <div>
          <h1 class="m-0 titulo-comercio">
            {{ $ubicacion->nombre_comercial ?: '—' }}
            @if($ubicacion->situacion === 'clausurado')
              <span class="badge badge-danger align-middle ml-2">Clausurado</span>
            @endif
          </h1>

          <div class="text-muted">
            <i class="far fa-id-card mr-1"></i>{{ $titular }}
            <span class="mx-2">·</span>
            <i class="fas fa-user-tag mr-1"></i>{{ ucfirst($ubicacion->persona_tipo ?? '—') }}
          </div>

          <div class="mt-2">
            <span class="badge {{ $estadoClass }} mr-1">
              <i class="fas fa-clipboard-check mr-1"></i>{{ $estadoVisual }}
            </span>

            <span class="badge {{ $cambioClass }} mr-1">
              <i class="fas fa-exchange-alt mr-1"></i>{{ $cambioTxt }}
            </span>

            @if($vto)
              <span class="badge badge-{{ $vtoBadge }} mr-1">
                <i class="far fa-clock mr-1"></i>Vto: {{ $vto->format('d/m/Y') }}
              </span>
            @endif

            @if(!empty($ubicacion->tipo_hab))
              <span class="badge badge-light">
                <i class="fas fa-certificate mr-1"></i>
                {{ $ubicacion->tipo_hab === 'definitiva' ? 'Definitiva' : 'Provisoria' }}
              </span>
            @endif
          </div>
        </div>

        <!-- Botonera -->
        <div class="btn-group">
          <a wire:navigate href="{{ url()->previous() }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Volver
          </a>

          @isset($ubicacion->id)
            <a href="#" wire:click.prevent="editaComercio({{ $ubicacion->id }})" class="btn btn-primary btn-sm">
              <i class="fa fa-edit mr-1"></i> Editar
            </a>
          @endisset

          @can('manage-ubicaciones')
            <button type="button" class="btn btn-danger btn-sm"
              x-on:click.prevent="if (confirm('¿Eliminar definitivamente este comercio? Esta acción no se puede deshacer.')) { $wire.deleteComercio() }">
              <i class="fa fa-trash mr-1"></i> Eliminar
            </button>
          @endcan
        </div>
      </div>

    </div>
  </div>
</div>


  <div class="container-fluid mt-3">

    {{-- TIMELINE (si corresponde) --}}
    @if($ubicacion->habilita_seguimiento)
      <livewire:comercio.timeline :ubicacion-id="$ubicacion->id" :created-at="$ubicacion->created_at" />
    @endif

    {{-- GRID PRINCIPAL --}}
    <div class="row">

      {{-- Identificación --}}
      <div class="col-lg-6">
        <div class="card mb-3 {{ $ubicacion->situacion==='clausurado' ? 'border-danger' : 'border-secondary' }}">
          <div class="card-header bg-light"><strong><i class="far fa-id-badge mr-1"></i>Identificación</strong></div>
          <div class="card-body">
            <div class="row">
              <div class="col-sm-6 mb-2">
                <div class="text-muted small">DNI / CUIT</div>
                <div class="font-weight-bold">{{ $ubicacion->dni_cuit ?: '—' }}</div>
              </div>
              <div class="col-sm-6 mb-2">
                <div class="text-muted small">{{ $esJuridica ? 'Razón social' : 'Apellido y Nombres' }}</div>
                <div class="font-weight-bold">{{ $titular }}</div>
              </div>
            </div>

            <div class="row">
              <div class="col-sm-6 mb-2">
                <div class="text-muted small">Correo</div>
                <div class="font-weight-bold">{{ $ubicacion->correo ?: '—' }}</div>
              </div>
              <div class="col-sm-6 mb-2">
                <div class="text-muted small">Teléfono(s)</div>
                <div class="font-weight-bold">{{ $tels !== '' ? $tels : ( $ubicacion->telefono ?: '—') }}</div>
              </div>
            </div>

            <div class="row">
              <div class="col-sm-12 mb-2">
                <div class="text-muted small">Nombre de Fantasía</div>
                <div class="font-weight-bold">{{ $ubicacion->nombre_comercial ?: '—' }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Rubro y estado --}}
      <div class="col-lg-6">
        <div class="card mb-3 {{ $ubicacion->situacion==='clausurado' ? 'border-danger' : 'border-secondary' }}">
          <div class="card-header bg-light"><strong><i class="fas fa-tags mr-1"></i>Rubro y Estado</strong></div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6 mb-2">
                <div class="text-muted small">Rubro (principal)</div>
                <div class="font-weight-bold">{{ optional($ubicacion->rubro)->subrubro ?: '—' }}</div>
              </div>
              @php
                use Illuminate\Support\Str;

                // Tomo rubro_general desde ubicacion o desde la relación rubro (por si ahí está)
                $rg = (string) ($ubicacion->rubro_general ?? optional($ubicacion->rubro)->rubro_general ?? '');
                $rgN = Str::of($rg)->lower()->trim()->ascii()->toString();

                // Tomo el nombre del rubro desde la relación (subrubro/nombre) y normalizo
                $rubroTxt = (string) (optional($ubicacion->rubro)->subrubro ?? optional($ubicacion->rubro)->nombre ?? '');
                $rubroN = Str::of($rubroTxt)->lower()->trim()->ascii()->toString();

                $esAlojTur = $rgN === 'alojamiento de alquiler turistico';
                $esCamping = Str::contains($rubroN, 'camping'); // por si viene "Camping ..." o similar
              @endphp

              {{-- Alojamiento turístico --}}
              @if($esAlojTur)

                {{-- Caso CAMPING --}}
                @if($esCamping)
                  <hr>
                  <div class="row">
                    <div class="col-md-4 mb-2">
                      <div class="text-muted small">Fogones</div>
                      <div class="font-weight-bold">{{ $ubicacion->camping_fogones ?? '—' }}</div>
                    </div>

                    <div class="col-md-4 mb-2">
                      <div class="text-muted small">Dormis</div>
                      <div class="font-weight-bold">{{ $ubicacion->camping_dormis ?? '—' }}</div>
                    </div>

                    <div class="col-md-4 mb-2">
                      <div class="text-muted small">Otros Servicios</div>
                      <div class="font-weight-bold">{{ $ubicacion->camping_otros_servicios ?? '—' }}</div>
                    </div>
                  </div>

                {{-- Caso NO camping --}}
                @else
                  <hr>
                  <div class="row">
                    <div class="col-md-6 mb-2">
                      <div class="text-muted small">Unidades de Alojamiento</div>
                      <div class="font-weight-bold">{{ $ubicacion->alojamiento_unidades ?? '—' }}</div>
                    </div>

                    <div class="col-md-6 mb-2">
                      <div class="text-muted small">Plazas Totales</div>
                      <div class="font-weight-bold">{{ $ubicacion->alojamiento_plazas ?? '—' }}</div>
                    </div>
                  </div>
                @endif

              @endif

        <div class="col-md-6 mb-2">
          <div class="text-muted small">Rubros anexos</div>
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
            </div>

            <div class="row">
              <div class="col-md-4 mb-2">
                <div class="text-muted small">Estado</div>
                <span class="badge {{ $estadoClass }} mr-1">{{ $estadoVisual  }}</span>
              </div>
              <div class="col-md-4 mb-2">
                <div class="text-muted small">Cambio</div>
                <span class="badge {{ $cambioChip['class'] }}">{{ $cambioChip['label'] }}</span>
              </div>
              <div class="col-md-4 mb-2">
                <div class="text-muted small">Situación</div>
                <div class="font-weight-bold">{{ $ubicacion->situacion ? ucfirst($ubicacion->situacion) : '—' }}</div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-4 mb-2">
                <div class="text-muted small">Tipo de habilitación</div>
                <div class="font-weight-bold">{{ $ubicacion->tipo_hab === 'definitiva' ? 'Definitiva' : 'Provisoria' }}</div>
              </div>
              @if($ubicacion->fecha_alta)
                <div class="col-md-4 mb-2">
                  <div class="text-muted small">Fecha de alta</div>
                  <div class="font-weight-bold">{{ \Illuminate\Support\Carbon::parse($ubicacion->fecha_alta)->format('d/m/Y') }}</div>
                </div>
              @endif
              @if($ubicacion->fecha_baja)
                <div class="col-md-4 mb-2">
                  <div class="text-muted small">Fecha de baja</div>
                  <div class="font-weight-bold">{{ \Illuminate\Support\Carbon::parse($ubicacion->fecha_baja)->format('d/m/Y') }}</div>
                </div>
              @endif
              @if($vto)
                <div class="col-md-4 mb-2">
                  <div class="text-muted small">Vencimiento</div>
                  <span class="badge badge-{{ $vtoBadge }}">{{ $vto->format('d/m/Y') }}</span>
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>

      {{-- Domicilios, mapa y varios --}}
      <div class="col-lg-6">
        <div class="card mb-3 border-secondary">
          <div class="card-header bg-light"><strong><i class="fas fa-map-marker-alt mr-1"></i>Domicilio y Ubicación</strong></div>
          <div class="card-body">
            <div class="row">
              <div class="col-sm-8 mb-2">
                <div class="text-muted small">Domicilio del comercio</div>
                <div class="font-weight-bold">{{ $ubicacion->domicilio_comercio ?: '—' }}</div>
              </div>
              <div class="col-sm-4 mb-2">
                <div class="text-muted small">Barrio</div>
                <div class="font-weight-bold">{{ $ubicacion->barrio ?: '—' }}</div>
              </div>
              <div class="col-sm-4 mb-2">
                <div class="text-muted small">Nomenclatura</div>
                <div class="font-weight-bold">{{ $ubicacion->nomenclatura ?: '—' }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Económicos / Observaciones --}}
      <div class="col-lg-6">
        <div class="card mb-3 border-secondary">
          <div class="card-header bg-light">
            <strong><i class="fas fa-file-invoice mr-1"></i>Disposición / Habilitación</strong>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-sm-6 mb-2">
                <div class="text-muted small">N° de disposición</div>
                <div class="font-weight-bold">{{ $nroDisp !== '' ? $nroDisp : '—' }}</div>
              </div>
              <div class="col-sm-6 mb-2">
                <div class="text-muted small">N° de habilitación comercial</div>
                <div class="font-weight-bold">{{ $nroHab !== '' ? $nroHab : '—' }}</div>
              </div>
            </div>

            {{-- Si querés conservar Observaciones abajo, lo podés dejar --}}
            <hr class="my-2">
            <div class="row">
              <div class="col-sm-12 mb-2">
                <div class="text-muted small">Observaciones</div>
                <div class="font-weight-bold">{{ $ubicacion->observaciones ?: '—' }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div> {{-- row --}}

    <div class="card mb-4 border-secondary" x-data="{open:false}">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
      <strong class="mr-2">
        <i class="far fa-folder-open mr-1"></i>Historial de estado
      </strong>

      <button class="btn btn-sm btn-outline-secondary d-flex align-items-center"
              type="button"
              @click="open=!open">
        <span class="mr-1" x-text="open ? 'Ocultar' : 'Ver'"></span>
        <i :class="open ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"></i>
      </button>
    </div>


      <div x-show="open" x-collapse x-cloak>
        <div class="card-body">
          <table class="table table-sm table-striped">
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Estado</th>
                <th>Alta</th>
                <th>Baja</th>
                <th>Vto</th>
                <th>Usuario</th>
              </tr>
            </thead>
            <tbody>
              @forelse($ubicacion->estadosHistorial as $h)
                <tr>
                  <td>{{ $h->created_at?->format('d/m/Y H:i') }}</td>
                  <td>{{ $h->estado_label }}</td>
                  <td>{{ $h->fecha_alta ? \Carbon\Carbon::parse($h->fecha_alta)->format('d/m/Y') : '' }}</td>
                  <td>{{ $h->fecha_baja ? \Carbon\Carbon::parse($h->fecha_baja)->format('d/m/Y') : '' }}</td>
                  <td>{{ $h->fecha_vto  ? \Carbon\Carbon::parse($h->fecha_vto )->format('d/m/Y') : '' }}</td>
                  <td>{{ optional(\App\Models\User::find($h->user_id))->name }}</td>
                </tr>
              @empty
                <tr><td colspan="6" class="text-muted">Sin movimientos.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- ACTAS --}}
    @php
      $movs = $ubicacion->movimientos()->latest()->get();
    @endphp
    <div class="card mb-4 border-secondary" x-data="{openMovs:false}">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <strong class="mr-2"><i class="far fa-clipboard mr-1"></i>Actas</strong>
          <span class="badge badge-info">{{ $movs->count() }}</span>
        </div>
        <button class="btn btn-sm btn-outline-secondary" type="button" @click="openMovs = !openMovs">
          <span class="mr-1" x-text="openMovs ? 'ocultar' : 'ver'"></span>
          <i :class="openMovs ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"></i>
        </button>
      </div>

      <div x-show="openMovs" x-collapse x-cloak>
        <div class="card-body p-2">
          @if($movs->isEmpty())
            <div class="text-center text-muted py-3">Sin movimientos aún.</div>
          @else
            <div class="table-responsive">
              <table class="table table-sm table-bordered mb-0">
                <thead class="thead-light">
                  <tr>
                    <th>Título</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Descripción</th>
                    <th>Archivo</th>
                    <th>Fecha</th>
                    <th class="text-center">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($movs as $mov)
                    @php
                      $path = ltrim(preg_replace('#^storage/#i', '', (string)($mov->archivo ?? '')), '/');
                      $disk = \Illuminate\Support\Facades\Storage::disk('public');
                      $ok   = $path && $disk->exists($path);
                      $url  = $ok ? route('files.show', ['path' => $path]) : null;
                      $isImg= $ok && preg_match('/\.(jpe?g|png|gif|webp|bmp)$/i', $path);
                      $fecha = \Illuminate\Support\Carbon::parse($mov->fecha ?? $mov->created_at)->format('d/m/Y H:i');
                    @endphp
                    <tr>
                      <td class="text-sm">{{ $mov->titulo ?? '—' }}</td>
                      <td class="text-sm">{{ $mov->tipo ?? '—' }}</td>
                      <td class="text-sm">{{ $mov->estado ?? '—' }}</td>
                      <td class="text-sm">{{ $mov->descripcion ?? '—' }}</td>
                      <td class="text-sm">
                        @if($ok && $url)
                          @if($isImg)
                            <a href="{{ $url }}" target="_blank"><img src="{{ $url }}" style="max-width:80px;max-height:60px;object-fit:cover;"></a>
                          @else
                            <a href="{{ $url }}" target="_blank">Ver</a>
                          @endif
                        @else
                          —
                        @endif
                      </td>
                      <td class="text-sm">{{ $fecha }}</td>
                      <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger"
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
    </div>

    {{-- DOCUMENTACIÓN --}}
    <div class="card mb-4 border-secondary" x-data="{open:false}">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <strong class="mr-3"><i class="far fa-folder-open mr-1"></i>Documentación presentada</strong>
          <span class="badge badge-primary">{{ $docsOK }}/{{ $docsTotal }}</span>
        </div>
        <button class="btn btn-sm btn-outline-secondary" type="button" @click="open=!open">
          <span class="mr-1" x-text="open ? 'ocultar' : 'ver'"></span>
          <i :class="open ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"></i>
        </button>
      </div>

      <div x-show="open" x-collapse x-cloak>
        <div class="card-body">
          @if(empty($schema['items']))
            <div class="alert alert-info mb-3">
              Para el estado <strong>{{ strtoupper($estadoChip['label'] ?? '-') }}</strong> no se requiere documentación.
            </div>
          @else
            <div class="row">
              @foreach($schema['items'] as $it)
                @php $ok = !empty($docs[$it['key']] ?? false); @endphp
                <div class="col-md-6 mb-2">
                  <div class="p-2 rounded border d-flex justify-content-between align-items-center
                              {{ $ok ? 'bg-success text-white border-success' : 'bg-light text-muted border-secondary' }}">
                    <span class="small">{{ $it['label'] }}</span>
                    <span class="badge {{ $ok ? 'badge-light' : 'badge-secondary' }}">{{ $ok ? 'Sí' : 'No' }}</span>
                  </div>
                </div>
              @endforeach
            </div>
          @endif

          {{-- Uso de inmueble --}}
          @if(data_get($schema,'uso_inmueble.show'))
            <hr>
            @php
              $usoChk  = (bool)($docs[data_get($schema,'uso_inmueble.checkboxKey')] ?? false);
              $tipoSel = $docs['doc_uso_inmueble_tipo'] ?? null;
              $opts    = data_get($schema,'uso_inmueble.options',[]);
              if (!$tipoSel) {
                foreach ([
                  'doc_uso_boleto' => 'boleto',
                  'doc_uso_contrato' => 'contrato',
                  'doc_uso_comodato' => 'comodato',
                  'doc_uso_titulo' => 'titulo',
                  'doc_uso_cert_ocupacion' => 'cert_ocupacion',
                ] as $flag => $val) {
                  if (!empty($docs[$flag])) { $tipoSel = $val; break; }
                }
              }
            @endphp

            <div class="row">
              <div class="col-md-4 mb-2">
                <div class="p-2 rounded border {{ $usoChk ? 'bg-success text-white border-success' : 'bg-light text-muted border-secondary' }}">
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="small">Presenta comprobante</span>
                    <span class="badge {{ $usoChk ? 'badge-light' : 'badge-secondary' }}">{{ $usoChk ? 'Sí' : 'No' }}</span>
                  </div>
                </div>
              </div>
              <div class="col-md-8 mb-2">
                <div class="p-2 rounded border bg-light d-flex justify-content-between align-items-center">
                  <span class="small">Tipo</span>
                  <strong class="text-nowrap ml-2">{{ $tipoSel && isset($opts[$tipoSel]) ? $opts[$tipoSel] : '—' }}</strong>
                </div>
              </div>
            </div>
          @endif
        </div>
      </div>
    </div>

  </div> {{-- /container-fluid --}}
  @include('livewire.comercio.form')
</div>

@push('styles')
<style>

  /* ---------- General ---------- */
  .card {
    border-radius: 0.7rem !important;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #e2e2e2 !important;
  }

  .card-header {
    font-weight: 600;
    font-size: 0.95rem;
    background: #f7f9fb !important;
    border-bottom: 1px solid #e5e5e5 !important;
  }

  .card-body {
    background: #ffffff;
    padding-top: 1.15rem !important;
  }

  .titulo-comercio {
    font-size: 1.9rem !important;
    font-weight: 800 !important;
    letter-spacing: -0.5px;
  }

  /* ---------- Etiquetas / Categorías ---------- */
  .badge {
    padding: 0.45em 0.65em !important;
    font-size: 0.75rem !important;
    font-weight: 600 !important;
    border-radius: 0.35rem !important;
  }

  .badge-light { 
    background: #f2f2f2 !important; 
    color: #555 !important; 
  }

  .badge-success { background-color: #2ecc71 !important; }
  .badge-info    { background-color: #3498db !important; }
  .badge-warning { background-color: #f1c40f !important; color:#333 !important; }
  .badge-danger  { background-color: #e74c3c !important; }

  /* ---------- Títulos pequeños ---------- */
  .text-muted.small {
    font-size: 0.72rem !important;
    letter-spacing: 0.3px;
    text-transform: uppercase;
  }

  .font-weight-bold {
    font-size: 0.92rem;
  }

  /* ---------- Encabezado general ---------- */
  .content-header {
    border-bottom: 1px solid #e5e5e5;
    background: linear-gradient(to right, #ffffff, #fafafa);
    padding-bottom: 1rem;
    padding-top: 0.5rem;
  }

  /* ---------- Botonera derecha ---------- */
  .btn-group .btn {
    border-radius: 0.4rem !important;
    font-size: 0.78rem;
  }

  .btn-primary {
    background: #4a6cf7 !important;
    border-color: #4a6cf7 !important;
  }

  .btn-danger {
    background: #e74c3c !important;
    border-color: #e74c3c !important;
  }

  .btn-secondary {
    background: #bdc3c7 !important;
    border-color: #bdc3c7 !important;
  }

  /* ---------- Separadores ---------- */
  hr.my-2 {
    border-top: 1px solid #ddd !important;
  }

  /* ---------- Tablas ---------- */
  table.table {
    border-radius: 0.5rem !important;
    overflow: hidden;
  }

  .table thead th {
    background: #f7f9fb !important;
    font-weight: 600 !important;
  }

  .table tbody tr td {
    font-size: 0.82rem !important;
  }

  /* ---------- Badges de documentación ---------- */
  .docs-box {
    transition: 0.2s;
  }

  .docs-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 6px rgba(0,0,0,0.12);
  }

</style>
@endpush


