<?php

namespace App\Filament\Resources\GimnasioResource\Pages;

use App\Filament\Resources\GimnasioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGimnasios extends ListRecords
{
    protected static string $resource = GimnasioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
