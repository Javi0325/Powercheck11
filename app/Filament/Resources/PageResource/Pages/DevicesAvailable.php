<?php

namespace App\Filament\Resources\PageResource\Pages;

use App\Filament\Resources\PageResource;
use Filament\Resources\Pages\Page;

class DevicesAvailable extends Page
{
    protected static string $resource = PageResource::class;

    protected static string $view = 'filament.resources.page-resource.pages.devices-available';
}
