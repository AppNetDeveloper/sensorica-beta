<?php

namespace App\Exports;

use App\Models\Maintenance;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MaintenancesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $query;
    protected $totals;

    public function __construct($query, $totals = [])
    {
        $this->query = $query;
        $this->totals = $totals;
    }

    public function collection()
    {
        return $this->query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Línea de Producción',
            'Creado',
            'Inicio',
            'Fin',
            'Parada Previa',
            'Tiempo Avería',
            'Tiempo Total',
            'Causas',
            'Piezas',
            'Operario',
            'Usuario',
            'Anotaciones',
            'Anotaciones Operario',
        ];
    }

    public function map($maintenance): array
    {
        $created = Carbon::parse($maintenance->created_at);
        $end = $maintenance->end_datetime ? Carbon::parse($maintenance->end_datetime) : Carbon::now();
        $totalSeconds = max(0, $created->diffInSeconds($end));

        $stoppedSeconds = 0;
        $downtimeSeconds = 0;

        if ($maintenance->start_datetime) {
            $start = Carbon::parse($maintenance->start_datetime);
            $stoppedSeconds = max(0, $created->diffInSeconds($start));
            $downtimeSeconds = max(0, $start->diffInSeconds($end));
        } else {
            $stoppedSeconds = $totalSeconds;
        }

        return [
            $maintenance->id,
            optional($maintenance->productionLine)->name ?? '-',
            $maintenance->created_at ? Carbon::parse($maintenance->created_at)->format('Y-m-d H:i') : '-',
            $maintenance->start_datetime ? Carbon::parse($maintenance->start_datetime)->format('Y-m-d H:i') : '-',
            $maintenance->end_datetime ? Carbon::parse($maintenance->end_datetime)->format('Y-m-d H:i') : '-',
            gmdate('H:i:s', $stoppedSeconds),
            gmdate('H:i:s', $downtimeSeconds),
            gmdate('H:i:s', $totalSeconds),
            $maintenance->causes ? $maintenance->causes->pluck('name')->join(', ') : '-',
            $maintenance->parts ? $maintenance->parts->pluck('name')->join(', ') : '-',
            optional($maintenance->operator)->name ?? '-',
            optional($maintenance->user)->name ?? '-',
            $maintenance->annotations ?? '-',
            $maintenance->operator_annotations ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Mantenimientos';
    }
}
