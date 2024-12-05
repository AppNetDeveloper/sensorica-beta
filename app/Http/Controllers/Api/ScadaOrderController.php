<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Scada;
use App\Models\ScadaOrder;
use App\Models\ScadaOrderList;

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
            'status' => 'required|integer', // Nuevo estado
            'orden' => 'required|integer', // Nuevo orden
        ]);

        // Buscar el pedido por ID
        $order = ScadaOrder::where('order_id', $validated['order_id'])->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Orden no encontrada.'], 404);
        }

        // Actualizar el estado y el orden, ajustando los números dentro de la columna
        $order->update([
            'status' => $validated['status'],
            'orden' => $validated['orden'],
        ]);

        // Reordenar las tarjetas en la columna después de la actualización
        $ordersInColumn = ScadaOrder::where('status', $validated['status'])
            ->orderBy('orden')
            ->get();

        foreach ($ordersInColumn as $index => $orderInColumn) {
            $orderInColumn->update(['orden' => $index + 1]);
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
        // Obtener las líneas asociadas a este scada_order_id con sus procesos y materiales
        $lines = ScadaOrderList::where('scada_order_id', $scadaOrderId)
            ->with(['processes.material']) // Cargar los procesos relacionados y sus materiales
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


}
