<?php

namespace App\Imports;

use App\Models\RfidReading;
use App\Models\RfidColor;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class RfidReadingImport implements ToCollection
{
    /**
     * Identificador de la línea de producción.
     *
     * @var int
     */
    protected $production_line_id;

    /**
     * Constructor para inyectar el id de la línea de producción.
     *
     * @param int $production_line_id
     */
    public function __construct($production_line_id)
    {
        $this->production_line_id = $production_line_id;
    }

    /**
     * Se procesan cada una de las filas del Excel.
     *
     * Se asume que el archivo tiene un encabezado y que las columnas están en el siguiente orden:
     *  - Columna 0: ID (puede ignorarse)
     *  - Columna 1: Nombre
     *  - Columna 2: EPC
     *  - Columna 3: COLOR
     *
     * @param \Illuminate\Support\Collection $rows
     */
    public function collection(Collection $rows)
    {
        // Eliminamos las dos primeras filas: título y encabezado.
        $rows->shift(); // Quita la primera fila (título)
        $rows->shift(); // Quita la segunda fila (encabezados)

        foreach ($rows as $row) {
            // Obtenemos los valores de cada columna
            $name  = trim($row[1] ?? '');
            $epc   = trim($row[2] ?? '');
            $color = trim($row[3] ?? '');

            // Validación: se asume que EPC y COLOR son obligatorios.
            if (empty($epc) || empty($color)) {
                continue; // Omitir filas incompletas
            }

            // Buscar en la tabla rfid_colors utilizando la columna 'name'
            $rfidColor = RfidColor::where('name', $color)->first();

            // Si no se encuentra el color, se omite la fila
            if (!$rfidColor) {
                continue;
            }
            $rfidColorId = $rfidColor->id;

            // Buscar un registro existente en rfid_readings para el EPC y la línea de producción indicados
            $rfidReading = RfidReading::where('epc', $epc)
                ->where('production_line_id', $this->production_line_id)
                ->first();

            if ($rfidReading) {
                // Si existe, se actualiza el nombre y el id del color
                $rfidReading->update([
                    'name'           => $name,
                    'rfid_color_id'  => $rfidColorId,
                ]);
            } else {
                // Si no existe, se crea un nuevo registro
                RfidReading::create([
                    'name'                => $name,
                    'epc'                 => $epc,
                    'production_line_id'  => $this->production_line_id,
                    'rfid_color_id'       => $rfidColorId,
                ]);
            }
        }
    }
}
