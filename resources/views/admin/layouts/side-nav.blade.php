@php
    $entradaActiva = request()->routeIs('mesa.inbox') || request()->routeIs('mesa.historial*');
    $comerciosActivo = request()->routeIs('ubicaciones')
        || request()->routeIs('comercio.data')
        || request()->routeIs('prox_vto.index')
        || request()->routeIs('vto.index');
    $puedeVerComercios = auth()->user()?->can('view-ubicaciones') || auth()->user()?->can('administrative-user');
@endphp

<aside class="main-sidebar sidebar-dark-primary elevation-4 modern-sidebar">
    <a href="{{ url('/') }}" class="brand-link text-center">
        <span class="brand-emblem">
            <img src="{{ asset('images/MEB.webp') }}" alt="Municipalidad de El Bolsón">
        </span>
        <span class="brand-caption">Gestión de Comercio</span>
    </a>

    <div class="sidebar d-flex flex-column">
        <div class="sidebar-heading">Navegación</div>
        <nav class="flex-grow-1">
            <ul class="nav nav-pills nav-sidebar flex-column d-flex h-100" data-widget="treeview" role="menu" data-accordion="false">
                @can('administrative-user')
                    <li class="nav-item has-treeview {{ $entradaActiva ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link nav-parent {{ $entradaActiva ? 'active' : '' }}">
                            <i class="nav-icon fas fa-inbox"></i>
                            <p>Entrada <i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('mesa.inbox') }}" class="nav-link d-flex align-items-center justify-content-between {{ request()->routeIs('mesa.inbox') ? 'active' : '' }}">
                                    <span class="nav-label"><i class="far fa-circle nav-icon"></i><span>Mesa de entrada</span></span>
                                    @livewire('notifications.bell-mesa')
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('mesa.historial') }}" class="nav-link {{ request()->routeIs('mesa.historial*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Registro histórico</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endcan

                @if($puedeVerComercios)
                    <li class="nav-item has-treeview {{ $comerciosActivo ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link nav-parent {{ $comerciosActivo ? 'active' : '' }}">
                            <i class="nav-icon fas fa-store"></i>
                            <p>Comercios <i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('view-ubicaciones')
                                <li class="nav-item">
                                    <a href="{{ route('ubicaciones') }}" class="nav-link {{ request()->routeIs('ubicaciones') || request()->routeIs('comercio.data') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i><p>Lista</p>
                                    </a>
                                </li>
                            @endcan
                            @can('administrative-user')
                                <li class="nav-item">
                                    <a href="{{ route('prox_vto.index') }}" class="nav-link d-flex align-items-center justify-content-between {{ request()->routeIs('prox_vto.index') ? 'active' : '' }}">
                                        <span class="nav-label"><i class="far fa-circle nav-icon"></i><span>Próximos a vencer</span></span>
                                        @livewire('notifications.bell-prox-vto')
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('vto.index') }}" class="nav-link d-flex align-items-center justify-content-between {{ request()->routeIs('vto.index') ? 'active' : '' }}">
                                        <span class="nav-label"><i class="far fa-circle nav-icon"></i><span>Vencidos</span></span>
                                        @livewire('notifications.bell-vencidos')
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endif

                <li class="nav-item">
                    <a href="{{ route('mapas') }}" class="nav-link {{ request()->routeIs('mapas') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-map-marked-alt"></i><p>Mapa</p>
                    </a>
                </li>

                @can('manage-ubicaciones')
                    <li class="nav-item">
                        <a href="{{ route('actas.seguimiento') }}" class="nav-link {{ request()->routeIs('actas.seguimiento') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-clipboard-check"></i><p>Seguimiento de actas</p>
                        </a>
                    </li>
                @endcan

                @can('access-admin')
                    <li class="nav-item">
                        <a href="{{ route('historial') }}" class="nav-link {{ request()->routeIs('historial') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-book-open"></i><p>Auditoría</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('reportes') }}" class="nav-link {{ request()->routeIs('reportes') ? 'active' : '' }}">
                            <i class="nav-icon far fa-chart-bar"></i><p>Reportes</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.index') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users-cog"></i><p>Usuarios</p>
                        </a>
                    </li>
                @endcan

                <li class="nav-item mt-auto sidebar-logout">
                    <form action="{{ route('logout') }}" method="POST" class="m-0">@csrf
                        <button type="submit" class="nav-link d-flex align-items-center w-100 border-0">
                            <i class="nav-icon fas fa-sign-out-alt"></i><p>Cerrar sesión</p>
                        </button>
                    </form>
                </li>
            </ul>
        </nav>
    </div>
