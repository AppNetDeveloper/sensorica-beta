<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ProductionLineOrdersKanbanPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear el nuevo permiso
        $permission = 'productionline-orders-kanban';
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);

        // Solo asignar el permiso al rol admin, no a otros roles

        // También asignarlo al rol admin
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permission);
        }

        $this->command->info('Permiso productionline-orders-kanban creado y asignado correctamente.');
    }
}
