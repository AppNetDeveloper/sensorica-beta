<?php
// Nuevo comando Artisan
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\ShiftList;
use App\Models\Barcode;
use App\Models\MqttSendServer1;
use App\Models\MqttSendServer2;
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
        parent::__Construct();
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
            $this->info("Día de la semana: {$dayOfWeek}");
    
            // Verificar si es de lunes a viernes
            if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
                // Obtener la hora actual
                $currentTime = Carbon::now()->format('H:i:s');
                $this->info("Hora actual: {$currentTime}");
    
                // Definir el rango para la consulta de 'start'
                $startLowerBound = Carbon::now()->subSeconds(1)->format('H:i:s');
                $startUpperBound = Carbon::now()->addSeconds(1)->format('H:i:s');
                $this->info("Buscando shifts con start entre {$startLowerBound} y {$startUpperBound}");
    
                $shifts = ShiftList::whereBetween('start', [$startLowerBound, $startUpperBound])->get();
                $this->info("Shifts encontrados para 'start': " . $shifts->count());
    
                foreach ($shifts as $shift) {
                    $this->info("Procesando shift id: {$shift->id} con start: {$shift->start}");
                    // Buscar el registro en barcodes con el mismo production_line_id
                    $barcode = Barcode::where('production_line_id', $shift->production_line_id)->first();
                    
                    if ($barcode) {
                        $this->info("Barcode encontrado para production_line_id: {$shift->production_line_id}");
                        // Crear el topic y el mensaje JSON
                        $mqttTopic = $barcode->mqtt_topic_barcodes . '/shift';
                        $jsonMessage = json_encode([
                            'shift_type' => 'Turno Programado',
                            'event'      => 'start',
                            'duration'   => 480 // Duración del turno en minutos
                        ]);
                        $this->info("Publicando mensaje MQTT en topic: {$mqttTopic} | Mensaje: {$jsonMessage}");
    
                        // Publicar el mensaje MQTT
                        $this->publishMqttMessage($mqttTopic, $jsonMessage);
                    } else {
                        $this->info("No se encontró barcode para production_line_id: {$shift->production_line_id}");
                    }
                }
    
                // Buscar en la tabla shift_list los turnos que coincidan con la hora actual en el campo 'end'
                $this->info("Buscando shifts con end igual a {$currentTime}");
                $shiftFins = ShiftList::where('end', $currentTime)->get();
                $this->info("Shifts encontrados para 'end': " . $shiftFins->count());
    
                foreach ($shiftFins as $shiftFin) {
                    $this->info("Procesando shift id: {$shiftFin->id} con end: {$shiftFin->end}");
                    // Buscar el registro en barcodes con el mismo production_line_id
                    $barcodeFin = Barcode::where('production_line_id', $shiftFin->production_line_id)->first();
                    
                    if ($barcodeFin) {
                        $this->info("Barcode encontrado para production_line_id: {$shiftFin->production_line_id}");
                        // Crear el topic y el mensaje JSON
                        $mqttTopicFin = $barcodeFin->mqtt_topic_barcodes . '/shift';
                        $jsonMessageFin = json_encode([
                            'shift_type' => 'Turno Programado',
                            'event'      => 'stop',
                            'duration'   => 0
                        ]);
                        $this->info("Publicando mensaje MQTT en topic: {$mqttTopicFin} | Mensaje: {$jsonMessageFin}");
    
                        // Publicar el mensaje MQTT
                        $this->publishMqttMessage($mqttTopicFin, $jsonMessageFin);
                    } else {
                        $this->info("No se encontró barcode para production_line_id: {$shiftFin->production_line_id}");
                    }
                }
            } else {
                $this->info("No es un día laboral. Día: {$dayOfWeek}");
            }
    
            // Esperar 0.1 segundos antes de la próxima verificación
            sleep(1); // Puedes ajustar el intervalo de espera según tus necesidades
            $this->info("Volver a procesar en 1 segundo.");
    
            // Verificación de interrupción limpia
            if ($this->shouldStop()) {
                $this->info("Detención del proceso solicitada.");
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
            // Inserta en la tabla mqtt_send_server1
            MqttSendServer1::createRecord($topic, $message);

            // Inserta en la tabla mqtt_send_server2
            MqttSendServer2::createRecord($topic, $message);

            $this->info("Stored message in both mqtt_send_server1 and mqtt_send_server2 tables.");

        } catch (\Exception $e) {
            Log::error("Error storing message in databases: " . $e->getMessage());
        }
    }

    /**
     * Verificación para detener el proceso de forma segura
     *
     * @return bool
     */
    private function shouldStop()
    {
        // Aquí podrías implementar una lógica para detener el bucle de forma controlada
        // como verificar una señal de sistema o un archivo de control.
        return false;
    }
}
