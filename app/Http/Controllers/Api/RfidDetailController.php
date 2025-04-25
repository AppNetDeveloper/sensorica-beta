<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RfidDetail;
use App\Models\RfidList;
use App\Models\RfidAnt;
use App\Models\RfidReading;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use GuzzleHttp\Client;
use App\Models\OperatorPost;  // Asegúrate de tener este modelo
use App\Models\Operator;      // Asegúrate de tener este modelo
use App\Models\RfidBlocked;   // Importamos el modelo para la tabla rfid_blocked
use App\Models\ShiftHistory;

class RfidDetailController extends Controller
{
    public function store(Request $request)
    {
        try {
            // 1. Validación básica de los datos recibidos
            $validator = Validator::make($request->all(), [
                'epc'           => 'required|string',
                'rssi'          => 'required|integer',
                'serialno'      => 'required|string',
                'tid'           => 'required|string',
                'antenna_name'  => 'required|string'
            ]);
            Log::info("--======= Estamos en el método store de RfidDetailController ========--");

            if ($validator->fails()) {
                Log::warning('Fallo de validación en store RfidDetailController', [
                    'datos_recibidos' => $request->all()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors'  => $validator->errors()
                ], 422);
            }
            Log::info("RfidDetailController ha validado los datos");

            // 2. Obtener la antena RFID
            $rfidAnt = RfidAnt::where('name', $request->input('antenna_name'))->first();
            if (!$rfidAnt) {
                Log::info("Antena RFID no encontrada en RfidDetailController");
                return response()->json([
                    'success' => false,
                    'message' => 'Antena RFID no encontrada'
                ], 404);
            }

            // 3. Obtener el rfid_reading usando el EPC (grupo único para varias TID)
            $epcInput = $request->input('epc');
            // Eliminar los ceros iniciales
            $epcInput = ltrim($epcInput, '0');

            $rfidReading = RfidReading::where('epc', $epcInput)->first();

            if (!$rfidReading) {
                Log::info("RFID reading no encontrado para el EPC: " . $epcInput);
                // Agregar el EPC a la tabla rfid_blocked
                RfidBlocked::create(['epc' => $epcInput]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'RFID reading no encontrado para el EPC proporcionado , EPC bloqueado!!!'
                ], 404);
            }

            // 4. Obtener o crear el rfid_detail según el TID
            try {
                $rfidDetail = RfidDetail::where('tid', $request->input('tid'))->first();

                if (!$rfidDetail) {
                    // Solo se crea el registro si RFID_AUTO_ADD está habilitado en el env
                    if (env('RFID_AUTO_ADD', false)) {
                        $rfidDetail = RfidDetail::create([
                            'name'                     => $request->input('tid'),
                            'token'                    => bin2hex(random_bytes(16)),
                            'production_line_id'       => $rfidAnt->production_line_id,
                            'rfid_reading_id'          => $rfidReading->id,
                            'rfid_type'                => 'default',
                            'count_total'              => 0,
                            'count_total_0'            => 0,
                            'count_total_1'            => 0,
                            'count_shift_0'            => 0,
                            'count_shift_1'            => 0,
                            'count_order_0'            => 0,
                            'count_order_1'            => 0,
                            'mqtt_topic_1'             => 'rfid/' . $request->input('tid'),
                            'function_model_0'         => 'none',
                            'function_model_1'         => 'sendMqttValue1',
                            'invers_sensors'           => 0,
                            'unic_code_order'          => uniqid(),
                            'shift_type'               => 'shift',
                            'event'                    => null,
                            'downtime_count'           => 0,
                            'optimal_production_time'  => 50,
                            'reduced_speed_time_multiplier' => 5,
                            'epc'                      => $epcInput,
                            'tid'                      => $request->input('tid'),
                            'rssi'                     => $request->input('rssi'),
                            'serialno'                 => $request->input('serialno'),
                            'send_alert'               => 0,
                            'search_out'               => 0,
                            'last_ant_detect'          => $request->input('antenna_name'),
                            'last_status_detect'       => '0'
                        ]);
                        Log::info("Nuevo RfidDetail creado para TID: " . $request->input('tid'));
                    } else {
                        Log::info("RFID Detail no encontrado y RFID_AUTO_ADD está deshabilitado.");
                        return response()->json([
                            'success' => false,
                            'message' => 'RFID Detail no encontrado y la creación automática está deshabilitada'
                        ], 404);
                    }
                }
            } catch (\Exception $e) {
                Log::error("Error al obtener/crear RfidDetail: " . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Error al obtener o crear RfidDetail',
                    'error'   => $e->getMessage(),
                ], 500);
            }



