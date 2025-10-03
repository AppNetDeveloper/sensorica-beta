<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class VendorProcurementPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissionGroups = [
            'vendor-suppliers' => ['view', 'create', 'edit', 'delete'],
            'vendor-items' => ['view', 'create', 'edit', 'delete'],
            'vendor-orders' => ['view', 'create', 'edit', 'delete'],
        ];

        foreach ($permissionGroups as $prefix => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => sprintf('%s-%s', $prefix, $action),
                    'guard_name' => 'web',
                ]);
            }
        }

        if ($admin = Role::where('name', 'admin')->first()) {
            foreach ($permissionGroups as $prefix => $actions) {
                foreach ($actions as $action) {
                    $permissionName = sprintf('%s-%s', $prefix, $action);
                    $permission = Permission::where('name', $permissionName)->first();
                    if ($permission && !$admin->hasPermissionTo($permission)) {
                        $admin->givePermissionTo($permission);
                    }
                }
            }
        }

        $this->command?->info('✓ Vendor procurement permissions seeded successfully');
    }
}
