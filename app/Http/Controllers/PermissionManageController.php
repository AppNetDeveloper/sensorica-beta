<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
// O si tienes tu propio modelo, ajusta el namespace.

class PermissionManageController extends Controller
{
    /**
     * Muestra la vista "manage-permission.blade.php".
     */
    public function index()
    {
        return view('permissions.manage-permission');
    }

    /**
     * Listar Permisos en JSON (GET /manage-permission/list-all).
     */
    public function listAll()
    {
        // Retorna datos de la tabla 'permissions'
        $permissions = Permission::select('id', 'name')->get();
        return response()->json($permissions);
    }

    /**
     * Crear o actualizar (POST /manage-permission/store-or-update).
     * - Si llega "id" => actualizamos
     * - Si no => creamos
     */
    public function storeOrUpdate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        if ($request->filled('id')) {
            $permission = Permission::findOrFail($request->id);
        } else {
            $permission = new Permission();
        }

        $permission->name = $request->name;
        $permission->save();

        return response()->json(['success' => true]);
    }

    /**
     * Eliminar un permiso (DELETE /manage-permission/delete/{id}).
     */
    public function delete($id)
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();

        return response()->json(['success' => true]);
    }
}
