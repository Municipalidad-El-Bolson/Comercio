<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand -->
    <a href="{{ url('/') }}" class="brand-link">
        <img src="{{ asset('images/MEB.webp') }}" alt="Logo" style="width:150px; height:auto;">
    </a>

    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column" style="min-height: 100vh;">
        <!-- Menu -->
        <nav class="mt-2 flex-grow-1">
            {{-- Hacemos el UL flex para poder empujar el logout al fondo con mt-auto --}}
            <ul class="nav nav-pills nav-sidebar flex-column d-flex flex-column h-100" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="{{ route('mesa.inbox') }}" class="nav-link d-flex align-items-center justify-content-between">
                        <span><i class="fas fa-inbox me-2"></i> Mesa de entrada</span>
                        @livewire('notifications.bell')
                    </a>
                </li>

            {{--<li class="nav-item">
                    <a href="{{ route('/') }}" class="nav-link">
                        <i class="nav-icon fas fa-map-marked-alt"></i>
                        <p>Vencimientos</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('/') }}" class="nav-link">
                        <i class="nav-icon fas fa-map-marked-alt"></i>
                        <p>Proximos a vencer</p>
                    </a>
                </li> --}}
                
                <li class="nav-item">
                    <a href="{{ route('mapas') }}" class="nav-link">
                        <i class="nav-icon fas fa-map-marked-alt"></i>
                        <p>Mapa</p>
                    </a>
                </li>

                @can('manage-ubicaciones')
                <li class="nav-item">
                    <a href="{{ route('ubicaciones') }}" class="nav-link">
                        <i class="nav-icon fas fa-list"></i>
                        <p>Comercios</p> {{-- Renombrado --}}
                    </a>
                </li>
                @endcan

                @can('access-admin')
                <li class="nav-item">
                    <a href="{{ route('historial') }}" class="nav-link">
                        <i class="nav-icon fas fa-book-open"></i>
                        <p>Auditoria</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('reportes') }}" class="nav-link">
                        <i class="nav-icon far fa-calendar-check"></i>
                        <p>Reportes</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('users.index') }}" class="nav-link">
                        <i class="nav-icon fas fa-users-cog"></i>
                        <p>Usuarios</p>
                    </a>
                </li>
                @endcan

                {{-- ===== Logout SIEMPRE ABAJO ===== --}}
                <li class="nav-item mt-auto">
                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-danger nav-link d-flex align-items-center w-100 text-white">
                            <i class="nav-icon fas fa-sign-out-alt"></i>
                            <p class="ms-2 mb-0 d-none d-md-inline text-white">Cerrar sesión</p>
                        </button>
                    </form>
                </li>

                {{-- ================================== --}}
            </ul>
        </nav>
    </div>
</aside>
<style>
    .sidebar-collapse .sidebar-footer .btn {
        width: 40px !important;
        padding: 0.5rem;
    }
    .sidebar-collapse .sidebar-footer .btn span {
        display: none !important;
    }

    .main-sidebar {
        min-height: 100vh;
    }
</style>
