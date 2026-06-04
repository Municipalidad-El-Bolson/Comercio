<div> {{-- ÚNICO ROOT ELEMENT para Livewire --}}

  <div class="container py-4">
    <div class="row justify-content-center">
      <div class="col-12 col-lg-10 col-xl-8">

        <div class="text-center mb-3">
          <h1 class="m-0 pb-2 border-bottom" style="font-size:2.50rem;">Lista de usuarios</h1>
        </div>

        <div class="card shadow-sm border-0">
          <div class="card-body">

            {{-- Barra superior --}}
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-2 mb-3">

              <div class="input-group input-group-sm" style="min-width:260px;">
                <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                <input type="text"
                       class="form-control"
                       placeholder="Buscar nombre, email o rol…"
                       wire:model.live.debounce.300ms="search">
              </div>

              <a href="{{ route('register-user') }}"
                 class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2">
                <i class="fas fa-user-plus"></i><span>Crear usuario</span>
              </a>

            </div>

            @if (session('status'))
              <div class="alert alert-success py-2 mb-3">
                {{ session('status') }}
              </div>
            @endif

            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th role="button" wire:click="sortBy('name')">Nombre</th>
                    <th role="button" wire:click="sortBy('email')">Email</th>
                    <th>Rol</th>
                    <th class="text-end">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($users as $u)
                    <tr>
                      <td>{{ $u->name }}</td>
                      <td>{{ $u->email }}</td>
                      <td>{{ ucfirst($u->role) }}</td>
                      <td class="text-end">
                        <button class="btn btn-outline-danger btn-sm"
                                wire:click="confirmDelete({{ $u->id }})">
                          <i class="fas fa-trash-alt me-1"></i> Eliminar
                        </button>
                      </td>
                    </tr>
                  @empty
                    <tr><td colspan="4" class="text-center py-4">No se encontraron usuarios.</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>

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

</div> {{-- FIN ROOT --}}
