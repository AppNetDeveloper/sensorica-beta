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
            
            // Obtener disponibilidad actual
            $availability = LineAvailability::where('production_line_id', $id)
                ->where('active', true)
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->id,
                        'production_line_id' => $item->production_line_id,
                        'shift_list_id' => $item->shift_list_id,
                        'day_of_week' => $item->day_of_week,
                        'active' => $item->active
                    ];
                });
            
            // Devolver datos estructurados como JSON
            return response()->json([
                'productionLine' => $productionLine,
                'shifts' => $shifts,
                'availability' => $availability
            ]);
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
        // Log para depuración
        \Log::info('LineAvailabilityController::saveAvailability - Request recibido', [
            'all' => $request->all(),
            'production_line_id' => $request->production_line_id,
            'customer_id' => $request->customer_id,
            'days' => $request->days
        ]);
        
        $validator = Validator::make($request->all(), [
            'production_line_id' => 'required|exists:production_lines,id',
            'customer_id' => 'required|exists:customers,id', // Añadimos validación para customer_id
            'days' => 'required|array',
        ]);
        
        if ($validator->fails()) {
            \Log::error('LineAvailabilityController::saveAvailability - Validación fallida', [
                'errors' => $validator->errors()->toArray()
            ]);
            return response()->json(['success' => false, 'message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }
        
        $productionLineId = $request->production_line_id;
        $customerId = $request->customer_id;
        $days = $request->days;
        
        // Verificar que la línea de producción pertenece al cliente
        $productionLine = ProductionLine::where('id', $productionLineId)
            ->first(); // Primero verificamos que exista la línea
            
        if (!$productionLine) {
            \Log::error('LineAvailabilityController::saveAvailability - Línea de producción no encontrada', [
                'production_line_id' => $productionLineId
            ]);
            return response()->json(['success' => false, 'message' => 'Línea de producción no encontrada'], 404);
        }
        
        // Verificar que la línea pertenece al cliente
        if ($productionLine->customer_id != $customerId) {
            \Log::error('LineAvailabilityController::saveAvailability - Cliente no tiene acceso a esta línea', [
                'production_line_id' => $productionLineId,
                'customer_id' => $customerId,
                'line_customer_id' => $productionLine->customer_id
            ]);
            return response()->json(['success' => false, 'message' => 'Cliente no encontrado o no tiene acceso a esta línea de producción'], 403);
        }
        
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
