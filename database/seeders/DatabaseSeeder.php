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
            'manage-permission','create-permission','edit-permission','delete-permission',
            'manage-role','create-role','edit-role','delete-role','show-role',
            'manage-user','create-user','edit-user','delete-user','show-user',
            'manage-module','create-module','delete-module','show-module','edit-module',
            'manage-setting',
            'manage-crud',
            'manage-langauge','create-langauge','delete-langauge','show-langauge','edit-langauge', 
            'customer-show', 'customer-create', 'customer-edit', 'customer-delete',
            'workers-show', 'workers-create', 'workers-edit', 'workers-delete',
            'product-show', 'product-create', 'product-edit', 'product-delete',
            'server-show', 'server-create', 'server-edit', 'server-delete',
            'db-upload-show', 'db-upload-create', 'db-upload-edit', 'db-upload-delete',
            'shift-show', 'shift-create', 'shift-edit', 'shift-delete',
            'worker-post-show', 'worker-post-create', 'worker-post-edit', 'worker-post-delete',
            'rfid-post-show', 'rfid-post-create', 'rfid-post-edit', 'rfid-post-delete',
            'rfid-show', 'rfid-create', 'rfid-edit', 'rfid-delete',
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
            ['email' => 'dev@boisolo.com'], // criterio para buscar el usuario
            [
                'name'       => 'Boisolo Developer',
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
    }
}
