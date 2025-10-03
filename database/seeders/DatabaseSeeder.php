<?php

namespace Database\Seeders;

use App\Models\Modual;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            'manage-permission', 'create-permission', 'edit-permission', 'delete-permission',
            'manage-role', 'create-role', 'edit-role', 'delete-role', 'show-role',
            'manage-user', 'create-user', 'edit-user', 'delete-user', 'show-user',
            'manage-module', 'create-module', 'delete-module', 'show-module', 'edit-module',
            'manage-setting',
            'manage-crud',
            'manage-langauge', 'create-langauge', 'delete-langauge', 'show-langauge', 'edit-langauge', 
            'customer-show', 'customer-create', 'customer-edit', 'customer-delete',
            'workers-show', 'workers-create', 'workers-edit', 'workers-delete',
            'product-show', 'product-create', 'product-edit', 'product-delete',
            'server-show', 'server-create', 'server-edit', 'server-delete',
            'db-upload-show', 'db-upload-create', 'db-upload-edit', 'db-upload-delete',
            'shift-show', 'shift-create', 'shift-edit', 'shift-delete',
            'worker-post-show', 'worker-post-create', 'worker-post-edit', 'worker-post-delete',
            'rfid-post-show', 'rfid-post-create', 'rfid-post-edit', 'rfid-post-delete',
            'rfid-show', 'rfid-create', 'rfid-edit', 'rfid-delete',
            'servermonitor show', 'servermonitor create', 'servermonitor edit', 'servermonitor delete',
            'servermonitorbusynes show', 'servermonitorbusynes create', 'servermonitorbusynes edit', 'servermonitorbusynes delete',
            'servermonitorbusyness-show', 'servermonitorbusyness-create', 'servermonitorbusyness-edit', 'servermonitorbusyness-delete',
            'servermonitorbusynes create', 'servermonitorbusynes update', 'servermonitorbusynes delete',
            'servermonitorbusynes show', 'servermonitorbusynes index', 'servermonitor create', 'servermonitor update',
            'servermonitor delete', 'servermonitor show', 'servermonitor index',
            'whatsapp show', 'whatsapp create', 'whatsapp update', 'whatsapp delete',
            'telegram show', 'telegram create', 'telegram update', 'telegram delete',
            'process-show', 'process-create', 'process-edit', 'process-delete',
            'productionline-process-view', 'productionline-process-create', 'productionline-process-edit', 'productionline-process-delete',
            'productionline-show', 'productionline-create', 'productionline-edit', 'productionline-delete',
            'productionline-sensors', 'productionline-orders', 'productionline-kanban', 'productionline-incidents', 'productionline-weight-stats', 'productionline-production-stats', 'productionline-live-view', 'productionline-live-machine',
            // Maintenance module permissions
            'maintenance-show', 'maintenance-create', 'maintenance-edit', 'maintenance-delete',
        ];
        

        $modules = [
            'user','role','module','setting','crud','langauge','permission',
        ];

        // Crear o encontrar los permisos sin duplicar
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Crear o encontrar roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);
        $encargadoRole = Role::firstOrCreate(['name' => 'encargado']);
        $oficinaRole = Role::firstOrCreate(['name' => 'oficina']);

        // Asignar todos los permisos al rol "admin"
        $adminRole->syncPermissions($permissions);

        // Crear o encontrar el usuario admin
        $user = User::firstOrCreate(
            ['email' => 'dev@aixmart.net'], // criterio para buscar el usuario
            [
                'name'       => 'Xmart Developer',
                'password'   => Hash::make('@BSLserveriot***'),
                'avatar'     => 'avatar.png',
                'type'       => 'admin',
                'lang'       => 'es',
                'created_by' => '0',
            ]
        );

        // Asignar el rol "admin" al usuario
        $user->assignRole($adminRole);

        // Crear o encontrar cada módulo
        foreach ($modules as $module) {
            Modual::firstOrCreate(['name' => $module]);
        }

        $this->call([
            IaPromptsTableSeeder::class,
            OriginalOrderPermissionsTableSeeder::class,
            ProductionOrderCallbackPermissionsSeeder::class,
            FleetPermissionsSeeder::class,
            CustomerClientsPermissionsSeeder::class,
            RoutePlanPermissionsSeeder::class,
            DeliveryPermissionsSeeder::class,
            VendorProcurementPermissionsSeeder::class,
            AssetManagementPermissionsSeeder::class,
        ]);
    }
}
