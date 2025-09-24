<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CustomerClientsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $perms = [
            'customer-clients-view',
            'customer-clients-create',
            'customer-clients-edit',
            'customer-clients-delete',
        ];
        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }
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
