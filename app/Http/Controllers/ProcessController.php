<?php

namespace App\Http\Controllers;

use App\Models\Process;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProcessController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:process-show|process-create|process-edit|process-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:process-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:process-edit', ['only' => ['edit', 'update', 'bulkUpdate']]);
        $this->middleware('permission:process-delete', ['only' => ['destroy', 'bulkDelete']]);
    }

    /**
     * Display a listing of the processes.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $processes = Process::orderBy('sequence')->get();
        return view('processes.index', compact('processes'));
    }

    /**
     * Show the form for creating a new process.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('processes.create');
    }

    /**
     * Store a newly created process in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:processes,code',
            'name' => 'required|string|max:255',
            'sequence' => 'required|integer|min:1', // Eliminada validación unique para permitir secuencias repetidas
            'description' => 'nullable|string',
            'color' => 'required|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'posicion_kanban' => 'required|integer|min:1',
        ]);

        Process::create($validated);

        return redirect()->route('processes.index')
            ->with('success', 'Proceso creado correctamente.');
    }

    /**
     * Display the specified process.
     *
     * @param  \App\Models\Process  $process
     * @return \Illuminate\View\View
     */
    public function show(Process $process)
    {
        return view('processes.show', compact('process'));
    }

    /**
     * Show the form for editing the specified process.
     *
     * @param  \App\Models\Process  $process
     * @return \Illuminate\View\View
     */
    public function edit(Process $process)
    {
        // Asegurarse de que el factor de corrección use punto como separador decimal
        $process->factor_correccion = number_format((float)$process->factor_correccion, 2, '.', '');
        return view('processes.edit', compact('process'));
    }

    /**
     * Update the specified process in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Process  $process
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Process $process)
    {
        // Validar los datos
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:processes,code,' . $process->id,
            'name' => 'required|string|max:255',
            'sequence' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'factor_correccion' => 'required|numeric|min:0.1|max:10000',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'posicion_kanban' => 'nullable|integer|min:1',
        ]);

        $process->update($validated);

        return redirect()->route('processes.index')
            ->with('success', 'Proceso actualizado correctamente.');
    }

    /**
     * Remove the specified process from storage.
     *
     * @param  \App\Models\Process  $process
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Process $process)
    {
        try {
            $process->delete();
            return redirect()->route('processes.index')
                ->with('success', 'Proceso eliminado correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'No se pudo eliminar el proceso. Asegúrese de que no esté siendo utilizado.');
        }
    }

    /**
     * Actualizar múltiples procesos en un solo paso.
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'process_ids' => 'required|array|min:1',
            'process_ids.*' => 'integer|distinct|exists:processes,id',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'posicion_kanban' => 'nullable|integer|min:1',
            'sequence' => 'nullable|integer|min:1',
            'factor_correccion' => 'nullable|numeric|min:0.1|max:10000',
        ]);

        $fields = collect(['color', 'posicion_kanban', 'sequence', 'factor_correccion'])
            ->filter(fn ($field) => array_key_exists($field, $validated) && $validated[$field] !== null)
            ->mapWithKeys(fn ($field) => [$field => $validated[$field]])
            ->toArray();

        if (empty($fields)) {
            return redirect()->back()->with('error', 'Selecciona al menos un campo para actualizar.');
        }

        Process::whereIn('id', $validated['process_ids'])->update($fields);

        return redirect()->route('processes.index')
            ->with('success', 'Procesos actualizados correctamente.');
    }

    /**
     * Remove multiple processes from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'process_ids' => 'required|array|min:1',
            'process_ids.*' => 'integer|distinct|exists:processes,id',
        ]);

        try {
            $processes = Process::whereIn('id', $validated['process_ids'])->get();
            $deletedCount = $processes->count();

            // Delete the processes
            Process::whereIn('id', $validated['process_ids'])->delete();

            return redirect()->route('processes.index')
                ->with('success', "Se han eliminado correctamente {$deletedCount} procesos.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'No se pudieron eliminar los procesos seleccionados. Asegúrese de que no estén siendo utilizados en líneas de producción o pedidos activos.');
        }
    }
}
