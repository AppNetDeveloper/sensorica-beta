<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ProductionLineArticlePermissionsSeeder extends Seeder
{
    private array $permissionGroups = [
        'productionline-article' => ['view', 'create', 'edit', 'delete', 'bulk-delete'],
    ];

    public function run(): void
    {
        // Crear permisos para artículos de líneas de producción
        foreach ($this->permissionGroups as $prefix => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => sprintf('%s-%s', $prefix, $action),
                    'guard_name' => 'web',
                ]);
            }
        }

        // Asignar permisos al rol admin si existe
        if ($admin = Role::where('name', 'admin')->first()) {
            foreach ($this->permissionGroups as $prefix => $actions) {
                foreach ($actions as $action) {
                    $permissionName = sprintf('%s-%s', $prefix, $action);
                    $permission = Permission::where('name', $permissionName)->first();
                    if ($permission && !$admin->hasPermissionTo($permission)) {
                        $admin->givePermissionTo($permission);
                    }
                }
            }
        }

        $this->command?->info('✓ Production line article permissions seeded successfully');
    }
}