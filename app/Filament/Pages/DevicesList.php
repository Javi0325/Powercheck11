<?php
namespace App\Filament\Pages;

use App\Models\Device;
use Filament\Pages\Page;
use Filament\Facades\Filament;

class DevicesList extends Page
{
    protected static ?string $navigationIcon = 'heroicon-s-wifi';
    protected static string $view = 'filament.pages.devices-list';
    protected static ?string $title = 'Dispositivos';

    public function getDevicesProperty()
    {
        return Device::where('status', 'ready')->orderBy('last_seen', 'desc')->get();
    }
}