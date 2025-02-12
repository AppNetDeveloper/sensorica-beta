<?php

namespace App\Http\Controllers;

use App\Models\RfidReading;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\RfidReadingImport;
use App\Models\ProductionLine;
use App\Models\RfidColor;

class RfidCategoryController extends Controller
{
    /**
     * Lista todas las categorías RFID asociadas a una línea de producción.
     */
    public function index($production_line_id)
    {
        $categories = RfidReading::where('production_line_id', $production_line_id)->get();

        return view('rfid.categories.index', [
            'production_line_id' => $production_line_id,
            'categories' => $categories,
        ]);
    }

    /**
     * Muestra el formulario para crear una nueva categoría RFID.
     */

    
    public function create($production_line_id)
    {
        return view('rfid.categories.form', [
            'rfidReading' => new RfidReading(),
            'production_line_id' => $production_line_id,
            'productionLines' => ProductionLine::all(),
            'rfidColors' => RfidColor::all(),
        ]);
    }
    
    public function edit($id)
    {
        $rfidReading = RfidReading::findOrFail($id);
    
        return view('rfid.categories.form', [
            'rfidReading' => $rfidReading,
            'production_line_id' => $rfidReading->production_line_id,
            'productionLines' => ProductionLine::all(),
            'rfidColors' => RfidColor::all(),
        ]);
    }
    

    /**
     * Almacena una nueva categoría RFID en la base de datos.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'epc' => 'required|string|unique:rfid_readings,epc|max:255',
            'production_line_id' => 'required|exists:production_lines,id',
        ]);

        RfidReading::create($request->all());

        return redirect()->route('rfid.categories.index', $request->production_line_id)
            ->with('status', __('Categoría RFID creada exitosamente.'));
    }


    /**
     * Actualiza una categoría RFID existente en la base de datos.
     */
    public function update(Request $request, $id)
    {
        $rfidReading = RfidReading::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'epc' => 'required|string|unique:rfid_readings,epc,' . $rfidReading->id . '|max:255',
        ]);

        $rfidReading->update($request->all());

        return redirect()->route('rfid.categories.index', $rfidReading->production_line_id)
            ->with('status', __('Categoría RFID actualizada exitosamente.'));
    }

    /**
     * Elimina una categoría RFID de la base de datos.
     */
    public function destroy($id)
    {
        $rfidReading = RfidReading::findOrFail($id);
        $productionLineId = $rfidReading->production_line_id;

        $rfidReading->delete();

        return redirect()->route('rfid.categories.index', $productionLineId)
            ->with('status', __('Categoría RFID eliminada exitosamente.'));
    }
    /**
     * Importa un archivo Excel para actualizar o agregar categorías RFID.
     *
     * Se espera que el archivo tenga el mismo formato que el exportado:
     *  - Columna 0: ID (opcional)
     *  - Columna 1: Nombre
     *  - Columna 2: EPC
     *
     * @param \Illuminate\Http\Request $request
     * @param int $production_line_id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function import(Request $request, $production_line_id)
    {
        // Validar que se haya enviado un archivo y que sea del tipo adecuado
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,ods',
        ]);

        // Realizar la importación usando la clase RfidReadingImport
        Excel::import(new RfidReadingImport($production_line_id), $request->file('excel_file'));

        return redirect()->route('rfid.categories.index', $production_line_id)
            ->with('status', __('Archivo Excel importado exitosamente.'));
    }
}
