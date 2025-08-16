<section class="content">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Historial de movimientos</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item active">Historial</li>
                    </ol>
                </div>
            </div>

            <ol class="list-group list-group-numbered">
                @forelse($logs as $log)
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold">{{ $log->description }}</div>
                            Usuario: {{ $log->causer?->name ?? 'Sistema' }}
                        </div>
                        <span class="badge bg-primary rounded-pill">
                            {{ $log->created_at->format('d/m/Y H:i') }}
                        </span>
                    </li>
                @empty
                    <li class="list-group-item">No hay registros todavía</li>
                @endforelse
            </ol>

            <div class="mt-3">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</section>
