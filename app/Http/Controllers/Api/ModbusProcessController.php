<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\ControlWeight;
use App\Models\ApiQueuePrint;
use Rawilk\Printing\Facades\Printing;
use Picqer\Barcode\BarcodeGeneratorPNG;
use App\Models\ControlHeight;
use App\Models\Printer;
use App\Models\LiveTrafficMonitor;
use App\Models\OrderStat;
use App\Models\Modbus;
use App\Models\OperatorPost;
use App\Models\Operator;
use App\Models\SupplierOrder;
use App\Models\ProductList;
use Carbon\Carbon;

class ModbusProcessController extends Controller
{
    public function processMqttData(Request $request)
    {
                    // Ignorar desconexión del cliente
                    ignore_user_abort(true);
    // Validar los datos recibidos
    $validatedData = $request->validate([
        'id' => 'required|integer', // Asegúrate de que ID sea un número entero
        'data' => 'required|array', // Asegúrate de que data sea un array
    ]);

    // Usar los datos validados
    $id = $validatedData['id'];
    $data = $validatedData['data'];
        

        $config = Modbus::where('id', $id)->first();
        $topic= $config->mqtt_topic_modbus;
        // Verificar que $config exista
        if (!$config) {
            return response()->json([
                'status' => 'error',
                'message' => "No se encontró un Modbus con el tópico '{$topic}'",
            ], 404);
        }
    
    
        // Extraer Value
        if (empty($config->json_api)) {
            $value = $data['value'] ?? null;
            if ($value === null) {
                Log::error("Error: No se encontró 'value' en el JSON cuando json_api está vacío.");
                return response()->json([
                    'status' => 'error',
                    'message' => "No se encontró un Value válido.",
                ], 200);
            }
        } else {
            $jsonPath = $config->json_api;
            $value = $this->getValueFromJson($data, $jsonPath);
            if ($value === null) {
                Log::warning("Advertencia: No se encontró la clave '$jsonPath' en la respuesta JSON, buscando el valor directamente.");
                $value = $data['value'] ?? null;
                if ($value === null) {
                    Log::error("Error: No se encontró 'value' en el JSON.");
                    return response()->json([
                        'status' => 'error',
                        'message' => "No se encontró un Value válido.",
                    ], 404);
                }
            }
        }
        
        
        
        // Verifica el valor del campo 'model_name' y llama al método correspondiente
        if ($config['model_name'] === 'height') {
            $this->processHeightModel($config, $value, $data);
        } elseif ($config['model_name'] === 'weight') {
            $this->processWeightModel($config, $value, $data);
        } else {
            // Manejo de casos no reconocidos
            return response()->json([
                'status' => 'error',
                'message' => "No se encontró un Value válido.",
            ], 200);
        }


        $numericValue = floatval($value); // Usamos floatval para manejar tanto enteros como decimales
        if ($numericValue <= 0) {
            $this->makeZero($config, $value);
            return response()->json([
                'status' => 'success',
                'message' => 'Zero action taken.',
            ]);
        }
    
        return response()->json([
            'status' => 'success',
            'message' => 'Datos procesados correctamente.',
        ]);
    }
    
    
    private function getValueFromJson($data, $jsonPath)
    {
        $keys = explode(', ', $jsonPath);
        foreach ($keys as $key) {
            $key = trim($key);
            if (isset($data[$key])) {
                return isset($data[$key]['value']) ? $data[$key]['value'] : null;
            }
        }
        return null;
    }
    
    private function makeZero($config, $value)
    {
        Log::info("Making zero for Modbus ID: {$config->id}, current value: {$value}");
        $topic= $config->mqtt_topic_modbus;
        // Generar el nuevo tópico cambiando 'peso' por 'zero'
        $topicParts = explode('/', $topic);
        
        if (in_array('peso', $topicParts)) {
            $topicZero = str_replace('peso', 'zero', implode('/', $topicParts));
        } else {
            // Si 'peso' no está en el tópico, usar el tópico original
            $topicZero = $topic . '/zero';
        }
        
        // Definir el mensaje JSON
        $messageZero = json_encode(['value' => true]);
        
        // Publicar el mensaje MQTT
        $this->publishMqttMessage($topicZero, $messageZero);
        
        Log::info("Zero command sent for Modbus ID: {$config->id} on topic: {$topicZero}");
    }

