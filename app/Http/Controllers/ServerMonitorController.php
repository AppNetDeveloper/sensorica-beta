<?php

namespace App\Http\Controllers;

use App\Models\HostList;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServerMonitorController extends Controller
{
    /**
     * Muestra el dashboard unificado de monitoreo y gestión de servidores.
     */
    public function index()
    {
        $user = auth()->user();
        $query = HostList::query();

        // Filtrar según permisos:
        if ($user->hasPermissionTo('servermonitor show') && $user->hasPermissionTo('servermonitorbusynes show')) {
            // Mostrar hosts propios y globales
            $query->where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereNull('user_id');
            });
        } elseif ($user->hasPermissionTo('servermonitor show')) {
            // Solo hosts propios
            $query->where('user_id', $user->id);
        } elseif ($user->hasPermissionTo('servermonitorbusynes show')) {
            // Solo hosts globales
            $query->whereNull('user_id');
        } else {
            abort(403, 'No tienes permiso para ver los servidores.');
        }

        // Ordenamos por id ascendente para que el host con el id más pequeño aparezca primero.
        $hosts = $query->with(['hostMonitors' => function($query) {
            $query->orderBy('created_at', 'desc');
        }])
        ->orderBy('id', 'asc')
        ->get();

        return view('servermonitor.index', compact('hosts'));
    }

    /**
     * Muestra el formulario para crear un nuevo host.
     */
    public function create()
    {
        // Verificar si el usuario tiene permiso para crear (opcional)
        $this->authorize('create', HostList::class); 
        // O bien, chequear si el usuario tiene el permiso con Spatie:
        // if(!auth()->user()->hasPermissionTo('servermonitor create')) { abort(403); }

        return view('servermonitor.create');
    }

    /**
     * Almacena un nuevo host en la base de datos.
     */
    public function store(Request $request)
    {
        // Verificar permiso (opcional)
        // if(!auth()->user()->hasPermissionTo('servermonitor create')) { abort(403); }

        $validated = $request->validate([
            'host'     => 'required|string|unique:host_lists,host',
            'name'     => 'required|string',
            'emails'   => 'nullable|string',
            'phones'   => 'nullable|string',
            'telegrams'=> 'nullable|string',
        ]);

        // Generar token único (si no se envía uno)
        $token = Str::random(60);

        // Asignar user_id si el host es "propio"
        // (Depende de tu lógica: si quieres que por defecto se asigne al usuario actual, hazlo aquí)
        $userId = auth()->user()->id;

        $host = HostList::create([
            'host'     => $validated['host'],
            'name'     => $validated['name'],
            'emails'   => $validated['emails'] ?? null,
            'phones'   => $validated['phones'] ?? null,
            'telegrams'=> $validated['telegrams'] ?? null,
            'token'    => $token,
            'user_id'  => $userId,
        ]);

        return redirect()->route('servermonitor.index')
                         ->with('success', 'Servidor creado exitosamente.');
    }

    /**
     * Muestra el formulario para editar un host existente.
     */
    public function edit(HostList $host)
    {
        // Verificar permiso (opcional)
        // if(!auth()->user()->hasPermissionTo('servermonitor edit')) { abort(403); }

        // Opcionalmente, verifica si el host pertenece al usuario (si aplica tu lógica)
        // if($host->user_id !== auth()->id() && !auth()->user()->hasPermissionTo('servermonitorbusynes show')) {
        //     abort(403, 'No tienes permiso para editar este servidor.');
        // }

        return view('servermonitor.edit', compact('host'));
    }

    /**
     * Actualiza la información de un host existente.
     */
    public function update(Request $request, HostList $host)
    {
        // Verificar permiso (opcional)
        // if(!auth()->user()->hasPermissionTo('servermonitor edit')) { abort(403); }

        $validated = $request->validate([
            'host'     => 'required|string|unique:host_lists,host,'.$host->id,
            'name'     => 'required|string',
            'emails'   => 'nullable|string',
            'phones'   => 'nullable|string',
            'telegrams'=> 'nullable|string',
        ]);

        $host->update($validated);

        return redirect()->route('servermonitor.index')
                         ->with('success', 'Servidor actualizado exitosamente.');
    }

    /**
     * Elimina un host de la base de datos.
     */
    public function destroy(HostList $host)
    {
        // Verificar permiso (opcional)
        // if(!auth()->user()->hasPermissionTo('servermonitor delete')) { abort(403); }

        // Verificar si el host pertenece al usuario o es global y el usuario tiene permisos
        // if($host->user_id !== auth()->id() && !auth()->user()->hasPermissionTo('servermonitorbusynes delete')) {
        //     abort(403, 'No tienes permiso para eliminar este servidor.');
        // }

        $host->delete();

        return redirect()->route('servermonitor.index')
                         ->with('success', 'Servidor eliminado exitosamente.');
    }

    /**
     * Devuelve en formato JSON el último registro de monitoreo para un host dado.
     */
    public function getLatest(HostList $host)
    {
        $latest = $host->hostMonitors()->orderBy('created_at', 'desc')->first();

        if (!$latest) {
            return response()->json([
                'timestamp' => now()->format('H:i:s'),
                'cpu'       => 0,
                'memory'    => 0,
                'disk'      => 0,
            ]);
        }

        return response()->json([
            'timestamp' => $latest->created_at->format('H:i:s'),
            'cpu'       => $latest->cpu,
            'memory'    => $latest->memory_used_percent,
            'disk'      => $latest->disk,
        ]);
    }

    /**
     * Devuelve en formato JSON los últimos 20 registros de monitoreo para un host.
     */
    public function getHistory(HostList $host)
    {
        // Obtenemos los 40 registros más recientes (orden descendente) y luego los ordenamos ascendentemente
        $history = $host->hostMonitors()
                        ->orderBy('created_at', 'desc')
                        ->limit(160)
                        ->get()
                        ->sortBy('created_at')
                        ->values(); // Reindexar la colección

        $data = $history->map(function($item) {
            return [
                'timestamp' => $item->created_at->format('H:i:s'),
                'cpu'       => $item->cpu,
                'memory'    => $item->memory_used_percent,
                'disk'      => $item->disk,
            ];
        });

        return response()->json($data);
    }
}
