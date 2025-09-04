<div class="mx-auto max-w-md">
    <div class="bg-white shadow-lg rounded-lg p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-6 text-center">
            Registrar Usuario
        </h1>

        @if (session('status'))
            <div class="p-3 mb-6 rounded bg-green-100 text-green-800 text-sm font-medium">
                {{ session('status') }}
            </div>
        @endif

        <form wire:submit.prevent="submit" class="space-y-6">
            <!-- Nombre -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nombre</label>
                <input id="name" type="text" wire:model.defer="name"
                       class="w-full border-gray-300 rounded-lg shadow-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('name') 
                    <p class="text-red-600 text-xs mt-2">{{ $message }}</p> 
                @enderror
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input id="email" type="email" wire:model.defer="email"
                       class="w-full border-gray-300 rounded-lg shadow-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('email') 
                    <p class="text-red-600 text-xs mt-2">{{ $message }}</p> 
                @enderror
            </div>

            <!-- Rol -->
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Rol</label>
                <select id="role" wire:model="role"
                        class="w-full border-gray-300 rounded-lg shadow-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="" disabled>Seleccioná un rol</option>
                    @foreach ($roleOptions as $opt)
                        <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                    @endforeach
                </select>
                @error('role') 
                    <p class="text-red-600 text-xs mt-2">{{ $message }}</p> 
                @enderror
            </div>

            <!-- Contraseña -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Contraseña</label>
                <input id="password" type="password" wire:model.defer="password"
                       class="w-full border-gray-300 rounded-lg shadow-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('password') 
                    <p class="text-red-600 text-xs mt-2">{{ $message }}</p> 
                @enderror
            </div>

            <!-- Confirmar contraseña -->
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirmar contraseña</label>
                <input id="password_confirmation" type="password" wire:model.defer="password_confirmation"
                       class="w-full border-gray-300 rounded-lg shadow-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Botón -->
            <div class="flex justify-center mt-8">
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition btn-dark">
                    Registrar
                </button>
            </div>
        </form>
    </div>
</div>
