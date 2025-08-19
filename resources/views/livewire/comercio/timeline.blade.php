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
                    <select class="form-control form-control-sm mr-2" style="max-width:220px" wire:model="etapaActual">
                        @foreach ($etapas as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    <input type="date" class="form-control form-control-sm mr-2" style="max-width:180px"
                        wire:model="fecha">

                    <button class="btn btn-success btn-sm" wire:click="avanzar">
                        Avanzar
                    </button>
                </div>

                {{-- Timeline --}}
                @php
                    $keys = array_keys($etapas);
                    $idxActual = array_search($etapaActual, $keys, true);
                @endphp

                <div class="timeline-wrap">
                    @foreach ($etapas as $key => $label)
                        @php
                            $i = array_search($key, $keys, true);
                            $status = $i < $idxActual ? 'done' : ($i === $idxActual ? 'current' : 'todo');
                            $mov = $historial->get($key);
                            $fechaEtapa = $mov?->fecha
                                ? \Illuminate\Support\Carbon::parse($mov->fecha)->format('d-m-Y')
                                : ($key === 'comercio_inicio' && $createdAt
                                    ? \Illuminate\Support\Carbon::parse($createdAt)->format('d-m-Y')
                                    : null);
                            $isLast = $i === count($keys) - 1;
                        @endphp

                        <div class="step {{ $status }} {{ $isLast ? 'last' : '' }}">
                            <div class="dot">
                                @if ($status === 'done')
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
                                {{ $fechaEtapa ?? '—' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Estilos propios del timeline --}}
        <style>
            .timeline-wrap {
                position: relative;
                display: flex;
                align-items: flex-start;
                /* etiquetas debajo */
                padding: 24px 8px 0 8px;
            }

            /* cada step ocupa el mismo ancho */
            .timeline-wrap .step {
                position: relative;
                flex: 1 1 0;
                text-align: center;
            }

            /* línea de fondo gris entre pasos:
       la dibujamos en cada step hacia la derecha, excepto en el último,
       y la centramos verticalmente respecto del punto */
            .timeline-wrap .step:not(.last)::after {
                content: "";
                position: absolute;
                top: 18px;
                /* centro vertical del dot (36px/2) */
                left: calc(50% + 18px);
                /* mitad + radio del dot para que arranque desde el borde derecho del círculo */
                right: -50%;
                height: 2px;
                background: #e2e3e5;
                transform: translateY(-50%);
                /* centra exacto en el centro del dot */
                z-index: 0;
            }

            /* línea verde de progreso: solo para pasos 'done' y 'current' */
            .timeline-wrap .step.done:not(.last)::after,
            .timeline-wrap .step.current:not(.last)::after {
                background: #28a745;
            }

            /* DOT */
            .timeline-wrap .dot {
                width: 36px;
                height: 36px;
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

            .timeline-wrap .step.done .dot {
                background: #e9f7ec;
                border-color: #28a745;
                color: #28a745;
            }

            .timeline-wrap .step.current .dot {
                background: #28a745;
                border-color: #28a745;
                color: #fff;
            }

            .timeline-wrap .label {
                margin-top: 8px;
                font-size: .95rem;
                white-space: nowrap;
            }

            .timeline-wrap .date {
                font-size: .8rem;
                margin-top: 4px;
            }

            /* Cortar la línea después del paso FINAL:
       ya lo logramos con .last (no se dibuja ::after),
       pero además evitamos “verde” extra a la derecha del último estando current */
            .timeline-wrap .step.last::after {
                display: none;
            }

            @media (max-width: 600px) {
                .timeline-wrap {
                    flex-direction: column !important;
                    align-items: stretch !important;
                    padding: 16px 0 0 0 !important;
                }

                .timeline-wrap .step {
                    text-align: left !important;
                    margin-bottom: 32px;
                }

                .timeline-wrap .step .dot {
                    margin: 0 0 0 8px;
                }

                .timeline-wrap .step:not(.last)::after {
                    content: "";
                    position: absolute;
                    left: 34px;
                    top: 36px;
                    width: 2px;
                    height: 32px;
                    background: #e2e3e5;
                    z-index: 0;
                    right: auto;
                    bottom: auto;
                    transform: none;
                }

                .timeline-wrap .step.done:not(.last)::after,
                .timeline-wrap .step.current:not(.last)::after {
                    background: #28a745;
                }

                .timeline-wrap .step.last::after {
                    display: none;
                }
            }
        </style>
    </div>


</div>
