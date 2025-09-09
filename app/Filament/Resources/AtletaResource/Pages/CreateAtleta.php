<?php

namespace App\Filament\Resources\AtletaResource\Pages;

use App\Filament\Resources\AtletaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class CreateAtleta extends CreateRecord
{
    protected static string $resource = AtletaResource::class;
    protected ?string $generatedPassword = null;

    protected function shouldSaveRelationships(): bool
    {
        return false;
    }
    protected function handleRecordCreation(array $data): Model
    {
        $userData  = data_get($this->data, 'user', []);
        $name      = trim((string) data_get($userData, 'name', ''));
        $apellidos = trim((string) data_get($userData, 'apellidos', ''));
        $email     = trim((string) data_get($userData, 'email', ''));
        $celular   = trim((string) data_get($userData, 'celular', ''));

        $password = Str::random(10);
        $this->generatedPassword = $password;
        // Entrenador autenticado
        $authUser = Auth::user();
        $authUser = auth()->user();

        // ADAPTA estas dos líneas según tu modelo:
        // Opción 1: si el entrenador ES el User autenticado (sin tabla Entrenador)
        // $entrenadorId = $authUser->id;
        // $gimnasioId   = $authUser->gimnasio_id;

        // Opción 2: si tienes modelo Entrenador relacionado al User
        $entrenadorId = optional($authUser->entrenador)->id;
        $gimnasioId   = optional($authUser->entrenador)->gimnasio_id;

        if (blank($entrenadorId) || blank($gimnasioId)) {
            throw ValidationException::withMessages([
                'entrenador_id' => 'No se pudo determinar el entrenador actual.',
                'gimnasio_id'   => 'No se pudo determinar el gimnasio del entrenador.',
            ]);
        }

        return DB::transaction(function () use (
            $data,
            $name,
            $apellidos,
            $email,
            $celular,
            $password,
            $entrenadorId,
            $gimnasioId
        ) {
            $user = \App\Models\User::create([
                'name'      => $name,
                'apellidos' => $apellidos,
                'email'     => $email,
                'celular'   => $celular,
                'password'  => Hash::make($password),
            ]);

            $user->assignRole('atleta');

            $data['user_id']       = $user->id;
            $data['entrenador_id'] = $entrenadorId;
            $data['gimnasio_id']   = $gimnasioId;

            // 3) Crear el Atleta (modelo del recurso actual)
            /** @var class-string<Model> $model */
            $model = static::getModel();
            return $model::create($data);
        });
    }
    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Atleta creado exitosamente')
            ->body("El atleta ha sido creado con éxito. Contraseña temporal: {$this->generatedPassword}")
            ->success()
            ->send();
    }
}
