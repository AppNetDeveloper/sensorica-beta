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
        $this->middleware('permission:process-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:process-delete', ['only' => ['destroy']]);
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
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:processes,code,' . $process->id,
            'name' => 'required|string|max:255',
            'sequence' => 'required|integer|min:1', // Eliminada validación unique para permitir secuencias repetidas
            'description' => 'nullable|string',
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
}
