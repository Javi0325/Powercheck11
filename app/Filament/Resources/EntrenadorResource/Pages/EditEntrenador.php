<?php

namespace App\Filament\Resources\EntrenadorResource\Pages;

use App\Filament\Resources\EntrenadorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEntrenador extends EditRecord
{
    protected static string $resource = EntrenadorResource::class;

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
