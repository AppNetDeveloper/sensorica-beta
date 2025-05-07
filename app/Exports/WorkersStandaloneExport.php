<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon; // Para formatear fechas

class WorkersStandaloneExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $data;

    /**
     * Recibe los datos ya procesados (ordenados, filtrados y aplanados)
     * desde el controlador.
     *
     * @param array $data Array de filas para el Excel.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Devuelve la colección de datos que se escribirán en el Excel.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Convierte el array de datos pre-procesados en una colección de Laravel
        return collect($this->data);
    }

    /**
     * Define los encabezados de las columnas del Excel.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'Codigo Trabajador',
            'Nombre Trabajador',
            'Unidades Turno', // Suma total del trabajador para el periodo
            'Puesto',
            'Inicio Puesto',
            'Fin Puesto',
            'Cantidad Puesto', // Cantidad específica de ese puesto
            'Cajas/Hora', // Nueva columna
            'Confeccion',
        ];
    }

    /**
     * Mapea cada fila de datos de la colección a las celdas del Excel.
     * Aquí se realiza el formateo final.
     *
     * @param mixed $row Un elemento del array $this->data pasado en el constructor.
     * Es un array asociativo como el que preparamos en el controlador.
     * @return array
     */
    public function map($row): array
    {
        // Formatear fechas para que coincidan con el formato del JS (dd/mm/yyyy hh:mm:ss)
        // o devolver '-' si la fecha es nula o inválida.
        $formattedCreatedAt = '-';
        if (!empty($row['post_created_at'])) {
            try {
                $formattedCreatedAt = Carbon::parse($row['post_created_at'])->format('d/m/Y H:i:s');
            } catch (\Exception $e) {
                // Log::warning('Error al parsear post_created_at en Export: ' . $row['post_created_at']);
            }
        }

        $formattedFinishAt = '-';
        if (!empty($row['post_finish_at'])) {
            try {
                $formattedFinishAt = Carbon::parse($row['post_finish_at'])->format('d/m/Y H:i:s');
            } catch (\Exception $e) {
                // Log::warning('Error al parsear post_finish_at en Export: ' . $row['post_finish_at']);
            }
        }

        return [
            $row['worker_client_id'] ?? '-',
            $row['worker_name'] ?? 'Sin Nombre',
            $row['total_quantity_sum'] ?? 0,
            $row['post_name'] ?? 'N/A',
            $formattedCreatedAt,
            $formattedFinishAt,
            $row['post_count'] ?? 0,
            $row['post_cajas_hora'] ?? 'N/A', // Mapeo del nuevo dato
            $row['product_name'] ?? 'N/A',
        ];
    }

    // ShouldAutoSize se encarga de ajustar el ancho de las columnas automáticamente.
}
