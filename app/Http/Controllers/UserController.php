<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Spatie\Permission\Models\Role;
use App\Facades\UtilityFacades;
use Illuminate\Support\Facades\File;

class UserController extends Controller
{
    /**
     * Muestra la vista principal de "Gestión de Usuarios".
     */
    public function index()
    {
        // Verificar si el usuario actual tiene rol de admin
        $isAdmin = Auth::user()->hasRole('admin');
        
        // Obtener usuarios con sus roles
        $usersQuery = User::with('roles');
        
        // Si no es admin, filtrar para no mostrar usuarios con rol admin
        if (!$isAdmin) {
            // Excluir usuarios con rol admin
            $adminRoleId = Role::where('name', 'admin')->first()->id;
            $usersQuery->whereDoesntHave('roles', function($query) use ($adminRoleId) {
                $query->where('id', $adminRoleId);
            });
        }
        
        $users = $usersQuery->get();
        return view('users.index', compact('users'));
    }

    /**
     * Mostrar el formulario para crear un nuevo usuario.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Obtener todos los roles disponibles
        $allRoles = Role::pluck('name', 'name')->all();
        
        // Verificar si el usuario actual tiene rol de admin
        $isAdmin = Auth::user()->hasRole('admin');
        
        // Si no es admin, filtrar el rol de admin de la lista
        if (!$isAdmin) {
            $allRoles = array_filter($allRoles, function($roleName) {
                return strtolower($roleName) !== 'admin';
            });
        }
        
        $roles = $allRoles;
        return view('users.create', compact('roles'));
    }
    
    /**
     * Almacenar un nuevo usuario en la base de datos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|same:confirm-password',
            'roles' => 'required'
        ]);
    
        $input = $request->all();
        $input['password'] = Hash::make($input['password']);
    
        $user = User::create($input);
        $user->assignRole($request->input('roles'));
    
        return redirect()->route('users.index')
                        ->with('success', 'Usuario creado exitosamente');
    }
    
    /**
     * Mostrar el formulario para editar un usuario específico.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = User::with('roles')->find($id);
        
        // Verificar si el usuario actual tiene rol de admin
        $isAdmin = Auth::user()->hasRole('admin');
        
        // Si no es admin y está intentando editar un usuario admin, denegar acceso
        if (!$isAdmin && $user->hasRole('admin')) {
            return redirect()->route('users.index')
                ->with('error', 'No tienes permiso para editar este usuario.');
        }
        
        // Obtener todos los roles disponibles
        $allRoles = Role::pluck('name', 'name')->all();
        
        // Si no es admin, filtrar el rol de admin de la lista
        if (!$isAdmin) {
            $allRoles = array_filter($allRoles, function($roleName) {
                return strtolower($roleName) !== 'admin';
            });
        }
        
        $roles = $allRoles;
        $userRole = $user->roles->pluck('name', 'name')->all();
    
        return view('users.edit', compact('user', 'roles', 'userRole'));
    }
    
    /**
     * Actualizar un usuario específico en la base de datos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$id,
            'password' => 'same:confirm-password',
            'roles' => 'required'
        ]);
    
        $input = $request->all();
        if(!empty($input['password'])){ 
            $input['password'] = Hash::make($input['password']);
        }else{
            $input = Arr::except($input, ['password']);
        }
    
        $user = User::find($id);
        $user->update($input);
        DB::table('model_has_roles')->where('model_id',$id)->delete();
    
        $user->assignRole($request->input('roles'));
    
        return redirect()->route('users.index')
                        ->with('success', 'Usuario actualizado exitosamente');
    }

    /**
     * Mostrar un usuario específico.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::with('roles')->find($id);
        
        // Verificar si el usuario actual tiene rol de admin
        $isAdmin = Auth::user()->hasRole('admin');
        
        // Si no es admin y está intentando ver un usuario admin, denegar acceso
        if (!$isAdmin && $user->hasRole('admin')) {
            return redirect()->route('users.index')
                ->with('error', 'No tienes permiso para ver este usuario.');
        }
        
        return view('users.show', compact('user'));
    }

    /**
     * Eliminar un usuario específico de la base de datos.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::with('roles')->find($id);
        
        // Verificar si el usuario actual tiene rol de admin
        $isAdmin = Auth::user()->hasRole('admin');
        
        // Si no es admin y está intentando eliminar un usuario admin, denegar acceso
        if (!$isAdmin && $user->hasRole('admin')) {
            return redirect()->route('users.index')
                ->with('error', 'No tienes permiso para eliminar este usuario.');
        }
        
        DB::table('model_has_roles')->where('model_id', $id)->delete();
        $user->delete();

        return redirect()->route('users.index')
                        ->with('success', 'Usuario eliminado exitosamente');
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
