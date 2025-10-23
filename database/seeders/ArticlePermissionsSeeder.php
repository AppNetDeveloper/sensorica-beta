<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ArticlePermissionsSeeder extends Seeder
{
    private array $permissionGroups = [
        'article-family' => ['show', 'create', 'edit', 'delete'],
        'article' => ['show', 'create', 'edit', 'delete'],
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

        $this->command?->info('✓ Article permissions seeded successfully');
    }
}