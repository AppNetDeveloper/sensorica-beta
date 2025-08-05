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

        // Crear permisos solo si no existen
        $createdPermissions = [];
        foreach ($permissions as $permission) {
            $existingPermission = Permission::where('name', $permission)->first();
            if (!$existingPermission) {
                Permission::create(['name' => $permission]);
                $createdPermissions[] = $permission;
                $this->command->info("Permiso '{$permission}' creado correctamente.");
            } else {
                $this->command->info("Permiso '{$permission}' ya existe, omitiendo.");
                $createdPermissions[] = $permission;
            }
        }

        // Asignar permisos al rol de administrador
        $role = Role::where('name', 'Admin')->first();
        if ($role) {
            $role->givePermissionTo($createdPermissions);
            $this->command->info("Permisos asignados al rol 'Admin'.");
        } else {
            $this->command->warn("Rol 'Admin' no encontrado. No se pudieron asignar permisos.");
        }

        // Asignar permisos al rol de supervisor si existe
        $supervisorRole = Role::where('name', 'Supervisor')->first();
        if ($supervisorRole) {
            $supervisorPermissions = [
                'workcalendar-list',
                'workcalendar-create',
                'workcalendar-edit',
            ];
            $supervisorRole->givePermissionTo($supervisorPermissions);
            $this->command->info("Permisos asignados al rol 'Supervisor'.");
        } else {
            $this->command->info("Rol 'Supervisor' no encontrado. No se asignaron permisos de supervisor.");
        }

        $this->command->info('Proceso de permisos de calendario laboral completado correctamente.');
    }
}