    public function processWeightModel($config, $value, $data)
    {
        $updatedValue = $value / $config->conversion_factor;
        
        
        if ($config->calibration_type == '0') { 
            // O 'software' si usas un booleano
            if ($updatedValue > $config->tara_calibrate) {
            // Restamos 'tara_calibrate' si es mayor
                $updatedValue -= $config->tara_calibrate;
            } 
                // Ahora, comparamos con 'tara' después de la posible resta anterior
            if ($updatedValue > $config->tara) {
                $updatedValue -= $config->tara;
            }
        } else { // Calibración por HARDWARE
            //Por momento no tengo logica de recalibrate por hRDWARE
        }
        
        
        $mqttTopic = $config->mqtt_topic . '1/gross_weight';
        $mqttTopic2 = $config->mqtt_topic . '2/gross_weight';
       // Obtiene el último valor guardado
        $lastValue = $config->last_value;
      //  Log::info("Mi valor:{$lastValue}");
        // Actualiza el valor en la base de datos si ha cambiado

            $updateResponse = $config->update(['last_value' => $updatedValue]);
        if($config->model_type < 1) {  
            if($updateResponse) {
                // Buscar el registro en product_lists donde productName coincide con el de $config
                try {
                    if (isset($config->productName) && $config->productName !== '') {
                        $product = ProductList::where('client_id', $config->productName)->first();
                        $boxKgTheoretic = $product ? $product->box_kg : 0;
                    } else {
                        Log::warning('productName no está definido o está vacío en config.');
                        $boxKgTheoretic = 0;
                        $product = null;
                    }
                } catch (\Exception $e) {
                    $prodName = isset($config->productName) ? $config->productName : 'N/A';
                    Log::error("Error al obtener el producto para productName '{$prodName}': " . $e->getMessage());
                    $boxKgTheoretic = 0;
                    $product = null;
                }                

            } else {
                $boxKgTheoretic = 0;
                $product = null;
            }

            if($boxKgTheoretic > 0) {
                $excessWeight = $updatedValue - $boxKgTheoretic;
            } else {
                $excessWeight = 0;
            }

            // Construye el mensaje
            $message = [
                'value' => $updatedValue,
                'time' => date('c'),
                'excessWeight' => $excessWeight,
                'boxKgTheoretic' => $boxKgTheoretic,
                'productName'     => $product ? $product->name : 'N/A',
                'productClientId'     => $product ? $product->client_id: 'N/A'
            ];
        }else{
            // Construye el mensaje
            $message = [
                'value' => $updatedValue,
                'time' => date('c'),
            ];
        }
            

            //Log::info("Mensaje MQTT: " . json_encode($message));

            // Publica el mensaje MQTT
            $this->publishMqttMessage($mqttTopic, $message);
            //OJO CON ESTO ES SOLO SI LA BASCULA TIENE UN SOLO CONTADOR OJO
            $this->publishMqttMessage($mqttTopic2, $message);

                // Comprueba si max_kg y value son ambos 0

            if ($config->max_kg == 0 && $value == 0) {
                // Resetea campos específicos
                $config->update([
                    'max_kg' => 0,
                    'last_kg' => 0,
                    'demension' => 0,
                    'last_rep' => 0
                ]);

                Log::info("Valores reseteados: max_kg, last_kg, demension y last_rep a 0.");

                // No llamamos a processWeightData si se cumple la condición
                return;
            }

        $this->processWeightData($config, $updatedValue, $data);
    }

