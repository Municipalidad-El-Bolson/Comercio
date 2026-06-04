<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand -->
    <a href="{{ url('/') }}" class="brand-link text-center">
        <img src="{{ asset('images/MEB.webp') }}" alt="Logo" style="width:150px; height:auto;">
    </a>

    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column" style="min-height: 100vh;">
        <nav class="mt-2 flex-grow-1">
            <ul class="nav nav-pills nav-sidebar flex-column d-flex flex-column h-100" data-widget="treeview" role="menu" data-accordion="false">
                
                {{-- Mesa de entrada --}}
                <li class="nav-item">
                    <a href="{{ route('mesa.inbox') }}"
                       class="nav-link d-flex align-items-center justify-content-between {{ request()->routeIs('mesa.inbox') ? 'active' : '' }}">
                        <span><i class="fas fa-inbox me-2"></i> Mesa de entrada</span>
                        @livewire('notifications.bell-mesa')
                    </a>
                </li>

                {{-- Próximos a vencer --}}
                <li class="nav-item">
                <a href="{{ route('prox_vto.index') }}"
                    class="nav-link d-flex align-items-center justify-content-between {{ request()->routeIs('prox_vto.index') ? 'active' : '' }}">
                    <span><i class="fas fa-hourglass-half me-2"></i> Próximos a vencer</span>
                    @livewire('notifications.bell-prox-vto')
                </a>
                </li>

                {{-- Vencidos --}}
                <li class="nav-item">
                <a href="{{ route('vto.index') }}"
                    class="nav-link d-flex align-items-center justify-content-between {{ request()->routeIs('vto.index') ? 'active' : '' }}">
                    <span><i class="fas fa-calendar-times me-2"></i> Vencidos</span>
                    @livewire('notifications.bell-vencidos')
                </a>
                </li>

                {{-- Mapa --}}
                <li class="nav-item">
                    <a href="{{ route('mapas') }}"
                       class="nav-link {{ request()->routeIs('mapas') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-map-marked-alt"></i>
                        <p>Mapa</p>
                    </a>
                </li>

                {{-- Comercios --}}
                @can('manage-ubicaciones')
                <li class="nav-item">
                    <a href="{{ route('ubicaciones') }}"
                       class="nav-link {{ request()->routeIs('ubicaciones') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-list"></i>
                        <p>Comercios</p>
                    </a>
                </li>
                @endcan

                {{-- Auditoría --}}
                @can('access-admin')
                <li class="nav-item">
                    <a href="{{ route('historial') }}"
                       class="nav-link {{ request()->routeIs('historial') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-book-open"></i>
                        <p>Auditoría</p>
                    </a>
                </li>

                {{-- Reportes --}}
                <li class="nav-item">
                    <a href="{{ route('reportes') }}"
                       class="nav-link {{ request()->routeIs('reportes') ? 'active' : '' }}">
                        <i class="nav-icon far fa-calendar-check"></i>
                        <p>Reportes</p>
                    </a>
                </li>

                {{-- Usuarios --}}
                <li class="nav-item">
                    <a href="{{ route('users.index') }}"
                       class="nav-link {{ request()->routeIs('users.index') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-users-cog"></i>
                        <p>Usuarios</p>
                    </a>
                </li>
                @endcan

                {{-- Logout (siempre al final) --}}
                <li class="nav-item mt-auto">
                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit"
                                class="btn btn-danger nav-link d-flex align-items-center w-100 text-white">
                            <i class="nav-icon fas fa-sign-out-alt"></i>
                            <p class="ms-2 mb-0 d-none d-md-inline text-white">Cerrar sesión</p>
                        </button>
                    </form>
                </li>

            </ul>
        </nav>
    </div>
</aside>

<style>
    .main-sidebar {
        min-height: 100vh;
    }

    /* Estilo del tab activo (más visible que el hover) */
    .nav-sidebar .nav-link.active {
        background-color: #007bff !important;
        color: #fff !important;
    }
    .nav-sidebar .nav-link.active i {
        color: #fff !important;
    }

    /* Hover igual al activo */
    .nav-sidebar .nav-link:hover {
        background-color: rgba(0, 123, 255, 0.7);
        color: #fff;
    }
</style>
