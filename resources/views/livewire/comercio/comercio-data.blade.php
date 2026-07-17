<div class="commerce-profile container-fluid px-2 px-md-3 px-xl-4 pb-4">
{{-- HEADER / HERO --}}
@php
  $esJuridica  = ($ubicacion->getRawOriginal('persona_tipo') ?? $ubicacion->persona_tipo ?? 'fisica') === 'juridica';

  $apellido    = (string)($ubicacion->getRawOriginal('apellido') ?? $ubicacion->apellido ?? '');
  $nombres     = (string)($ubicacion->getRawOriginal('nombres') ?? $ubicacion->nombres ?? '');
  $razonSocial = (string)($ubicacion->getRawOriginal('razon_social') ?? $ubicacion->razon_social ?? '');

  $titularBase = trim($apellido.' '.$nombres);
  $titular     = $razonSocial !== '' ? $razonSocial : ($titularBase !== '' ? $titularBase : '—');

  // ====== ESTADO CRUDO DESDE BD (sin accessors) ======
  $estadoBaseRaw  = $ubicacion->getRawOriginal('estado_base');
  $estadoLabelRaw = $ubicacion->getRawOriginal('estado_label');
  $estadoRaw      = $ubicacion->getRawOriginal('estado');

  $estadoBase  = $estadoBaseRaw ? trim((string)$estadoBaseRaw) : null;
  $estadoLabel = trim((string)($estadoLabelRaw ?? ''));

  // Fallback si no hay estado_base migrado
  if (!$estadoBase) {
    $raw = \Illuminate\Support\Str::of((string)$estadoRaw)->lower()->trim()->toString();
    $estadoBase = match ($raw) {
      'entramite','en trámite','en tramite','021','alta','vigente' => '021',
      'irregular','032'                                            => '032',
      '040','040/25'                                               => '040',
      'baja'                                                       => 'baja',
      'baja_oficio','baja de oficio'                               => 'baja_oficio',
      'sin_efecto','expediente sin efecto','exp_sin_efecto'         => 'sin_efecto',
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
      default       => strtoupper((string)$estadoBase),
    };
  }

  // Parseo "BASE - Cambio" (solo texto)
  $cambioTxt = 'Ninguno';
  if (preg_match('/^\s*(021|032|040)\s*-\s*(.+)$/ui', $estadoLabel, $m)) {
    $estadoLabel = trim($m[1]);
    $cambioTxt   = trim($m[2]);
  }

  $estadoClass = match ($estadoBase) {
    '021'                => 'badge-success',
    '032'                => 'badge-warning',
    '040'                => 'badge-info',
    'baja','baja_oficio' => 'badge-danger',
    'sin_efecto'         => 'badge-dark',
    default              => 'badge-light',
  };

  $cambioClass = ($cambioTxt !== 'Ninguno') ? 'badge-info' : 'badge-light';

  // VTO (solo visual, NO toca estado)
  $fechaVtoRaw = $ubicacion->getRawOriginal('fecha_vto');
  $vto         = $fechaVtoRaw ? \Illuminate\Support\Carbon::parse($fechaVtoRaw) : null;
  $vtoBadge    = $vto ? ($vto->isPast() ? 'danger' : ($vto->diffInDays(now()) <= 30 ? 'warning' : 'success')) : null;

  $estadoVisual = match ($estadoBase) {
    '021' => '021/90',
    '032' => '032/01',
    '040' => '040/25',
    default => $estadoLabel,
  };
  $tels = $ubicacion->telefonos?->pluck('telefono')->filter()->implode(' / ') ?? '';
  $disp = $ubicacion->disposiciones?->sortByDesc(fn($d) => $d->fecha ?? $d->created_at)->first();

  $hab = $ubicacion->habilitaciones?->sortByDesc(fn($h) => $h->fecha ?? $h->created_at)->first();

  $nroDisp = trim((string)($disp->numero ?? ''));
  $nroHab  = trim((string)($hab->numero  ?? ''));

  $anexos = $ubicacion->rubros?->when($ubicacion->rubro_id, fn($c) => $c->where('id','!=',$ubicacion->rubro_id))->pluck('subrubro')->filter()->values()->all() ?? [];