    // Implementar funciones para otros modelos
    public function processHeightModel($config, $value, $data)
    {
        // Lógica para procesar datos de altura
        Log::info("Procesando modelo de altura. Valor: {$value}"); 

        // Obtener valores relevantes de la configuración
        $dimensionDefault = $config->dimension_default;
        $dimensionMax = $config->dimension_max;
        $offsetMeter = $config->offset_meter;
        $dimensionVariation = $config->dimension_variacion;
        $dimensionOffset = $config->offset_meter;

        // Calcular el valor actual
        $currentValue = $dimensionDefault - $value + $offsetMeter;

        Log::info("Valor actual calculado: {$currentValue} y dimension maxima anterior : {$dimensionMax}, ID: {$config->id}");

        // Verificar si el valor actual es mayor que el máximo registrado
        if ($currentValue > $dimensionMax) {
            Log::info("Actualizando dimension_max: Valor actual {$currentValue} es mayor que dimension_max anterior {$dimensionMax}, ID: {$config->id}");
            $config->dimension_max = $currentValue;
            $config->save();

            Log::info("Nuevo dimension_max guardado en modbuses: {$currentValue}");

        } else {
            Log::info("No se actualiza dimension_max: Valor actual {$currentValue} no es mayor que dimension_max {$dimensionMax}, ID: {$config->id}");
        }

                    // Actualizar dimension en otros registros de Modbuses donde dimension_id = $config->id

                Modbus::where('dimension_id', $config->id)
                    ->where('dimension', '<', $currentValue) // Verifica que el valor actual es mayor
                    ->where('max_kg', '!=', 0) // Verifica que max_kg no sea 0
                    ->update(['dimension' => $currentValue]);
                


        Log::info("dimension_max actualizado en otros registros de Modbuses donde dimension_id = {$config->id}");

        if (($value + $dimensionOffset) > ($dimensionDefault - $dimensionVariation) && $dimensionMax > ($dimensionOffset + $dimensionVariation)) {
             // Guardar el valor máximo actual antes de reiniciar
        $controlHeight = new ControlHeight();
        $controlHeight->modbus_id = $config->id;
        $controlHeight->height_value = $dimensionMax;
        $controlHeight->save();

        Log::info("Nuevo registro en control_heights guardado con dimension_max. Valor: {$dimensionMax}, ID: {$config->id}");

        // Reiniciar dimension_max a 0
        $config->dimension_max = 0;
        $config->save();
        Log::info("Nuevo registro en control_heights guardado con currentValue. Valor: {$currentValue}, ID: {$config->id}  Y dimension_max reiniciado a 0 en modbuses");
        }

    }


