<div class="mx-auto max-w-md">
    <h1 class="text-xl font-semibold mb-4">Registrar usuario</h1>

    @if (session('status'))
        <div class="p-3 mb-4 rounded bg-green-100 text-green-800">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit.prevent="submit" class="space-y-4">
        <div>
            <label for="name" class="block text-sm mb-1">Nombre</label>
            <input id="name" type="text" wire:model.defer="name" class="w-full border rounded px-3 py-2">
            @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="block text-sm mb-1">Email</label>
            <input id="email" type="email" wire:model.defer="email" class="w-full border rounded px-3 py-2">
            @error('email') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="role" class="block text-sm mb-1">Rol</label>
            <select id="role" wire:model="role" class="w-full border rounded px-3 py-2">
                <option value="" disabled>Seleccioná un rol</option>
                @foreach ($roleOptions as $opt)
                    <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                @endforeach
            </select>
            @error('role') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm mb-1">Contraseña</label>
            <input id="password" type="password" wire:model.defer="password" class="w-full border rounded px-3 py-2">
            @error('password') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm mb-1">Confirmar contraseña</label>
            <input id="password_confirmation" type="password" wire:model.defer="password_confirmation" class="w-full border rounded px-3 py-2">
        </div>

        <button type="submit" class="w-full rounded px-4 py-2 bg-black text-white">
            Registrar
        </button>
    </form>
</div>