@endphp


  <div class="container-fluid mt-3">
  <div class="card commerce-hero-card mb-4 border-secondary">
    <div class="card-body">

      <div class="commerce-hero-layout">
        <div class="commerce-identity">
          <h1 class="m-0 titulo-comercio">
            {{ $ubicacion->nombre_comercial ?: '—' }}
            @if($ubicacion->situacion === 'clausurado')
              <span class="badge badge-danger align-middle ml-2">Clausurado</span>
            @endif
            @if($ubicacion->baja_temporaria)
              <span class="badge badge-secondary align-middle ml-2">Baja temporaria</span>
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
        <div class="commerce-actions" role="group" aria-label="Acciones del comercio">
          <a href="{{ route('ubicaciones') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Volver
          </a>

          @if($this->contactoWhatsappUrl)
            <button type="button" class="btn btn-success btn-sm"
                    wire:click="abrirComunicacion"
                    wire:loading.attr="disabled" wire:target="abrirComunicacion">
              <i class="fab fa-whatsapp mr-1"></i>
              <span wire:loading.remove wire:target="abrirComunicacion">WhatsApp</span>
              <span wire:loading wire:target="abrirComunicacion">Abriendo…</span>
            </button>
          @else
            <button type="button" class="btn btn-success btn-sm" disabled title="No hay teléfono cargado">
              <i class="fab fa-whatsapp mr-1"></i> WhatsApp
            </button>
          @endif

          @if($this->contactoEmailUrl)
            <a href="{{ $this->contactoEmailUrl }}" class="btn btn-outline-primary btn-sm">
              <i class="far fa-envelope mr-1"></i> Email
            </a>
          @else
            <button type="button" class="btn btn-outline-primary btn-sm" disabled title="No hay correo cargado">
              <i class="far fa-envelope mr-1"></i> Email
            </button>
          @endif

          @can('manage-ubicaciones')
          @isset($ubicacion->id)
            <button type="button" wire:click="editaComercio({{ $ubicacion->id }})"
                    wire:loading.attr="disabled" wire:target="editaComercio"
                    class="btn btn-primary btn-sm">
              <i class="fa fa-edit mr-1"></i> Editar
            </button>
          @endisset
          @endcan

          @can('manage-ubicaciones')
            <button type="button" class="btn btn-danger btn-sm"
              wire:loading.attr="disabled" wire:target="deleteComercio"
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

    {{-- Comunicación asistida --}}
    @if($comunicacionAbierta)
    <div id="comunicacion-asistida" class="card mb-3 border-success" wire:key="comunicacion-asistida">
      <div class="card-header bg-light communication-header">
        <strong><i class="fab fa-whatsapp mr-1 text-success"></i>Comunicación asistida</strong>
        <div class="communication-heading-actions">
          <span class="text-muted small mr-3">WhatsApp abre el mensaje listo para revisar y enviar</span>
          <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="$set('comunicacionAbierta', false)" title="Cerrar comunicación" aria-label="Cerrar comunicación">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-3 mb-2">
            <label class="text-muted small mb-1" for="contactoPlantilla">Asunto / plantilla</label>
            <select id="contactoPlantilla" class="form-control form-control-sm" wire:model.live="contactoPlantilla">
              @foreach($this->contactoPlantillas as $key => $tpl)
                <option value="{{ $key }}">{{ $tpl['label'] }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-3 mb-2">
            <label class="text-muted small mb-1" for="contactoTelefono">Teléfono WhatsApp</label>
            @if(count($this->contactoTelefonos))
              <select id="contactoTelefono" class="form-control form-control-sm" wire:model.live="contactoTelefono">
                @foreach($this->contactoTelefonos as $tel)
                  <option value="{{ $tel }}">{{ $tel }}</option>
                @endforeach
              </select>
            @else
              <input id="contactoTelefono" class="form-control form-control-sm" value="Sin teléfono cargado" disabled>
            @endif
          </div>

          <div class="col-md-6 mb-2">
            <label class="text-muted small mb-1" for="contactoDetalle">Detalle para completar el mensaje</label>
            <input id="contactoDetalle" type="text" class="form-control form-control-sm"
                   wire:model.live.debounce.300ms="contactoDetalle"
                   placeholder="Ej.: falta libre deuda municipal / coordinar inspección el viernes / deuda pendiente">
          </div>
        </div>

        @if($contactoPlantilla === 'personalizado')
          <div class="row">
            <div class="col-md-4 mb-2">
              <label class="text-muted small mb-1" for="contactoAsuntoCustom">Asunto personalizado</label>
              <input id="contactoAsuntoCustom" type="text" class="form-control form-control-sm"
                     wire:model.live.debounce.300ms="contactoAsuntoCustom"
                     placeholder="Ej.: Citación del área de Comercio">
            </div>
            <div class="col-md-8 mb-2">
              <label class="text-muted small mb-1" for="contactoMensajeCustom">Speech / mensaje personalizado</label>
              <textarea id="contactoMensajeCustom" class="form-control form-control-sm" rows="2"
                        wire:model.live.debounce.300ms="contactoMensajeCustom"
                        placeholder="Podés usar: &#123;&#123;titular&#125;&#125;, &#123;&#123;hc&#125;&#125;, &#123;&#123;comercio&#125;&#125;, &#123;&#123;rubro&#125;&#125;, &#123;&#123;vencimiento&#125;&#125;"></textarea>
            </div>
          </div>
        @endif

        <div class="row align-items-end">
          <div class="col-lg-8 mb-2">
            <div class="text-muted small">Vista previa</div>
            <div class="border rounded bg-light p-2 small" style="white-space: pre-line;">{{ $this->contactoMensaje }}</div>
          </div>
          <div class="col-lg-4 mb-2 text-lg-right">
            @if($this->contactoWhatsappUrl)
              <a class="btn btn-success btn-sm mb-1" href="{{ $this->contactoWhatsappUrl }}" target="_blank" rel="noopener">
                <i class="fab fa-whatsapp mr-1"></i>Enviar WhatsApp
              </a>
            @else
              <button class="btn btn-success btn-sm mb-1" disabled title="No hay teléfono cargado">
                <i class="fab fa-whatsapp mr-1"></i>Enviar WhatsApp
              </button>
            @endif

            @if($this->contactoEmailUrl)
              <a class="btn btn-outline-primary btn-sm mb-1" href="{{ $this->contactoEmailUrl }}">
                <i class="far fa-envelope mr-1"></i>Enviar email
              </a>
            @else
              <button class="btn btn-outline-primary btn-sm mb-1" disabled title="No hay correo cargado">
                <i class="far fa-envelope mr-1"></i>Enviar email
              </button>
            @endif
          </div>
        </div>
      </div>
    </div>
    @endif

    {{-- TIMELINE (si corresponde) --}}
    @if($ubicacion->habilita_seguimiento)
      <div wire:ignore wire:key="timeline-shell-{{ $ubicacion->id }}">
        @livewire('comercio.timeline', [
          'ubicacionId' => $ubicacion->id,
          'createdAt' => $ubicacion->created_at,
        ], key('timeline-comercio-'.$ubicacion->id))
      </div>
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

                $esAlojTur = optional($ubicacion->rubro)->esAlojamientoTuristico() ?? false;
                $esCamping = optional($ubicacion->rubro)->esCamping() ?? false;
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
        <i class="far fa-folder-open mr-1"></i>Historial completo
      </strong>

      <div class="d-flex align-items-center">
        <a class="btn btn-sm btn-outline-success mr-1" href="{{ route('comercio.historial.excel', $ubicacion) }}">
          <i class="fas fa-file-excel mr-1"></i>Excel
        </a>
        <a class="btn btn-sm btn-outline-danger mr-2" href="{{ route('comercio.historial.pdf', $ubicacion) }}">
          <i class="fas fa-file-pdf mr-1"></i>PDF
        </a>
        <button class="btn btn-sm btn-outline-secondary d-flex align-items-center"
              type="button"
              @click="open=!open">
          <span class="mr-1" x-text="open ? 'Ocultar' : 'Ver'"></span>
          <i :class="open ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"></i>
        </button>
      </div>
    </div>


      <div x-show="open" x-collapse x-cloak>
        <div class="card-body">
          <table class="table table-sm table-striped">
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Usuario</th>
                <th>Acción y ediciones</th>
              </tr>
            </thead>
            <tbody>
              @forelse($auditoriaComercio as $h)
                <tr>
                  <td>{{ $h->created_at?->format('d/m/Y H:i') }}</td>
                  <td>{{ $h->user?->name ?? '(sistema)' }}</td>
                  <td>
                    <div class="font-weight-bold">{{ $h->message }}</div>
                    @foreach($h->diff_lines as $line)
                      <div class="small text-muted">{{ $line }}</div>
                    @endforeach
                  </td>
                </tr>
              @empty
                <tr><td colspan="3" class="text-muted">Sin ediciones registradas.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- ACTAS --}}
    @php
      $movs = $ubicacion->movimientos()->where('tipo', 'acta')->latest()->get();
    @endphp
    <div class="card mb-4 border-secondary" wire:key="actas-comercio-{{ $ubicacion->id }}">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <strong class="mr-2"><i class="far fa-clipboard mr-1"></i>Actas</strong>
          <span class="badge badge-info">{{ $movs->count() }}</span>
        </div>
        <div>
          <button type="button" class="btn btn-sm btn-primary mr-1"
                  wire:click="mostrarMovimientos({{ $ubicacion->id }})">
            <i class="fas fa-plus mr-1"></i>Nueva acta
          </button>
          <button class="btn btn-sm btn-outline-secondary" type="button" wire:click="$toggle('actasAbiertas')">
            <span class="mr-1">{{ $actasAbiertas ? 'ocultar' : 'ver' }}</span>
            <i class="fas {{ $actasAbiertas ? 'fa-chevron-up' : 'fa-chevron-down' }}"></i>
          </button>
        </div>
      </div>

      <div class="{{ $actasAbiertas ? '' : 'd-none' }}">
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
                    <th>Vencimiento</th>
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
                    <tr wire:key="acta-perfil-{{ $mov->id }}">
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
                      <td class="text-sm">{{ $mov->fecha_vencimiento?->format('d/m/Y') ?? '—' }}</td>
                      <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger"
                                wire:click.prevent="eliminarMovimiento({{ $mov->id }})"
                                wire:loading.attr="disabled"
                                wire:target="eliminarMovimiento({{ $mov->id }})">
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
  <div wire:ignore wire:key="movimiento-modal-shell-{{ $ubicacion->id }}">
    <livewire:comercio.movimiento-modal wire:key="movimiento-modal-comercio-{{ $ubicacion->id }}" />
  </div>
