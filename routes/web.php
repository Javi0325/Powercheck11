<?php
use App\Models\Device;
use Illuminate\Support\Facades\Route;

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