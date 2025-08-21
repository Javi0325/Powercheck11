<x-filament::page>
    <div class="space-y-4">
        <h1 class="text-2xl font-bold">¡Bienvenido, atleta!</h1>
        <p>Esta es tu sección especial con información extra.</p>
        <x-filament::button color="primary" tag="a" href="{{ route('home') }}">
            Ir a la página principal
        </x-filament::button>
    </div>
</x-filament::page>