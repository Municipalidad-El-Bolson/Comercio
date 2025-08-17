<div class="container">
    <livewire:comercio.timeline :ubicacion-id="$ubicacion->id" />
  <div class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h1 class="m-0">Detalle del Comercio</h1>
        <div class="btn-group">
          <a wire:navigate href="{{ url()->previous() }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Volver
          </a>
          @isset($ubicacion->id)
          <a href="#" wire:click.prevent="$dispatch('editarDesdeDetalle', {{ $ubicacion->id }})" class="btn btn-primary btn-sm">
            <i class="fa fa-edit mr-1"></i> Editar
          </a>
          @endisset
        </div>
      </div>
    </div>
  </div>

  <div class="container-fluid">
    {{-- Identificación --}}
    <div class="card mb-3">
      <div class="card-header bg-light">
        <strong>Identificación</strong>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-3 mb-2">
            <div class="text-muted small">Tipo de persona</div>
            <div class="font-weight-bold">{{ ucfirst($ubicacion->persona_tipo) }}</div>
          </div>
          <div class="col-md-3 mb-2">
            <div class="text-muted small">DNI / CUIT</div>
            <div class="font-weight-bold">{{ $ubicacion->dni_cuit ?: '—' }}</div>
          </div>
          <div class="col-md-3 mb-2">
            <div class="text-muted small">Razón social</div>
            <div class="font-weight-bold">{{ $ubicacion->razon_social ?: '—' }}</div>
          </div>
          <div class="col-md-3 mb-2">
            <div class="text-muted small">Nombre comercial</div>
            <div class="font-weight-bold">{{ $ubicacion->nombre_comercial ?: '—' }}</div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-3 mb-2">
            <div class="text-muted small">Apellido</div>
            <div class="font-weight-bold">{{ $ubicacion->apellido ?: '—' }}</div>
          </div>
          <div class="col-md-3 mb-2">
            <div class="text-muted small">Nombres</div>
            <div class="font-weight-bold">{{ $ubicacion->nombres ?: '—' }}</div>
          </div>
          <div class="col-md-3 mb-2">
            <div class="text-muted small">Correo</div>
            <div class="font-weight-bold">{{ $ubicacion->correo ?: '—' }}</div>
          </div>
          <div class="col-md-3 mb-2">
            <div class="text-muted small">Teléfono</div>
            <div class="font-weight-bold">{{ $ubicacion->telefono ?: '—' }}</div>
          </div>
        </div>
      </div>
    </div>

    {{-- Rubro y estado --}}
    <div class="card mb-3">
      <div class="card-header bg-light">
        <strong>Rubro y Estado</strong>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4 mb-2">
            <div class="text-muted small">Rubro madre</div>
            <div class="font-weight-bold">{{ optional($ubicacion->rubro)->rubro_madre ?: '—' }}</div>
          </div>
          <div class="col-md-4 mb-2">
            <div class="text-muted small">Subrubro</div>
            <div class="font-weight-bold">{{ optional($ubicacion->rubro)->subrubro ?: '—' }}</div>
          </div>
          <div class="col-md-4 mb-2">
            <div class="text-muted small">Estado</div>
            @php
              $estado = strtolower($ubicacion->estado ?? '');
              $badge = $estado === 'vigente' ? 'success' : ($estado === 'irregular' ? 'danger' : 'warning');
            @endphp
            <span class="badge badge-{{ $badge }}">{{ ucfirst($ubicacion->estado ?? '-') }}</span>
          </div>
        </div>

        <div class="row">
          <div class="col-md-4 mb-2">
            <div class="text-muted small">Situación</div>
            <div class="font-weight-bold">{{ ucfirst($ubicacion->situacion) }}</div>
          </div>
          <div class="col-md-4 mb-2">
            <div class="text-muted small">Fecha de alta</div>
            <div class="font-weight-bold">{{ $ubicacion->fecha_alta ? \Illuminate\Support\Carbon::parse($ubicacion->fecha_alta)->format('Y-m-d') : '—' }}</div>
          </div>
          <div class="col-md-4 mb-2">
            <div class="text-muted small">Fecha de baja</div>
            <div class="font-weight-bold">{{ $ubicacion->fecha_baja ? \Illuminate\Support\Carbon::parse($ubicacion->fecha_baja)->format('Y-m-d') : '—' }}</div>
          </div>
        </div>
      </div>
    </div>

    {{-- Domicilios y otros --}}
    <div class="card mb-3">
      <div class="card-header bg-light">
        <strong>Domicilios y Otros</strong>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-2">
            <div class="text-muted small">Domicilio del comercio</div>
            <div class="font-weight-bold">{{ $ubicacion->domicilio_comercio ?: '—' }}</div>
          </div>
          <div class="col-md-6 mb-2">
            <div class="text-muted small">Domicilio del responsable</div>
            <div class="font-weight-bold">{{ $ubicacion->domicilio_responsable ?: '—' }}</div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-4 mb-2">
            <div class="text-muted small">Nomenclatura</div>
            <div class="font-weight-bold">{{ $ubicacion->nomenclatura ?: '—' }}</div>
          </div>
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

    {{-- Documentación --}}
    <div class="card mb-4">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <strong>Documentación presentada</strong>
        <span class="text-muted small">Sí / No</span>
      </div>
      <div class="card-body">
        @php
          $docs = optional($ubicacion->documentos)->toArray() ?? [];
          $generales = [
            'doc_libre_deuda_municipal'      => 'Certificado de libre deuda municipal',
            'doc_planeamiento_urbano'        => 'Dirección de Planeamiento Urbano',
            'doc_solicitud_habilitacion_pago'=> 'Solicitud de habilitación + pago',
            'doc_comprobante_uso_local'      => 'Comprobante de uso del local',
            'doc_afip_constancia'            => 'Constancia de inscripción emitida por AFIP',
            'doc_recaudacion_rn'             => 'Constancia de inscripción emitida por Agencia de Recaudación Tributaria de Río Negro',
            'doc_fotocopia_dni'              => 'Fotocopia del DNI',
            'doc_comprobante_uso_inmueble'   => 'Comprobante de uso del inmueble a destinar a comercio',
            'doc_libre_deuda_tasas_inmueble' => 'Libre deuda de tasas municipales de la propiedad',
            'doc_aptitud_tecnica_local'      => 'Certificado de aptitud técnica del local a habilitar',
            'doc_cocap_rhi'                  => 'Certificado de CO.CA.P.R.HI',
            'doc_nota_carteleria_obras'      => 'Nota a Obras Públicas declarando cartelería',
            'doc_libro_actas_100'            => 'Libro de actas de 100 hojas',
          ];
          $juridicas = [
            'doc_acta_constitucion'          => 'Acta de constitución de sociedad u organización',
            'doc_contrato_societario'        => 'Contrato societario',
            'doc_docs_representantes'        => 'Documentación de representantes',
          ];
          $badgeYN = function($v){
            return $v ? '<span class="badge badge-success">Sí</span>' : '<span class="badge badge-secondary">No</span>';
          };
        @endphp

        <div class="row">
          <div class="col-md-6">
            <h6 class="mb-2">Generales</h6>
            <ul class="list-group list-group-flush">
              @foreach($generales as $key => $label)
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                  <span>{{ $label }}</span>
                  {!! $badgeYN((bool)($docs[$key] ?? false)) !!}
                </li>
              @endforeach
            </ul>
          </div>

          @if(($ubicacion->persona_tipo ?? 'fisica') === 'juridica')
          <div class="col-md-6">
            <h6 class="mb-2">Personas Jurídicas</h6>
            <ul class="list-group list-group-flush">
              @foreach($juridicas as $key => $label)
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                  <span>{{ $label }}</span>
                  {!! $badgeYN((bool)($docs[$key] ?? false)) !!}
                </li>
              @endforeach
            </ul>
          </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
