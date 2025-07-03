<?php

namespace App\Http\Controllers;

use App\Models\SensorTransformation;
use App\Models\ProductionLine;
use Illuminate\Http\Request;

class SensorTransformationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $production_line_id = $request->input('production_line_id');
        
        // Obtener la línea de producción para conseguir el customer_id
        $productionLine = ProductionLine::findOrFail($production_line_id);
        $customer_id = $productionLine->customer_id;
        
        // Obtener las transformaciones de sensores asociadas a la línea de producción
        $sensorTransformations = SensorTransformation::where('production_line_id', $production_line_id)->get();
    
        return view('sensor-transformations.index', compact('sensorTransformations', 'production_line_id', 'customer_id'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $production_line_id = $request->production_line_id;
        // Obtener solo la línea de producción específica
        $productionLine = ProductionLine::findOrFail($production_line_id);
        $customer_id = $productionLine->customer_id;
        
        return view('sensor-transformations.create', compact('production_line_id', 'productionLine', 'customer_id'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'production_line_id' => 'required|exists:production_lines,id',
            'min_value' => 'nullable|numeric',
            'mid_value' => 'nullable|numeric',
            'max_value' => 'nullable|numeric',
            'input_topic' => 'required|string|max:255',
            'output_topic' => 'required|string|max:255',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'active' => 'nullable|boolean',
            'below_min_value_output' => 'nullable|string|max:255',
            'min_to_mid_value_output' => 'nullable|string|max:255',
            'mid_to_max_value_output' => 'nullable|string|max:255',
            'above_max_value_output' => 'nullable|string|max:255',
        ]);

        SensorTransformation::create($request->all());
        
        // Mantener el production_line_id en la redirección
        $production_line_id = $request->input('production_line_id');
        return redirect()->route('sensor-transformations.index', ['production_line_id' => $production_line_id])->with('success', 'Transformación de sensor creada exitosamente.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // No se implementa la vista de detalle individual
        return redirect()->back();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $sensorTransformation = SensorTransformation::findOrFail($id);
        $production_line_id = $request->input('production_line_id', $sensorTransformation->production_line_id);
        
        // Obtener la línea de producción para conseguir el customer_id
        $productionLine = ProductionLine::findOrFail($production_line_id);
        $customer_id = $productionLine->customer_id;
        
        return view('sensor-transformations.edit', compact('sensorTransformation', 'productionLine', 'production_line_id', 'customer_id'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'production_line_id' => 'required|exists:production_lines,id',
            'min_value' => 'nullable|numeric',
            'mid_value' => 'nullable|numeric',
            'max_value' => 'nullable|numeric',
            'input_topic' => 'required|string|max:255',
            'output_topic' => 'required|string|max:255',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'active' => 'nullable|boolean',
            'below_min_value_output' => 'nullable|string|max:255',
            'min_to_mid_value_output' => 'nullable|string|max:255',
            'mid_to_max_value_output' => 'nullable|string|max:255',
            'above_max_value_output' => 'nullable|string|max:255',
        ]);

        $sensorTransformation = SensorTransformation::findOrFail($id);
        $sensorTransformation->update($request->all());
        
        // Mantener el production_line_id en la redirección
        $production_line_id = $request->input('production_line_id');
        return redirect()->route('sensor-transformations.index', ['production_line_id' => $production_line_id])->with('success', 'Transformación de sensor actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $sensorTransformation = SensorTransformation::findOrFail($id);
        // Guardar el production_line_id antes de eliminar
        $production_line_id = $sensorTransformation->production_line_id;
        $sensorTransformation->delete();

        return redirect()->route('sensor-transformations.index', ['production_line_id' => $production_line_id])->with('success', 'Transformación de sensor eliminada exitosamente.');
    }
}
