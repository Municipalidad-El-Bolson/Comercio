<div> {{--  ÚNICO ROOT ELEMENT para Livewire --}}
  <div class="container py-4">
    <div class="row justify-content-center">
      <div class="col-12 col-lg-10 col-xl-8">

        {{-- Título --}}
        <div class="text-center mb-3">
          <h1 class="m-0 pb-2 border-bottom" style="font-size:2.50rem;">Lista de usuarios</h1>
        </div>

        <div class="card shadow-sm border-0">
          <div class="card-body">

            {{-- Barra superior: buscador + tamaño página + crear --}}
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-2 mb-3">
              <div class="d-flex flex-wrap align-items-center gap-2">

                {{-- Buscador --}}
                <div class="input-group input-group-sm" style="min-width:260px;">
                  <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                  <input type="text"
                         class="form-control"
                         placeholder="Buscar nombre, email o rol…"
                         wire:model.debounce.300ms="search">
                </div>
              </div>

              <a href="{{ route('register-user') }}"
                 class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2">
                <i class="fas fa-user-plus"></i><span>Crear usuario</span>
              </a>
            </div>

            {{-- Mensaje de estado --}}
            @if (session('status'))
              <div class="alert alert-success py-2 mb-3">
                {{ session('status') }}
              </div>
            @endif

            {{-- Tabla --}}
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th role="button" wire:click="sortBy('name')" class="text-nowrap">
                      Nombre
                      @if($sortField==='name')
                        <small class="text-muted">{{ $sortDir==='asc'?'▲':'▼' }}</small>
                      @endif
                    </th>
                    <th role="button" wire:click="sortBy('email')" class="text-nowrap">
                      Email
                      @if($sortField==='email')
                        <small class="text-muted">{{ $sortDir==='asc'?'▲':'▼' }}</small>
                      @endif
                    </th>
                    <th>Rol</th>
                    <th class="text-end" style="width:140px;">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($users as $u)
                    <tr>
                      <td class="text-capitalize">{{ $u->name }}</td>
                      <td>{{ $u->email }}</td>
                      <td>
                        @php
                          $badge = match($u->role){
                            'admin'  => 'bg-primary',
                            'writer' => 'bg-warning text-dark',
                            default  => 'bg-secondary'
                          };
                        @endphp
                        <span class="badge {{ $badge }}">{{ ucfirst($u->role) }}</span>
                      </td>
                      <td class="text-end">
                        <button class="btn btn-outline-danger btn-sm"
                                wire:click="confirmDelete({{ $u->id }})">
                          <i class="fas fa-trash-alt me-1"></i> Eliminar
                        </button>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="4" class="text-center text-muted py-4">
                        No se encontraron usuarios.
                      </td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>

            {{-- Paginación --}}
            <div class="card-footer bg-white border-0">
              <nav class="d-flex justify-content-center">
                {{ $users->onEachSide(1)->links('pagination::bootstrap-4') }}
              </nav>
            </div>

          </div>
        </div>

      </div>
    </div>
  </div>

  {{-- Estilos coherentes con el resto del sistema --}}
  <style>
    .btn-primary { background:#2563eb; border-color:#2563eb; }
    .btn-primary:hover { background:#1d4ed8; border-color:#1d4ed8; }
  </style>
</div>
