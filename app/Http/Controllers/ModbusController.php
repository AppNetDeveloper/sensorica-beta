<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Modbus;
use Illuminate\Support\Facades\Validator;

class ModbusController extends Controller
{
    public function index($production_line_id)
    {
        $modbuses = Modbus::where('production_line_id', $production_line_id)->get();
        return view('modbuses.index', compact('modbuses', 'production_line_id'));
    }

    public function create($production_line_id)
    {
        return view('modbuses.create', compact('production_line_id'));
    }

    public function store(Request $request, $production_line_id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            // Añadir validaciones para todos los campos necesarios. Aquí solo se muestra 'name' por brevedad.
            // Debes ajustar según tus necesidades, por ejemplo:
            'max_kg' => 'nullable|numeric',
            'min_kg' => 'nullable|numeric',
            'downtime_count' => 'nullable|integer',
            // etc...
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $modbus = Modbus::create(array_merge($request->all(), ['production_line_id' => $production_line_id]));

        return redirect()->route('modbuses.index', $production_line_id)->with('success', 'Modbus creado correctamente.');
    }

    public function edit($id)
    {
        $modbus = Modbus::findOrFail($id);
        return view('modbuses.edit', compact('modbus'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            // Añadir validaciones para todos los campos necesarios. Aquí solo se muestra 'name' por brevedad.
            // Debes ajustar según tus necesidades, por ejemplo:
            'max_kg' => 'nullable|numeric',
            'min_kg' => 'nullable|numeric',
            'downtime_count' => 'nullable|integer',
            // etc...
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $modbus = Modbus::findOrFail($id);
        $modbus->update($request->all());

        return redirect()->route('modbuses.index', $modbus->production_line_id)->with('success', 'Modbus actualizado correctamente.');
    }

    public function destroy($production_line_id, $modbus)
    {
        // Encuentra el modbus y elimínalo
        $modbus = Modbus::findOrFail($modbus);
        $modbus->delete();
    
        return redirect()->route('modbuses.index', $production_line_id)->with('success', 'Modbus eliminado con éxito');
    }
    public function queuePrint()
    {
        return view('modbuses.queueprint');
    }
    public function listStats()
    {
        return view('modbuses.liststats');
    }

}