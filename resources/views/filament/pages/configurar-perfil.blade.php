{{-- resources/views/filament/pages/configurar-perfil.blade.php --}}
<x-filament-panels::page>
    {{-- Formulario de Usuario (siempre) --}}
    {{ $this->getForm('formUser') }}

    {{-- Solo si es entrenador --}}
    @if (auth()->user()?->hasRole('entrenador'))
        <div class="mt-6">
            {{ $this->getForm('formEntrenador') }}
        </div>
    @endif

    {{-- Solo si es atleta --}}
    @if (auth()->user()?->hasRole('atleta'))
        <div class="mt-6">
            {{ $this->getForm('formAtleta') }}
        </div>
    @endif
</x-filament-panels::page>
