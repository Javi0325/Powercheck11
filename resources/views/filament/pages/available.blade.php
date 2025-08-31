<x-app-layout>
    <div class="p-4">
        <h2 class="text-xl font-bold mb-2">Dispositivos</h2>

        <div class="space-y-2" x-data x-init="setInterval(()=>location.reload(),10000)">
            @foreach($devices as $d)
                <div class="border rounded p-3 flex items-center justify-between">
                    <div>
                        <div class="font-semibold">{{ $d['name'] }} ({{ $d['ip'] }})</div>
                        <div class="mt-1">
                            @if($d['isAvailable'])
                                <span class="px-2 py-0.5 rounded bg-green-100 text-green-700 text-sm">Disponible</span>
                            @else
                                <span class="px-2 py-0.5 rounded bg-gray-100 text-gray-700 text-sm">Offline</span>
                            @endif

                            @if($d['assigned'])
                                <span class="ml-2 px-2 py-0.5 rounded bg-blue-100 text-blue-700 text-sm">Ocupado</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex gap-2">
                        @if($d['isAvailable'] && !$d['assigned'])
                            <form method="POST" action="{{ route('devices.claim', $d['id']) }}">
                                @csrf
                                <button class="px-3 py-1 rounded bg-indigo-600 text-white">Conectar</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('devices.release', $d['id']) }}">
                                @csrf
                                <button class="px-3 py-1 rounded bg-gray-200">Liberar</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>