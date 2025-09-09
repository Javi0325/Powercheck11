<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * php artisan shield:generate --all --panel=powerCheck crear los permisos
     * php artisan db:seed --class=RolesSeeder
     * crear un usuario super_admin
     * php artisan tinker
     * $user = App\Models\User::find(1);
     * $user->assignRole('super_admin');
     */
    protected string $guard = 'web';
    public function run(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        //Roles
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => $this->guard]);
        $admin      = Role::firstOrCreate(['name' => 'admin',       'guard_name' => $this->guard]);
        $entrenador      = Role::firstOrCreate(['name' => 'entrenador',  'guard_name' => $this->guard]);
        $atleta    = Role::firstOrCreate(['name' => 'atleta',      'guard_name' => $this->guard]);

        $superAdmin->syncPermissions(
            Permission::query()->where('guard_name', $this->guard)->pluck('id')
        );
        $admin->syncPermissions(
            $this->permissionsForModules(['gimnasio', 'entrenador'])
        );
        $entrenador->syncPermissions(
            $this->permissionsForModules(['atleta'])
        );
        $atleta->syncPermissions([]);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
    protected function permissionsForModules(array $modules)
    {
        return Permission::query()
            ->where('guard_name', $this->guard)
            ->where(function ($q) use ($modules) {
                foreach ($modules as $m) {
                    $q->orWhere('name', 'like', '%_' . $m);
                }
            })
            ->pluck('id');
    }
}
