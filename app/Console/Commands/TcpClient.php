<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Barcode;
use Illuminate\Support\Facades\Log;
//anadir carbon
use Carbon\Carbon;


class TcpClient extends Command
{
    protected $signature = 'tcp:client';
    protected $description = 'Connect to multiple TCP servers and read messages continuously';

    protected $processes = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Matar todos los procesos existentes antes de iniciar
        $this->terminateAllProcesses();

        // Obtener todos los barcodes de la base de datos y establecer conexiones
        $barcodes = Barcode::all();
        foreach ($barcodes as $barcode) {
            $this->startConnection($barcode);
        }

        // Esperar indefinidamente para mantener los procesos activos
        while (true) {
            sleep(60);
        }
    }

    private function terminateAllProcesses()
    {
        foreach ($this->processes as $id => $pid) {
            $this->info("[" . Carbon::now()->toDateTimeString() . "]Stopping TCP client for barcode ID: $id");
            if (posix_kill($pid, SIGTERM)) {
                unset($this->processes[$id]);
                $this->info("[" . Carbon::now()->toDateTimeString() . "]Successfully stopped process for barcode ID: $id");
            } else {
                $this->error("[" . Carbon::now()->toDateTimeString() . "]Failed to stop process for barcode ID: $id with PID: $pid");
            }
        }
    }

    protected function startConnection($barcode)
    {
        $ip = $barcode->conexion_type == 1 ? $barcode->ip_barcoder : $barcode->ip_zerotier;
        $port = $barcode->port_barcoder;

        if (empty($ip) || empty($port)) {
            $this->info("[" . Carbon::now()->toDateTimeString() . "]Ignoring TCP client for barcode ID: {$barcode->id} due to empty IP or port.");
            return;
        }

        $pid = pcntl_fork();
        if ($pid == -1) {
            $this->error("[" . Carbon::now()->toDateTimeString() . "]Error al crear un proceso hijo para barcode ID: {$barcode->id}");
        } elseif ($pid) {
            // Proceso padre
            $this->processes[$barcode->id] = $pid; // Guardar el PID del proceso hijo
        } else {
            // Proceso hijo
            $this->handleBarcode($barcode);
            exit(0); // Terminar el proceso hijo después de su tarea
        }
    }

    protected function handleBarcode($barcode)
    {
        $conexionType = $barcode->conexion_type;

        if ($conexionType == 0) {
            $this->info("[" . Carbon::now()->toDateTimeString() . "]No TCP connection will be made for barcode ID {$barcode->id}.");
            return;
        }

        // Obtener la información de conexión
        $host = $conexionType == 1 ? $barcode->ip_barcoder : $barcode->ip_zerotier;
        $port = $barcode->port_barcoder;

        // Verificar si los valores de IP y puerto son válidos
        if (empty($host) || empty($port)) {
            $this->info("[" . Carbon::now()->toDateTimeString() . "]Ignoring TCP client for barcode ID: {$barcode->id} due to empty IP or port.");
            return;
        }

        // Bucle principal para gestionar la reconexión
        while (true) {
            $this->info("[" . Carbon::now()->toDateTimeString() . "]Connecting to TCP server at $host:$port for barcode ID {$barcode->id}");
            $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

            if ($socket === false) {
                $this->error("[" . Carbon::now()->toDateTimeString() . "]Error al crear el socket: " . socket_strerror(socket_last_error()));
                sleep(5); // Esperar 5 segundos antes de intentar nuevamente
                continue;
            }

            // Intentar conectar al servidor TCP
            $result = @socket_connect($socket, $host, $port);

            if ($result === false) {
                $errorCode = socket_last_error($socket);
                $this->error("[" . Carbon::now()->toDateTimeString() . "]Error al conectar al servidor: " . socket_strerror($errorCode) . " (Código de error: $errorCode)");
                socket_close($socket);
                sleep(5); // Esperar 5 segundos antes de intentar reconectar
                continue; // Reiniciar el bucle y volver a intentar conectar
            }

            $this->info("[" . Carbon::now()->toDateTimeString() . "]Conectado al servidor TCP en $host:$port para barcode ID {$barcode->id}");

            // Leer mensajes continuamente hasta que haya un error
            while (true) {
                $response = @socket_read($socket, 2048, PHP_NORMAL_READ);
                if ($response === false) {
                    $this->error("[" . Carbon::now()->toDateTimeString() . "]Error al leer del servidor: " . socket_strerror(socket_last_error($socket)));
                    break; // Salir del bucle y cerrar la conexión para reconectar
                }

                // Verifica si el servidor ha cerrado la conexión
                if ($response === '') {
                    $this->info("[" . Carbon::now()->toDateTimeString() . "]El servidor ha cerrado la conexión para barcode ID {$barcode->id}");
                    break; // Salir del bucle y reconectar
                }

                // Procesar el mensaje recibido
                if (trim($response) !== '') {
                    $this->processMessage($barcode->id, $response);
                } else {
                    //$this->info("[" . Carbon::now()->toDateTimeString() . "]Mensaje vacío recibido para barcode ID {$barcode->id}, ignorando.");
                }
            }

            // Cerrar el socket antes de intentar reconectar
            socket_close($socket);
            $this->info("[" . Carbon::now()->toDateTimeString() . "]Intentando reconectar en 5 segundos para barcode ID {$barcode->id}...");
            sleep(5); // Esperar antes de intentar reconectar
        }
    }  

    protected function processMessage($id, $barcodeValue)
    {
        $this->info("[" . Carbon::now()->toDateTimeString() . "]Lectura del barcode id : {$id}  value : $barcodeValue");
        $barcode = $this->barcoderLatest($id);
        // Si el resultado es null, espera y vuelve a intentar una vez más
        if ($barcode === null) {
            sleep(1); // Espera 1 segundo antes de intentar de nuevo
            $barcode = $this->barcoderLatest($id);
        }
        $mqttTopicBase = $barcode->mqtt_topic_barcodes;
        $mqttTopicBarcodes = $mqttTopicBase ."/prod_order_mac";
       // $this->info("[" . Carbon::now()->toDateTimeString() . "]mqtt topic : " . $mqttTopicBarcodes);
        $mqttTopicOrders = $mqttTopicBase ."/prod_order_notice";
        $mqttTopicFinish = $mqttTopicBase ."/order_finish";
        $mqttTopicPause = $mqttTopicBase ."/order_pause";
        $opeId = $barcode->ope_id;
        $orderNotice = $barcode->order_notice;
        $lastBarcode = $barcode->last_barcode;
        $machineId = $barcode->machine_id;
        $mqttTopicShift = $mqttTopicBase ."/shift";
        $mqttTopicNext = $mqttTopicBase ."/prod_order_notice_next";
        $iniciarModel = $barcode->iniciar_model;

        $orderNoticeData = json_decode($orderNotice, true);
        $orderId = $orderNoticeData['orderId'] ?? null;

        $comando = [];
        $mqttTopic = null; // Initialize to null to avoid errors
        $barcodeValue = trim($barcodeValue);

        if (in_array($lastBarcode, ['FINALIZAR', 'PAUSAR', null, '']) && $barcodeValue === $iniciarModel) {
            // Case 1: lastBarcode is FINALIZAR, PAUSAR, NULL, or empty, and barcodeValue is INICIAR
            //primero llamamos a obtener el orderid pero comprobamos si $iniciarModel es INICIAR o INICIAR-2 si es 2 tenemos que buscar todos los 
            if ($iniciarModel === 'INICIAR') {
                // Buscar todas las líneas que tienen el mismo `mqtt_topic_barcodes` con el valor `$mqttTopicBase`
                $relatedBarcodes = Barcode::where('mqtt_topic_barcodes', $mqttTopicBase)
                                            ->orderByRaw("CAST(SUBSTRING(machine_id, -2) AS UNSIGNED) ASC")
                                            ->get();
    
                foreach ($relatedBarcodes as $relatedBarcode) {
                    // Aquí puedes realizar cualquier acción que necesites con cada barcode relacionado
                    $this->info("[" . Carbon::now()->toDateTimeString() . "]Encontrada línea relacionada con ID: {$relatedBarcode->id} y valor de iniciar_model: {$relatedBarcode->iniciar_model}");
                    // Si necesitas algo específico de cada barcode relacionado, puedes trabajar con $relatedBarcode aquí

                    $relatedBarcode->sended = 1;
                    $relatedBarcode->last_barcode = "INICIAR";
                    $relatedBarcode->save();
                    $this->info("[" . Carbon::now()->toDateTimeString() . "]Puesto en modo escucha ID: {$relatedBarcode->id}" );
                }
                $nowDateTime = date('Y-m-d H:i:s');

                //ahorra preguntamos el next orderid
                $this->sendNextOrder($barcode->machine_id, $mqttTopicNext);
                 // Re actualizar el last_barcode
                $barcodenew = $this->waitTimeNow($id, $nowDateTime);

                if ($barcodenew === "ERROR") {
                    // Detenemos la ejecución si se detecta un error.
                    foreach ($relatedBarcodes as $relatedBarcode) {
                        $relatedBarcode->sended = 0;
                        $relatedBarcode->last_barcode = "FINALIZAR";
                        $relatedBarcode->save();
                        $this->error("[" . Carbon::now()->toDateTimeString() . "]Error al actualizar el barcode con ID: {$relatedBarcode->id}, Vuelvo a FINZALIAR");
                    }
                    return; // Finaliza la ejecución del método actual
                }
                
                    //actualizar el OrderId
                    $updatedOrderId = $this->orderIdNew($barcodenew);
                    
                    foreach ($relatedBarcodes as $relatedBarcode) {
                        // Aquí puedes realizar cualquier acción que necesites con cada barcode relacionado
                        $this->info("[" . Carbon::now()->toDateTimeString() . "]mando mqtt a: {$relatedBarcode->id} y valor de iniciar_model: {$relatedBarcode->iniciar_model}");
                    //ahorra mandamos el mac
                        $this->sendOrderMac("0",$updatedOrderId,$relatedBarcode->machine_id, $mqttTopicBarcodes);
                    }
                
            }

            if ($iniciarModel === 'INICIAR-2') {
                $relatedBarcodes = Barcode::where('mqtt_topic_barcodes', $mqttTopicBase)
                                            ->orderByRaw("CAST(SUBSTRING(machine_id, -2) AS UNSIGNED) ASC")
                                            ->get();
                
                $this->info("[" . Carbon::now()->toDateTimeString() . "]Se encontraron {$relatedBarcodes->count()} líneas para INICIAR-2.");

                foreach ($relatedBarcodes as $relatedBarcode) {
                    // Aquí puedes realizar cualquier acción que necesites con cada barcode relacionado
                    $this->info("[" . Carbon::now()->toDateTimeString() . "]Encontrada línea relacionada con ID: {$relatedBarcode->id} y valor de iniciar_model: {$relatedBarcode->iniciar_model}");
                    
                    $relatedBarcode->sended = 1;
                    $relatedBarcode->last_barcode = "INICIAR";
                    $relatedBarcode->save();

                    $nowDateTime = date('Y-m-d H:i:s');
                    //ahorra preguntamos el next orderid
                    $this->sendNextOrder($relatedBarcode->machine_id, $mqttTopicNext);
                    // Re actualizar el last_barcode
                    $barcodenew = $this->waitTimeNow($relatedBarcode->id, $nowDateTime);

                    if ($barcodenew === "ERROR") {
                        // Detenemos la ejecución si se detecta un error.
                        $relatedBarcode->sended = 0;
                        $relatedBarcode->last_barcode = "FINALIZAR";
                        $relatedBarcode->save();
                        $this->error("[" . Carbon::now()->toDateTimeString() . "]Error al actualizar el barcode con ID: {$relatedBarcode->id}, Vuelvo a FINZALIAR");
                        return; // Finaliza la ejecución del método actual
                    }
                        //actualizar el OrderId
                        $updatedOrderId = $this->orderIdNew($barcodenew);
                        //ahorra preguntamos el next orderid
                        $this->sendOrderMac("0",$updatedOrderId,$relatedBarcode->machine_id, $mqttTopicBarcodes);
                    
                }
                
            }
            
            
        } elseif ($lastBarcode === 'INICIAR' && $barcodeValue === $iniciarModel) {

            if ($iniciarModel === 'INICIAR') {
                // Buscar todas las líneas que tienen el mismo `mqtt_topic_barcodes` con el valor `$mqttTopicBase`
                $relatedBarcodes = Barcode::where('mqtt_topic_barcodes', $mqttTopicBase)
                                            ->orderByRaw("CAST(SUBSTRING(machine_id, -2) AS UNSIGNED) ASC")
                                            ->get();
                foreach ($relatedBarcodes as $relatedBarcode) {
                    // Aquí puedes realizar cualquier acción que necesites con cada barcode relacionado
                    $this->info("[" . Carbon::now()->toDateTimeString() . "]Encontrada línea relacionada con ID: {$relatedBarcode->id} y valor de iniciar_model: {$relatedBarcode->iniciar_model}");
                    // Si necesitas algo específico de cada barcode relacionado, puedes trabajar con $relatedBarcode aquí
                    // Aquí puedes realizar cualquier acción que necesites con cada barcode relacionado
                    $this->info("[" . Carbon::now()->toDateTimeString() . "]mando mqtt a: {$relatedBarcode->id} y valor de iniciar_model: {$relatedBarcode->iniciar_model}");
                    //ahorra mandamos el mac
                    // Re actualizar el last_barcode
                    $barcoderLatest = $this->barcoderLatest($relatedBarcode->id,);
                    //actualizar el OrderId
                    $updatedOrderIdLatest = $this->orderIdNew($barcoderLatest);

                    $this->sendOrderMac("1",$updatedOrderIdLatest,$relatedBarcode->machine_id, $mqttTopicBarcodes);
                    $relatedBarcode->sended = 1;
                    $relatedBarcode->last_barcode = "INICIAR";
                    $relatedBarcode->save();
                    $this->info("[" . Carbon::now()->toDateTimeString() . "]Puesto en modo escucha" );
                }
                //ahorra preguntamos el next orderid

                $nowDateTime = date('Y-m-d H:i:s');
                //ahorra preguntamos el next orderid
                $this->sendNextOrder($barcode->machine_id, $mqttTopicNext);
                // Re actualizar el last_barcode
                $barcodenew = $this->waitTimeNow($barcode->id, $nowDateTime);

                if ($barcodenew === "ERROR") {
                    // Detenemos la ejecución si se detecta un error.
                    foreach ($relatedBarcodes as $relatedBarcode) {
                        $relatedBarcode->sended = 0;
                        $relatedBarcode->last_barcode = "FINALIZAR";
                        $relatedBarcode->save();
                        $this->error("[" . Carbon::now()->toDateTimeString() . "]Error al actualizar el barcode con ID: {$relatedBarcode->id}, Vuelvo a FINZALIAR");
                    }
                    return; // Finaliza la ejecución del método actual
                }
                    $updatedOrderId = $this->orderIdNew($barcodenew);
                    
                    foreach ($relatedBarcodes as $relatedBarcode) {
                        // Aquí puedes realizar cualquier acción que necesites con cada barcode relacionado
                        $this->info("[" . Carbon::now()->toDateTimeString() . "]mando mqtt a: {$relatedBarcode->id} y valor de iniciar_model: {$relatedBarcode->iniciar_model}");
                    //ahorra mandamos el mac
                        $this->sendOrderMac("0",$updatedOrderId,$relatedBarcode->machine_id, $mqttTopicBarcodes);
                    }
                

            }
            if ($iniciarModel === 'INICIAR-2') {
                $relatedBarcodes = Barcode::where('mqtt_topic_barcodes', $mqttTopicBase)
                                            ->orderByRaw("CAST(SUBSTRING(machine_id, -2) AS UNSIGNED) ASC")
                                            ->get();
    
                foreach ($relatedBarcodes as $relatedBarcode) {
                    // Aquí puedes realizar cualquier acción que necesites con cada barcode relacionado
                    $this->info("[" . Carbon::now()->toDateTimeString() . "]Encontrada línea relacionada con ID: {$relatedBarcode->id} y valor de iniciar_model: {$relatedBarcode->iniciar_model}");
                    // Aquí puedes realizar cualquier acción que necesites con cada barcode relacionado
                    $this->info("[" . Carbon::now()->toDateTimeString() . "]mando mqtt a: {$relatedBarcode->id} y valor de iniciar_model: {$relatedBarcode->iniciar_model}");
                    //ahorra mandamos el mac
                    // Re actualizar el last_barcode
                    $barcoderLatest = $this->barcoderLatest($relatedBarcode->id,);
                    //actualizar el OrderId
                    $updatedOrderIdLatest = $this->orderIdNew($barcoderLatest);

                    $this->sendOrderMac("1",$updatedOrderIdLatest,$relatedBarcode->machine_id, $mqttTopicBarcodes);
                    $relatedBarcode->sended = 1;
                    $relatedBarcode->last_barcode = "INICIAR";
                    $relatedBarcode->save();

                    $nowDateTime = date('Y-m-d H:i:s');
                    //ahorra preguntamos el next orderid
                    $this->sendNextOrder($relatedBarcode->machine_id, $mqttTopicNext);
                    // Re actualizar el last_barcode
                    $barcodenew = $this->waitTimeNow($relatedBarcode->id, $nowDateTime);

                    if ($barcodenew === "ERROR") {
                        // Detenemos la ejecución si se detecta un error.
                        $relatedBarcode->sended = 0;
                        $relatedBarcode->last_barcode = "FINALIZAR";
                        $relatedBarcode->save();
                        $this->error("[" . Carbon::now()->toDateTimeString() . "]Error al actualizar el barcode con ID: {$relatedBarcode->id}, Vuelvo a FINZALIAR");
                        return; // Finaliza la ejecución del método actual
                    }
                        //actualizar el OrderId
                        $updatedOrderId = $this->orderIdNew($barcodenew);
                        //ahorra preguntamos el next orderid
                        $this->sendOrderMac("0",$updatedOrderId,$relatedBarcode->machine_id, $mqttTopicBarcodes);
                    
                  
                }
                
            }
            
        } elseif ($lastBarcode === 'INICIAR' && $barcodeValue === 'FINALIZAR') {

                    $updatedOrderNotice = json_decode($barcode->order_notice, true);
                    $updatedOrderId = $updatedOrderNotice['orderId'] ?? null;
                    $comando = [
                        "orderId" => $updatedOrderId
                    ];
                    $this->publishMqttMessage($mqttTopicFinish, $comando);
                    
                    $barcode->last_barcode = "FINALIZAR";
                    $barcode->save();
            
        } elseif ($lastBarcode === 'INICIAR' && $barcodeValue === 'PAUSAR') {

                    $updatedOrderNotice = json_decode($barcode->order_notice, true);
                    $updatedOrderId = $updatedOrderNotice['orderId'] ?? null;
                    $comando = [
                        "orderId" => $updatedOrderId
                    ];
                    $this->publishMqttMessage($mqttTopicPause, $comando);
        
                    $barcode->last_barcode = "PAUSAR";
                    $barcode->save();

        } elseif ($barcodeValue === 'Turno Programado Start') {
            // Case 5: barcodeValue is Turno Programado
            $comando = [
                "shift" => "shift",
                "action" => "start"
            ];
            $this->publishMqttMessage($mqttTopicShift, $comando);
        } elseif ($barcodeValue === 'Turno Programado Stop') {
            // Case 5: barcodeValue is Turno Programado
            $comando = [
                "shift" => "shift",
                "action" => "stop"
            ];
            $this->publishMqttMessage($mqttTopicShift, $comando);
        }else {
            // Si ninguna de las condiciones se cumple
            $this->info('Barcode no válido.' .$barcodeValue. ' ' . $lastBarcode. ' ' . $id);
        }
        
    }

    private function waitTimeNow($id, $dataTime)
    {
        $maxRetries = 20; // Define un número máximo de reintentos para evitar ciclos infinitos
        $retryCount = 0;

        while ($retryCount < $maxRetries) {
            usleep(500000); // Espera de 0.5 segundos

            $barcodenew = Barcode::find($id);

            // Verificar si $barcodenew no es null y tiene un valor para `updated_at`
            if ($barcodenew && !is_null($barcodenew->updated_at)) {
                // Si el barcode tiene un `updated_at` más nuevo, retornar la línea actualizada
                if ($barcodenew->updated_at > $dataTime) {
                    $this->info("[" . Carbon::now()->toDateTimeString() . "]El barcode ya está actualizado: " . date('Y-m-d H:i:s', strtotime($barcodenew->updated_at)));
                    return $barcodenew;
                } else {
                    $this->info("[" . Carbon::now()->toDateTimeString() . "]vuelvo a buscar en un segundo: " . date('Y-m-d H:i:s'));
                }
            } else {
                $this->info("[" . Carbon::now()->toDateTimeString() . "]El campo `updated_at` es null o no se encontró un barcode con ID: {$id}, reintentando...");
            }

            $retryCount++;
        }
        // Si alcanzamos el máximo de reintentos sin cumplir la condición, retornamos la línea encontrada (aunque sin cambios).
        $this->error("[" . Carbon::now()->toDateTimeString() . "]No se pudo actualizar el barcode con el nuevo OrderID. Se ha alcanzado el tiempo después de varios intentos.");
        return "ERROR";
    }

    private function barcoderLatest($id)
    {
            $barcodenew = Barcode::find($id);
            return $barcodenew;
    }
    
    
    private function orderIdNew($barcodenew)
    {
        // Verificar si $barcodenew no es null
        if ($barcodenew === null) {
            $this->info("[" . Carbon::now()->toDateTimeString() . "]El objeto barcodenew es null. No se puede proceder.");
            return null;
        }
    
        // Intentar obtener el order_notice y verificar si es null o está vacío
        $updatedOrderNotice = json_decode($barcodenew->order_notice, true);
    
        if ($updatedOrderNotice === null) {
            // Buscar en la base de datos nuevamente por el ID
            $barcodeFromDb = Barcode::find($barcodenew->id);
    
            // Si sigue siendo null, asignar un valor por defecto
            if ($barcodeFromDb === null || $barcodeFromDb->order_notice === null) {
                $this->info("[" . Carbon::now()->toDateTimeString() . "]El valor order_notice sigue siendo null. Asignando valor por defecto.");
                return null; // Puedes cambiar el valor por defecto aquí si es necesario
            }
    
            // Intentar obtener el order_notice de la nueva consulta
            $updatedOrderNotice = json_decode($barcodeFromDb->order_notice, true);
        }
    
        // Extraer el orderId, si existe
        $updatedOrderId = $updatedOrderNotice['orderId'] ?? null;
    
        $this->info("[" . Carbon::now()->toDateTimeString() . "]Actualizo JSON de la db: " . ($updatedOrderId ?? "Valor por defecto NULL"));
        return $updatedOrderId;
    }
    private function sendNextOrder($machineId, $mqttTopic)
    {
        $comando = [
            "machineId" => $machineId,
            "time" => date('Y-m-d H:i:s'),
        ];

        $this->publishMqttMessage($mqttTopic, $comando);
        $this->info("[" . Carbon::now()->toDateTimeString() . "]mesaje enviado : ");
    }

    private function sendOrderMac($action,$updatedOrderId,$machineId, $mqttTopicBarcodes)
    {
            // Convertir $action a entero para evitar problemas con el formato
            $action = (int) $action;
            
                $comando = [
                    "action" => $action,
                    "orderId" => $updatedOrderId,
                    "machineId" => $machineId,
                    "opeId" => "ENVASADO"
                ];
                $mqttTopic = $mqttTopicBarcodes;
                $this->publishMqttMessage($mqttTopic, $comando);
    }
    // Funciones mqtt
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
        
            // Comentado para reducir logs y carga del sistema
            // // Guardar en servidor 2
            // $fileName2 = storage_path("app/mqtt/server2/{$sanitizedTopic}_{$uniqueId}.json");
            // if (!file_exists(dirname($fileName2))) {
            //     mkdir(dirname($fileName2), 0755, true);
            // }
            // file_put_contents($fileName2, $jsonData . PHP_EOL);
            // //Log::info("Mensaje almacenado en archivo (server2): {$fileName2}");
        } catch (\Exception $e) {
            Log::error("Error storing message in file: " . $e->getMessage());
        }
    }
}
