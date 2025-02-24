<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Facades\UtilityFacades;
use Illuminate\Support\Facades\File;

class UserController extends Controller
{
    /**
     * Muestra la vista principal de "Gestión de Usuarios" (con DataTables + AJAX).
     * Ya no hacemos $table->render('users.index').
     */
    public function index()
    {
        // Puedes devolver la misma Blade "users.index" que
        // hayas modificado para usar DataTables + AJAX.
        return view('users.index');
    }

    /**
     * Listar todos los usuarios en formato JSON para DataTables (AJAX).
     * GET /users/list-all/json
     */
    public function listAllJson()
    {
        // Ajusta los campos a los que realmente tiene tu tabla:
        // Por ejemplo, si tienes 'phone', inclúyelo.
        $users = User::select('id', 'name', 'email', 'phone')->get();

        return response()->json($users);
    }

    /**
     * Crear o actualizar (Store or Update) un usuario vía AJAX.
     * POST /users/store-or-update
     *
     * Lógica:
     * - Si llega "id" => es UPDATE
     * - Si no hay "id" => es CREATE
     */
    public function storeOrUpdateAjax(Request $request)
    {
        // Validar lo mínimo que necesites (aquí, name y email).
        $this->validate($request, [
            'name'  => 'required',
            'email' => 'required|email',
            'role'  => 'required|exists:roles,id',  // Validamos que el rol exista
        ]);
    
        if ($request->id) {
            // Modo Edición
            $user = User::findOrFail($request->id);
        } else {
            // Modo Creación
            $user = new User();
        }
    
        // Asignamos valores
        $user->name  = $request->name;
        $user->email = $request->email;
    
        // Si tu tabla tiene 'phone'
        if (isset($request->phone)) {
            $user->phone = $request->phone;
        }
    
        // Si llega 'password', lo encriptamos
        if (!empty($request->password)) {
            $user->password = Hash::make($request->password);
        }
    
        $user->save();
    
        // Asignar el rol seleccionado
        $user->syncRoles([$request->role]); // Asignamos el rol
    
        return response()->json(['success' => true]);
    }
    

    /**
     * Eliminar un usuario vía AJAX.
     * DELETE /users/delete/{id}
     */
    public function deleteAjax($id)
    {
        // Si tuvieras restricciones (por ej. no borrar al usuario #1),
        // podrías validar aquí.
        $user = User::findOrFail($id);

        // Ojo: si usas Spatie Roles, tal vez quieras eliminar roles primero
        // DB::table('model_has_roles')->where('model_id', $id)->delete();

        $user->delete();

        return response()->json(['success' => true]);
    }


    /******************************************************************
    *               MÉTODOS OPCIONALES / HEREDADOS                    *
    *     (Si ya no los necesitas, puedes eliminarlos o comentarlos)  *
    ******************************************************************/

    /**
     * [OPCIONAL] Vista de crear usuario (antes se usaba con Yajra).
     * Ya no es necesaria si haces todo vía AJAX en la misma vista.
     */
    public function create()
    {
        if (Auth::user()->can('create-user')) {
            $roles = Role::pluck('name', 'name')->all();
            return view('users.create', compact('roles'));
        } else {
            return redirect()->back()->with('error', 'Permission denied.');
        }
    }

    /**
     * [OPCIONAL] Guardar usuario (antes se usaba con el form normal).
     * Reemplazado por storeOrUpdate() si vas 100% AJAX.
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name'     => 'required',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|same:confirm-password',
            'roles'    => 'required'
        ]);

        $role_r = Role::findByName($request->roles);

        $user = User::create([
            'name'             => $request['name'],
            'email'            => $request['email'],
            'password'         => Hash::make($request['password']),
            'confirm_password' => 'required|same:password',
            'type'             => $role_r->name,
            'created_by'       => Auth::user()->id,
        ]);

        $user->assignRole($role_r);

        return redirect()->route('users.index')
            ->with('success', __('User created successfully'));
    }

    /**
     * [OPCIONAL] Mostrar un usuario (vía ID).
     */
    public function show($id)
    {
        // Usa findOrFail para lanzar 404 si no existe
        $user = User::findOrFail($id);
    
        // Devuelve la vista con la variable $user
        return view('users.show', compact('user'));
    }
    

