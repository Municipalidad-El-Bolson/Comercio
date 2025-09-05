<div> {{-- ÚNICO ROOT --}}
  <div class="container-fluid pt-4">

    <div class="row justify-content-center">
      <div class="col-12 col-md-8 col-lg-6">
        
        {{-- Título apoyado sobre la card --}}
        <div class="content-header py-0 mb-0 text-center">
          <h1 class="m-0 pb-2 border-bottom">Registrar usuarios</h1>
        </div>

        <div class="card shadow-sm mt-0">
          <div class="card-body">
            
            {{-- Mensaje de estado --}}
            @if (session('status'))
              <div class="alert alert-success py-2 mb-4">
                {{ session('status') }}
              </div>
            @endif

            {{-- Formulario --}}
            <form wire:submit.prevent="submit" class="row g-3">
              {{-- Nombre --}}
              <div class="col-12">
                <label for="name" class="form-label fw-medium">Nombre</label>
                <input id="name" type="text" wire:model.defer="name"
                       class="form-control form-control-sm @error('name') is-invalid @enderror">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              {{-- Email --}}
              <div class="col-12">
                <label for="email" class="form-label fw-medium">Email</label>
                <input id="email" type="email" wire:model.defer="email"
                       class="form-control form-control-sm @error('email') is-invalid @enderror">
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              {{-- Contraseña --}}
              <div class="col-md-6">
                <label for="password" class="form-label fw-medium">Contraseña</label>
                <input id="password" type="password" wire:model.defer="password"
                       class="form-control form-control-sm @error('password') is-invalid @enderror">
                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              {{-- Rol --}}
              <div class="col-md-6">
                <label for="role" class="form-label fw-medium">Rol</label>
                <select id="role" wire:model="role"
                        class="form-select form-select-sm @error('role') is-invalid @enderror">
                  <option value="" disabled>Seleccioná un rol</option>
                  @foreach ($roleOptions as $opt)
                    <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                  @endforeach
                </select>
                @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              {{-- Confirmar contraseña --}}
              <div class="col-md-6">
                <label for="password_confirmation" class="form-label fw-medium">Confirmar contraseña</label>
                <input id="password_confirmation" type="password" wire:model.defer="password_confirmation"
                       class="form-control form-control-sm">
              </div>

              {{-- Botón Registrar --}}
              <div class="col-md-6 d-flex align-items-end justify-content-end">
                <button type="submit" class="btn btn-primary px-4">
                  <i class="fas fa-save me-1"></i> Registrar
                </button>
              </div>
            </form>

          </div>
        </div>

      </div>
    </div>
  </div>
</div>
