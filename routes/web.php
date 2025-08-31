<?php
use App\Models\Device;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\DeviceSession;
use Illuminate\Support\Carbon;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/home', function () {
    return view('welcome');
})->name('home');


//Route::get('/powerCheck/devices-list', function () {
    // Trae todos los dispositivos de la base de datos
    //$devices = Device::all();

    // Devuelve la lista como JSON
    //return response()->json([
    //    'success' => true,
    //    'devices' => $devices
    //]);
//});


Route::middleware(['auth'])->group(function () {

    // Vista simple: lista de dispositivos con su disponibilidad y ocupación
    Route::get('/devices/available', function () {
        $devices = Device::orderByDesc('last_seen')->get()
            ->map(fn($d) => [
                'id'         => $d->id,
                'name'       => $d->name,
                'ip'         => $d->ip,
                'isAvailable'=> $d->is_available,
                'assigned'   => (bool)$d->assigned_athlete_id,
            ]);
        return view('devices.available', compact('devices'));
    })->name('devices.available');

    // Reclamar un dispositivo (el atleta lo conecta a su sesión)
    Route::post('/devices/{device}/claim', function (Request $request, Device $device) {
        if (!$device->is_available) {
            return back()->withErrors('El dispositivo no está disponible ahora.');
        }

        $active = $device->activeSession()->first();
        if ($active && $active->athlete_id !== auth()->id()) {
            return back()->withErrors('El dispositivo ya está ocupado.');
        }

        if (!$active) {
            DeviceSession::create([
                'device_id'  => $device->id,
                'athlete_id' => auth()->id(),
                'status'     => 'active',
                'started_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addMinutes(30), // opcional
            ]);
        }

        return back()->with('status', '¡Dispositivo conectado!');
    })->name('devices.claim');

    // Liberar dispositivo
    Route::post('/devices/{device}/release', function (Request $request, Device $device) {
        $active = $device->activeSession()->first();
        if ($active && $active->athlete_id === auth()->id()) {
            $active->update(['status' => 'ended', 'ended_at' => now()]);
        }
        return back()->with('status', 'Dispositivo liberado.');
    })->name('devices.release');
});