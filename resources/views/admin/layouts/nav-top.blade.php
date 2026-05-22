<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    @php
        $habilitacionesPorVencer = \App\Models\Ubicacion::obtenerProximasAVencer(30)->count();
    @endphp

    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="{{ route('panel') }}" class="nav-link">Home</a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="{{ route('mapas') }}" class="nav-link">Comercios Georreferenciados</a>
        </li>
    </ul>

    <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#" aria-label="Notificaciones">
                <i class="far fa-bell"></i>
                @if ($habilitacionesPorVencer > 0)
                    <span class="badge badge-warning navbar-badge">{{ $habilitacionesPorVencer }}</span>
                @endif
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-item dropdown-header">Notificaciones</span>
                <div class="dropdown-divider"></div>
                <a href="{{ route('reportes') }}" class="dropdown-item">
                    <i class="far fa-calendar-alt mr-2"></i>
                    {{ $habilitacionesPorVencer }} habilitaciones proximas a vencer
                </a>
            </div>
        </li>
    </ul>
</nav>