    private function processWeightData(Modbus $config, $value, $data)
    {
    // Obtener valores actuales de la base de datos
    $maxKg = floatval($config->max_kg);
    $totalKgOrder = floatval($config->total_kg_order);
    $totalKgShift = floatval($config->total_kg_shift);
    $repNumber = intval($config->rep_number);
    $minKg = floatval($config->min_kg);
    $lastKg = floatval($config->last_kg);
    $lastRep = intval($config->last_rep);
    $variacionNumber = floatval($config->variacion_number);
    $topic_control = $config->mqtt_topic . '1/control_weight';
    $topic_control2 = $config->mqtt_topic . '2/control_weight';
    $topic_box_control = $config->mqtt_topic . '1';
    $topic_box_control2 = $config->mqtt_topic . '2';
    $dimensionFinal = intval($config->dimension);
    //Log::debug("({$minKg} kg)");

    // Inicializar la variable para el número de cajas
    $newBoxNumber = intval($config->rec_box);
    $newBoxNumberShift = intval($config->rec_box_shift);
    $newBoxNumberUnlimited = intval($config->rec_box_unlimited);    
        // Lógica de control de peso y repeticiones
        if ($value >= $minKg) { // Si el valor actual es mayor o igual al mínimo
            Log::info("Valor actual ({$value} kg) es mayor o igual al mínimo ({$minKg} kg)"); // Logging detallado
            //hacermos este if para que contorize bien desde inicio si no el lastkg es 0 y lo ve como un valor mall hasta la segunda validacion
            if($lastKg <= 0){
                $lastKg = $value; // Actualizar el último valor a la nueva medida
            }
            if (abs($value - $lastKg) <= $variacionNumber) { // Si la variación está dentro del rango permitido
                Log::info("Valor estable dentro del rango de variación.");
                $lastRep++; // Incrementar el contador de repeticiones
                //original era asi , solo validaba mas grande 
                //if ($lastRep >= $repNumber && $value >= $minKg && $value > $maxKg) { 
                if ($lastRep >= $repNumber && $value >= $minKg) { // Si se alcanza el número de repeticiones requerido, pero el valor es mas grande que minimo permitido y que el valor es mas grande que maxKG
                    Log::info("Número de repeticiones alcanzado. Nuevo máximo: {$value} kg");
                    $maxKg = $value; // Actualizar el valor máximo
                    $lastRep = 0; // Reiniciar el contador de repeticiones
                }
            } else {
                Log::info("Valor fuera del rango de variación. Reiniciando repeticiones. El valor actual es:{$value} kg, el valor minimo: {$minKg} kg");
                $lastRep = 0; // Reiniciar el contador de repeticiones si la variación está fuera del rango permitido
            }

            $lastKg = $value; // Actualizar el último valor con el valor actual
        } elseif ($maxKg > $minKg && $value < $minKg) { // Si el valor es menor que el mínimo y $maxKg no es nulo
            Log::info("Valor por debajo del mínimo. Enviando mensaje de control de peso: {$maxKg} kg");



            // Verificar si el JSON tiene el campo 'check' y usarlo para asignar a maxKg
            if (isset($data['check'])) {
                $maxKg = $data['check'] / $config->conversion_factor;;
                Log::info("Se ha obtenido el valor de 'check' desde el JSON: {$maxKg}");
            } else {
                Log::info("No se encontró el campo 'check' en los datos recibidos.El valor actual es:{$value} kg");
            }
            
            // Incrementar el recuento de cajas en rec_box
            $newBoxNumber++; // es por orderId
            $newBoxNumberShift++; //por turno
            $newBoxNumberUnlimited++; //indefinido
            // Generar un número de barcoder único
            $uniqueBarcoder = uniqid('', true);

            
            //buscamos en ControlWeight si ya existe un registro para el last_box_number = $newBoxNumber con modbus_id = $config->id en los ultimos 5 minutos
            $fiveMinutesAgo = Carbon::now()->subMinutes(5);
            $controlWeightExists = ControlWeight::where('modbus_id', '=', $config->id)
                ->where('last_box_number', '=', $newBoxNumber)
                ->where('created_at', '>=', $fiveMinutesAgo)
                ->exists();
            if ($controlWeightExists) {
                Log::alert("message: control weight already exists for last_box_number=$newBoxNumber and modbus_id=$config->id in the last 5 minutes");
                return;
            }

            // Buscar el registro en product_lists donde productName coincide con el de $config
            if($config->model_type < 1) {  
                try {
                    if (isset($config->productName) && $config->productName !== '') {
                        $product = ProductList::where('client_id', $config->productName)->first();
                        $boxKgTheoretic = $product ? $product->box_kg : 0;
                        $productClientId = $product ? $product->name  : "N/A";
                    } else {
                        Log::warning('productName no está definido o está vacío en config.');
                        $boxKgTheoretic = 0;
                        $productClientId = "N/A";
                    }
                } catch (\Exception $e) {
                    $prodName = isset($config->productName) ? $config->productName : 'N/A';
                    Log::error("Error al obtener el producto para productName '{$prodName}': " . $e->getMessage());
                    $boxKgTheoretic = 0;
                    $productClientId = "N/A";
                }
            }else{
                $boxKgTheoretic = 0;
                $productClientId = "N/A";
            }

            

            try {
                $box_m3 = (
                    isset($config->box_width, $config->box_length, $dimensionFinal) &&
                    $config->box_width > 0 &&
                    $config->box_length > 0 &&
                    $dimensionFinal > 0
                ) ? ($config->box_width * $config->box_length * $dimensionFinal) / 1000000000 : 0;
            
                // Si $box_m3 resulta ser 0 (o null, según tu lógica) se puede decidir no procesar el paquete
                if ($box_m3 == 0) {
                    // Aquí puedes agregar alguna acción adicional, por ejemplo, un log o retorno temprano
                    Log::warning("El valor calculado de box_m3 es 0; se cancelará el procesamiento del paquete.");
                }
            } catch (\Exception $e) {
                Log::error("Error al procesar el paquete de datos: " . $e->getMessage());
                $box_m3 = 0; // Asigna 0 en caso de error
                return response()->json(['error' => 'Ocurrió un error al procesar los datos'], 500);
            }            
            

            $messageControl = [
                        'type' => "NoEPC",
                        'unit' => "Kg",
                        'value' => $maxKg,
                        'excess' => $maxKg - $boxKgTheoretic,
                        'total_excess' => "0",
                        'rating' => "1",
                        'time' => date('c'),
                        'check' => "1",
                        'dimension' => $dimensionFinal,
                        'barcode_qr_rfid' => $uniqueBarcoder,
                        'box_m3' => $box_m3 ?? 0,
                        'box_kg_theoretic' => $boxKgTheoretic,
                        'productClientId' => $productClientId,

                ];
            $this->publishMqttMessage($topic_control, $messageControl); // Enviar mensaje de control
            $this->publishMqttMessage($topic_control2, $messageControl); // Enviar mensaje de control    


            // Intentar guardar los datos en la tabla control_weight
            if ($config->is_material_receiver) {
                try {
                    // Buscar en supplier_orders una línea sin control_weight_id asociada
                    $supplierOrder = SupplierOrder::whereNull('control_weight_id')->first();
                    
                    if ($supplierOrder) {
                        $controlWeight = ControlWeight::create([
                            'modbus_id'             => $config->id,
                            'last_control_weight'   => $maxKg,
                            'last_dimension'        => $dimensionFinal,
                            'last_box_number'       => $newBoxNumber,
                            'last_box_shift'        => $newBoxNumberShift,
                            'last_barcoder'         => $uniqueBarcoder,
                            'last_final_barcoder'   => null,
                            'supplier_order_id'     => $supplierOrder->id,
                        ]);
                        Log::info("Datos guardados en control_weight, Modbus ID: {$config->id}");
                        
                        // Asignar el id del control_weight recién creado en supplier_orders
                        $supplierOrder->update(['control_weight_id' => $controlWeight->id]);
                        Log::info("Se asignó control_weight_id {$controlWeight->id} en supplier_orders (ID: {$supplierOrder->id}).");
                    } else {
                        $controlWeight = ControlWeight::create([
                            'modbus_id'             => $config->id,
                            'last_control_weight'   => $maxKg,
                            'last_dimension'        => $dimensionFinal,
                            'last_box_number'       => $newBoxNumber,
                            'last_box_shift'        => $newBoxNumberShift,
                            'last_barcoder'         => $uniqueBarcoder,
                            'last_final_barcoder'   => null,
                            'supplier_order_id'     => null,
                        ]);
                        Log::info("Datos guardados en control_weight, Modbus ID: {$config->id}");
                        Log::info("No se encontró una línea en supplier_orders sin control_weight_id para el Modbus ID: {$config->id}");
                    }
                } catch (\Exception $e) {
                    Log::error("Error al guardar datos en control_weight, Modbus ID: {$config->id}: " . $e->getMessage());
                }
            } else {
                Log::info("El Modbus ID: {$config->id} no está configurado como báscula de recepción de material.");
                try {
                    $controlWeight = ControlWeight::create([
                        'modbus_id'             => $config->id,
                        'last_control_weight'   => $maxKg,
                        'last_dimension'        => $dimensionFinal,
                        'last_box_number'       => $newBoxNumber,
                        'last_box_shift'        => $newBoxNumberShift,
                        'last_barcoder'         => $uniqueBarcoder,
                        'last_final_barcoder'   => null,
                        'supplier_order_id'     => null,
                    ]);
                    Log::info("Datos guardados en control_weight, Modbus ID: {$config->id}");
                } catch (\Exception $e) {
                    Log::error("Error al guardar datos en control_weight, Modbus ID: {$config->id}, Error: " . $e->getMessage());
                }
            }            
            
        // Añadimos la lógica para buscar en operator_post y actualizar en operators
        try {
            $operatorPost = OperatorPost::where('finish_at', null)
                ->where('modbus_id', $config->id)
                ->first();

            if ($operatorPost) {
                $operatorId = $operatorPost->operator_id;

                // Buscar el operador por ID
                $operator = Operator::find($operatorId);

                if ($operator) {
                    // Incrementar los valores de count_shift y count_order
                    $operator->increment('count_shift');
                    $operator->increment('count_order');

                    Log::info("Operador actualizado: count_shift y count_order incrementados para el Operator ID: {$operatorId}");
                } else {
                    Log::info("No se encontró el operador con ID: {$operatorId}");
                }
            } else {
                Log::info("No se encontró ningún registro en operator_post con updated_at NULL y modbus_id: {$config->id}");
            }
        } catch (\Exception $e) {
            // Log de errores al intentar actualizar los datos
            Log::info("Error al procesar datos de operator_post y operators para el Modbus ID: {$config->id}");
        }

            $totalKgShift=$maxKg + $totalKgShift;
            $totalKgOrder= $maxKg + $totalKgOrder;
            $finalMaxKg= $maxKg;
            $finalDimensionFinal= $dimensionFinal;

            $maxKg = 0; // Reiniciar el valor máximo
            $lastKg = 0; // Reiniciar el último valor
            $lastRep = 0; // Reiniciar el contador de repeticiones
            $dimensionFinal = 0; //Reiniciar altura de la caja palet


            //llamar mqtt recuento de bultos cajas
            $messageBoxNumber = [
                    'value' => $newBoxNumber,
                    'status' => 2
            ]; 
            $this->publishMqttMessage($topic_box_control, $messageBoxNumber); // Enviar mensaje de control

            //actualizr el peso acumulado por turno y order cuando se ha generado una nueva caja
                

            $messageTotalKgOrder = [
                'value' => round($totalKgOrder), // Redondea sin decimales
                'status' => 2
            ];
            
            $this->publishMqttMessage($topic_box_control2, $messageTotalKgOrder); // Enviar mensaje de control

                //llamar a la api externa si se ha pedido desde el cliente, esto comprueba si el cliente nos ha mandado valor en api para devolverle las info
            $apiQueue = ApiQueuePrint::where('modbus_id', $config->id)
                                    ->where('used', false)
                                    ->orderBy('id', 'asc')
                                    ->first();

            if ($apiQueue) {
                if ($apiQueue->value == 0) {
                    $apiQueue->used = true;
                    $apiQueue->save();
                    Log::info("No llamo a la API externa por que el valor es 0, el Modbus ID: {$config->id}");
                } else {
                    $this->callExternalApi($apiQueue, $config, $newBoxNumber, $finalMaxKg, $finalDimensionFinal, $uniqueBarcoder);
                    Log::info("Llamada a la API externa para el Modbus ID: {$config->id} FINALIZADA");
                }
            }else{
                Log::info("No hay llamada a la API externa para el Modbus ID: {$config->id}");
            }

            //llamar a la impresora local para imprimir si es un bulto anonimo para habilitar bultos anonimos tenemos que anadir una impresora a la modbus si impresora no existe no se imprime, el printer_id tiene que no estar null con 0 o vacio
            if (!is_null($config->printer_id) && trim($config->printer_id)) {
                $this->printLabel($config, $uniqueBarcoder);
            } else {
                Log::info('No hay configuración para imprimir una etiqueta.');
            }
        }

        $config->update([
            'rec_box' => $newBoxNumber,
            'rec_box_shift' => $newBoxNumberShift,
            'rec_box_unlimited' => $newBoxNumberUnlimited,
            'max_kg' => $maxKg,
            'last_kg' => $lastKg,
            'last_rep' => $lastRep,
            'dimension' => $dimensionFinal,
            'total_kg_order' => $totalKgOrder,
            'total_kg_shift' => $totalKgShift
        ]);
        
        Log::info("Datos actualizados y reseteado a 0, el Modbus ID: {$config->id}");
        $this->OrderStat($config, $newBoxNumberShift, $newBoxNumber, $totalKgShift, $totalKgOrder, $lastKg);

    }
    public function OrderStat($config, $newBoxNumberShift, $newBoxNumber, $totalKgShift, $totalKgOrder, $lastKg) {
        try {
            // Obtener la última entrada de order_stats correspondiente a la línea de producción del Modbus
            $orderStats = OrderStat::where('production_line_id', $config->production_line_id)
                        ->latest('id') // Ordenar por ID para obtener el último registro
                        ->first();    
    
            if ($orderStats) {
                // Determinar el prefijo de columna basado en model_type
                $weightColumnPrefix = "weights_{$config->model_type}_" ;
    
                // Preparar los valores para actualizar en order_stats
                $weightUpdates = [
                    "{$weightColumnPrefix}shiftNumber" => $newBoxNumberShift,
                    "{$weightColumnPrefix}shiftKg" => $config->model_type == 0 ? $totalKgShift : $totalKgShift + $lastKg,
                    "{$weightColumnPrefix}orderNumber" => $newBoxNumber,
                    "{$weightColumnPrefix}orderKg" => $config->model_type == 0 ? $totalKgOrder : $totalKgOrder + $lastKg
                ];
    
                // Actualizar los valores en la entrada de order_stats
                $orderStats->update($weightUpdates);
    
                Log::info("Valores actualizados en order_stats para production_line_id: {$config->production_line_id}, model_type: {$config->model_type}");
            } else {
                Log::info("No se encontró un registro en order_stats para production_line_id: {$config->production_line_id}");
            }
        } catch (\Exception $e) {
            Log::error("Error al actualizar order_stats: " . $e->getMessage());
            // Aquí puedes agregar código para enviar una notificación o registrar el error
        }
    }

