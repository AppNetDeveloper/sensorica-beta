<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class FleetPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $perms = [
            'fleet-view',
            'fleet-create',
            'fleet-edit',
            'fleet-delete',
        ];
        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        // Assign to admin role if present
        if ($admin = Role::where('name', 'admin')->first()) {
            foreach ($perms as $p) {
                $perm = Permission::where('name', $p)->first();
                if ($perm && !$admin->hasPermissionTo($perm)) {
                    $admin->givePermissionTo($perm);
                }
            }
        }
    }
}
