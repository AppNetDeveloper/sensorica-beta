<?php

namespace App\Http\Controllers;

use App\Models\Printer;
use Illuminate\Http\Request;

class PrinterController extends Controller
{
    public function index()
    {
        $printers = Printer::all();
        return response()->json($printers);
    }

    public function show($id)
    {
        $printer = Printer::findOrFail($id);
        return response()->json($printer);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'name_printer' => 'required|string|max:255',
            'api_printer' => 'required|string|max:255',
            'type' => 'required|boolean',
        ]);

        $printer = Printer::create($request->all());

        return response()->json($printer, 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'name_printer' => 'required|string|max:255',
            'api_printer' => 'required|string|max:255',
            'type' => 'required|boolean',
        ]);

        $printer = Printer::findOrFail($id);
        $printer->update($request->all());

        return response()->json($printer);
    }

    public function destroy($id)
    {
        $printer = Printer::findOrFail($id);
        $printer->delete();

        return response()->json(null, 204);
    }
}