</div>

@push('styles')
<style>

  /* ---------- General ---------- */
  .commerce-profile .card {
    border-radius: 0.7rem !important;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #e2e2e2 !important;
  }

  .commerce-profile .card-header {
    font-weight: 600;
    font-size: 0.95rem;
    background: #f7f9fb !important;
    border-bottom: 1px solid #e5e5e5 !important;
  }

  .commerce-profile .card-body {
    background: #ffffff;
    padding-top: 1.15rem !important;
  }

  .commerce-profile .titulo-comercio {
    font-size: 1.9rem !important;
    font-weight: 800 !important;
    letter-spacing: -0.5px;
  }

  /* ---------- Etiquetas / Categorías ---------- */
  .commerce-profile .badge {
    padding: 0.45em 0.65em !important;
    font-size: 0.75rem !important;
    font-weight: 600 !important;
    border-radius: 0.35rem !important;
  }

  .commerce-profile .badge-light {
    background: #f2f2f2 !important; 
    color: #555 !important; 
  }

  .commerce-profile .badge-success { background-color: #2d9d68 !important; }
  .commerce-profile .badge-info    { background-color: #2d7db3 !important; }
  .commerce-profile .badge-warning { background-color: #f0bf3a !important; color:#302700 !important; }
  .commerce-profile .badge-danger  { background-color: #d84a4a !important; }

  /* ---------- Títulos pequeños ---------- */
  .commerce-profile .text-muted.small {
    font-size: 0.72rem !important;
    letter-spacing: 0.3px;
    text-transform: uppercase;
  }

  .commerce-profile .font-weight-bold {
    font-size: 0.92rem;
  }

  /* ---------- Encabezado general ---------- */
  .commerce-profile .content-header {
    border-bottom: 1px solid #e5e5e5;
    background: linear-gradient(to right, #ffffff, #fafafa);
    padding-bottom: 1rem;
    padding-top: 0.5rem;
  }

  /* ---------- Botonera derecha ---------- */
  .commerce-profile .commerce-actions .btn {
    border-radius: 0.4rem !important;
    font-size: 0.78rem;
  }

  .commerce-profile .btn-primary {
    background: #4a6cf7 !important;
    border-color: #4a6cf7 !important;
  }

  .commerce-profile .btn-danger {
    background: #e74c3c !important;
    border-color: #e74c3c !important;
  }

  .commerce-profile .btn-secondary {
    background: #657383 !important;
    border-color: #657383 !important;
  }

  /* ---------- Separadores ---------- */
  .commerce-profile hr.my-2 {
    border-top: 1px solid #ddd !important;
  }

  /* ---------- Tablas ---------- */
  .commerce-profile table.table {
    border-radius: 0.5rem !important;
    overflow: hidden;
  }

  .commerce-profile .table thead th {
    background: #f7f9fb !important;
    font-weight: 600 !important;
  }

  .commerce-profile .table tbody tr td {
    font-size: 0.82rem !important;
  }

  /* ---------- Badges de documentación ---------- */
  .commerce-profile .docs-box {
    transition: 0.2s;
  }

  .commerce-profile .docs-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 6px rgba(0,0,0,0.12);
  }

  .commerce-profile {
    max-width: 1540px;
    margin: 0 auto;
    color: #253244;
  }

  .commerce-profile .commerce-hero-layout {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1.5rem;
  }

  .commerce-profile .commerce-identity {
    min-width: 0;
    flex: 1 1 auto;
  }

  .commerce-profile .commerce-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: .45rem;
    flex: 0 1 470px;
  }

  .commerce-profile .commerce-actions .btn {
    min-height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 1px 2px rgba(31, 45, 61, .12);
  }

  .commerce-profile .communication-header,
  .commerce-profile .communication-heading-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
  }

  .commerce-profile .card {
    transition: box-shadow .18s ease, transform .18s ease;
  }

  .commerce-profile .card:hover {
    box-shadow: 0 5px 18px rgba(37, 50, 68, .09);
  }

  @media (max-width: 991.98px) {
    .commerce-profile .commerce-hero-layout {
      flex-direction: column;
    }
    .commerce-profile .commerce-actions {
      width: 100%;
      flex-basis: auto;
      justify-content: flex-start;
    }
  }

  @media (max-width: 575.98px) {
    .commerce-profile .titulo-comercio {
      font-size: 1.5rem !important;
    }
    .commerce-profile .commerce-actions .btn {
      flex: 1 1 calc(50% - .45rem);
    }
    .commerce-profile .communication-header,
    .commerce-profile .communication-heading-actions {
      align-items: flex-start;
      flex-direction: column;
    }
    .commerce-profile .communication-heading-actions .text-muted {
      margin-right: 0 !important;
    }
  }

  /* Segunda capa visual: perfil administrativo */
  .commerce-profile {
    --profile-navy: #17385f;
    --profile-blue: #286da8;
    --profile-sky: #eaf4fb;
    --profile-green: #17845f;
    --profile-ink: #20344b;
    --profile-muted: #6f7f91;
    --profile-line: #dce6ef;
    max-width: 1480px;
    color: var(--profile-ink);
    padding-top: .25rem;
  }
  .commerce-profile > .container-fluid { padding-left: 0; padding-right: 0; }
  .commerce-profile .card {
    border: 1px solid var(--profile-line) !important;
    border-radius: 1rem !important;
    box-shadow: 0 5px 18px rgba(30,58,86,.065);
    transition: box-shadow .2s ease, transform .2s ease;
  }
  .commerce-profile .card:hover { box-shadow: 0 10px 28px rgba(30,58,86,.105); }
  .commerce-profile .card-header {
    min-height: 54px;
    padding: .85rem 1.1rem;
    border-bottom: 1px solid var(--profile-line) !important;
    background: linear-gradient(135deg,#f9fbfd,#eef5fa) !important;
    color: var(--profile-navy);
    font-size: .95rem;
    font-weight: 700;
  }
  .commerce-profile .card-header strong > i {
    width: 28px; height: 28px;
    display: inline-flex; align-items: center; justify-content: center;
    margin-right: .45rem !important;
    border-radius: .55rem;
    background: #dcecf7;
    color: var(--profile-blue);
  }
  .commerce-profile .card-body { padding: 1.15rem 1.2rem !important; }
  .commerce-profile .commerce-hero-card {
    position: relative;
    border: 0 !important;
    background: linear-gradient(125deg,#17385f 0%,#245f91 64%,#268b8b 125%);
    box-shadow: 0 16px 35px rgba(23,56,95,.2);
  }
  .commerce-profile .commerce-hero-card::after {
    content: ''; position: absolute; width: 270px; height: 270px;
    right: -85px; top: -150px; border-radius: 50%;
    background: rgba(255,255,255,.075); pointer-events: none;
  }
  .commerce-profile .commerce-hero-card .card-body {
    position: relative; z-index: 1;
    padding: 1.5rem 1.6rem !important;
    background: transparent; color: #fff;
  }
  .commerce-profile .commerce-hero-card .text-muted { color: rgba(255,255,255,.76) !important; }
  .commerce-profile .titulo-comercio {
    margin-bottom: .45rem !important; color: #fff;
    font-size: 2rem !important; line-height: 1.15;
    letter-spacing: -.035em;
  }
  .commerce-profile .badge {
    padding: .48em .72em !important;
    border-radius: 999px !important;
    font-size: .73rem !important;
    font-weight: 700 !important;
  }
  .commerce-profile .commerce-hero-card .badge-light {
    border: 1px solid rgba(255,255,255,.25);
    background: rgba(255,255,255,.13) !important;
    color: #fff !important;
  }
  .commerce-profile .text-muted.small {
    color: var(--profile-muted) !important;
    font-size: .68rem !important; font-weight: 700;
    letter-spacing: .055em; text-transform: uppercase;
  }
  .commerce-profile .font-weight-bold { color: #253d57; font-size: .93rem; line-height: 1.45; }
  .commerce-profile .commerce-actions { gap: .55rem; }
  .commerce-profile .commerce-actions .btn {
    min-height: 38px; padding: .42rem .75rem;
    border-color: rgba(255,255,255,.3) !important;
    border-radius: .65rem !important;
    background: rgba(255,255,255,.11) !important;
    color: #fff !important; font-weight: 700;
    box-shadow: 0 4px 10px rgba(9,31,52,.12);
  }
  .commerce-profile .commerce-actions .btn:hover:not(:disabled) {
    border-color: #fff !important; background: #fff !important;
    color: var(--profile-navy) !important; transform: translateY(-1px);
  }
  .commerce-profile .commerce-actions .btn-success:not(:disabled) {
    border-color: #47cfa1 !important; background: #168763 !important;
  }
  .commerce-profile .commerce-actions .btn-primary {
    border-color: #b8dfff !important; background: #fff !important;
    color: var(--profile-navy) !important;
  }
  .commerce-profile .commerce-actions .btn-danger:not(:disabled) {
    border-color: rgba(255,190,190,.65) !important;
    background: rgba(161,42,51,.72) !important;
  }
  .commerce-profile .commerce-actions .btn:disabled { opacity: .48; box-shadow: none; }
  .commerce-profile .btn:not(.commerce-actions .btn) { border-radius: .58rem; font-weight: 600; }
  .commerce-profile .table-responsive { border: 1px solid var(--profile-line); border-radius: .8rem; }
  .commerce-profile table.table { margin-bottom: 0; }
  .commerce-profile .table thead th {
    padding: .7rem; border-top: 0;
    border-bottom: 1px solid var(--profile-line) !important;
    background: #edf4f9 !important; color: #526a80;
    font-size: .7rem; font-weight: 800 !important;
    letter-spacing: .04em; text-transform: uppercase;
  }
  .commerce-profile .table tbody td {
    padding: .7rem; border-color: #edf1f5;
    color: #31485f; font-size: .82rem !important; vertical-align: middle;
  }
  .commerce-profile .table tbody tr:hover { background: var(--profile-sky); }
  .commerce-profile #comunicacion-asistida {
    border-color: #acdcca !important;
    box-shadow: 0 12px 30px rgba(23,132,95,.12);
  }
  .commerce-profile #comunicacion-asistida .card-header {
    background: linear-gradient(135deg,#edfaf5,#ddf4ea) !important;
    color: #126849;
  }
  @media (max-width: 991.98px) {
    .commerce-profile .commerce-hero-card .card-body { padding: 1.25rem !important; }
  }
  @media (max-width: 575.98px) {
    .commerce-profile .card-header { align-items: flex-start !important; gap: .65rem; flex-wrap: wrap; }
    .commerce-profile .card-body { padding: 1rem !important; }
  }
</style>
@endpush

@push('scripts')
<script>
  document.addEventListener('livewire:init', () => {
    Livewire.on('comunicacion-abierta', () => {
      requestAnimationFrame(() => requestAnimationFrame(() => {
        document.getElementById('comunicacion-asistida')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }));
    });
  });
</script>
@endpush
