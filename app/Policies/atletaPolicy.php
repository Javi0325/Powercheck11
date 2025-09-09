<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Atleta;
use Illuminate\Auth\Access\HandlesAuthorization;

class AtletaPolicy
{
    use HandlesAuthorization;

    /**
     * Bypass total para admins (antes de otros checks).
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasAnyRole(['super_admin'])) {
            return true;
        }
        return null;
    }

    protected function entrenadorId(User $user): ?int
    {
        if (isset($user->entrenador)) {
            return $user->entrenador->id ?? null;
        }

        return $user->id;
    }

    /**
     * Comprueba si el usuario es dueño del atleta.
     * (Si no es dueño, no puede ver ni editar).
     */
    protected function owns(User $user, Atleta $atleta): bool
    {
        $eid = $this->entrenadorId($user);
        return !is_null($eid) && $atleta->entrenador_id === $eid;
    }

    /**
     * Listado (se controla mejor con getEloquentQuery() en el Resource).
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_atleta');
    }

    /**
     * Ver un registro concreto (bloquea acceso por URL si no es dueño).
     */
    public function view(User $user, Atleta $atleta): bool
    {
        return $user->can('view_atleta') && $this->owns($user, $atleta);
    }

    /**
     * Crear (permite a entrenadores crear).
     */
    public function create(User $user): bool
    {
        return $user->can('create_atleta');
    }

    /**
     * Actualizar (solo dueño).
     */
    public function update(User $user, Atleta $atleta): bool
    {
        return $user->can('update_atleta') && $this->owns($user, $atleta);
    }

    /**
     * Eliminar (solo dueño).
     */
    public function delete(User $user, Atleta $atleta): bool
    {
        return $user->can('delete_atleta') && $this->owns($user, $atleta);
    }

    /**
     * Eliminación masiva: permite si puede y (opcional) validas dueño en la acción.
     * (Filament aplica por lote; conviene filtrar records antes de ejecutar).
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_atleta');
    }

    public function forceDelete(User $user, Atleta $atleta): bool
    {
        return $user->can('force_delete_atleta') && $this->owns($user, $atleta);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_atleta');
    }

    public function restore(User $user, Atleta $atleta): bool
    {
        return $user->can('restore_atleta') && $this->owns($user, $atleta);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_atleta');
    }

    public function replicate(User $user, Atleta $atleta): bool
    {
        return $user->can('replicate_atleta') && $this->owns($user, $atleta);
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_atleta');
    }
}
