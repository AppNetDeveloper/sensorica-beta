<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LineAvailability;
use App\Models\ProductionLine;
use App\Models\ShiftList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LineAvailabilityController extends Controller
{
    /**
     * Obtener la configuración de disponibilidad para una línea de producción
     *
     * @param  int  $id ID de la línea de producción
     * @return \Illuminate\Http\Response
     */
    public function getAvailability($id)
    {
        try {
            $productionLine = ProductionLine::findOrFail($id);
            $shifts = ShiftList::where('production_line_id', $id)->get();
            
            // Agrupar disponibilidad por día de la semana
            $availabilityByDay = [];
            
            // Inicializar array para cada día de la semana (1-7)
            for ($i = 1; $i <= 7; $i++) {
                $availabilityByDay[$i] = collect([]);
            }
            
            // Obtener disponibilidad actual
            $availability = LineAvailability::where('production_line_id', $id)
                ->where('active', true)
                ->get();
                
            // Agrupar por día
            foreach ($availability as $item) {
                $availabilityByDay[$item->day_of_week]->push($item);
            }
            
            // Renderizar la vista y devolverla como respuesta
            $html = view('production-lines.scheduler', compact('productionLine', 'shifts', 'availabilityByDay'))->render();
            
            return response($html);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cargar el planificador: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Guardar la configuración de disponibilidad para una línea de producción
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function saveAvailability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'production_line_id' => 'required|exists:production_lines,id',
            'days' => 'required|array',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }
        
        $productionLineId = $request->production_line_id;
        $days = $request->days;
        
        try {
            DB::beginTransaction();
            
            // Eliminar configuración anterior
            LineAvailability::where('production_line_id', $productionLineId)->delete();
            
            // Crear nuevos registros para cada día y turno
            foreach ($days as $day => $shifts) {
                foreach ($shifts as $shiftId) {
                    LineAvailability::create([
                        'production_line_id' => $productionLineId,
                        'shift_list_id' => $shiftId,
                        'day_of_week' => $day,
                        'active' => true
                    ]);
                }
            }
            
            DB::commit();
            
            return response()->json(['success' => true, 'message' => 'Disponibilidad guardada correctamente']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error al guardar la disponibilidad: ' . $e->getMessage()], 500);
        }
    }
}