    private function printLabel($config, $uniqueBarcoder)
    {
        // Buscar la impresora en la base de datos (una sola vez)
        $printer = Printer::find($config->printer_id);

        if (!$printer) {
            // Manejo de caso donde la impresora no se encuentra
           // error_log('Impresora no encontrada con el ID: ' . $config->printer_id);
            return; // Salir de la función si no hay impresora
        }

        if ($printer->type == 0) { // Impresión local (CUPS)
            $generator = new BarcodeGeneratorPNG();
            $barcodeData = $generator->getBarcode($uniqueBarcoder, $generator::TYPE_CODE_128);

            // Convertir a Base64
            $base64Image = base64_encode($barcodeData);

            try {
                $printJob = Printing::newPrintTask()
                    ->printer($printer->name)
                    ->content($base64Image)
                    ->send();

                Log::info('Etiqueta impresa correctamente.');
            } catch (\Exception $e) {
                Log::error('Error al imprimir la etiqueta: ' . $e->getMessage());
                // Opcional: Mostrar mensaje de error al usuario
            }
        } else {
             // Impresión mediante API de Python
            $response = Http::post($printer->api_printer, [
                'barcode' => $uniqueBarcoder,
            ]);

            if ($response->failed()) {
               // error_log('Error al llamar a la API de Python: ' . $response->body());
            }
        }
    }


