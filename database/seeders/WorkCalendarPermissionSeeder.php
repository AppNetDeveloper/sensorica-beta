<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class WorkCalendarPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Crear permisos para el calendario laboral
        $permissions = [
            'workcalendar-list',
            'workcalendar-create',
            'workcalendar-edit',
            'workcalendar-delete',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Asignar permisos al rol de administrador
        $role = Role::where('name', 'Admin')->first();
        if ($role) {
            $role->givePermissionTo($permissions);
        }

        // Asignar permisos al rol de supervisor si existe
        $supervisorRole = Role::where('name', 'Supervisor')->first();
        if ($supervisorRole) {
            $supervisorRole->givePermissionTo([
                'workcalendar-list',
                'workcalendar-create',
                'workcalendar-edit',
            ]);
        }

        $this->command->info('Permisos de calendario laboral creados correctamente.');
    }
}
