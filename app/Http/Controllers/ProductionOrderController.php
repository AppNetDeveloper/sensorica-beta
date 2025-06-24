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
       // Log::info('=== INICIO: Actualización por lotes de órdenes de producción ===');
        
        try {
            // Validar la solicitud
            // Log::debug('Validando datos de entrada', ['request_data' => $request->all()]);
            //ponemos que si production_line_id es vacio lo ponemo null
            $orders = $request->input('orders', []);

            // Bucle para limpiar los datos ANTES de validar
            foreach ($orders as $key => $orderData) {
                if (isset($orderData['production_line_id']) && $orderData['production_line_id'] === '') {
                    $orders[$key]['production_line_id'] = null;
                }
            }
        
            // Reemplazar los datos de la petición con los datos ya limpios
            $request->merge(['orders' => $orders]);

            $validated = $request->validate([
                'orders' => 'required|array|min:1',
                'orders.*.id' => 'required|integer|exists:production_orders,id',
                'orders.*.production_line_id' => 'nullable|integer|exists:production_lines,id',
                'orders.*.orden' => 'required|integer|min:0',
                'orders.*.status' => 'required|integer|min:0|max:5',
            ]);
            
            $ordersCount = count($validated['orders']);
            //Log::info("Validación exitosa", ['total_orders' => $ordersCount]);
            
            $updatedCount = 0;
            $errors = [];
            
            // Iniciar una transacción para asegurar que todas las actualizaciones se realicen o ninguna
            DB::beginTransaction();
            //Log::debug('Transacción de base de datos iniciada');
            
            foreach ($request->orders as $index => $orderData) {
                $orderId = $orderData['id'];
                $logPrefix = "[Orden {$index}/{$ordersCount} ID:{$orderId}]";
                
                //Log::debug("$logPrefix Procesando orden");
                
                try {
                    $order = ProductionOrder::lockForUpdate()->find($orderId);
                    
                    if (!$order) {
                        $errorMsg = "$logPrefix No encontrada en la base de datos";
                        Log::error($errorMsg);
                        $errors[] = $errorMsg;
                        continue;
                    }
                    
                    // Registrar el estado actual antes de la actualización
                    Log::debug("$logPrefix Estado actual", [
                        'production_line_id' => $order->production_line_id,
                        'orden' => $order->orden,
                        'status' => $order->status,
                        'updated_at' => $order->updated_at
                    ]);
                    
                    // Preparar datos de actualización
                    $updateData = [
                        'production_line_id' => $orderData['production_line_id'] ?? null,
                        'orden' => $orderData['orden'],
                        'status' => $orderData['status'],
                    ];
                    
                    Log::debug("$logPrefix Aplicando cambios", $updateData);
                    
                    // Actualizar el modelo
                    $order->update($updateData);
                    $order->refresh();
                    $updatedCount++;
                    
                    Log::debug("$logPrefix Actualización exitosa", [
                        'nuevo_production_line_id' => $order->production_line_id,
                        'nuevo_orden' => $order->orden,
                        'nuevo_status' => $order->status
                    ]);
                    
                } catch (\Exception $e) {
                    $errorMsg = "$logPrefix Error: " . $e->getMessage();
                    Log::error($errorMsg, [
                        'exception' => $e->getTraceAsString(),
                        'order_data' => $orderData
                    ]);
                    $errors[] = $errorMsg;
                }
            }
            
            // Si llegamos aquí, todas las actualizaciones fueron exitosas
            DB::commit();
            
            $successMessage = "Actualización completada. Actualizadas: $updatedCount/$ordersCount órdenes.";
            
            if (!empty($errors)) {
                Log::warning("Proceso completado con errores parciales", [
                    'total_solicitadas' => $ordersCount,
                    'actualizadas' => $updatedCount,
                    'errores' => count($errors),
                    'errores_detalle' => $errors
                ]);
                
                $response = [
                    'success' => $updatedCount > 0,
                    'message' => $successMessage . " Errores: " . count($errors),
                    'updated' => $updatedCount,
                    'total' => $ordersCount,
                    'has_errors' => true,
                    'errors' => $errors
                ];
                
                return response()->json($response, $updatedCount > 0 ? 207 : 400);
            }
            
            // Éxito total
            Log::info("Actualización por lotes completada con éxito", [
                'total_procesadas' => $updatedCount
            ]);
            
            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'updated' => $updatedCount,
                'total' => $ordersCount
            ]);
            
        } catch (\Throwable $e) {
            // Hacer rollback en caso de cualquier excepción
            DB::rollBack();
            
            $errorContext = [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ];
            
            Log::error("ERROR CRÍTICO en actualización por lotes", $errorContext);
            
            // Si estamos en entorno local o desarrollo, incluir más detalles
            $errorMessage = config('app.env') === 'local' 
                ? $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()
                : 'Ocurrió un error inesperado. Por favor revise los logs del sistema.';
            
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error' => $errorContext
            ], 500);
        } finally {
            // Registrar métricas de rendimiento
            $executionTime = microtime(true) - LARAVEL_START;
            Log::debug("Tiempo de ejecución: " . round($executionTime, 3) . " segundos");
        }
    }
}
