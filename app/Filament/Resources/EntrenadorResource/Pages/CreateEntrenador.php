<?php

namespace App\Filament\Resources\EntrenadorResource\Pages;

use App\Filament\Resources\EntrenadorResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class CreateEntrenador extends CreateRecord
{
    protected static string $resource = EntrenadorResource::class;
    protected ?string $generatedPassword = null;

    protected function shouldSaveRelationships(): bool
    {
        return false;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $userData = data_get($this->data, 'user', []);

        $name = trim((string) data_get($userData, 'name', ''));
        $apellidos = trim((string) data_get($userData, 'apellidos', ''));
        $email = trim((string) data_get($userData, 'email', ''));
        $celular = trim((string) data_get($userData, 'celular', ''));
        $password = Str::random(10);
        $this->generatedPassword = $password;

        return DB::transaction(function () use ($data, $name, $apellidos, $email, $celular, $password) {
            $user = User::create([
                'name' => $name,
                'apellidos' => $apellidos,
                'email' => $email,
                'celular' => $celular,
                'password' => Hash::make($password),
            ]);
            $user->assignRole('entrenador');
            $data['user_id'] = $user->id;
            $model = static::getModel();
            return $model::create($data);
        });
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