            // --- Bloque: Filtrado por reset (tarjeta maestra) antes de crear el registro en rfid_list ---

            // Buscar la tarjeta maestra (reset = 1) para este grupo de rfid_reading
            $masterReset = RfidDetail::where('rfid_reading_id', $rfidReading->id)
            ->where('reset', 1)
            ->orderBy('created_at', 'desc')
            ->first();
            //vamos a poner un log para mostrar la tarjeta maestra
            Log::info("Tarjeta Maestra encontrada: " . json_encode($masterReset));

            if ($masterReset) {
                // Si se encontró la tarjeta maestra, diferenciamos según el TID recibido
                $currentTid = $request->input('tid');

                // Buscar el último registro insertado en rfid_list para la tarjeta maestra
                $lastMasterRecord = RfidList::where('tid', $masterReset->tid)
                    ->orderBy('created_at', 'desc')
                    ->first(); 

                if ($currentTid !== $masterReset->tid) {
                    // Caso: La tarjeta leída NO es la maestra
                    if ($lastMasterRecord) {

                    //buscamos en shift_history el ultimo registro por production_line_id  que type= shift y action = start
                    $shiftHistory = ShiftHistory::where('production_line_id', $rfidReading->production_line_id)
                        ->where('type', 'shift')
                        ->where('action', 'start')
                        ->orderBy('created_at', 'desc')
                        ->first();
                    Log::info("Ultimo registro de shift_history encontrado: " . json_encode($shiftHistory));

                    //buscamos que el created_at del shift _history sea mayor o igual al created_at de la última tarjeta maestra si no es asi hacemos return
                    if ($shiftHistory->created_at->lt($lastMasterRecord->created_at)) {
                        Log::info("El registro de shift_history es anterior que el último registro de la tarjeta maestra. Tarjeta permitida en este filtro");
                        Log::info("Fecha del ultimo registro de shift_history: " . $shiftHistory->created_at);
                        Log::info("Fecha del ultimo registro de la tarjeta maestra: " . $lastMasterRecord->created_at);

                    }else{
                        Log::info("La tarjeta no es permitida todavia por no pasar el punto en este turno.");
                        Log::info("Fecha del ultimo registro de shift_history: " . $shiftHistory->created_at);
                        Log::info("Fecha del ultimo registro de la tarjeta maestra: " . $lastMasterRecord->created_at);
                        // return response()->json([
                        //   'success' => false,
                        // 'message' => 'La tarjeta no es permitida todavia por no pasar el punto en este turno.'
                        //], 200);
                    }
                        // Verificar si ya se ha insertado la tarjeta actual desde el último registro de la maestra
                        $registroExistente = RfidList::where('tid', $currentTid)
                            ->where('created_at', '>=', $lastMasterRecord->created_at)
                            ->exists();

                        if ($registroExistente) {
                            Log::info("La tarjeta con TID {$currentTid} ya fue registrada después del último reset.");
                            return response()->json([
                                'success' => false,
                                'message' => 'La tarjeta ya fue registrada en este ciclo.'
                            ], 200);
                        }
                    }
                } else {
                    // Caso: La tarjeta leída ES la maestra (reset)
                    if ($lastMasterRecord) {
                        $fechaMaster = $lastMasterRecord->created_at;
                        $inicioHoy    = Carbon::today(); // 00:00 del día actual

                        // Verificar que, después de la última inserción de la tarjeta maestra,
                        // se haya insertado al menos otro registro (de otra tarjeta)
                        if ($fechaMaster->lt($inicioHoy)) {
                            // Si la última master es de ayer o antes, consideramos
                            // que ya hubo otro registro “válido”
                            $otroRegistroExiste = true;
                            Log::info("La última tarjeta maestra es de {$fechaMaster->toDateString()}, fuera de hoy: forzando otroRegistroExiste = true.");
                        } else {
                            // Si la master fue hoy, evaluamos normalmente
                            $otroRegistroExiste = RfidList::where('rfid_reading_id', $rfidReading->id)
                                ->where('tid', '<>', $masterReset->tid)
                                ->where('created_at', '>', $fechaMaster)
                                ->whereDate('created_at', $inicioHoy)
                                ->exists();
                            Log::info("Chequeo normal de otros registros hoy: " . ($otroRegistroExiste ? 'sí' : 'no'));
                        }

                        // Calcular la diferencia en minutos desde la última inserción de la tarjeta maestra
                        $minutosTranscurridos = Carbon::now()->diffInMinutes(Carbon::parse($lastMasterRecord->created_at));

                        if (!$otroRegistroExiste || $minutosTranscurridos < 5) {
                            Log::info("Condiciones para reinsertar la tarjeta maestra no cumplidas: " .
                                "otro registro insertado = " . ($otroRegistroExiste ? 'sí' : 'no') .
                                ", minutos transcurridos = {$minutosTranscurridos}.");
                            return response()->json([
                                'success' => false,
                                'message' => 'La tarjeta maestra ya fue registrada recientemente o no se ha reiniciado el ciclo.'
                            ], 200);
                        }
                    }
                }
            }
                        // 5. Actualizar contadores del rfid_detail
                        $rfidDetail->increment('count_total');
                        $rfidDetail->increment('count_total_1');
                        $rfidDetail->increment('count_shift_1');
                        $rfidDetail->increment('count_order_1');
            
