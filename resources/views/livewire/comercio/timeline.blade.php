<div class="expediente-panel">
    <div class="card mb-3 border-secondary">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-route mr-2 text-primary"></i>Seguimiento del expediente</strong>
            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="$toggle('colapsado')">
                <i class="fas {{ $colapsado ? 'fa-chevron-down' : 'fa-chevron-up' }} mr-1"></i>
                {{ $colapsado ? 'Expandir' : 'Minimizar' }}
            </button>
        </div>

        <div class="{{ $colapsado ? 'd-none' : '' }}">
            <div class="card-body pb-3">
                @if($observacionesMesa->isNotEmpty())
                    <div class="alert alert-warning py-2">
                        <div class="font-weight-bold mb-1"><i class="fas fa-comment-alt mr-1"></i>Observaciones de Mesa de entrada</div>
                        @foreach($observacionesMesa as $observacionMesa)
                            <div class="small mb-1"><strong>Expediente Nº {{ $observacionMesa->nro_ingreso }}</strong> ({{ $observacionMesa->fecha?->format('d/m/Y') }}): {{ $observacionMesa->observacion }}</div>
                        @endforeach
                    </div>
                @endif

                @can('manage-ubicaciones')
                <div class="expediente-controls mb-3">
                    <div class="expediente-field expediente-stage">
                        <label for="expediente-etapa">Nueva etapa</label>
                        <select id="expediente-etapa" class="form-control form-control-sm" wire:model.live="etapaActual">
                            @foreach ($etapas as $key => $meta)
                                <option value="{{ $key }}">{{ $meta['title'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="expediente-field expediente-date">
                        <label for="expediente-fecha">Fecha</label>
                        <input id="expediente-fecha" type="date" class="form-control form-control-sm" wire:model.defer="fechaManual">
                    </div>
                    <div class="expediente-field expediente-note">
                        <label for="expediente-obs">Observación</label>
                        <input id="expediente-obs" type="text" class="form-control form-control-sm"
                               placeholder="Detalle opcional del avance" wire:model.defer="obs">
                    </div>
                    <button type="button" class="btn btn-success btn-sm expediente-save"
                            wire:click="guardarEtapa" wire:loading.attr="disabled" wire:target="guardarEtapa">
                        <i class="fas fa-save mr-1"></i>
                        <span wire:loading.remove wire:target="guardarEtapa">Guardar avance</span>
                        <span wire:loading wire:target="guardarEtapa">Guardando…</span>
                    </button>
                </div>
                @endcan

                <div class="timeline-wrap" aria-label="Etapas del expediente">
                    @foreach ($steps as $step)
                        <div class="step {{ $step['status'] }} {{ $step['is_last'] ? 'last' : '' }}">
                            <div class="dot" aria-hidden="true">
                                @if ($step['status'] === 'done')
                                    <i class="fas fa-check"></i>
                                @elseif($step['status'] === 'current')
                                    <i class="fas fa-file-alt"></i>
                                @else
                                    <i class="far fa-circle"></i>
                                @endif
                            </div>
                            <div class="label {{ $step['status'] === 'current' ? 'font-weight-bold' : '' }}" title="{{ $step['tooltip'] }}">
                                <span>{{ $step['title'] }}</span>
                            </div>
                            <div class="date text-muted">{{ $step['fecha_str'] }}</div>
                        </div>
                    @endforeach
                </div>

                <hr class="my-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0"><i class="fas fa-history mr-1"></i>Historial del expediente</h6>
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="$toggle('historialColapsado')">
                        <i class="fas {{ $historialColapsado ? 'fa-chevron-down' : 'fa-chevron-up' }} mr-1"></i>
                        {{ $historialColapsado ? 'Expandir' : 'Minimizar' }}
                    </button>
                </div>
                <div class="table-responsive {{ $historialColapsado ? 'd-none' : '' }}">
                    <table class="table table-sm table-striped table-bordered mb-0">
                        <thead class="thead-light">
                            <tr><th>Fecha</th><th>Desde</th><th>Hacia</th><th>Usuario</th><th>Observaciones</th></tr>
                        </thead>
                        <tbody>
                            @forelse($historial as $registro)
                                <tr>
                                    <td>{{ $registro->fecha?->format('d/m/Y') }}</td>
                                    <td>{{ data_get($etapas, $registro->sector_desde.'.title', 'Inicio del expediente') }}</td>
                                    <td>{{ data_get($etapas, $registro->sector_hasta.'.title', $registro->sector_hasta) }}</td>
                                    <td>{{ $registro->user?->name ?? '(sin registro)' }}</td>
                                    <td>{{ $registro->observacion ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted text-center py-3">Todavía no hay avances registrados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <style>
        .expediente-panel .expediente-controls{display:flex;align-items:flex-end;gap:.65rem;padding:.85rem;border:1px solid #e2e8f0;border-radius:.65rem;background:#f8fafc}.expediente-panel .expediente-field label{display:block;margin-bottom:.25rem;color:#64748b;font-size:.72rem;font-weight:700;letter-spacing:.025em;text-transform:uppercase}.expediente-panel .expediente-stage{flex:1 1 240px}.expediente-panel .expediente-date{flex:0 1 180px}.expediente-panel .expediente-note{flex:2 1 320px}.expediente-panel .expediente-save{flex:0 0 auto;height:31px}.expediente-panel .timeline-wrap{--step-width:145px;--dot-size:36px;display:flex;align-items:flex-start;padding:24px 8px 8px;overflow-x:auto;-webkit-overflow-scrolling:touch}.expediente-panel .timeline-wrap .step{position:relative;flex:0 0 var(--step-width);text-align:center}.expediente-panel .timeline-wrap .step:not(.last)::after{content:"";position:absolute;top:calc(var(--dot-size)/2);left:calc(50% + var(--dot-size)/2);right:-50%;height:2px;background:#dce3ea;transform:translateY(-50%);z-index:0}.expediente-panel .timeline-wrap .step.done:not(.last)::after,.expediente-panel .timeline-wrap .step.current:not(.last)::after{background:#28a745}.expediente-panel .timeline-wrap .dot{position:relative;z-index:2;width:var(--dot-size);height:var(--dot-size);margin:0 auto;border:2px solid #cbd5e1;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:#f8fafc;color:#64748b}.expediente-panel .timeline-wrap .step.done .dot{border-color:#28a745;background:#e8f7ed;color:#22863a}.expediente-panel .timeline-wrap .step.current .dot{border-color:#2673b8;background:#2673b8;color:#fff;box-shadow:0 0 0 4px rgba(38,115,184,.12)}.expediente-panel .timeline-wrap .label{margin-top:8px;padding:0 5px;font-size:.86rem;line-height:1.05rem}.expediente-panel .timeline-wrap .label span{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}.expediente-panel .timeline-wrap .date{margin-top:4px;font-size:.75rem}.expediente-panel .timeline-wrap .step.last::after{display:none}@media(min-width:1400px){.expediente-panel .timeline-wrap{--step-width:160px}}@media(max-width:767.98px){.expediente-panel .expediente-controls{align-items:stretch;flex-direction:column}.expediente-panel .expediente-stage,.expediente-panel .expediente-date,.expediente-panel .expediente-note{width:100%;flex-basis:auto}.expediente-panel .expediente-save{width:100%}.expediente-panel .timeline-wrap{--step-width:135px}}
    </style>
</div>
