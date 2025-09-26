<?php

namespace App\Imports;

use App\Models\RfidDetail;
use App\Models\RfidReading;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Log;

class RfidDeviceImport implements ToCollection
{
    protected $production_line_id;

    /**
     * Constructor que recibe el id de la línea de producción.
     *
     * @param int $production_line_id
     */
    public function __construct($production_line_id)
    {
        $this->production_line_id = $production_line_id;
    }

    /**
     * Procesa la colección de filas del Excel.
     *
     * Se asume que el archivo tiene dos filas iniciales:
     *   - Fila 1: Título (por ejemplo: "RFID Dispositivos | Xmart Developer")
     *   - Fila 2: Encabezados con las siguientes columnas:
     *       Nombre, RFID Lectura EPC, RFID Tipo, MQTT Topic 1, Función Modelo 0, 
     *       Función Modelo 1, Invers Sensors, Tiempo óptimo de producción, 
     *       Multiplicador velocidad reducida, EPC, TID
     *
     * La columna TID (índice 10) se usa como identificador único para determinar
     * si el registro existe (en cuyo caso se actualiza) o se debe crear uno nuevo.
     *
     * Además, para el campo `rfid_reading_id` se busca en la tabla `rfid_readings` el
     * registro cuyo campo `epc` coincida con el valor de la columna RFID Lectura EPC (índice 1).
     *
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        // Omitir las dos primeras filas: título y encabezados.
        $rows->shift();
        $rows->shift();

        foreach ($rows as $row) {
            // Extraer valores y recortar espacios en blanco.
            $name                           = trim($row[0] ?? '');
            $readingEpc                     = trim($row[1] ?? ''); // Valor del EPC de la lectura
            $rfid_type                      = trim($row[2] ?? '');
            $mqtt_topic_1                   = trim($row[3] ?? '');
            $function_model_0               = trim($row[4] ?? '');
            $function_model_1               = trim($row[5] ?? '');
            $invers_sensors                 = trim($row[6] ?? '');
            $optimal_production_time        = trim($row[7] ?? '');
            $reduced_speed_time_multiplier  = trim($row[8] ?? '');
            $epc_device                     = trim($row[9] ?? '');
            $tid                            = trim($row[10] ?? '');

            // Si TID está vacío, se omite la fila.
            if (empty($tid)) {
                Log::info('Fila omitida: TID vacío.', ['row' => $row]);
                continue;
            }

            // Buscar el registro en rfid_readings usando el valor de EPC de la lectura.
            $rfidReading = RfidReading::where('epc', $readingEpc)
                ->where('production_line_id', $this->production_line_id)
                ->first();

            // Si no se encuentra la lectura, se omite la fila.
            if (!$rfidReading) {
                Log::info('Fila omitida: No se encontró lectura RFID para el EPC dado.', [
                    'readingEpc' => $readingEpc,
                    'row' => $row
                ]);
                continue;
            }

            // Preparar los datos para insertar o actualizar.
            $data = [
                'name'                           => $name,
                'rfid_reading_id'                => $rfidReading->id,  // Se obtiene el id de rfid_readings
                'rfid_type'                      => $rfid_type,
                'mqtt_topic_1'                   => $mqtt_topic_1,
                'function_model_0'               => $function_model_0,
                'function_model_1'               => $function_model_1,
                'invers_sensors'                 => $invers_sensors,
                'optimal_production_time'        => $optimal_production_time,
                'reduced_speed_time_multiplier'  => $reduced_speed_time_multiplier,
                'epc'                            => $epc_device,
                'tid'                            => $tid,
                'production_line_id'             => $this->production_line_id,
            ];

            // Buscar si ya existe un dispositivo con ese TID en esta línea.
            $device = RfidDetail::where('tid', $tid)
                        ->where('production_line_id', $this->production_line_id)
                        ->first();

            if ($device) {
                // Si existe, se actualiza.
                $device->update($data);
                Log::info('Dispositivo actualizado', ['tid' => $tid]);
            } else {
                // Si no existe, se crea un nuevo registro.
                RfidDetail::create($data);
                Log::info('Nuevo dispositivo creado', ['tid' => $tid]);
            }
        }
    }
}
