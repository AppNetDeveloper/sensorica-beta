<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Modbus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\ScadaOrder;
use App\Models\ScadaList;
use App\Models\ScadaMaterialType;
use App\Models\ScadaOperatorLog;
use App\Models\Operator;
use App\Models\ScadaDosageHistory;
use App\Models\ScadaOrderListProcess;
use Illuminate\Support\Facades\DB; // Importar DB facade para transacciones
use App\Models\ScadaOrderList;

class ModbusController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/modbuses",
     *     summary="Get modbuses by customer token",
     *     tags={"Modbus"},
     *     @OA\Parameter(
     *         name="token",
     *         in="query",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="token", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Token not provided",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invalid token",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getModbuses(Request $request)
    {
        $token = $request->query('token'); // Obtener el token de los parámetros de la consulta

        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 400);
        }

        // Busca el cliente usando el token
        $customer = Customer::where('token', $token)->first();

        if (!$customer) {
            return response()->json(['error' => 'Invalid token'], 404);
        }

        // Obtén los modbuses con model_name 'weight'
        $modbuses = Modbus::where('model_name', 'weight')->get(['name', 'token']);

        return response()->json($modbuses);
    }
       /**
     * @OA\Post(
     *     path="/api/modbus/send",
     *     summary="Send dosage value via MQTT",
     *     tags={"Modbus"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="tolvaId", type="integer"),
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="value", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Token not provided",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invalid token",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */

     public function sendDosage(Request $request)
     {
         // Obtener los datos del request
         $id = $request->input('id');
         $token = trim($request->input('token')); // Eliminar espacios en blanco y caracteres no visibles
         $inValue = $request->input('value'); // El valor ingresado
         $inValue = is_numeric($inValue) ? $inValue + 0 : 0;  // Convierte a número, si no es numérico, será 0
 
         // Validación básica
         if (!$token) {
             return response()->json(['error' => 'Token not provided'], 400);
         }
 
         // Buscar el modbus utilizando el token
         $modbus = Modbus::where('token', $token)
                         ->where('id', $id)
                         ->first();
 
         if (!$modbus) {
             return response()->json(['error' => 'Invalid token or Modbus not found'], 404);
         }
 
         // Primero, enviar el comando de cancelación
         $cancelResponse = $this->sendCancel($request);
         if ($cancelResponse->status() !== 200) {
             return $cancelResponse;
         }
 
         // Modificar el tópico MQTT de peso a dosificación
         $topic = str_replace('peso', 'dosifica', $modbus->mqtt_topic_modbus);
 
         // Crear el JSON con el valor de dosificación
         $message = json_encode(['value' => $inValue]);
 
         // Publicar el mensaje MQTT y registrar en las tablas
         $this->publishMqttMessage($topic, $message);
         //vamos a poner hora y fecha en este log
         Log::info( date('Y-m-d H:i:s') . ' Message sent to MQTT topic: ' . $topic . ' with value: ' . $inValue); // Guardar en el log
 
             // Preparar el segundo tópico MQTT y mensaje
         $secondTopic = $modbus->mqtt_topic . '1/dosage';
         $secondMessage = json_encode([
             'value' => $inValue,
             'time' => date('Y-m-d H:i:s')
         ]);
 
         // Publicar el segundo mensaje MQTT
         $this->publishMqttMessage($secondTopic, $secondMessage);
 
         // Log para el segundo mensaje
         Log::info(date('Y-m-d H:i:s') . ' Second message sent to MQTT topic: ' . $secondTopic . ' with value: ' . $inValue . ' and time: ' . date('Y-m-d H:i:s'));
 
        try {
            // Obtenemos de scada_order la linea que tiene status 1
            // o si no existe, buscamos la linea que es status=0 pero que el orden es el más pequeño.
            // Si ninguna de las dos condiciones encuentra un resultado, $linea será null.
            $linea = ScadaOrder::where('status', 1)->orderBy('orden')->first()
                    ?? ScadaOrder::where('status', 0)->orderBy('orden')->first();

            if ($linea === null) {
                $orderId="No se encontró Orden";
                Log::info(date('Y-m-d H:i:s') . ' No se encontró ninguna línea con status 1 ni con status 0.');
            } else {
                $orderId=$linea->order_id;
                Log::info(date('Y-m-d H:i:s') . ' Order de trabajo : ' . $orderId);

            }
                $modbusId = $modbus->id;
                $modbusName=$modbus->name;

            //ahora buscamos en scada_list por el id de modbus  modbus_id = $modbusId y sacamos la linea de scada_list

            $scadaListLine = ScadaList::where('modbus_id', $modbusId)->first();
            if ($scadaListLine === null) {
                Log::info(date('Y-m-d H:i:s') . ' No se encontró ninguna línea en ScadaList con modbus_id = ' . $modbusId);
            } else {
                $scadaListmaterialTypeId = $scadaListLine->material_type_id;

                Log::info(date('Y-m-d H:i:s') . ' ScadaList material type ID: ' . $scadaListmaterialTypeId);

                //ahora sacamos de scada_material_type con where id = $scadaListmaterialTypeId y sacamos la linea de scada_material_type.
                $scadaMaterialTypeLine = ScadaMaterialType::where('id', $scadaListmaterialTypeId)->first();
                if ($scadaMaterialTypeLine === null) {
                    $scadaMaterialTypeName= "material no encontrado";
                    Log::info(date('Y-m-d H:i:s') . ' No se encontró ninguna línea en ScadaMaterialType con id = ' . $scadaListmaterialTypeId);
                } else {
                    $scadaMaterialTypeName= $scadaMaterialTypeLine->name;
                    Log::info(date('Y-m-d H:i:s') . ' ScadaMaterialType material type name: ' .$scadaMaterialTypeName );
                }


            }
            //sacamod de scada_operator_logs con where scada_id = $scadaListLine->id y sacamos el último log de scada_operator_logs.
            $scadaOperatorLogsLine = ScadaOperatorLog::where('scada_id', $scadaListLine->scada_id)->orderByDesc('created_at')->first();
            if ($scadaOperatorLogsLine === null) {
                Log::info(date('Y-m-d H:i:s') . ' No se encontró ningún log de ScadaOperatorLog para la línea con id = ' . $scadaListLine->scada_id);
            } else {
                Log::info(date('Y-m-d H:i:s') . ' ScadaOperatorLog last log: ' . $scadaOperatorLogsLine->operator_id);
                //ahora sacamos de operators la información del operador con el where id = $scadaOperatorLogsLine->operator_id y sacamos el name
                $operatorLine = Operator::where('id', $scadaOperatorLogsLine->operator_id)->first();
                if ($operatorLine === null) {
                    $operatorName = "Operador no encontrado";
                    Log::info(date('Y-m-d H:i:s') . ' No se encontró ningún operador con el id = ' . $scadaOperatorLogsLine->operator_id);
                } else {
                    $operatorName = $operatorLine->name;
                    Log::info(date('Y-m-d H:i:s') . ' Operador encontrado: ' . $operatorName);
                    // Aquí puedes hacer lo que necesites con el operador encontrado
                }
            }
            try {
                $nuevaLineaHistorial = ScadaDosageHistory::create([
                    'operator_name' => $operatorName, // Reemplaza con el valor real
                    'orderId' => $orderId,                       // Reemplaza con el valor real
                    'dosage_kg' => $inValue / 10,                  // Reemplaza con el valor real
                    'material_name' => $scadaMaterialTypeName,           // Reemplaza con el valor real
                ]);

                // $nuevaLineaHistorial ahora contiene el modelo recién creado con su ID y timestamps
                Log::info('nueva dosificacion creada con ID: ' . $nuevaLineaHistorial->id);
            } catch (\Exception $e) {
                Log::error('Error al crear la línea de historial de dosificación: ' . $e->getMessage());
            }

        } catch (\Exception $e) {
            Log::error(date('Y-m-d H:i:s') . ' Error al obtener la línea: ' . $e->getMessage());
        }


        return response()->json(['message' => 'Dosage value sent successfully']);
    }

    /**
     * Recalcula el proceso de dosificación automáticamente para un proceso específico.
     *
     * Si el material en tolva es insuficiente para el proceso actual (considerando un residuo de 50kg),
     * se calcula el porcentaje que se puede cumplir. Todas las líneas del mismo pedido
     * (scada_order_list_id) se actualizan a este porcentaje, y se crean nuevas líneas
     * duplicadas para la cantidad restante.
     *
     * @param  \Illuminate\Http\Request  $request La solicitud HTTP entrante.
     * @param  int|string  $id  El ID del registro en scada_order_list_process que disparó el recálculo.
     * @return \Illuminate\Http\JsonResponse Una respuesta JSON indicando el resultado de la operación.
     */
    public function recalculateDosingProcess(Request $request, $id)
    {
        Log::info('Solicitud de recálculo de dosificación automática recibida para el proceso ID: ' . $id);

        // Buscar el registro del proceso original en la base de datos
        try {
            $originalProcessRecord = ScadaOrderListProcess::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Proceso original no encontrado en scada_order_list_process con ID: ' . $id);
            return response()->json([
                'status' => 'error',
                'message' => 'Registro del proceso original no encontrado con ID: ' . $id,
            ], 404);
        }

        // Extraer los valores del proceso original
        $scadaOrderListId = $originalProcessRecord->scada_order_list_id;
        $scadaMaterialTypeId = $originalProcessRecord->scada_material_type_id;
        $ordenOriginal = $originalProcessRecord->orden;
        $measure = $originalProcessRecord->measure;
        $valueRequiredByOriginalProcess = (float) $originalProcessRecord->value; // Valor requerido por el proceso original

        Log::info('Valores extraídos para el proceso ID ' . $id . ':', [
            'scada_order_list_id' => $scadaOrderListId,
            'scada_material_type_id' => $scadaMaterialTypeId,
            'orden' => $ordenOriginal,
            'measure' => $measure,
            'value_required_by_process' => $valueRequiredByOriginalProcess,
        ]);

        // Comprobar si el valor requerido por el proceso original es mayor que 50
        if ($valueRequiredByOriginalProcess <= 50) {
            Log::warning('Proceso ID ' . $id . ' detenido: el valor requerido por el proceso (' . $valueRequiredByOriginalProcess . ') no es mayor que 50.');
            return response()->json([
                'status' => 'stopped_due_to_process_value',
                'message' => 'Proceso detenido. El valor requerido por el proceso (' . $valueRequiredByOriginalProcess . ' ' . $measure . ') debe ser mayor que 50 para recalcular.',
                'process_id' => $id,
                'value_required_by_process' => $valueRequiredByOriginalProcess,
                'required_minimum_for_process_value' => 50
            ], 200);
        }

        Log::info('Proceso ID ' . $id . ': El valor requerido (' . $valueRequiredByOriginalProcess . ') es > 50. Buscando peso actual de la tolva.');

        $scadaListItem = ScadaList::where('material_type_id', $scadaMaterialTypeId)->first();
        if (!$scadaListItem) {
            Log::error('No se encontró registro en scada_list para material_type_id: ' . $scadaMaterialTypeId);
            return response()->json(['status' => 'error', 'message' => 'Configuración de material no encontrada (scada_list).'], 404);
        }
        $modbusId = $scadaListItem->modbus_id;
        Log::info('Proceso ID ' . $id . ': modbus_id encontrado: ' . $modbusId);

        $modbusDevice = Modbus::find($modbusId);
        if (!$modbusDevice) {
            Log::error('No se encontró dispositivo Modbus con ID: ' . $modbusId);
            return response()->json(['status' => 'error', 'message' => 'Dispositivo Modbus no encontrado.'], 404);
        }
        $currentHopperWeight = (float) $modbusDevice->last_value;
        Log::info('Proceso ID ' . $id . ': Peso actual de la tolva: ' . $currentHopperWeight);

        $residueAmount = 50.0;
        $usableWeight = $currentHopperWeight - $residueAmount;
        Log::info('Proceso ID ' . $id . ': Peso utilizable en tolva (después de residuo de ' . $residueAmount . 'kg): ' . $usableWeight . ' ' . $measure);

        // Nueva comprobación: si no hay material utilizable
        if ($usableWeight <= 0) {
            Log::warning('Proceso ID ' . $id . ': No hay material utilizable en la tolva por encima del residuo de ' . $residueAmount . 'kg. Usable: ' . $usableWeight);
            return response()->json([
                'status' => 'no_usable_material_above_residue',
                'message' => 'No hay material utilizable en la tolva por encima del residuo de ' . $residueAmount . ' ' . $measure . '.',
                'process_id' => $id,
                'current_total_weight_in_hopper' => $currentHopperWeight,
                'usable_weight_in_hopper' => $usableWeight,
                'residue_configured' => $residueAmount
            ], 200);
        }

        $finalValueToDoseForThisProcess = 0;
        $message = '';
        $splitOccurred = false;
        $newScadaOrderListId = null;

        if ($usableWeight < $valueRequiredByOriginalProcess) {
            // Material insuficiente para el valor original del proceso actual, pero hay algo usable.
            $percentageToFulfill = ($valueRequiredByOriginalProcess > 0) ? ($usableWeight / $valueRequiredByOriginalProcess) * 100.0 : 0;
            $actualDosingAmountForThisProcess = $usableWeight; // Lo que realmente se puede dosificar para este proceso

            Log::warning('Proceso ID ' . $id . ': Material insuficiente. Usable: ' . $usableWeight . ', Requerido: ' . $valueRequiredByOriginalProcess . '. Porcentaje a cumplir: ' . $percentageToFulfill . '%');
            $message = 'Material insuficiente en tolva para el requerimiento original. La orden ha sido ajustada al ' . round($percentageToFulfill, 2) . '% de lo disponible y se ha creado una orden de trabajo restante.';
            $splitOccurred = true;

            DB::beginTransaction();
            try {
                // 1. Duplicar el registro en scada_order_list
                $originalScadaOrderList = ScadaOrderList::find($scadaOrderListId);
                if (!$originalScadaOrderList) {
                    throw new \Exception('No se encontró el registro original de scada_order_list con ID: ' . $scadaOrderListId);
                }
                $newScadaOrderList = $originalScadaOrderList->replicate();
                // Podrías querer modificar algún campo del nuevo pedido, ej. el nombre o estado.
                // $newScadaOrderList->name = $originalScadaOrderList->name . ' - Remanente';
                $newScadaOrderList->save();
                $newScadaOrderListId = $newScadaOrderList->id;
                Log::info('Proceso ID ' . $id . ': Registro scada_order_list duplicado. Nuevo ID de pedido: ' . $newScadaOrderListId);

                // 2. Actualizar y duplicar líneas en scada_order_list_process
                $relatedProcesses = ScadaOrderListProcess::where('scada_order_list_id', $scadaOrderListId)->get();

                foreach ($relatedProcesses as $process) {
                    // ==================================================================
                    // NUEVA LÓGICA: Si un material ya ha sido usado (used=1), se ignora.
                    if ($process->used == 1) {
                        Log::info('Proceso ID ' . $process->id . ' omitido porque ya tiene used=1.');
                        // Salta a la siguiente iteración del bucle, ignorando este material.
                        continue; 
                    }
                    // ==================================================================

                    $originalLineValue = (float) $process->value;

                    // Calcular valores basados en el porcentaje global de cumplimiento
                    $updatedValueForExistingLine = round($originalLineValue * ($percentageToFulfill / 100.0), 2);
                    $remainderValueForNewLine = round($originalLineValue - $updatedValueForExistingLine, 2);

                    // Si es el proceso que disparó el recálculo, su valor a dosificar es el $actualDosingAmountForThisProcess
                    if ($process->id == $id) {
                        $valueForThisLineInExistingOrder = round($actualDosingAmountForThisProcess, 2);
                        $valueForThisLineInNewOrder = round($originalLineValue - $valueForThisLineInExistingOrder, 2);

                        // Actualizar la línea existente (la que disparó el recálculo)
                        $process->value = $valueForThisLineInExistingOrder;
                        $process->save();

                        // Crear nueva línea para el remanente de ESTA línea específica en el NUEVO pedido
                        if ($valueForThisLineInNewOrder > 0.009) {
                            $newProcessLineForTrigger = $process->replicate();
                            $newProcessLineForTrigger->scada_order_list_id = $newScadaOrderListId;
                            $newProcessLineForTrigger->value = $valueForThisLineInNewOrder;
                            $newProcessLineForTrigger->used = 0;
                            $newProcessLineForTrigger->operator_id = null;
                            $newProcessLineForTrigger->save();
                        }
                    } else {
                        // Para otras líneas del mismo pedido original
                        $process->value = $updatedValueForExistingLine;
                        $process->save();

                        // Crear nueva línea para el remanente de OTRAS líneas en el NUEVO pedido
                        if ($remainderValueForNewLine > 0.009) {
                            $newProcessLine = $process->replicate();
                            $newProcessLine->scada_order_list_id = $newScadaOrderListId;
                            $newProcessLine->value = $remainderValueForNewLine;
                            $newProcessLine->used = 0;
                            $newProcessLine->operator_id = null;
                            $newProcessLine->save();
                        }
                    }
                }
                DB::commit();
                Log::info('Proceso ID ' . $id . ': Líneas de la orden ' . $scadaOrderListId . ' actualizadas. Líneas remanentes creadas para la nueva orden ' . $newScadaOrderListId);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error durante la actualización/duplicación de líneas para la orden ' . $scadaOrderListId . ': ' . $e->getMessage());
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error al actualizar las líneas del pedido: ' . $e->getMessage()
                ], 500);
            }

            $updatedOriginalProcessRecord = ScadaOrderListProcess::find($id); // Re-fetch para el valor final a dosificar
            $finalValueToDoseForThisProcess = (float) $updatedOriginalProcessRecord->value;

        } else {
            // Material suficiente para el valor original del proceso actual
            $finalValueToDoseForThisProcess = $valueRequiredByOriginalProcess;
            $message = 'Material suficiente para el requerimiento original del proceso ID: ' . $id . '.';
            Log::info('Proceso ID ' . $id . ': Material suficiente. Valor a dosificar: ' . $finalValueToDoseForThisProcess . ' ' . $measure);
        }

        return response()->json([
            'status' => $splitOccurred ? 'adjusted_due_to_shortage' : 'success',
            'message' => $message,
            'process_id' => $id,
            'split_occurred' => $splitOccurred,
            'original_scada_order_list_id' => $scadaOrderListId,
            'new_scada_order_list_id_for_remainder' => $newScadaOrderListId, // ID del nuevo pedido duplicado
            'data_from_original_process' => [
                'scada_material_type_id' => $scadaMaterialTypeId,
                'orden' => $ordenOriginal,
                'original_value_required' => $valueRequiredByOriginalProcess,
            ],
            'hopper_data' => [
                'modbus_id' => $modbusId,
                'current_total_weight' => $currentHopperWeight,
                'residue_configured' => $residueAmount,
                'usable_weight' => $usableWeight,
            ],
            'dosing_info' => [
                'value_to_dose_for_this_process' => $finalValueToDoseForThisProcess,
                'measure' => $measure,
            ]
        ], 200);
    }
     /**
     * @OA\Post(
     *     path="/api/modbus/cancel",
     *     summary="Cancel ongoing dosage for a Modbus",
     *     tags={"Modbus"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="token", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cancellation command sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Token not provided",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invalid token or Modbus not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
     public function sendCancel(Request $request)
     {
         // Obtener los datos del request
         $id = $request->input('id');
         $token = trim($request->input('token')); // Eliminar espacios en blanco y caracteres no visibles
 
         // Validación básica
         if (!$token) {
             return response()->json(['error' => 'Token not provided'], 400);
         }
 
         // Buscar el modbus utilizando el token
         $modbus = Modbus::where('token', $token)
                         ->where('id', $id)
                         ->first();
 
         if (!$modbus) {
             return response()->json(['error' => 'Invalid token or Modbus not found'], 404);
         }
 
         // Modificar el tópico MQTT para cancelar dosificación
         $cancelTopic = str_replace('peso', 'cancel', $modbus->mqtt_topic_modbus);
 
         // Crear el JSON con el comando de cancelación
         $cancelMessage = json_encode(['value' => true]);
 
         // Publicar el mensaje de cancelación MQTT
         $this->publishMqttMessage($cancelTopic, $cancelMessage);
         Log::info(date('Y-m-d H:i:s') . ' Cancelation message sent to MQTT topic: ' . $cancelTopic);
        //poner pausa 1 segundo
        sleep(1);
 
         return response()->json(['message' => 'Cancelation command sent successfully']);
     }
 

    public function setZero(Request $request)
    {
        // Obtener los datos del request
        $id = $request->input('id');
        $token = trim($request->input('token')); // Eliminar espacios en blanco y caracteres no visibles

        // Validación básica
        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 400);
        }

        // Buscar el modbus utilizando el token
        $modbus = Modbus::where('token', $token)
                        ->where('id', $id)
                        ->first();

        if (!$modbus) {
            return response()->json(['error' => 'Invalid token or Modbus not found'], 404);
        }

        // Modificar el tópico MQTT de la tabla para usar 'zero'
        $topic = str_replace('peso', 'zero', $modbus->mqtt_topic_modbus);

        // Crear el JSON con el valor true para el comando zero
        $message = json_encode(['value' => true]);

        // Publicar el mensaje MQTT y registrar en las tablas
        $this->publishMqttMessage($topic, $message);

        return response()->json(['message' => 'Zero command sent successfully']);
    }


    // Función privada para enviar mensaje MQTT
    private function publishMqttMessage($topic, $message)
    {

        try {
            // Preparar los datos a almacenar, agregando la fecha y hora
            $data = [
                'topic'     => $topic,
                'message'   => $message,
                'timestamp' => now()->toDateTimeString(),
            ];
        
            // Convertir a JSON
            $jsonData = json_encode($data);
        
            // Sanitizar el topic para evitar creación de subcarpetas
            $sanitizedTopic = str_replace('/', '_', $topic);
            // Generar un identificador único (por ejemplo, usando microtime)
            $uniqueId = round(microtime(true) * 1000); // milisegundos
        
            // Guardar en servidor 1
            $fileName1 = storage_path("app/mqtt/server1/{$sanitizedTopic}_{$uniqueId}.json");
            if (!file_exists(dirname($fileName1))) {
                mkdir(dirname($fileName1), 0755, true);
            }
            file_put_contents($fileName1, $jsonData . PHP_EOL);
            //Log::info("Mensaje almacenado en archivo (server1): {$fileName1}");
        
            // Guardar en servidor 2
            $fileName2 = storage_path("app/mqtt/server2/{$sanitizedTopic}_{$uniqueId}.json");
            if (!file_exists(dirname($fileName2))) {
                mkdir(dirname($fileName2), 0755, true);
            }
            file_put_contents($fileName2, $jsonData . PHP_EOL);
            //Log::info("Mensaje almacenado en archivo (server2): {$fileName2}");
        } catch (\Exception $e) {
            Log::error("Error storing message in file: " . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/modbus/tara",
     *     summary="Set tara value for a Modbus",
     *     tags={"Modbus"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="value", type="number")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tara value set successfully",
     *         @OA\JsonContent(@OA\Property(property="message", type="string"))
     *     ),
     *     @OA\Response(response=400, description="Token not provided",
     *         @OA\JsonContent(@OA\Property(property="error", type="string"))
     *     ),
     *     @OA\Response(response=404, description="Invalid token or Modbus not found",
     *         @OA\JsonContent(@OA\Property(property="error", type="string"))
     *     )
     * )
     */
    public function setTara(Request $request)
    {
        $token = trim($request->input('token'));
        $id = $request->input('id');
        $value = $request->input('value');
        $value = is_numeric($value) ? $value + 0 : 0;

        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 400);
        }

        $modbus = Modbus::where('token', $token)->where('id', $id)->first();
        if (!$modbus) {
            return response()->json(['error' => 'Invalid token or Modbus not found'], 404);
        }

        $modbus->tara = $value;
        $modbus->save();

        return response()->json(['message' => 'Tara value set successfully']);
    }

    /**
     * @OA\Post(
     *     path="/api/modbus/tara/reset",
     *     summary="Reset tara value for a Modbus to zero",
     *     tags={"Modbus"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="token", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tara reset successfully",
     *         @OA\JsonContent(@OA\Property(property="message", type="string"))
     *     ),
     *     @OA\Response(response=400, description="Token not provided",
     *         @OA\JsonContent(@OA\Property(property="error", type="string"))
     *     ),
     *     @OA\Response(response=404, description="Invalid token or Modbus not found",
     *         @OA\JsonContent(@OA\Property(property="error", type="string"))
     *     )
     * )
     */
    public function resetTara(Request $request)
    {
        $token = trim($request->input('token'));
        $id = $request->input('id');

        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 400);
        }

        $modbus = Modbus::where('token', $token)->where('id', $id)->first();
        if (!$modbus) {
            return response()->json(['error' => 'Invalid token or Modbus not found'], 404);
        }

        $modbus->tara = 0;
        $modbus->save();

        return response()->json(['message' => 'Tara reset successfully']);
    }
}
