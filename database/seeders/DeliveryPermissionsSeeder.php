<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DeliveryPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Crear permiso para ver entregas
        $permission = Permission::firstOrCreate([
            'name' => 'deliveries-view',
            'guard_name' => 'web'
        ]);

        // Asignar a rol admin por defecto
        if ($admin = Role::where('name', 'admin')->first()) {
            if (!$admin->hasPermissionTo($permission)) {
                $admin->givePermissionTo($permission);
            }
        }

        // Crear rol de transportista si no existe
        $driverRole = Role::firstOrCreate([
            'name' => 'driver',
            'guard_name' => 'web'
        ]);

        // Asignar permiso al rol driver
        if (!$driverRole->hasPermissionTo($permission)) {
            $driverRole->givePermissionTo($permission);
        }

        $this->command->info('✓ Delivery permissions created successfully');
        $this->command->info('✓ Permission "deliveries-view" assigned to admin and driver roles');
    }
}