    private function callExternalApi($apiQueue, $config, $newBoxNumber, $maxKg, $dimensionFinal, $uniqueBarcoder)
    {
        Log::info("Llamada a la API externa para el Modbus ID: {$config->id}");
    
        $apiQueue->used = true;
        $apiQueue->control_weight = $maxKg;
        $apiQueue->control_height = $dimensionFinal;
        $apiQueue->barcoder = $uniqueBarcoder;
        $apiQueue->box_number = $newBoxNumber;
        $apiQueue->save(); 
        
        if ($apiQueue->url_back === 'tcp') {
            $baseUrl = env('LOCAL_SERVER');
            if (substr($baseUrl, -1) === '/') {
                $baseUrl = rtrim($baseUrl, '/');
            }
            $apiQueue->url_back = $baseUrl . '/api/publish-message';
            $useMethod = 'POST'; 
    
            $box_m3 = (
                isset($config->box_width, $config->box_length, $dimensionFinal) &&
                $config->box_width > 0 &&
                $config->box_length > 0 &&
                $dimensionFinal > 0
            ) ? ($config->box_width * $config->box_length * $dimensionFinal) / 1000000000 : 0;
    
            //para poner a la izcherda relleno de 8 digitos siempre
            $maxKgPadded = str_pad($maxKg, 8, '0', STR_PAD_LEFT);
            $maxKgInt = intval($maxKgPadded ); // Esto convierte 10.5 a 10
            $maxKgInt = str_pad($maxKgInt, 8, '0', STR_PAD_LEFT);
            $dimensionFinalPadded = str_pad($dimensionFinal, 4, '0', STR_PAD_LEFT);

            $message = "'token': '{$apiQueue->token_back}', ";
            $message .= "'value': '{$apiQueue->value}', ";
            $message .= "'rec_box': '{$newBoxNumber}', ";
            $message .= "'max_kg': '{$maxKgInt}', ";
            $message .= "'last_dimension': '{$dimensionFinalPadded}', ";
            $message .= "'last_barcoder': '{$uniqueBarcoder}', ";
            $message .= "'used_value': '{$apiQueue->value}', ";
            $message .= "'box_m3': '{$box_m3}'";
    
            $dataForTcp = [
                'message' => $message
            ];
    
            $ch = curl_init($apiQueue->url_back);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $useMethod);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dataForTcp));

            // Ignorar la verificación SSL
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
            Log::info("Enviando datos con cURL a {$apiQueue->url_back}. Datos: " . json_encode($dataForTcp));
            
            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
            if ($responseBody === false) {
                Log::error("Error en la petición cURL: " . curl_error($ch));
            } else {
                if ($httpCode >= 200 && $httpCode < 300) {
                    Log::info("Respuesta de la API externa (cURL) para TCP: " . $responseBody);
                } else {
                    Log::error("Error en la respuesta de la API externa (cURL) para TCP. Código de estado: " . $httpCode . ", Cuerpo: " . $responseBody);
                }
            }
            curl_close($ch);
    
            return;
        }
        
        $dataToSend = [
            'token' => $apiQueue->token_back,
            'rec_box' => $newBoxNumber,
            'max_kg' => $maxKg,
            'last_dimension' => $dimensionFinal,
            'last_barcoder' => $uniqueBarcoder,
            'used_value' => $apiQueue->value,
            'box_m3' => (
                isset($config->box_width, $config->box_length, $dimensionFinal) &&
                $config->box_width > 0 &&
                $config->box_length > 0 &&
                $dimensionFinal > 0
            )
                ? ($config->box_width * $config->box_length * $dimensionFinal) / 1000000000
                : 0, // Si algún campo es 0 o null, el valor será 0
        ];
        
    
        $dataToSend2 = [
            'alto' => (string)$dimensionFinal,
            'peso' => (string)$maxKg,
            'used_value' => (string)$apiQueue->value,
        ];
    
        // Construir la cadena URL codificada sin comas
        $dataToSend3 = http_build_query($dataToSend2, '', '&');
        
        try {
            $useMethod = env('EXTERNAL_API_QUEUE_TYPE', 'put');
            $useModel = env('EXTERNAL_API_QUEUE_MODEL', 'dataToSend');
            $useCurl = env('USE_CURL', false);
    
            if ($useCurl) {
                // Implementación con cURL
                $ch = curl_init($apiQueue->url_back);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Equivalente a -k en curl
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($useMethod));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
                
                // Usar dataToSend3 que ya está en formato correcto para x-www-form-urlencoded
                curl_setopt($ch, CURLOPT_POSTFIELDS, $dataToSend3);
                
                Log::info("Enviando datos con cURL a {$apiQueue->url_back}. Datos: " . $dataToSend3);
                
                $responseBody = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                
                if ($responseBody === false) {
                    throw new \Exception(curl_error($ch));
                }
                
                curl_close($ch);
                
                if ($httpCode >= 200 && $httpCode < 300) {
                    $responseData = json_decode($responseBody, true);
                    Log::info("Respuesta de la API externa (cURL): " . json_encode($responseData));
                } else {
                    Log::error("Error en la respuesta de la API externa (cURL). Código de estado: " . $httpCode . ", Cuerpo: " . $responseBody);
                }
            } else {
                // Código existente con Http facade
                if ($useModel == 'dataToSend3') {
                    if ($useMethod != 'post') {
                        Log::info("Enviando datos a {$apiQueue->url_back} con PUT. Datos: " . json_encode($dataToSend3));
                        $response = Http::withHeaders([
                            'Content-Type' => 'application/x-www-form-urlencoded',
                        ])->withBody($dataToSend3, 'application/x-www-form-urlencoded')->put($apiQueue->url_back);
                    } else {
                        Log::info("Enviando datos a {$apiQueue->url_back} con POST. Datos: " . json_encode($dataToSend3));
                        $response = Http::withHeaders([
                            'Content-Type' => 'application/x-www-form-urlencoded',
                        ])->withBody($dataToSend3, 'application/x-www-form-urlencoded')->post($apiQueue->url_back);
                    }
                } elseif ($useModel == 'dataToSend2') {
                    if ($useMethod != 'post') {
                        Log::info("Enviando datos a {$apiQueue->url_back} con PUT. Datos: " . json_encode($dataToSend2));
                        $response = Http::put($apiQueue->url_back, $dataToSend2);
                    } else {
                        Log::info("Enviando datos a {$apiQueue->url_back} con POST. Datos: " . json_encode($dataToSend2));
                        $response = Http::post($apiQueue->url_back, $dataToSend2);
                    }
                } else {
                    if ($useMethod != 'post') {
                        Log::info("Enviando datos a {$apiQueue->url_back} con PUT. Datos: " . json_encode($dataToSend));
                        $response = Http::put($apiQueue->url_back, $dataToSend);
                    } else {
                        Log::info("Enviando datos a {$apiQueue->url_back} con POST. Datos: " . json_encode($dataToSend));
                        $response = Http::post($apiQueue->url_back, $dataToSend);
                    }
                }
    
                if ($response->successful()) {
                    $responseData = $response->json();
                    Log::info("Respuesta de la API externa: " . json_encode($responseData));
                } else {
                    Log::error("Error en la respuesta de la API externa. Código de estado: " . $response->status() . ", Cuerpo: " . $response->body());
                }
            }
        } catch (\Exception $e) {
            Log::error("Error al llamar a la API externa para el Modbus ID: {$config->id}. Error: " . $e->getMessage());
        }
    
        $apiQueue->used = true;
        if ($apiQueue->save()) {
            Log::info("Estado 'used' actualizado a true para el Modbus ID: {$config->id}");
        } else {
            Log::error("No se pudo actualizar el estado 'used' en la base de datos para el Modbus ID: {$config->id}");
        }
    }
    



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

}