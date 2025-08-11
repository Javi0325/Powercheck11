<?php

namespace App\Filament\Resources\AtletaResource\Pages;

use App\Filament\Resources\AtletaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAtletas extends ListRecords
{
    protected static string $resource = AtletaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
