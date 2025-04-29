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
            'Confeccion',
        ];
    }

    /**
     * Mapea cada fila de datos de la colección a las celdas del Excel.
     * Aquí se realiza el formateo final.
     *
     * @param mixed $row Un elemento del array $this->data pasado en el constructor.
     * @return array
     */
    public function map($row): array
    {
        // $row es un array asociativo como el que preparamos en el controlador
        return [
            $row['worker_client_id'] ?? '-',
            $row['worker_name'] ?? 'Sin Nombre',
            $row['total_quantity_sum'] ?? 0, // La suma total calculada en el controlador
            $row['post_name'] ?? 'N/A',
            // Formatear fechas para que coincidan con el formato del JS (dd/mm/yyyy hh:mm:ss)
            $row['post_created_at'] ? Carbon::parse($row['post_created_at'])->format('d/m/Y H:i:s') : '-',
            $row['post_finish_at'] ? Carbon::parse($row['post_finish_at'])->format('d/m/Y H:i:s') : '-',
            $row['post_count'] ?? 0,
            $row['product_name'] ?? 'N/A',
        ];
    }

    // ShouldAutoSize se encarga de ajustar el ancho de las columnas automáticamente.
}
