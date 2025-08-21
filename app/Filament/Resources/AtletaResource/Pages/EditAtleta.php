<?php

namespace App\Filament\Resources\AtletaResource\Pages;

use App\Filament\Resources\AtletaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAtleta extends EditRecord
{
    protected static string $resource = AtletaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['user'])) {
            $this->record->user()->update([
                'name' => $data['user']['name'],
                'email' => $data['user']['email'],
                'celular' => $data['user']['celular'],
            ]);
            unset($data['user']);
        }
        return $data;
    }
}
