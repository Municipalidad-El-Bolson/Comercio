<div class="container-fluid py-4">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div><h1 class="h2 mb-1">Usuarios</h1><p class="text-muted mb-0">Administrá accesos y permisos del sistema.</p></div>
    <button type="button" class="btn btn-primary" wire:click="create"><i class="fas fa-user-plus me-1"></i> Nuevo usuario</button>
  </div>

  @if(session('status'))<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i>{{ session('status') }}</div>@endif
  @if(session('error'))<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}</div>@endif

  <div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3"><div class="row g-2 align-items-center"><div class="col-12 col-md-7"><div class="input-group"><span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span><input type="search" class="form-control" placeholder="Buscar por nombre, correo o rol" wire:model.live.debounce.300ms="search"></div></div><div class="col-6 col-md-2 ms-md-auto"><select class="form-select" wire:model.live="perPage"><option value="10">10 por página</option><option value="25">25 por página</option><option value="50">50 por página</option></select></div></div></div>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th role="button" wire:click="sortBy('name')">Usuario <i class="fas fa-sort ms-1 text-muted"></i></th><th role="button" wire:click="sortBy('email')">Correo</th><th>Rol</th><th>Sesión</th><th class="text-end">Acciones</th></tr></thead><tbody>
      @forelse($users as $u)<tr><td><div class="d-flex align-items-center"><div class="user-avatar me-2">{{ mb_strtoupper(mb_substr($u->name,0,1)) }}</div><div><div class="fw-semibold">{{ $u->name }}</div>@if(auth()->id()===$u->id)<small class="text-primary">Tu usuario</small>@endif</div></div></td><td>{{ $u->email }}</td><td><span class="badge role-{{ $u->role }}">{{ collect($roleOptions)->firstWhere('value',$u->role)['label'] ?? ucfirst($u->role) }}</span></td><td>@if($u->current_session_id)<span class="text-success small"><i class="fas fa-circle me-1" style="font-size:.5rem"></i>Activa</span>@else<span class="text-muted small">Sin sesión</span>@endif</td><td class="text-end text-nowrap"><button class="btn btn-outline-primary btn-sm me-1" wire:click="edit({{ $u->id }})" title="Editar"><i class="fas fa-edit"></i></button><button class="btn btn-outline-danger btn-sm" wire:click="confirmDelete({{ $u->id }})" wire:confirm="¿Eliminar al usuario {{ $u->name }}?" @disabled(auth()->id()===$u->id) title="Eliminar"><i class="fas fa-trash-alt"></i></button></td></tr>
      @empty<tr><td colspan="5" class="text-center text-muted py-5"><i class="fas fa-users fa-2x mb-2 d-block"></i>No se encontraron usuarios.</td></tr>@endforelse
    </tbody></table></div>
    @if($users->hasPages())<div class="card-footer bg-white">{{ $users->onEachSide(1)->links() }}</div>@endif
  </div>

  @if($showForm)
    <div class="user-modal-backdrop" wire:click.self="closeForm"><div class="user-modal card shadow-lg border-0">
      <div class="card-header bg-white d-flex justify-content-between align-items-center py-3"><div><h2 class="h5 mb-0">{{ $editingId ? 'Editar usuario' : 'Crear usuario' }}</h2><small class="text-muted">Los campos con * son obligatorios.</small></div><button type="button" class="btn-close" wire:click="closeForm" aria-label="Cerrar"></button></div>
      <form wire:submit="save"><div class="card-body"><div class="row g-3">
        <div class="col-12"><label class="form-label" for="user-name">Nombre completo *</label><input id="user-name" type="text" class="form-control @error('name') is-invalid @enderror" wire:model.blur="name" autocomplete="name" autofocus>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-12"><label class="form-label" for="user-email">Correo electrónico *</label><input id="user-email" type="email" class="form-control @error('email') is-invalid @enderror" wire:model.blur="email" autocomplete="off" placeholder="usuario@municipio.gob.ar">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-12"><label class="form-label" for="user-role">Rol *</label><select id="user-role" class="form-select @error('role') is-invalid @enderror" wire:model="role">@foreach($roleOptions as $opt)<option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>@endforeach</select>@error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror<div class="form-text">Administrador: acceso total · Escritor: gestiona comercios · Lector: consulta · Mesa: carga expedientes.</div></div>
        <div class="col-md-6"><label class="form-label" for="user-password">Contraseña {{ $editingId ? '' : '*' }}</label><input id="user-password" type="password" class="form-control @error('password') is-invalid @enderror" wire:model="password" autocomplete="new-password">@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-6"><label class="form-label" for="user-confirmation">Confirmar contraseña {{ $editingId ? '' : '*' }}</label><input id="user-confirmation" type="password" class="form-control" wire:model="password_confirmation" autocomplete="new-password"></div>
        <div class="col-12"><div class="form-text"><i class="fas fa-shield-alt me-1"></i>Mínimo 8 caracteres. En edición, dejala vacía para conservar la contraseña actual.</div></div>
      </div></div><div class="card-footer bg-light d-flex justify-content-end gap-2"><button type="button" class="btn btn-outline-secondary" wire:click="closeForm">Cancelar</button><button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save"><i class="fas fa-save me-1"></i>{{ $editingId ? 'Guardar cambios' : 'Crear usuario' }}</span><span wire:loading wire:target="save"><span class="spinner-border spinner-border-sm me-1"></span>Guardando...</span></button></div></form>
    </div></div>
  @endif

  <style>
    .user-avatar{width:38px;height:38px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:#e7f1ff;color:#0d6efd;font-weight:700}.badge[class*="role-"]{padding:.45rem .65rem}.role-admin{background:#fde2e2;color:#a61b1b}.role-writer{background:#e3f2fd;color:#075985}.role-reader{background:#edf2f7;color:#4a5568}.role-mesa{background:#fff3cd;color:#7a5500}.user-modal-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.58);z-index:1060;display:flex;align-items:center;justify-content:center;padding:1rem}.user-modal{width:min(680px,100%);max-height:calc(100vh - 2rem);overflow:auto;border-radius:.8rem}
  </style>
</div>