</aside>

<style>
    .modern-sidebar { background: linear-gradient(180deg, #112b46 0%, #0b1f34 58%, #071827 100%) !important; }
    .modern-sidebar .brand-link { display:flex; flex-direction:column; align-items:center; gap:.45rem; padding:1.05rem .8rem .9rem; border-bottom:1px solid rgba(255,255,255,.08); background:rgba(255,255,255,.025); }
    .modern-sidebar .brand-emblem { display:flex; align-items:center; justify-content:center; width:176px; min-height:58px; padding:.35rem .65rem; border-radius:14px; background:rgba(255,255,255,.96); box-shadow:0 10px 24px rgba(0,0,0,.18); }
    .modern-sidebar .brand-emblem img { width:145px; max-height:54px; object-fit:contain; }
    .modern-sidebar .brand-caption { color:#c9d9e8; font-size:.71rem; font-weight:700; letter-spacing:.11em; text-transform:uppercase; }
    .modern-sidebar .sidebar { min-height:calc(100vh - 111px); padding:.65rem .55rem 1rem; }
    .modern-sidebar .sidebar-heading { padding:.3rem .75rem .55rem; color:#7190aa; font-size:.68rem; font-weight:800; letter-spacing:.13em; text-transform:uppercase; }
    .modern-sidebar .nav-sidebar .nav-item { margin-bottom:.18rem; }
    .modern-sidebar .nav-sidebar .nav-link { min-height:44px; display:flex; align-items:center; border-radius:10px; color:#c8d7e5; transition:background-color .18s ease, color .18s ease, transform .18s ease; }
    .modern-sidebar .nav-sidebar .nav-link:hover { color:#fff; background:rgba(75,156,211,.16); transform:translateX(2px); }
    .modern-sidebar .nav-sidebar .nav-link.active { color:#fff !important; background:linear-gradient(135deg,#1976b9,#138c9f) !important; box-shadow:0 7px 16px rgba(0,89,142,.25); }
    .modern-sidebar .nav-sidebar .nav-icon { width:1.55rem; margin-right:.45rem; color:#85abc7; font-size:.96rem; }
    .modern-sidebar .nav-sidebar .nav-link.active .nav-icon { color:#fff; }
    .modern-sidebar .nav-sidebar .nav-treeview { margin:.25rem 0 .45rem; padding:.15rem 0 .15rem .45rem; border-left:1px solid rgba(106,178,216,.2); }
    .modern-sidebar .nav-sidebar .nav-treeview .nav-link { min-height:39px; font-size:.91rem; }
    .modern-sidebar .nav-sidebar .nav-treeview .nav-icon { font-size:.48rem; }
    .modern-sidebar .nav-sidebar .nav-label { min-width:0; display:flex; align-items:center; flex:1 1 auto; }
    .modern-sidebar .nav-sidebar .nav-label > span { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .modern-sidebar .sidebar-logout { padding-top:.6rem; border-top:1px solid rgba(255,255,255,.08); }
    .modern-sidebar .sidebar-logout button { color:#ffcbcb; background:rgba(217,70,70,.11); }
    .modern-sidebar .sidebar-logout button:hover { color:#fff; background:rgba(217,70,70,.3); }
    .sidebar-notification { position:relative; flex: 0 0 34px; width:34px; height:28px; display:inline-flex; align-items:center; justify-content:center; pointer-events: none; }
    .sidebar-notification-count { position:absolute; top:-3px; right:-2px; min-width:18px; height:18px; padding:0 4px; border-radius:9px; background:#e64646; color:#fff; font-size:10px; font-weight:800; line-height:18px; text-align:center; box-shadow:0 0 0 2px #102942; }
</style>
