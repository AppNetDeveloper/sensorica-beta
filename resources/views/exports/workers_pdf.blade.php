<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Informe Operadores {{ \Carbon\Carbon::parse($fromDate)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($toDate)->format('d/m/Y') }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif; /* Fuente compatible con DomPDF para caracteres especiales */
            font-size: 9px; /* Reducido un poco para más columnas */
            line-height: 1.2;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 3px 5px; /* Ajustado el padding */
            text-align: left;
            vertical-align: top; /* Alinear arriba para mejor legibilidad en filas agrupadas */
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        /* Estilo para la fila separadora (simulada) */
        .separator td {
            border: none; /* Sin bordes */
            height: 8px; /* Altura para crear espacio */
            padding: 0;
            background-color: #fff; /* Fondo blanco para que no se vea gris si hay alternancia */
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 16px;
        }
        /* Clases para ocultar/mostrar datos en filas agrupadas */
        .data-operator { /* Celdas con datos del operador */
             font-weight: bold; /* Opcional: resaltar primera fila */
        }
        .data-post { /* Celdas con datos del puesto */
            /* Puedes añadir padding izquierdo si quieres indentar */
             /* padding-left: 10px; */ /* Removido para ahorrar espacio, se puede re-añadir si se prefiere */
        }
        /* Estilo para celdas vacías en filas agrupadas */
        .empty-cell {
            color: #fff; /* Hacer el texto invisible si es necesario, o dejarlo vacío */
            /* Opcionalmente, para mantener la altura de la celda si está vacía: */
            /* visibility: hidden; */
        }
        td {
           word-wrap: break-word; /* Para ajustar texto largo */
           /* white-space: nowrap; */ /* Descomentar si se prefiere no ajustar y que se expanda */
        }
    </style>
</head>
<body>

    <h1>Informe de Operadores y Puestos</h1>
    <p><strong>Periodo:</strong> {{ \Carbon\Carbon::parse($fromDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($toDate)->format('d/m/Y') }}</p>

    <table>
        <thead>
            <tr>
                <th>Código Trabajador</th>
                <th>Nombre Trabajador</th>
                <th>Unidades Turno</th>
                <th>Puesto</th>
                <th>Inicio Puesto</th>
                <th>Fin Puesto</th>
                <th>Cantidad Puesto</th>
                <th>Cajas/Hora</th> {/* Nueva columna */}
                <th>Confección</th>
            </tr>
        </thead>
        <tbody>
            @if (empty($data))
                <tr>
                    <td colspan="9" style="text-align: center;">No se encontraron datos para el periodo seleccionado.</td> {/* Colspan actualizado */}
                </tr>
            @else
                @foreach ($data as $row)
                    {{-- Comprobar si es una fila separadora (todos los valores son '') --}}
                    @php
                        $isSeparator = true;
                        // Comprobamos si todas las claves esperadas para una fila de datos están vacías o no existen
                        // para determinar si es una fila separadora.
                        // Esto es más robusto que solo chequear si todos los valores son ''.
                        $dataKeys = ['worker_client_id', 'worker_name', 'total_quantity_sum', 'post_name', 'post_created_at', 'post_finish_at', 'post_count', 'post_cajas_hora', 'product_name'];
                        foreach ($dataKeys as $key) {
                            if (isset($row[$key]) && $row[$key] !== '') {
                                $isSeparator = false;
                                break;
                            }
                        }
                        // Si después de chequear todas las claves, $isSeparator sigue true,
                        // y el array $row tiene exactamente la estructura de $emptyRowStructure (todas las claves presentes y vacías)
                        // entonces es un separador.
                        if ($isSeparator && count(array_filter($row)) === 0 && count($row) === count($dataKeys)) {
                            // Es un separador
                        } else if (count(array_filter($row)) === 0 && count($row) < count($dataKeys) ) {
                            // Podría ser una fila vacía inesperada, no la tratamos como separador necesariamente
                            // o sí, dependiendo de la lógica de $emptyRowStructure en el controlador.
                            // Para este caso, si es completamente vacía, la trataremos como separador.
                            $isSeparator = true;
                             foreach ($row as $value) { //Fallback a la lógica original si la estructura no coincide
                                if ($value !== '') {
                                    $isSeparator = false;
                                    break;
                                }
                            }
                        } else {
                             $isSeparator = false; // No es un separador si tiene datos
                        }


                    @endphp

                    @if ($isSeparator)
                        <tr class="separator">
                            <td colspan="9"></td> {/* Fila vacía para separación visual, colspan actualizado */}
                        </tr>
                    @else
                        <tr>
                            {{-- Mostrar datos del operador solo si no están vacíos --}}
                            <td class="{{ ($row['worker_client_id'] ?? '') !== '' ? 'data-operator' : 'empty-cell' }}">{{ $row['worker_client_id'] ?? '' }}</td>
                            <td class="{{ ($row['worker_name'] ?? '') !== '' ? 'data-operator' : 'empty-cell' }}">{{ $row['worker_name'] ?? '' }}</td>
                            <td class="{{ ($row['total_quantity_sum'] ?? '') !== '' ? 'data-operator' : 'empty-cell' }}">{{ $row['total_quantity_sum'] ?? '' }}</td>

                            {{-- Mostrar siempre los datos del puesto, formateando fechas --}}
                            <td class="data-post">{{ $row['post_name'] ?? 'N/A' }}</td>
                            <td class="data-post">
                                @if(!empty($row['post_created_at']))
                                    {{ \Carbon\Carbon::parse($row['post_created_at'])->format('d/m/Y H:i:s') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="data-post">
                                @if(!empty($row['post_finish_at']))
                                    {{ \Carbon\Carbon::parse($row['post_finish_at'])->format('d/m/Y H:i:s') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="data-post">{{ $row['post_count'] ?? '0' }}</td>
                            <td class="data-post">{{ $row['post_cajas_hora'] ?? 'N/A' }}</td> {/* Nueva celda */}
                            <td class="data-post">{{ $row['product_name'] ?? 'N/A' }}</td>
                        </tr>
                    @endif
                @endforeach
            @endif
        </tbody>
    </table>

</body>
</html>
