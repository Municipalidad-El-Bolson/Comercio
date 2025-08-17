<div class="card mb-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <strong>Seguimiento de documentación</strong>
    <div class="d-flex align-items-center">
      <div class="mr-2">
        <select class="form-control form-control-sm" wire:model="etapaActual">
          @foreach($etapas as $key => $label)
            <option value="{{ $key }}">{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <input type="date" class="form-control form-control-sm mr-2" style="width:160px" wire:model="fecha">
      <input type="text" class="form-control form-control-sm mr-2" style="width:220px" placeholder="Observación (opcional)" wire:model.defer="obs">
      <button class="btn btn-primary btn-sm mr-2" wire:click="marcarEtapa">
        Guardar
      </button>
      <button class="btn btn-outline-success btn-sm" wire:click="avanzar">
        Avanzar
      </button>
    </div>
  </div>

  <div class="card-body">
    <div class="timeline-wrap">
      @php
        $keys = array_keys($etapas);
        $idxActual = array_search($etapaActual, $keys, true);
      @endphp

      @foreach($etapas as $key => $label)
        @php
          $i = array_search($key, $keys, true);
          $status = $i < $idxActual ? 'done' : ($i === $idxActual ? 'current' : 'todo');
          $mov = $historial->get($key);
          $fecha = $mov?->fecha ? \Illuminate\Support\Carbon::parse($mov->fecha)->format('d-m-Y') : null;
        @endphp

        <div class="step {{ $status }}">
          <div class="dot">
            @if($status === 'done')
              <i class="fas fa-check"></i>
            @elseif($status === 'current')
              <i class="fas fa-truck"></i>
            @else
              <i class="far fa-circle"></i>
            @endif
          </div>
          <div class="label {{ $status === 'current' ? 'font-weight-bold' : '' }}">
            {{ $label }}
          </div>
          <div class="date text-muted">
            {{ $fecha ?? '—' }}
          </div>
        </div>
      @endforeach
    </div>
  </div>

  <style>
    .timeline-wrap{
      display:flex; align-items:flex-start; position:relative; padding:10px 10px 0 10px;
    }
    .timeline-wrap::before{
      content:""; position:absolute; left:40px; right:40px; top:28px; height:2px; background:#ced4da;
    }
    .step{ flex:1 1 0; text-align:center; position:relative; }
    .step .dot{
      width:36px; height:36px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center;
      background:#e9ecef; border:2px solid #adb5bd; position:relative; z-index:2;
    }
    .step.done .dot{ background:#d4edda; border-color:#28a745; color:#28a745; }
    .step.current .dot{ background:#28a745; border-color:#28a745; color:#fff; }
    .step.todo .dot{ background:#f8f9fa; border-color:#ced4da; color:#6c757d; }

    .step .label{ margin-top:6px; font-size:.95rem; }
    .step .date{ font-size:.8rem; }
    /* colorear línea de progreso hasta la etapa actual */
    .timeline-wrap .step.done ~ .step::before,
    .timeline-wrap .step.current ~ .step::before{ background:#ced4da; }
    .timeline-wrap .step::before{
      content:""; position:absolute; left:-50%; right:50%; top:28px; height:2px; background:#28a745;
      z-index:1; display:block;
    }
    .timeline-wrap .step:first-child::before{ display:none; }
    /* gris para lo que falta */
    .timeline-wrap .step.todo::before{ background:#ced4da; }
  </style>
</div>