                        // --- Bloque: Actualizar contadores en Operators ---
                        try {
                            // Buscar en operator_post registros con el mismo rfid_reading_id y finish_at nulo o vacío
                            $operatorPost = OperatorPost::where('rfid_reading_id', $rfidReading->id)
                                            ->whereNull('finish_at')
                                            ->first();

            
                            if ($operatorPost) {
                                $operatorId = $operatorPost->operator_id;
                                $operator = Operator::find($operatorId);
                                if ($operator) {
                                    $operator->increment('count_shift');
                                    $operator->increment('count_order');
                                } else {
                                    Log::warning("No se encontró operator con id: {$operatorId}");
                                }
                            } else {
                                Log::info("No se encontró registro en operator_post para rfid_reading_id: " . $rfidReading->id);
                            }
                        } catch (\Exception $e) {
                            Log::error("Error al actualizar contadores en operators: " . $e->getMessage());
                        }
            
                        // --- Nuevo Bloque: Actualizar el campo 'count' en operator_post ---
                        try {
                            // Se verifica que $operatorId esté definido
                            if (isset($operatorId)) {
                                $operatorPostToUpdate = OperatorPost::where('operator_id', $operatorId)
                                                        ->where('rfid_reading_id', $rfidReading->id)
                                                        ->whereNull('finish_at')
                                                        ->first();

            
                                if ($operatorPostToUpdate) {
                                    $operatorPostToUpdate->increment('count');
                                } else {
                                    Log::info("No se encontró registro en operator_post para operator_id: {$operatorId} y rfid_reading_id: {$rfidReading->id}");
                                }
                            } else {
                                Log::warning("No se tiene definido operatorId para actualizar el campo count en operator_post");
                            }
                        } catch (\Exception $e) {
                            Log::error("Error al actualizar el contador 'count' en operator_post: " . $e->getMessage());
                        }
                        // --- Fin Bloque ---


            // 6. Crear el registro en rfid_list
            $rfidList = RfidList::create([
                'name'                => $rfidDetail->name,
                'value'               => '1',
                'production_line_id'  => $rfidAnt->production_line_id,
                'rfid_detail_id'      => $rfidDetail->id,
                'rfid_reading_id'     => $rfidReading->id,
                'rfid_ant_name'       => $request->input('antenna_name'),
                'model_product'       => '1',
                'orderId'             => $rfidDetail->orderId,
                'count_total'         => $rfidDetail->count_total,
                'count_total_1'       => $rfidDetail->count_total_1,
                'count_shift_1'       => $rfidDetail->count_shift_1,
                'count_order_1'       => $rfidDetail->count_order_1,
                'time_11'             => $rfidDetail->time_11,
                'epc'                 => $epcInput,
                'tid'                 => $request->input('tid'),
                'rssi'                => $request->input('rssi'),
                'serialno'            => $request->input('serialno'),
                'ant'                 => $request->input('ant')
            ]);