    /**
     * [OPCIONAL] Vista de editar usuario (antes).
     */
    public function edit($id)
    {
        if (Auth::user()->can('edit-user')) {
            $user = User::find($id);
            $roles = Role::pluck('name', 'name')->all();
            $userRole = $user->roles->pluck('name', 'name')->all();

            return view('users.edit', compact('user', 'roles', 'userRole'));
        } else {
            return redirect()->back()->with('error', 'Permission denied.');
        }
    }

    /**
     * [OPCIONAL] Actualizar usuario (antes).
     * Reemplazado por storeOrUpdate() si vas 100% AJAX.
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name'  => 'required',
            'email' => 'required|email|unique:users,email,' . $id,
            'roles' => 'required'
        ]);

        $input = $request->all();
        $user  = User::find($id);
        $user->update($input);

        DB::table('model_has_roles')->where('model_id', $id)->delete();
        $user->assignRole($request->input('roles'));

        return redirect()->route('users.index')
            ->with('message', __('User updated successfully'));
    }

    /**
     * [OPCIONAL] Eliminar usuario (antes) - se llamaba destroy($id).
     * Reemplazado por delete($id) en AJAX. 
     */
    public function destroy($id)
    {
        if (Auth::user()->can('delete-user')) {
            if ($id == 1) {
                return redirect()->back()->with('error', 'Permission denied.');
            } else {
                DB::table("users")->delete($id);
                return redirect()->route('users.index')->with('success', __('User delete successfully.'));
            }
        }
    }

    /**
     * [OPCIONAL] Perfil de usuario (ajustes).
     */
    public function profile()
    {
        $setting = UtilityFacades::settings();
        if (isset($setting['authentication']) && $setting['authentication'] == 'activate') {
            if (extension_loaded('imagick')) {
                $user = Auth::user();
                $google2fa_url = "";
                $secret_key = "";

                if ($user->loginSecurity()->exists()) {
                    $google2fa = (new \PragmaRX\Google2FAQRCode\Google2FA());
                    $google2fa_url = $google2fa->getQRCodeInline(
                        config('app.name'),
                        $user->email,
                        $user->loginSecurity->google2fa_secret
                    );
                    $secret_key = $user->loginSecurity->google2fa_secret;
                }

                $data = [
                    'user' => $user,
                    'secret' => $secret_key,
                    'google2fa_url' => $google2fa_url,
                ];
            }

            $userDetail = Auth::user();
            return view('users.profile', compact('data', 'userDetail'));
        } else {
            $userDetail = Auth::user();
            return view('users.profile', compact('userDetail'));
        }
    }

    /**
     * [OPCIONAL] Actualizar perfil.
     */
    public function editprofile(Request $request)
    {
        $userDetail = Auth::user();
        $user = User::findOrFail($userDetail['id']);

        $validator = \Validator::make(
            $request->all(),
            [
                'name'    => 'required|max:120',
                'email'   => 'required|email|unique:users,email,' . $userDetail['id'],
                'profile' => 'image|mimes:jpeg,png,jpg,svg|max:3072',
            ]
        );
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }

        // Si subió nueva imagen
        if ($request->hasFile('profile')) {
            $filenameWithExt = $request->file('profile')->getClientOriginalName();
            $filename        = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            $extension       = $request->file('profile')->getClientOriginalExtension();
            $fileNameToStore = $filename . '_' . time() . '.' . $extension;
            $dir             = storage_path('uploads/avatar/');
            $image_path      = $dir . $userDetail['avatar'];

            if (File::exists($image_path)) {
                File::delete($image_path);
            }
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            $path = $request->file('profile')->storeAs('uploads/avatar/', $fileNameToStore);

            $user['avatar'] = $fileNameToStore;
        }

        // Si llegó password (cambiar clave)
        if (!is_null($request->password)) {
            $user->password = bcrypt($request->password);
        }

        // Actualizar nombre / email
        $user['name']  = $request['name'];
        $user['email'] = $request['email'];
        $user->save();

        return redirect()->back()->with('success', __('Profile successfully updated.'));
    }
}
