<?php

namespace App\Http\Controllers;

use App\Models\ProductionLine;
use App\Models\Process;
use App\Models\ProductionLineProcess as Pivot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionLineProcessController extends Controller
{
    /**
     * Middleware para verificar permisos
     */
    public function __construct()
    {
        $this->middleware('permission:productionline-process-view', ['only' => ['index', 'show']]);
        $this->middleware('permission:productionline-process-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:productionline-process-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:productionline-process-delete', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the processes for a production line.
     *
     * @param  \App\Models\ProductionLine  $productionLine
     * @return \Illuminate\Http\Response
     */
    public function index(ProductionLine $productionLine)
    {
        $processes = $productionLine->processes()
            ->orderBy('production_line_process.order')
            ->get();
            
        return view('productionlines.processes.index', compact('productionLine', 'processes'));
    }

    /**
     * Show the form for creating a new process association.
     *
     * @param  \App\Models\ProductionLine  $productionLine
     * @return \Illuminate\Http\Response
     */
    public function create(ProductionLine $productionLine)
    {
        // Obtener procesos que aún no están asociados a esta línea de producción
        $availableProcesses = Process::whereNotIn('id', function($query) use ($productionLine) {
            $query->select('process_id')
                  ->from('production_line_process')
                  ->where('production_line_id', $productionLine->id);
        })->orderBy('sequence')->get();
        
        return view('productionlines.processes.create', compact('productionLine', 'availableProcesses'));
    }

    /**
     * Store a newly created process association in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ProductionLine  $productionLine
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, ProductionLine $productionLine)
    {
        $validated = $request->validate([
            'process_id' => 'required|exists:processes,id',
            'order' => 'required|integer|min:1'
        ]);        
        
        try {
            // Verificar si ya existe una relación con el mismo orden
            $existing = DB::table('production_line_process')
                ->where('production_line_id', $productionLine->id)
                ->where('order', $validated['order'])
                ->exists();
                
            if ($existing) {
                return redirect()->back()
                    ->with('error', __('Ya existe un proceso con este orden en la línea de producción.'))
                    ->withInput();
            }
            
            // Verificar si el proceso ya está asociado
            $exists = $productionLine->processes()
                ->where('process_id', $validated['process_id'])
                ->exists();
                
            if ($exists) {
                return redirect()->back()
                    ->with('error', __('Este proceso ya está asociado a la línea de producción.'))
                    ->withInput();
            }
            
            // Asociar el proceso con el orden especificado
            $productionLine->processes()->attach($validated['process_id'], [
                'order' => $validated['order']
            ]);
            
            return redirect()->route('productionlines.processes.index', $productionLine->id)
                ->with('success', __('Proceso asociado correctamente.'));
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', __('Error al asociar el proceso: ') . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified process association.
     *
     * @param  \App\Models\ProductionLine  $productionLine
     * @param  \App\Models\Process  $process
     * @return \Illuminate\Http\Response
     */
    public function show(ProductionLine $productionLine, Process $process)
    {
        $pivot = DB::table('production_line_process')
            ->where('production_line_id', $productionLine->id)
            ->where('process_id', $process->id)
            ->first();
            
        if (!$pivot) {
            abort(404);
        }
        
        return view('productionlines.processes.show', compact('productionLine', 'process', 'pivot'));
    }

    /**
     * Show the form for editing the specified process association.
     *
     * @param  \App\Models\ProductionLine  $productionLine
     * @param  \App\Models\Process  $process
     * @return \Illuminate\Http\Response
     */
    public function edit(ProductionLine $productionLine, Process $process)
    {
        $pivot = DB::table('production_line_process')
            ->where('production_line_id', $productionLine->id)
            ->where('process_id', $process->id)
            ->first();
            
        if (!$pivot) {
            abort(404);
        }
        
        return view('productionlines.processes.edit', compact('productionLine', 'process', 'pivot'));
    }

    /**
     * Update the specified process association in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ProductionLine  $productionLine
     * @param  \App\Models\Process  $process
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ProductionLine $productionLine, Process $process)
    {
        $validated = $request->validate([
            'order' => 'required|integer|min:1'
        ]);
        
        try {
            // Verificar si ya existe otra relación con el mismo orden
            $existing = DB::table('production_line_process')
                ->where('production_line_id', $productionLine->id)
                ->where('process_id', '!=', $process->id)
                ->where('order', $validated['order'])
                ->exists();
                
            if ($existing) {
                return redirect()->back()
                    ->with('error', __('Ya existe otro proceso con este orden en la línea de producción.'))
                    ->withInput();
            }
            
            // Actualizar el orden del proceso
            $productionLine->processes()->updateExistingPivot($process->id, [
                'order' => $validated['order']
            ]);
            
            return redirect()->route('productionlines.processes.index', $productionLine->id)
                ->with('success', __('Orden del proceso actualizado correctamente.'));
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', __('Error al actualizar el orden del proceso: ') . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified process association from storage.
     *
     * @param  \App\Models\ProductionLine  $productionLine
     * @param  \App\Models\Process  $process
     * @return \Illuminate\Http\Response
     */
    public function destroy(ProductionLine $productionLine, Process $process)
    {
        try {
            // Eliminar la relación
            $productionLine->processes()->detach($process->id);
            
            return redirect()->route('productionlines.processes.index', $productionLine->id)
                ->with('success', __('Proceso desasociado correctamente.'));
                
        } catch (\Exception $e) {
            return redirect()->route('productionlines.processes.index', $productionLine->id)
                ->with('error', __('Error al desasociar el proceso: ') . $e->getMessage());
        }
    }
}
