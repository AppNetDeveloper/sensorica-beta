<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// Si usas Spatie:
use Spatie\Permission\Models\Role;
// O si es un modelo propio, usa tu namespace, p.ej.:
// use App\Models\Role;

class RoleManageController extends Controller
{
    /**
     * Muestra la vista con DataTables (manage-role.blade.php).
     */
    public function index()
    {
        return view('roles.manage-role');
    }

    /**
     * Devuelve la lista de Roles en formato JSON para DataTables.
     * GET /manage-role/list-all
     */
    public function listAll()
    {
        $roles = Role::with('permissions')->get()->map(function($role) {
            $role->permissions = $role->permissions->pluck('name')->toArray();
            return $role;
        });
        return response()->json($roles);
    }

    /**
     * Crea o actualiza un rol vía AJAX (POST /manage-role/store-or-update).
     * - Si llega 'id' => actualizamos
     * - Si no => creamos un nuevo rol
     */
    public function storeOrUpdate(Request $request)
    {
        // Validación mínima
        $request->validate([
            'name' => 'required|string|max:255',
            // Si manejas ID, no es strictly necesario validarlo
        ]);

        if ($request->filled('id')) {
            // Modo Edición
            $role = Role::findOrFail($request->id);
        } else {
            // Modo Creación
            $role = new Role();
        }

        $role->name = $request->name;
        $role->save();

        // Si quisieras asignar permisos en la misma operación,
        // podrías recibir un array de permisos en $request->permissions
        // y usar $role->syncPermissions(...).

        return response()->json(['success' => true]);
    }

    /**
     * Elimina un rol vía AJAX (DELETE /manage-role/delete/{id}).
     */
    public function delete($id)
    {
        $role = Role::findOrFail($id);

        // Si usas Spatie, esto también quita la relación con permisos
        // en la tabla intermedia
        $role->delete();

        return response()->json(['success' => true]);
    }
    public function updatePermissions(Request $request, $id)
    {
        $request->validate([
            'permissions' => 'array'
        ]);

        $role = Role::findOrFail($id);

        // Sincronizar los permisos con el rol utilizando Spatie
        $permissions = $request->input('permissions', []);
        $role->syncPermissions($permissions);

        return response()->json(['success' => true]);
    }

}
