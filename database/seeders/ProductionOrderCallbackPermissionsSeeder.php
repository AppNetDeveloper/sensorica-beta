<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ProductionOrderCallbackPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $perms = [
            'callbacks.view',
            'callbacks.update',
            'callbacks.delete',
            'callbacks.force',
        ];

        foreach ($perms as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        // Assign to admin role if exists
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo($perms);
        }
    }
}
