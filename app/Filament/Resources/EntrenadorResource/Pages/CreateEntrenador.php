<?php

namespace App\Filament\Resources\EntrenadorResource\Pages;

use App\Filament\Resources\EntrenadorResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;

class CreateEntrenador extends CreateRecord
{
    protected static string $resource = EntrenadorResource::class;
    protected ?string $generatedPassword = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $password=Str::password(10);
        $this->generatedPassword = $password;

        $user= \App\Models\User::create([
            'name' => $data['user_name'] ,
            'apellidos'=>  $data['user_apellidos'],
            'email' => $data['user_email'],
            'password' => Hash::make($password),
            'celular' => $data['user_celular'],
        ]);
        $user->assignRole('entrenador');
        $data['user_id'] = $user->id;
        unset($data['user_name'], $data['user_apellidos'], $data['user_email'], $data['user_celular']);
        return $data;
    }
    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Entrenador creado exitosamente')
            ->body('La contraseña generada es: ' . $this->generatedPassword)
            ->success()
            ->persistent()
            ->send();
    }
}
