<?php

namespace App\Imports;

use App\Models\RfidReading;
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
     *
     * @param \Illuminate\Support\Collection $rows
     */
    public function collection(Collection $rows)
    {
        // Removemos la fila de encabezado
        // Eliminamos las dos primeras filas: título y encabezado.
        $rows->shift(); // Quita la primera fila (título)
        $rows->shift(); // Quita la segunda fila (encabezados)

        foreach ($rows as $row) {
            // Asegurarse de tener los datos necesarios. Se asume que el EPC es obligatorio.
            $name = trim($row[1] ?? '');
            $epc  = trim($row[2] ?? '');

            if (empty($epc)) {
                continue; // Omitir filas sin EPC
            }

            // Buscar un registro existente que corresponda al EPC para la línea de producción indicada
            $rfidReading = RfidReading::where('epc', $epc)
                ->where('production_line_id', $this->production_line_id)
                ->first();

            if ($rfidReading) {
                // Si existe, se actualiza el nombre u otros campos según sea necesario
                $rfidReading->update([
                    'name' => $name,
                    // No es necesario actualizar 'epc' ni 'production_line_id' si no cambian
                ]);
            } else {
                // Si no existe, se crea un nuevo registro
                RfidReading::create([
                    'name'                => $name,
                    'epc'                 => $epc,
                    'production_line_id'  => $this->production_line_id,
                ]);
            }
        }
    }
}
