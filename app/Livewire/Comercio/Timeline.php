<?php

namespace App\Livewire\Comercio;

use Livewire\Component;
use App\Models\Movimiento;
use App\Models\Ubicacion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Timeline extends Component
{
    public int $ubicacionId;
    public ?string $createdAt = null;

    // app/Livewire/Comercio/Timeline.php

    public array $etapas = [
        'ini_021_032'          => ['title' => 'Inicio 021/032',          'desc' => 'Inicio de trámite 021 / 032'],
        'inspeccion'           => ['title' => 'Inspección',              'desc' => 'Verificación en el local por Inspectoría.'],
        'control_expediente'   => ['title' => 'Control de expediente',   'desc' => 'Revisión de requisitos y documentación.'],
        'redaccion_disposicion'=> ['title' => 'Redacción de disposición','desc' => 'Elaboración en Dirección de Comercio.'],
        'firma_legal_tecnica'  => ['title' => 'Firma Legal y Técnica',   'desc' => 'Validación jurídica.'],
        'firma_comercio'       => ['title' => 'Firma Comercio',          'desc' => 'Aprobación administrativa.'],
        'protocolizacion'      => ['title' => 'Protocolización',         'desc' => 'Registro oficial de la disposición.'],
        'liquidacion'          => ['title' => 'Liquidación',             'desc' => 'Generación de tasas y emisión del cartón.'],
        'entrega'              => ['title' => 'Entrega',                 'desc' => 'Mesa de entradas entrega cartón al comerciante.'],
        'habilitacion_vigente' => ['title' => 'Habilitación vigente',    'desc' => 'Comercio queda en estado activo.'],
    ];

    public ?string $fechaManual = null;
    public ?string $etapaActual = null;
    public ?string $obs = null;
    public bool $colapsado = false;

    protected $rules = [
        'etapaActual' => 'required|string',
        'obs'         => 'nullable|string|max:2000',
    ];

    public function mount(int $ubicacionId, $createdAt = null)
    {
        $this->ubicacionId = $ubicacionId;

        // Normalizo createdAt (para fallback de la primera etapa)
        if ($createdAt instanceof Carbon) {
            $this->createdAt = $createdAt->toDateString();
        } elseif (is_string($createdAt) && $createdAt !== '') {
            $this->createdAt = Carbon::parse($createdAt)->toDateString();
        } else {
            $this->createdAt = optional(Ubicacion::find($ubicacionId))->created_at?->toDateString();
        }

        // Cargar movimientos existentes por etapa (último por etapa)
        $movs = Movimiento::query()
            ->where('ubicacion_id', $this->ubicacionId)
            ->whereIn('etapa', array_keys($this->etapas))
            ->select('etapa', DB::raw('MAX(fecha) as fecha'), DB::raw('MAX(id) as id'))
            ->groupBy('etapa')
            ->get()
            ->keyBy('etapa');

        $primeraNoHecha = collect(array_keys($this->etapas))->first(fn($k) => !isset($movs[$k]));
        $this->etapaActual = $primeraNoHecha ?? array_key_last($this->etapas);
    }

    public function guardarEtapa()
    {
        // Validación básica (incluye fecha opcional)
        $allowed = array_keys($this->etapas);
        $this->validate([
            'etapaActual' => ['required', Rule::in($allowed)],
            'fechaManual' => ['nullable','date'],
            'obs'         => ['nullable','string','max:500'],
        ]);

        // Fecha a usar: manual si viene; si no, hoy (zona horaria AR)
        $tz    = 'America/Argentina/Salta';
        $fecha = $this->fechaManual
            ? Carbon::parse(str_replace('T',' ', $this->fechaManual), $tz)->toDateString()
            : Carbon::now($tz)->toDateString();

        // Persistencia (mantengo tu tipo=timeline)
        Movimiento::updateOrCreate(
            [
                'ubicacion_id' => $this->ubicacionId,
                'etapa'        => $this->etapaActual,
                'tipo'         => 'timeline',
            ],
            [
                'titulo'       => $this->etapas[$this->etapaActual]['title'] ?? $this->etapaActual,
                'observacion'  => (string)($this->obs ?? ''),
                'fecha'        => $fecha,     // usa la fecha elegida
                'tipo_acta'    => null,
            ]
        );

        // Mover selector a la siguiente etapa NO completada
        $keys = array_keys($this->etapas);
        $completadas = Movimiento::where('ubicacion_id', $this->ubicacionId)
            ->where('tipo', 'timeline')
            ->whereIn('etapa', $keys)
            ->pluck('etapa')
            ->flip();

        $siguiente = collect($keys)->first(fn($k) => !$completadas->has($k));
        $this->etapaActual = $siguiente ?? array_key_last($this->etapas);

        // Limpio el campo de fecha manual para el próximo uso
        $this->reset('fechaManual', 'obs');

        $this->dispatch('toast', type: 'success', message: 'Etapa guardada');
    }

    public function getStepsProperty(): \Illuminate\Support\Collection
    {
        // Movimientos por etapa (último por etapa)
        $movs = Movimiento::query()
            ->where('ubicacion_id', $this->ubicacionId)
            ->where('tipo', 'timeline')
            ->whereIn('etapa', array_keys($this->etapas))
            ->select('etapa', DB::raw('MAX(fecha) as fecha'), DB::raw('MAX(id) as id'))
            ->groupBy('etapa')
            ->get()
            ->keyBy('etapa');

        $keys = array_keys($this->etapas);
        $lastIndex = count($keys) - 1;

        return collect($keys)->values()->map(function ($key, $i) use ($movs, $lastIndex) {
            $movFecha = optional($movs->get($key))->fecha;

            // Fallback: primera etapa toma createdAt si no hay movimiento
            if (!$movFecha && $i === 0 && $this->createdAt) {
                $movFecha = $this->createdAt;
            }

            // ✅ PINTADO por estado real:
            // - done    => hay movimiento guardado
            // - current => coincide con etapaActual
            // - todo    => el resto
            $status = $movs->has($key)
                ? 'done'
                : ($key === $this->etapaActual ? 'current' : 'todo');

            return [
                'key'       => $key,
                'title'     => $this->etapas[$key]['title'],   // corto
                'tooltip'   => $this->etapas[$key]['desc'],    // largo
                'status'    => $status,
                'is_last'   => $i === $lastIndex,
                'fecha_str' => $movFecha ? \Illuminate\Support\Carbon::parse($movFecha)->format('d-m-Y') : '—',
            ];
        });
    }

    public function render()
    {
        return view('livewire.comercio.timeline', [
            'steps'     => $this->steps,   // view-model listo para la vista
            'createdAt' => $this->createdAt,
        ]);
    }
}
