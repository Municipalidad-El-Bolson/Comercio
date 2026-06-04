<div>
    <div class="card mb-3">
        {{-- Header --}}
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <strong>Seguimiento de documentación</strong>

            <div class="ml-auto">
                <button class="btn btn-sm btn-outline-secondary" wire:click="$toggle('colapsado')">
                    {{ $colapsado ? 'Expandir' : 'Minimizar' }}
                </button>
            </div>
        </div>

        {{-- Contenido colapsable --}}
        <div class="{{ $colapsado ? 'd-none' : '' }}">
            <div class="card-body pb-3">
                {{-- Controles --}}
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <select class="form-control form-control-sm mr-2" style="max-width:320px" wire:model.live="etapaActual">
                        @foreach ($etapas as $key => $meta)
                            <option value="{{ $key }}">{{ $meta['title'] }}</option>
                        @endforeach
                    </select>

                     <input type="date"
                            class="form-control form-control-sm mr-2"
                            style="max-width:220px"
                            wire:model.defer="fechaManual" />

                    <input type="text"
                            class="form-control form-control-sm mr-2"
                            style="max-width:360px"
                            placeholder="Observación (opcional)"
                            wire:model.defer="obs" />

                    <button class="btn btn-success btn-sm" wire:click="guardarEtapa">
                        Guardar
                    </button>
                </div>

                {{-- Timeline --}}
                <div class="timeline-wrap">
                    @foreach ($steps as $step)
                        <div class="step {{ $step['status'] }} {{ $step['is_last'] ? 'last' : '' }}">
                            <div class="dot">
                                @if ($step['status'] === 'done')
                                    <i class="fas fa-check"></i>
                                @elseif($step['status'] === 'current')
                                    <i class="fas fa-file-alt"></i>
                                @else
                                    <i class="far fa-circle"></i>
                                @endif
                            </div>

                            {{-- Solo título, descripción como tooltip --}}
                            <div class="label {{ $step['status'] === 'current' ? 'font-weight-bold' : '' }}"
                                title="{{ $step['tooltip'] }}">
                                <span class="wrap-2">{{ $step['title'] }}</span>
                            </div>


                            <div class="date text-muted">
                                {{ $step['fecha_str'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Estilos timeline (idénticos a los tuyos) --}}
        <style>
            .timeline-wrap { position: relative; display: flex; align-items: flex-start; padding: 24px 8px 0 8px; }
            .timeline-wrap .step { position: relative; flex: 1 1 0; text-align: center; }
            .timeline-wrap .step:not(.last)::after { content: ""; position: absolute; top: 18px; left: calc(50% + 18px); right: -50%; height: 2px; background: #e2e3e5; transform: translateY(-50%); z-index: 0; }
            .timeline-wrap .step.done:not(.last)::after,
            .timeline-wrap .step.current:not(.last)::after { background: #28a745; }
            .timeline-wrap .dot { width: 36px; height: 36px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: #f8f9fa; border: 2px solid #ced4da; color: #6c757d; margin: 0 auto; position: relative; z-index: 2; }
            .timeline-wrap .step.done .dot { background: #e9f7ec; border-color: #28a745; color: #28a745; }
            .timeline-wrap .step.current .dot { background: #28a745; border-color: #28a745; color: #fff; }
            .timeline-wrap .label { margin-top: 8px; font-size: .95rem; white-space: nowrap; }
            .timeline-wrap .date { font-size: .8rem; margin-top: 4px; }
            .timeline-wrap .step.last::after { display: none; }
            @media (max-width: 600px) {
                .timeline-wrap { flex-direction: column !important; align-items: stretch !important; padding: 16px 0 0 0 !important; }
                .timeline-wrap .step { text-align: left !important; margin-bottom: 32px; }
                .timeline-wrap .step .dot { margin: 0 0 0 8px; }
                .timeline-wrap .step:not(.last)::after { content: ""; position: absolute; left: 34px; top: 36px; width: 2px; height: 32px; background: #e2e3e5; z-index: 0; right: auto; bottom: auto; transform: none; }
                .timeline-wrap .step.done:not(.last)::after,
                .timeline-wrap .step.current:not(.last)::after { background: #28a745; }
                .timeline-wrap .step.last::after { display: none; }
            }
        </style>
        <style>
            .timeline-wrap .label .truncate {
                display: inline-block;
                max-width: 180px;          /* ajustá según tu layout */
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                vertical-align: bottom;
            }
            /* si querés, podés aumentar el ancho en pantallas grandes */
            @media (min-width: 1400px) {
                .timeline-wrap .label .truncate { max-width: 220px; }
            }
        </style>
        <style>
            /* === Layout base === */
            .timeline-wrap {
                --step-width: 180px;       /* ancho uniforme por paso */
                --dot-size: 36px;
                position: relative;
                display: flex;
                align-items: flex-start;
                padding: 24px 8px 0 8px;
                gap: 0;                    /* líneas quedan continuas */
                overflow-x: auto;          /* si no entra, scrollea horizontal */
                -webkit-overflow-scrolling: touch;
            }

            .timeline-wrap .step {
                position: relative;
                flex: 0 0 var(--step-width);  /* TODOS los steps mismo ancho */
                text-align: center;
            }

            /* línea de fondo */
            .timeline-wrap .step:not(.last)::after {
                content: "";
                position: absolute;
                top: calc(var(--dot-size)/2);
                left: calc(50% + var(--dot-size)/2);
                right: -50%;
                height: 2px;
                background: #e2e3e5;
                transform: translateY(-50%);
                z-index: 0;
            }
            .timeline-wrap .step.done:not(.last)::after,
            .timeline-wrap .step.current:not(.last)::after { background: #28a745; }

            /* DOT */
            .timeline-wrap .dot {
                width: var(--dot-size);
                height: var(--dot-size);
                border-radius: 50%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: #f8f9fa;
                border: 2px solid #ced4da;
                color: #6c757d;
                margin: 0 auto;
                position: relative;
                z-index: 2;
            }
            .timeline-wrap .step.done .dot   { background:#e9f7ec; border-color:#28a745; color:#28a745; }
            .timeline-wrap .step.current .dot{ background:#28a745; border-color:#28a745; color:#fff; }

            /* Título con 2 líneas como máximo */
            .timeline-wrap .label {
                margin-top: 8px;
                font-size: .95rem;
                line-height: 1.1rem;
                padding: 0 6px; /* un poco de aire lateral */
            }
            .timeline-wrap .label .wrap-2 {
                display: -webkit-box;
                -webkit-line-clamp: 2;      /* <= 2 líneas */
                -webkit-box-orient: vertical;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: normal;         /* permite salto */
                word-break: break-word;      /* corta palabras largas si hace falta */
            }

            .timeline-wrap .date { font-size: .8rem; margin-top: 4px; }
            .timeline-wrap .step.last::after { display: none; }

            /* Responsive: podés ajustar ancho por paso según viewport */
            @media (min-width: 1400px) { .timeline-wrap { --step-width: 200px; } }
            @media (max-width: 768px)  { .timeline-wrap { --step-width: 160px; } }
            @media (max-width: 600px)  { .timeline-wrap { --step-width: 140px; } }
        </style>
        <style>
            .timeline-wrap {
                --min-step: 180px;          /* ancho mínimo por step */
                --dot-size: 36px;
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(var(--min-step), 1fr));
                gap: 0;                     /* mantené 0 para que la línea se vea continua */
                align-items: start;
                padding: 24px 8px 0 8px;
            }
            .timeline-wrap .step { position: relative; text-align: center; }

            /* Las líneas horizontales entre grid-celdas son más difíciles:
                este enfoque dibuja cada tramo dentro de su celda hasta la mitad */
            .timeline-wrap .step:not(.last)::after {
                content: "";
                position: absolute;
                top: calc(var(--dot-size)/2);
                left: calc(50% + var(--dot-size)/2);
                right: 0;
                height: 2px;
                background: #e2e3e5;
                transform: translateY(-50%);
                z-index: 0;
            }
            .timeline-wrap .step.done:not(.last)::after,
            .timeline-wrap .step.current:not(.last)::after { background: #28a745; }
            .timeline-wrap {
                --step-width: 140px;     /* ancho fijo para cada paso */
                --dot-size: 36px;
                display: flex;
                align-items: flex-start;
                padding: 24px 8px 0 8px;
                overflow-x: auto;        /* scroll si no entran todos */
                -webkit-overflow-scrolling: touch;
            }

            .timeline-wrap .step {
                flex: 0 0 var(--step-width);
                text-align: center;
                position: relative;
            }

            /* línea entre pasos */
            .timeline-wrap .step:not(.last)::after {
                content: "";
                position: absolute;
                top: calc(var(--dot-size) / 2);
                left: calc(50% + var(--dot-size) / 2);
                right: -50%;
                height: 2px;
                background: #e2e3e5;
                transform: translateY(-50%);
                z-index: 0;
            }
            .timeline-wrap .step.done:not(.last)::after,
            .timeline-wrap .step.current:not(.last)::after {
                background: #28a745;
            }

            /* DOT */
            .timeline-wrap .dot {
                width: var(--dot-size);
                height: var(--dot-size);
                border-radius: 50%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: #f8f9fa;
                border: 2px solid #ced4da;
                color: #6c757d;
                margin: 0 auto;
                position: relative;
                z-index: 2;
            }
            .timeline-wrap .step.done .dot   { background:#e9f7ec; border-color:#28a745; color:#28a745; }
            .timeline-wrap .step.current .dot{ background:#28a745; border-color:#28a745; color:#fff; }

            /* Títulos en dos renglones */
            .timeline-wrap .label {
                margin-top: 8px;
                font-size: .90rem;
                line-height: 1.1rem;
                padding: 0 4px;
            }
            .timeline-wrap .label .wrap-2 {
                display: -webkit-box;
                -webkit-line-clamp: 2;   /* máximo 2 líneas */
                -webkit-box-orient: vertical;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: normal;
                word-break: break-word;  /* corta palabras largas */
            }

            /* Fecha */
            .timeline-wrap .date {
                font-size: .75rem;
                margin-top: 4px;
            }
            .timeline-wrap .step.last::after { display: none; }

            /* dot, label, date y wrap-2 = igual que en la variante A */
        </style>
        
    </div>
</div>
