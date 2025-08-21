<x-filament::page>
    <h1 class="text-xl font-bold mb-4">Dispositivos ESP32 disponibles</h1>
    <ul>
        @foreach($this->devices as $device)
            <li class="border-b py-2">
                {{ $device->name }} - {{ $device->ip }} (Última conexión: {{ $device->last_seen }})
            </li>
        @endforeach
    </ul>
</x-filament::page>