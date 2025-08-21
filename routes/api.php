<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Device;

Route::get('/test', function () {
    return ['ok' => true];
});

// POST /api/devices/ping
Route::post('/devices/ping', function (Request $request) {
    // 1) Autorización por Bearer token
    $token = $request->bearerToken();
    if (!$token || $token !== config('services.esp32.token')) {
        return response()->json(['ok' => false, 'message' => 'Unauthorized'], 401);
    }

    // 2) Datos opcionales enviados por el ESP32
    $name   = $request->input('name', 'ESP32');
    $status = $request->input('status', 'ready'); // puedes mandar 'ready'/'busy', etc.

    // 3) Actualizar/crear por IP del request
    $device = Device::updateOrCreate(
        ['ip' => $request->ip()],
        [
            'name'      => $name,
            'status'    => $status,
            'last_seen' => now(),
        ]
    );

    return response()->json([
        'ok'     => true,
        'device' => $device,
    ]);
});