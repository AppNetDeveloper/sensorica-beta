<?php
// Nuevo comando Artisan
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\ShiftList;
use App\Models\Barcode;
use Carbon\Carbon;

class CheckShiftList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shift:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check shift list and publish MQTT message if current time matches start time';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        while (true) {
            // Obtener el día actual de la semana
            $dayOfWeek = Carbon::now()->isoWeekday();
            $this->info("[" . Carbon::now()->toDateTimeString() . "]Día de la semana: {$dayOfWeek}");

            // Verificar si es de lunes a viernes
            if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
                // Obtener la hora actual
                $currentTime = Carbon::now()->format('H:i:s');
                $this->info("[" . Carbon::now()->toDateTimeString() . "]Hora actual: {$currentTime}");

                // Definir el rango para la consulta de 'start'
                $startLowerBound = Carbon::now()->subSeconds(0)->format('H:i:s');
                $startUpperBound = Carbon::now()->addSeconds(0)->format('H:i:s');
                $this->info("[" . Carbon::now()->toDateTimeString() . "]Buscando shifts con start entre {$startLowerBound} y {$startUpperBound}");

                // Solo se seleccionarán los registros cuyo updated_at sea mayor a 2 segundos atrás.
                $twoSecondsAgo = Carbon::now()->subSeconds(2);
                
                    // Procesar turnos que finalizan (campo 'end')
                    $this->info("[" . Carbon::now()->toDateTimeString() . "]Buscando shifts con end igual a {$currentTime}");
                    $shiftFins = ShiftList::where('end', $currentTime)
                        ->where('updated_at', '<', $twoSecondsAgo)
                        ->where(function ($query) {
                            $query->where('active', '!=', 0)
                                ->orWhereNull('active');
                        })
                        ->get();
                    $this->info("[" . Carbon::now()->toDateTimeString() . "]Shifts encontrados para 'end': " . $shiftFins->count());

                    foreach ($shiftFins as $shiftFin) {
                        $this->info("[" . Carbon::now()->toDateTimeString() . "]Procesando shift id: {$shiftFin->id} con end: {$shiftFin->end}");
                        // Buscar el registro en barcodes con el mismo production_line_id
                        $barcodeFin = Barcode::where('production_line_id', $shiftFin->production_line_id)->first();

                        if ($barcodeFin) {
                            $this->info("[" . Carbon::now()->toDateTimeString() . "]Barcode encontrado para production_line_id: {$shiftFin->production_line_id}");
                            // Crear el topic y el mensaje JSON
                            $mqttTopicFin = $barcodeFin->mqtt_topic_barcodes . '/timeline_event';
                            $jsonMessageFin = json_encode([
                                'type' => 'shift',
                                'action'      => 'end',
                                'description'   => 'Turno' // Duración del turno en minutos
                            ]);
                            $this->info("[" . Carbon::now()->toDateTimeString() . "]Publicando mensaje MQTT en topic: {$mqttTopicFin} | Mensaje: {$jsonMessageFin}");

                            // Publicar el mensaje MQTT
                            $this->publishMqttMessage($mqttTopicFin, $jsonMessageFin);

                            // Actualizar el campo updated_at para marcar que se ha procesado
                            $shiftFin->update(['updated_at' => Carbon::now()]);
                        } else {
                            $this->info("[" . Carbon::now()->toDateTimeString() . "]No se encontró barcode para production_line_id: {$shiftFin->production_line_id}");
                        }
                    }
                } else {
                    $this->info("[" . Carbon::now()->toDateTimeString() . "]No es un día laboral. Día: {$dayOfWeek}");
                }
                //Procesamos turno de start
                $shifts = ShiftList::whereBetween('start', [$startLowerBound, $startUpperBound])
                                    ->where('updated_at', '<', $twoSecondsAgo)
                                    ->where(function ($query) {
                                        $query->where('active', '!=', 0)
                                            ->orWhereNull('active');
                                    })
                                    ->get();
            

                $this->info("[" . Carbon::now()->toDateTimeString() . "]Shifts encontrados para 'start': " . $shifts->count());

                foreach ($shifts as $shift) {
                    $this->info("[" . Carbon::now()->toDateTimeString() . "]Procesando shift id: {$shift->id} con start: {$shift->start}");
                    // Buscar el registro en barcodes con el mismo production_line_id
                    $barcode = Barcode::where('production_line_id', $shift->production_line_id)->first();

                    if ($barcode) {
                        $this->info("[" . Carbon::now()->toDateTimeString() . "]Barcode encontrado para production_line_id: {$shift->production_line_id}");
                        // Crear el topic y el mensaje JSON
                        $mqttTopic = $barcode->mqtt_topic_barcodes . '/timeline_event';
                        $jsonMessage = json_encode([
                            'type' => 'shift',
                            'action'      => 'start',
                            'description'   => 'Turno' // Duración del turno en minutos
                        ]);
                        $this->info("[" . Carbon::now()->toDateTimeString() . "]Publicando mensaje MQTT en topic: {$mqttTopic} | Mensaje: {$jsonMessage}");

                        // Publicar el mensaje MQTT
                        $this->publishMqttMessage($mqttTopic, $jsonMessage);

                        // Actualizar el campo updated_at para marcar que se ha procesado
                        $shift->update(['updated_at' => Carbon::now()]);
                    } else {
                        $this->info("[" . Carbon::now()->toDateTimeString() . "]No se encontró barcode para production_line_id: {$shift->production_line_id}");
                    }
                }



            // Esperar 1 segundo antes de la próxima verificación
            sleep(1);
            $this->info("[" . Carbon::now()->toDateTimeString() . "]Volver a procesar en 1 segundo.");

            // Verificación de interrupción limpia
            if ($this->shouldStop()) {
                $this->info("[" . Carbon::now()->toDateTimeString() . "]Detención del proceso solicitada.");
                return 0;
            }
        }
    }

    /**
     * Publicar mensaje MQTT en las tablas mqtt_send_server1 y mqtt_send_server2
     *
     * @param string $topic
     * @param string $message
     * @return void
     */
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
            Log::info("Mensaje almacenado en archivo (server1): {$fileName1}");
        
            // Guardar en servidor 2
            $fileName2 = storage_path("app/mqtt/server2/{$sanitizedTopic}_{$uniqueId}.json");
            if (!file_exists(dirname($fileName2))) {
                mkdir(dirname($fileName2), 0755, true);
            }
            file_put_contents($fileName2, $jsonData . PHP_EOL);
            Log::info("Mensaje almacenado en archivo (server2): {$fileName2}");
        } catch (\Exception $e) {
            Log::error("Error storing message in file: " . $e->getMessage());
        }
    }

    /**
     * Verificación para detener el proceso de forma segura
     *
     * @return bool
     */
    private function shouldStop()
    {
        // Aquí podrías implementar una lógica para detener el bucle de forma controlada,
        // por ejemplo, verificando una señal del sistema o la existencia de un archivo de control.
        return false;
    }
}
