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
     */
    public function run(): void
    {
        //Roles
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $entrenador   = Role::firstOrCreate(['name' => 'entrenador']);
        $atleta   = Role::firstOrCreate(['name' => 'atleta']);
    }
}
