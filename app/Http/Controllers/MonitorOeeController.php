<?php

namespace App\Http\Controllers;

use App\Models\MonitorOee;
use App\Models\ProductionLine;
use Illuminate\Http\Request;

class MonitorOeeController extends Controller
{
    /**
     * Muestra la lista de monitores OEE.
     */
    public function index(Request $request)
    {
        $production_line_id = $request->input('production_line_id');
        
        // Obtener la línea de producción para conseguir el customer_id
        $productionLine = ProductionLine::findOrFail($production_line_id);
        $customer_id = $productionLine->customer_id;
        
        // Obtener los monitores OEE asociados a la línea de producción
        $monitorOees = MonitorOee::where('production_line_id', $production_line_id)->get();
    
        return view('oee.index', compact('monitorOees', 'production_line_id', 'customer_id'));
    }
    
    

    /**
     * Muestra el formulario para crear un nuevo Monitor OEE.
     */
    public function create(Request $request)
    {
        $production_line_id = $request->production_line_id;
        // Obtener solo la línea de producción específica en lugar de todas
        $productionLine = ProductionLine::findOrFail($production_line_id);
        $customer_id = $productionLine->customer_id;
        
        return view('oee.create', compact('production_line_id', 'productionLine', 'customer_id'));
    }
    

    /**
     * Almacena un nuevo Monitor OEE en la base de datos.
     */
    public function store(Request $request)
    {
        $request->validate([
            'production_line_id' => 'required|exists:production_lines,id',
            'mqtt_topic' => 'required|string|max:255',
            'mqtt_topic2' => 'nullable|string|max:255',
            'topic_oee' => 'nullable|string|max:255',
            'sensor_active' => 'nullable|boolean',
            'modbus_active' => 'nullable|boolean',
            'time_start_shift' => 'nullable|date',
        ]);

        MonitorOee::create($request->all());
        
        // Mantener el production_line_id en la redirección
        $production_line_id = $request->input('production_line_id');
        return redirect()->route('oee.index', ['production_line_id' => $production_line_id])->with('success', 'Monitor OEE creado exitosamente.');
    }

    /**
     * Muestra el formulario para editar un Monitor OEE existente.
     */
    public function edit(Request $request, $id)
    {
        $monitorOee = MonitorOee::findOrFail($id);
        $production_line_id = $request->input('production_line_id', $monitorOee->production_line_id);
        
        // Obtener la línea de producción para conseguir el customer_id
        $productionLine = ProductionLine::findOrFail($production_line_id);
        $customer_id = $productionLine->customer_id;
        
        $productionLines = collect([$productionLine]);
    return view('oee.edit', compact('monitorOee', 'productionLine', 'production_line_id', 'customer_id', 'productionLines'));
    }

    /**
     * Actualiza un Monitor OEE en la base de datos.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'production_line_id' => 'required|exists:production_lines,id',
            'mqtt_topic' => 'required|string|max:255',
            'mqtt_topic2' => 'nullable|string|max:255',
            'topic_oee' => 'nullable|string|max:255',
            'sensor_active' => 'nullable|boolean',
            'modbus_active' => 'nullable|boolean',
            'time_start_shift' => 'nullable|date',
        ]);

        $monitorOee = MonitorOee::findOrFail($id);
        $monitorOee->update($request->all());
        
        // Mantener el production_line_id en la redirección
        $production_line_id = $request->input('production_line_id');
        return redirect()->route('oee.index', ['production_line_id' => $production_line_id])->with('success', 'Monitor OEE editado exitosamente.');
    }

    /**
     * Elimina un Monitor OEE de la base de datos.
     */
    public function destroy($id)
    {
        $monitorOee = MonitorOee::findOrFail($id);
        // Guardar el production_line_id antes de eliminar
        $production_line_id = $monitorOee->production_line_id;
        $monitorOee->delete();

        return redirect()->route('oee.index', ['production_line_id' => $production_line_id])->with('success', 'Monitor OEE destruido exitosamente.');
    }
}
