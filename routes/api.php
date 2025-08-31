<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Device;
use App\Models\DeviceSession;
use App\Models\DeviceMetric;

Route::get('/test', fn() => ['ok' => true]);

// 1) HEARTBEAT / PING del ESP32 (sin token)
// Recomendado: deja 'throttle' para limitar abuso en LAN
Route::post('/devices/ping', function (Request $request) {
    // Identificamos el dispositivo por la IP de quien llama
    $device = Device::updateOrCreate(
        ['ip' => $request->ip()],
        [
            'name'      => $request->input('name', 'ESP32'),
            'status'    => 'ready',     // visible/disponible
            'last_seen' => now(),
        ]
    );

    $session = $device->activeSession()->first();

    return response()->json([
        'ok'                  => true,
        'device_id'           => $device->id,
        'is_available'        => $device->is_available,
        'assigned_athlete_id' => $session?->athlete_id,
        'at'                  => now()->toISOString(),
    ]);
})->middleware('throttle:30,1');

// 2) MÉTRICAS desde el ESP32 (sin token) – requiere sesión activa
Route::post('/devices/metrics', function (Request $request) {
    $device = Device::where('ip', $request->ip())->first();
    if (!$device) {
        return response()->json(['ok' => false, 'message' => 'Device not found'], 404);
    }

    $session = $device->activeSession()->first();
    if (!$session) {
        return response()->json(['ok' => false, 'message' => 'No active session'], 409);
    }

    $metric = DeviceMetric::create([
        'device_id'    => $device->id,
        'athlete_id'   => $session->athlete_id,
        'bpm'          => (float) $request->input('bpm', 0),
        'repeticiones' => (int) $request->input('repeticiones', 0),
    ]);

    // refresca last_seen
    $device->update(['last_seen' => now()]);

    return response()->json(['ok' => true, 'metric_id' => $metric->id]);
})->middleware('throttle:60,1');