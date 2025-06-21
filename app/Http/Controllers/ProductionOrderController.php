<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductionOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductionOrderController extends Controller
{
    public function index()
    {
        return view('productionorder.index'); // Cargar el Blade de producción
    }
    
    /**
     * Actualiza múltiples órdenes de producción en lote
     * Recibe un array de órdenes con sus nuevos valores de production_line_id, orden y status
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateBatch(Request $request)
    {
        // Validar la solicitud
        $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|integer|exists:production_orders,id',
            'orders.*.production_line_id' => 'nullable|integer|exists:production_lines,id',
            'orders.*.orden' => 'required|integer|min:0',
            'orders.*.status' => 'required|integer|min:0|max:5',
        ]);
        
        $updatedCount = 0;
        $errors = [];
        
        try {
            // Iniciar una transacción para asegurar que todas las actualizaciones se realicen o ninguna
            DB::beginTransaction();
            
            foreach ($request->orders as $orderData) {
                $order = ProductionOrder::find($orderData['id']);
                
                if ($order) {
                    // Manejar correctamente el production_line_id
                    // Si es null, establecerlo explícitamente como null
                    // Si tiene un valor, usar ese valor
                    if (isset($orderData['production_line_id'])) {
                        $order->production_line_id = $orderData['production_line_id'] ?: null;
                    } else {
                        $order->production_line_id = null;
                    }
                    
                    $order->orden = $orderData['orden'];
                    $order->status = $orderData['status'];
                    
                    // Registrar en log para depuración
                    Log::info("Actualizando orden ID: {$orderData['id']}", [
                        'production_line_id' => $order->production_line_id,
                        'orden' => $order->orden,
                        'status' => $order->status
                    ]);
                    
                    if ($order->save()) {
                        $updatedCount++;
                    } else {
                        $errors[] = "No se pudo actualizar la orden ID: {$orderData['id']}";
                    }
                } else {
                    $errors[] = "Orden no encontrada ID: {$orderData['id']}";
                }
            }
            
            // Si hay errores, revertir la transacción
            if (!empty($errors)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Se encontraron errores al actualizar las órdenes',
                    'errors' => $errors
                ], 422);
            }
            
            // Confirmar la transacción
            DB::commit();
            
            // Registrar en el log
            Log::info("Actualización masiva de órdenes de producción", [
                'user_id' => auth()->id(),
                'updated_count' => $updatedCount,
                'orders_count' => count($request->orders)
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Se actualizaron {$updatedCount} órdenes correctamente",
                'updated_count' => $updatedCount
            ]);
            
        } catch (\Exception $e) {
            // En caso de error, revertir la transacción
            DB::rollBack();
            
            Log::error("Error al actualizar órdenes en lote", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
            ], 500);
        }
    }
}
