<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Scada;
use App\Models\ScadaOrder;
use App\Models\ScadaOrderList;
use App\Models\ScadaOrderListProcess;

class ScadaOrderController extends Controller
{
        /**
     * @OA\Get(
     *     path="/api/scada-orders/{token}",
     *     summary="Obtener órdenes por token",
     *     tags={"SCADA Orders"},
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         description="Token único asociado a la SCADA",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Éxito",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="scada_id", type="integer"),
     *             @OA\Property(property="scada_name", type="string"),
     *             @OA\Property(property="orders", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Token inválido o SCADA no encontrada"
     *     )
     * )
     */
    public function getOrdersByToken($token)
    {
        // Buscar la SCADA asociada al token
        $scada = Scada::where('token', $token)->first();

        // Si no se encuentra la SCADA, devolver un error
        if (!$scada) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido o SCADA no encontrada.',
            ], 404);
        }

        // Obtener los órdenes asociados al SCADA
        $orders = ScadaOrder::where('scada_id', $scada->id)->get();

        // Formatear la respuesta JSON con el nombre de la SCADA
        return response()->json([
            'success' => true,
            'scada_id' => $scada->id,
            'scada_name' => $scada->name, // Agregar el nombre de la SCADA
            'orders' => $orders,
        ]);
    }
    /**
     * @OA\Post(
     *     path="/api/scada-orders/update",
     *     summary="Actualizar estado de la orden",
     *     tags={"SCADA Orders"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="order_id", type="string"),
     *             @OA\Property(property="status", type="integer"),
     *             @OA\Property(property="orden", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Estado actualizado correctamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Orden no encontrada"
     *     )
     * )
     */
    public function updateOrderStatus(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|string', // ID del pedido
            'status' => 'required|integer',  // Nuevo estado
            'orden' => 'required|integer',   // Nuevo orden
        ]);

        $order = ScadaOrder::where('order_id', $validated['order_id'])->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Orden no encontrada.'], 404);
        }

        // Cambiar la orden al nuevo status temporalmente con un orden arbitrario
        $order->status = $validated['status'];
        $order->save();

        // Obtener todas las órdenes de esa columna (status), excepto la actual
        $ordersInColumn = ScadaOrder::where('status', $validated['status'])
            ->where('id', '!=', $order->id)
            ->orderBy('orden')
            ->get();

        // Insertar la orden en la posición solicitada dentro de la colección
        // La idea es reconstruir el orden completamente:
        $desiredPosition = $validated['orden'];
        // Ajustar si la posición es mayor al total de órdenes + 1 (colocar al final)
        if ($desiredPosition > $ordersInColumn->count() + 1) {
            $desiredPosition = $ordersInColumn->count() + 1;
        }

        // Crear una nueva colección temporal con las órdenes
        $reordered = collect();

        // Insertar las órdenes antes de la posición deseada
        for ($i = 1; $i < $desiredPosition; $i++) {
            if ($ordersInColumn->isEmpty()) break;
            $reordered->push($ordersInColumn->shift());
        }

        // Ahora insertar la orden actual
        $reordered->push($order);

        // Insertar las órdenes restantes
        while (!$ordersInColumn->isEmpty()) {
            $reordered->push($ordersInColumn->shift());
        }

        // Finalmente, reasignar `orden` a todas las órdenes en la columna
        foreach ($reordered as $index => $ord) {
            $ord->update(['orden' => $index + 1]);
        }

        return response()->json(['success' => true, 'message' => 'Orden actualizada correctamente.']);
    }

    /**
     * @OA\Delete(
     *     path="/api/scada-orders/delete",
     *     summary="Eliminar una orden",
     *     tags={"SCADA Orders"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="order_id", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Orden eliminada correctamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Orden no encontrada"
     *     )
     * )
     */
    public function deleteOrder(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|string', // ID del pedido a borrar
        ]);

        // Buscar el pedido por ID
        $order = ScadaOrder::where('order_id', $validated['order_id'])->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Orden no encontrada.'], 404);
        }

        // Obtener el estado actual antes de eliminar
        $currentStatus = $order->status;

        // Eliminar el pedido
        $order->delete();

        // Reordenar las tarjetas restantes en la misma columna
        $ordersInColumn = ScadaOrder::where('status', $currentStatus)
            ->orderBy('orden')
            ->get();

        foreach ($ordersInColumn as $index => $orderInColumn) {
            $orderInColumn->update(['orden' => $index + 1]);
        }

        return response()->json(['success' => true, 'message' => 'Orden eliminada correctamente.']);
    }
    /**
     * @OA\Get(
     *     path="/api/scada-orders/{scadaOrderId}/lines",
     *     summary="Obtener estado de las líneas de una orden",
     *     tags={"SCADA Orders"},
     *     @OA\Parameter(
     *         name="scadaOrderId",
     *         in="path",
     *         description="ID de la orden en SCADA",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Éxito",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="lines", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No se encontraron líneas asociadas para este ID de SCADA Order"
     *     )
     * )
     */
    public function getLinesStatusByScadaOrderId($scadaOrderId)
    {
        // Obtener las líneas asociadas a este scada_order_id con sus procesos, materiales y operadores
        $lines = ScadaOrderList::where('scada_order_id', $scadaOrderId)
            ->with(['processes.material', 'processes.operator']) // Cargar procesos con materiales y operadores
            ->get();

        // Verificar si hay líneas asociadas
        if ($lines->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron líneas asociadas para este ID de scada_order.',
            ], 404);
        }

        // Inicializar variables para determinar el estado global
        $allUsed = true;  // Todas las líneas tienen used = 1
        $allPending = true; // Todas las líneas tienen used = 0

        // Iterar sobre las líneas y calcular el estado por línea
        foreach ($lines as $line) {
            $lineAllUsed = true;  // Todos los procesos de esta línea tienen used = 1
            $lineAllPending = true; // Todos los procesos de esta línea tienen used = 0

            foreach ($line->processes as $process) {
                if ($process->used === 0) {
                    $lineAllUsed = false; // Al menos un proceso no está completado
                }
                if ($process->used === 1) {
                    $lineAllPending = false; // Al menos un proceso está completado
                }

                // Agregar el nombre del material al proceso
                $process->material_name = $process->material ? $process->material->name : 'No disponible';

                // Agregar información del operador al proceso
                if ($process->operator) {
                    $process->operator_data = [
                        'id' => $process->operator->id,
                        'name' => $process->operator->name,
                        'email' => $process->operator->email, // Asegúrate de que estos campos existan
                    ];
                } else {
                    $process->operator_data = 'No disponible';
                }
            }

            // Determinar el estado de esta línea
            if ($lineAllUsed) {
                $line->status = 'Realizado';
            } elseif ($lineAllPending) {
                $line->status = 'Sin hacer';
            } else {
                $line->status = 'Parcial';
            }

            // Actualizar el estado global
            if (!$lineAllUsed) {
                $allUsed = false; // Si una línea no está completamente usada, el global no puede ser "Realizado"
            }
            if (!$lineAllPending) {
                $allPending = false; // Si una línea tiene algo usado, el global no puede ser "Sin hacer"
            }
        }

        // Determinar el estado global
        $globalStatus = 'Parcial';
        if ($allUsed) {
            $globalStatus = 'Realizado';
        } elseif ($allPending) {
            $globalStatus = 'Sin hacer';
        }

        // Respuesta con el estado global y el detalle de las líneas
        return response()->json([
            'success' => true,
            'status' => $globalStatus,
            'lines' => $lines,
        ]);
    }


    /**
     * @OA\Post(
     *     path="/api/scada-orders/process/update-used",
     *     summary="Actualizar el campo 'used' de un proceso específico",
     *     tags={"SCADA Orders"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=4264),
     *             @OA\Property(property="scada_order_list_id", type="integer", example=1499),
     *             @OA\Property(property="scada_material_type_id", type="integer", example=3),
     *             @OA\Property(property="used", type="integer", enum={0,1}, example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Proceso actualizado correctamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Proceso no encontrado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function updateProcessUsed(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
            'scada_order_list_id' => 'required|integer',
            'scada_material_type_id' => 'required|integer',
            'used' => 'required|integer|in:0,1',
        ]);

        // Buscar el proceso usando los tres campos
        $process = ScadaOrderListProcess::where('id', $validated['id'])
            ->where('scada_order_list_id', $validated['scada_order_list_id'])
            ->where('scada_material_type_id', $validated['scada_material_type_id'])
            ->first();

        if (!$process) {
            return response()->json(['success' => false, 'message' => 'Proceso no encontrado.'], 404);
        }

        // Actualizar el campo 'used'
        $process->used = $validated['used'];
        $process->save();

        // Obtener la scada_order_id a partir de scada_order_list_id
        $scadaOrderList = ScadaOrderList::find($validated['scada_order_list_id']);
        if ($scadaOrderList) {
            $scadaOrderId = $scadaOrderList->scada_order_id;
            // Obtener la scada_order correspondiente
            $scadaOrder = ScadaOrder::find($scadaOrderId);

            if ($scadaOrder) {
                // Verificar si todas las líneas y sus procesos están al 100% (used=1)
                $allCompleted = true;

                // Obtener todas las líneas asociadas a esta ScadaOrder
                $allLines = ScadaOrderList::where('scada_order_id', $scadaOrder->id)->get();

                foreach ($allLines as $line) {
                    // Obtener todos los procesos de esta línea
                    $processes = ScadaOrderListProcess::where('scada_order_list_id', $line->id)->get();

                    // Verificar si todos los procesos de la línea tienen used=1
                    foreach ($processes as $p) {
                        if ($p->used !== 1) {
                            $allCompleted = false;
                            break 2; // Salir de ambos loops
                        }
                    }
                }

                // Si todos los procesos de todas las líneas son used=1 => status=2
                // Si no, status=1 (iniciado)
                $newStatus = $allCompleted ? 2 : 1;

                // Actualizar el status solo si es diferente
                if ($scadaOrder->status !== $newStatus) {
                    $scadaOrder->status = $newStatus;
                    $scadaOrder->save();
                }
            }
        }

        return response()->json(['success' => true, 'message' => 'Proceso actualizado correctamente.']);
    }


}