            // Llamar a la función sendAlert si es necesario
            if (
                $rfidDetail->send_alert &&
                ($rfidDetail->last_status_detect == "0" ||
                 $rfidDetail->last_ant_detect !== $request->input('antenna_name'))
            ) {
                $this->sendAlert($rfidDetail, $request->input('antenna_name'));
            }
            // Actualizar los campos last_status_detect y last_ant_detect
            $rfidDetail->update([
                'last_status_detect' => '1',  // Marcado como conectado
                'last_ant_detect'    => $request->input('antenna_name')
            ]);

            return response()->json([
                'success'   => true,
                'message'   => 'Registro insertado en RFID list con éxito',
                //'rfid_list' => $rfidList
            ], 201);
        } catch (\Exception $e) {
            Log::error("Error en el método store: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud',
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ], 500);
        }
    }


    /**
     * Función para enviar alertas cuando un dispositivo se detecta
     *
     * @param \App\Models\RfidDetail $detail
     * @return void
     */
    protected function sendAlert(RfidDetail $detail, $antenaName)
    {
        // Obtiene la URL de la API y el número de teléfono desde las variables de entorno
        $apiUrl = rtrim(env('WHATSAPP_LINK'), '/') . '/send-message';
        $phoneNumber = env('WHATSAPP_PHONE_NOT');
        $dateTime = Carbon::now()->format('Y-m-d H:i:s');

        // Configura el cliente HTTP
        $client = new Client([
            'timeout'      => 3,
            'http_errors'  => false,
            'verify'       => false,
        ]);

        // Datos para enviar el mensaje de alerta
        $dataToSend = [
            'jid'     => "{$phoneNumber}@s.whatsapp.net",
            'message' => "Alerta: Punto de Control {$antenaName} ha detectado: {$detail->name}. Fecha: {$dateTime}",
        ];

        try {
            // Llamada asíncrona a la API
            $promise = $client->postAsync($apiUrl, [
                'json' => $dataToSend,
            ]);

            // Maneja la respuesta de la API
            $promise->then(
                function ($response) {
                    Log::info("Mensaje de alerta enviado correctamente: " . $response->getStatusCode());
                },
                function ($exception) {
                    Log::error("Error al enviar mensaje de alerta: " . $exception->getMessage());
                }
            );

            // Inicia el proceso sin bloquear el flujo principal
            $promise->wait(false);

        } catch (\Exception $e) {
            Log::error("Error en la llamada a la API de WhatsApp: " . $e->getMessage());
        }
    }

    public function getHistoryRfid(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'epc'        => 'nullable|string',
            'date_start' => 'nullable|date',
            'date_end'   => 'nullable|date',
            'show'       => 'nullable|in:all,10,latest'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors'  => $validator->errors()
            ], 422);
        }

        $query = RfidList::query();

        // Aplicar filtros solo si `antenna_name` no es `all`
        if ($request->filled('antenna_name') && $request->antenna_name !== 'all') {
            $query->where('rfid_ant_name', $request->antenna_name);
        }

        if ($request->filled('epc') && $request->epc !== 'all') {
            $query->where('epc', $request->epc);
        }

        if ($request->filled('date_start') && $request->date_start !== 'all') {
            $query->whereDate('created_at', '>=', Carbon::parse($request->date_start));
        }

        if ($request->filled('date_end') && $request->date_end !== 'all') {
            $query->whereDate('created_at', '<=', Carbon::parse($request->date_end));
        }

        if ($request->filled('show')) {
            switch ($request->show) {
                case '10':
                    $query->orderBy('created_at', 'desc')->limit(10);
                    break;
                case 'latest':
                    $query->orderBy('created_at', 'desc')->limit(1);
                    break;
                case 'all':
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $rfidLists = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Listado de RFID filtrado con éxito',
            'data'    => $rfidLists
        ]);
    }

    public function getFilters()
    {
        $antennas = RfidAnt::select('name')->get(); // Solo obtén el nombre de la antena
        $epcs     = RfidDetail::select('epc')->distinct()->pluck('epc');
        $tids     = RfidDetail::select('tid')->distinct()->pluck('tid');

        return response()->json([
            'success'  => true,
            'antennas' => $antennas,
            'epcs'     => $epcs,
            'tids'     => $tids
        ]);
    }
}
