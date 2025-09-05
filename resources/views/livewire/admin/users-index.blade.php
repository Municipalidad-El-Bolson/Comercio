
<div> {{-- ÚNICO ROOT --}}
  <div class="container-fluid pt-4"> {{-- separación superior --}}
    <div class="row justify-content-center">
      <div class="col-12 col-lg-10 col-xl-8">

        {{-- Título apoyado sobre la card --}}
        <div class="content-header py-0 mb-0 text-center">
          <h1 class="m-0 pb-2 border-bottom">Lista de usuarios</h1>
        </div>

        <div class="card shadow-sm mt-0">
          <div class="card-body">

            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-2 mb-3">
              <div class="d-flex gap-2 align-items-center">
                {{-- Buscador automático --}}
                <input type="text"
                       class="form-control"
                       style="min-width:220px"
                       placeholder="Buscar nombre, email o rol…"
                       wire:model.debounce.300ms="search">

                <select wire:model="perPage" class="form-select form-select-sm" style="width:auto;">
                  <option value="5">5</option>
                  <option value="10">10</option>
                  <option value="25">25</option>
                </select>
              </div>
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
                    <th role="button" wire:click="sortBy('name')" class="text-nowrap">
                      Nombre
                      @if($sortField==='name') <small>{{ $sortDir==='asc'?'▲':'▼' }}</small>@endif
                    </th>
                    <th role="button" wire:click="sortBy('email')" class="text-nowrap">
                      Email
                      @if($sortField==='email') <small>{{ $sortDir==='asc'?'▲':'▼' }}</small>@endif
                    </th>
                    <th>Rol</th>
                    <th class="text-end" style="width:160px;">Eliminar usuario</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($users as $u)
                    <tr>
                      <td>{{ $u->name }}</td>
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
                        <button class="btn btn-outline-danger content-center" wire:click="confirmDelete({{ $u->id }})">
                          <i class="fas fa-trash-alt"></i>
                        </button>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="4" class="text-center text-muted py-4">No se encontraron usuarios.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>

            <div class="mt-3">
              {{ $users->onEachSide(1)->links() }}
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
