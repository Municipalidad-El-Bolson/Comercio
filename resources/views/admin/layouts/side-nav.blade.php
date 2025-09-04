<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{ url('/') }}" class="brand-link">
        <img src="{{ asset('images/MEB.webp') }}" alt="Logo" style="width:50px; height:auto;">
    </a>

    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column" style="height: 100%;">
        
        <!-- Sidebar Menu -->
        <nav class="mt-2 flex-grow-1">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                data-accordion="false">
                <li class="nav-item">
                    <a href="{{ route('mapas') }}" class="nav-link">
                        <i class="nav-icon fas fa-map-marked-alt"></i><p>Mapa</p>
                    </a>
                </li>

                @can('manage-ubicaciones')
                <li class="nav-item">
                    <a href="{{ route('ubicaciones') }}" class="nav-link">
                        <i class="nav-icon fas fa-list"></i><p>Ubicaciones</p>
                    </a>
                </li>
                @endcan

                @can('access-admin')
                <li class="nav-item">
                    <a href="{{ route('historial') }}" class="nav-link">
                        <i class="nav-icon fas fa-book-open"></i><p>Historial</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('reportes') }}" class="nav-link">
                        <i class="nav-icon far fa-calendar-check"></i><p>Reportes</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('register-user') }}" class="nav-link">
                        <i class="nav-icon fas fa-user-plus"></i><p>Usuario Nuevo</p>
                    </a>
                </li>
                @endcan
            </ul>
        </nav>

        <!-- Botón de Logout siempre abajo -->
        <div class="mt-auto p-3">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-danger w-100">
                    <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                </button>
            </form>
        </div>
    </div>
</aside>

