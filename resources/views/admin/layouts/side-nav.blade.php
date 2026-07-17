<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand -->
    <a href="{{ url('/') }}" class="brand-link text-center">
        <img src="{{ asset('images/MEB.webp') }}" alt="Logo" style="width:150px; height:auto;">
    </a>

    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column" style="min-height: 100vh;">
        <nav class="mt-2 flex-grow-1">
            <ul class="nav nav-pills nav-sidebar flex-column d-flex flex-column h-100" data-widget="treeview" role="menu" data-accordion="false">
                
                @can('administrative-user')
                {{-- Mesa de entrada --}}
                <li class="nav-item">
                    <a href="{{ route('mesa.inbox') }}"
                       class="nav-link d-flex align-items-center justify-content-between {{ request()->routeIs('mesa.inbox') ? 'active' : '' }}">
                        <span class="nav-label"><i class="fas fa-inbox nav-icon"></i><span>Mesa de entrada</span></span>
                        @livewire('notifications.bell-mesa')
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('mesa.historial') }}"
                       class="nav-link {{ request()->routeIs('mesa.historial*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-history"></i>
                        <p>Registro histórico Mesa</p>
                    </a>
                </li>

                {{-- Próximos a vencer --}}
                <li class="nav-item">
                <a href="{{ route('prox_vto.index') }}"
                    class="nav-link d-flex align-items-center justify-content-between {{ request()->routeIs('prox_vto.index') ? 'active' : '' }}">
                    <span class="nav-label"><i class="fas fa-hourglass-half nav-icon"></i><span>Próximos a vencer</span></span>
                    @livewire('notifications.bell-prox-vto')
                </a>
                </li>

                {{-- Vencidos --}}
                <li class="nav-item">
                <a href="{{ route('vto.index') }}"
                    class="nav-link d-flex align-items-center justify-content-between {{ request()->routeIs('vto.index') ? 'active' : '' }}">
                    <span class="nav-label"><i class="fas fa-calendar-times nav-icon"></i><span>Vencidos</span></span>
                    @livewire('notifications.bell-vencidos')
                </a>
                </li>

                @endcan

                {{-- Mapa --}}
                <li class="nav-item">
                    <a href="{{ route('mapas') }}"
                       class="nav-link {{ request()->routeIs('mapas') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-map-marked-alt"></i>
                        <p>Mapa</p>
                    </a>
                </li>

                {{-- Comercios --}}
                @can('view-ubicaciones')
                <li class="nav-item">
                    <a href="{{ route('ubicaciones') }}"
                       class="nav-link {{ request()->routeIs('ubicaciones') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-list"></i>
                        <p>Comercios</p>
                    </a>
                </li>
                @endcan

                @can('manage-ubicaciones')
                <li class="nav-item">
                    <a href="{{ route('actas.seguimiento') }}"
                       class="nav-link {{ request()->routeIs('actas.seguimiento') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-clipboard-check"></i>
                        <p>Seguimiento de actas</p>
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

    .nav-sidebar .nav-link {
        min-height: 42px;
    }
    .nav-sidebar .nav-label {
        min-width: 0;
        display: flex;
        align-items: center;
        flex: 1 1 auto;
    }
    .nav-sidebar .nav-label > span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .sidebar-notification {
        position: relative;
        flex: 0 0 34px;
        width: 34px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
    }
    .sidebar-notification-count {
        position: absolute;
        top: -3px;
        right: -2px;
        min-width: 18px;
        height: 18px;
        padding: 0 4px;
        border-radius: 9px;
        background: #dc3545;
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        line-height: 18px;
        text-align: center;
        box-shadow: 0 0 0 2px #343a40;
    }
</style>
