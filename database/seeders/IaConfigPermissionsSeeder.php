<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class IaConfigPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $perms = [
            'ia-config.update',
        ];

        foreach ($perms as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Assign to admin role if exists
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo($perms);
        }

        $this->command?->info('✓ IA Config permissions seeded successfully');
    }
}