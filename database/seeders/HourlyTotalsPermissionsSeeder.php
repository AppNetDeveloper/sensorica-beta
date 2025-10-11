<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class HourlyTotalsPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear el permiso para ver totales horarios
        $permission = Permission::firstOrCreate(
            ['name' => 'hourly-totals-view'],
            ['name' => 'hourly-totals-view']
        );

        // Asignar el permiso al rol de admin
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $alreadyAssigned = DB::table('role_has_permissions')
                ->where('role_id', $adminRole->id)
                ->where('permission_id', $permission->id)
                ->exists();

            if (!$alreadyAssigned) {
                DB::table('role_has_permissions')->insert([
                    'role_id' => $adminRole->id,
                    'permission_id' => $permission->id,
                ]);
                $this->command->info('✓ Permiso "hourly-totals-view" asignado al rol Admin');
            } else {
                $this->command->info('ℹ️ El rol Admin ya tenía el permiso "hourly-totals-view"');
            }
        } else {
            $this->command->warn('⚠️ No se encontró el rol Admin; asigna el permiso manualmente.');
        }
    }
}
