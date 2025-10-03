<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AssetManagementPermissionsSeeder extends Seeder
{
    private array $permissionGroups = [
        'asset-categories' => ['view', 'create', 'edit', 'delete'],
        'asset-cost-centers' => ['view', 'create', 'edit', 'delete'],
        'asset-locations' => ['view', 'create', 'edit', 'delete'],
        'assets' => ['view', 'create', 'edit', 'delete', 'print-label'],
        'asset-receipts' => ['view', 'create', 'edit', 'delete'],
    ];

    public function run(): void
    {
        foreach ($this->permissionGroups as $prefix => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => sprintf('%s-%s', $prefix, $action),
                    'guard_name' => 'web',
                ]);
            }
        }

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

        $this->command?->info('✓ Asset management permissions seeded successfully');
    }
}
