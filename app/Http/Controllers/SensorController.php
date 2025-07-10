<?php

namespace App\Http\Controllers;

use App\Models\Sensor;
use App\Models\Barcode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class SensorController extends Controller
{
    public function index($production_line_id)
    {
        $sensors = Sensor::where('production_line_id', $production_line_id)->get();
        return view('smartsensors.index', compact('sensors', 'production_line_id'));
    }

    public function listSensors()
    {
        return view('sensors.index');
    }

    public function create($production_line_id)
    {
        $barcoders = Barcode::where('production_line_id', $production_line_id)->get();
        return view('smartsensors.create', compact('production_line_id', 'barcoders'));
    }

    public function store(Request $request, $production_line_id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'barcoder_id' => ['required', 'integer', Rule::exists('barcodes', 'id')],
            'sensor_type' => 'required|integer|in:0,1,2,3,4',
            'optimal_production_time' => 'nullable|numeric|min:0',
            'reduced_speed_time_multiplier' => 'nullable|numeric|min:0',
            'json_api' => 'nullable|string',
            'mqtt_topic_sensor' => 'required|string|max:255',
            'mqtt_topic_1' => 'required|string|max:255',
            'function_model_0' => 'required|string|max:255',
            'function_model_1' => 'required|string|max:255',
            'count_total' => 'nullable|integer|min:0',
            'count_total_0' => 'nullable|integer|min:0',
            'count_total_1' => 'nullable|integer|min:0',
            'count_shift_0' => 'nullable|integer|min:0',
            'count_shift_1' => 'nullable|integer|min:0',
            'count_order_0' => 'nullable|integer|min:0',
            'count_order_1' => 'nullable|integer|min:0',
            'invers_sensors' => 'nullable|in:0,1',
            'downtime_count' => 'nullable|integer|min:0',
            'unic_code_order' => 'nullable|string',
            'shift_type' => 'nullable|string',
            'productName' => 'nullable|string',
            'count_week_0' => 'nullable|integer|min:0',
            'count_week_1' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $request->all();
    
    // Convertir json_api a formato JSON válido si existe
    if (isset($data['json_api']) && !empty($data['json_api'])) {
        // Si el valor no es ya un JSON válido, lo convertimos a JSON
        if (!is_null($data['json_api']) && $data['json_api'][0] !== '{' && $data['json_api'][0] !== '[' && $data['json_api'][0] !== '"') {
            $data['json_api'] = json_encode($data['json_api']);
        }
    }
    
    $sensor = Sensor::create(array_merge($data, ['production_line_id' => $production_line_id]));

        return redirect()->route('smartsensors.index', $production_line_id)
                         ->with('success', 'Sensor creado exitosamente.');
    }

    public function edit($sensor_id)
    {
        $sensor = Sensor::findOrFail($sensor_id);
        $barcoders = Barcode::where('production_line_id', $sensor->production_line_id)->get();
        return view('smartsensors.edit', compact('sensor', 'barcoders'));
    }

    public function update(Request $request, $sensor_id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'barcoder_id' => ['required', 'integer', Rule::exists('barcodes', 'id')],
            'sensor_type' => 'required|integer|in:0,1,2,3,4',
            'optimal_production_time' => 'nullable|numeric|min:0',
            'reduced_speed_time_multiplier' => 'nullable|numeric|min:0',
            'json_api' => 'nullable|string',
            'mqtt_topic_sensor' => 'required|string|max:255',
            'mqtt_topic_1' => 'required|string|max:255',
            'function_model_0' => 'required|string|max:255',
            'function_model_1' => 'required|string|max:255',
            'count_total' => 'nullable|integer|min:0',
            'count_total_0' => 'nullable|integer|min:0',
            'count_total_1' => 'nullable|integer|min:0',
            'count_shift_0' => 'nullable|integer|min:0',
            'count_shift_1' => 'nullable|integer|min:0',
            'count_order_0' => 'nullable|integer|min:0',
            'count_order_1' => 'nullable|integer|min:0',
            'invers_sensors' => 'nullable|in:0,1',
            'downtime_count' => 'nullable|integer|min:0',
            'unic_code_order' => 'nullable|string',
            'shift_type' => 'nullable|string',
            'productName' => 'nullable|string',
            'count_week_0' => 'nullable|integer|min:0',
            'count_week_1' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $sensor = Sensor::findOrFail($sensor_id);
        $data = $request->all();
    
    // Convertir json_api a formato JSON válido si existe
    if (isset($data['json_api']) && !empty($data['json_api'])) {
        // Si el valor no es ya un JSON válido, lo convertimos a JSON
        if (!is_null($data['json_api']) && $data['json_api'][0] !== '{' && $data['json_api'][0] !== '[' && $data['json_api'][0] !== '"') {
            $data['json_api'] = json_encode($data['json_api']);
        }
    }
    
    $sensor->update($data);

        return redirect()->route('smartsensors.index', $sensor->production_line_id)
                         ->with('success', 'Sensor actualizado exitosamente.');
    }

    public function destroy($sensor_id)
    {
        $sensor = Sensor::findOrFail($sensor_id);
        $production_line_id = $sensor->production_line_id;
        $sensor->delete();

        return redirect()->route('smartsensors.index', $production_line_id)
                         ->with('success', 'Sensor eliminado exitosamente.');
    }
}