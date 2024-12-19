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
use App\Models\MqttSendServer1;
use App\Models\MqttSendServer2;
use App\Models\OrderStat;
use App\Models\Modbus;

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
        $topic= $config->topic;
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
        //Log::info("Mi valor:{$value}");
        
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

            // Construye el mensaje
            $message = [
                'value' => $updatedValue,
                'time' => date('c')
            ];

            Log::info("Mensaje MQTT: " . json_encode($message));

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
                    ->whereRaw('last_value >= max_kg - variation_number')//borrar esta linea si falla // Verifica que last_value no sea más pequeño que max_kg - variation_number
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

            if (abs($value - $lastKg) <= $variacionNumber) { // Si la variación está dentro del rango permitido
                Log::info("Valor estable dentro del rango de variación.");
                $lastRep++; // Incrementar el contador de repeticiones

                if ($lastRep >= $repNumber && $value >= $minKg && $value > $maxKg) { // Si se alcanza el número de repeticiones requerido, pero el valor es mas grande que minimo permitido y que el valor es mas grande que maxKG
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
            


            $messageControl = [
                        'type' => "NoEPC",
                        'unit' => "Kg",
                        'value' => $maxKg,
                        'excess' => "0",
                        'total_excess' => "0",
                        'rating' => "1",
                        'time' => date('c'),
                        'check' => "1",
                        'dimension' => $dimensionFinal
                ];
            $this->publishMqttMessage($topic_control, $messageControl); // Enviar mensaje de control
            $this->publishMqttMessage($topic_control2, $messageControl); // Enviar mensaje de control    

            // Incrementar el recuento de cajas en rec_box
            $newBoxNumber++; // es por orderId
            $newBoxNumberShift++; //por turno
            $newBoxNumberUnlimited++; //indefinido
            // Generar un número de barcoder único
            $uniqueBarcoder = uniqid('', true);

                

            // Intentar guardar los datos en la tabla control_weight
        try {
            $controlWeight = ControlWeight::create([
                'modbus_id' => $config->id,
                'last_control_weight' => $maxKg,
                'last_dimension' => $dimensionFinal,
                'last_box_number' => $newBoxNumber,
                'last_box_shift' => $newBoxNumberShift,
                'last_barcoder' => $uniqueBarcoder,
                'last_final_barcoder' => null,
            ]);

            // Log informativo de los datos guardados
            Log::info("Datos guardados en control_weight,el Modbus ID: {$config->id}");
        } catch (\Exception $e) {
            // Log de errores al intentar guardar los datos
            Log::info("Error al guardar datos en control_weight, el Modbus ID: {$config->id}");
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
                ->oldest()
                ->first();

            if ($apiQueue) {
                if ($apiQueue->value == 0) {
                    $apiQueue->used = true;
                    $apiQueue->save();
                    Log::info("No llamo a la API externa por que el valor es 0, el Modbus ID: {$config->id}");
                } else {
                    $this->callExternalApi($apiQueue, $config, $newBoxNumber, $finalMaxKg, $finalDimensionFinal, $uniqueBarcoder);
                    Log::info("Llamada a la API externa para el Modbus ID: {$config->id}");
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
        $apiQueue->save(); 
        
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
                ? ($config->box_width * $config->box_length * $dimensionFinal) / 1000
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
        // Inserta en la tabla mqtt_send_server1
        MqttSendServer1::createRecord($topic, $message);

        // Inserta en la tabla mqtt_send_server2
        MqttSendServer2::createRecord($topic, $message);

        Log::info("Stored message in both mqtt_send_server1 and mqtt_send_server2 tables.");

        } catch (\Exception $e) {
            Log::error("Error storing message in databases: " . $e->getMessage());
        }
    }

}