<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class OriginalOrderFilePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'original-order-files-upload',
            'original-order-files-delete',
        ];

        $created = [];
        foreach ($permissions as $name) {
            $perm = Permission::where('name', $name)->first();
            if (!$perm) {
                Permission::create(['name' => $name]);
                $this->command?->info("Permiso '{$name}' creado.");
            } else {
                $this->command?->info("Permiso '{$name}' ya existe.");
            }
            $created[] = $name;
        }

        // Asignación por defecto a roles comunes
        if ($role = Role::where('name', 'Admin')->first()) {
            $role->givePermissionTo($created);
            $this->command?->info("Permisos de archivos asignados a 'Admin'.");
        }

        if ($role = Role::where('name', 'Supervisor')->first()) {
            // Supervisores pueden subir pero no borrar por defecto
            $role->givePermissionTo(['original-order-files-upload']);
            $this->command?->info("Permisos de subida asignados a 'Supervisor'.");
        }
    }
}
