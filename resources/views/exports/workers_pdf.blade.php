<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Informe Operadores {{ $fromDate }} a {{ $toDate }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif; /* Fuente compatible con DomPDF para caracteres especiales */
            font-size: 10px;
            line-height: 1.2;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 4px 6px;
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
            height: 10px; /* Altura para crear espacio */
            padding: 0;
            background-color: #fff; /* Fondo blanco para que no se vea gris si hay alternancia */
        }
        /* Opcional: Colores alternos para filas de datos (ignora separadores) */
        /* Esto es más complejo de lograr perfectamente con los separadores,
           se puede omitir para simplificar o ajustar con CSS más avanzado si es necesario */
        /*
        tbody tr:not(.separator):nth-child(odd) {
             background-color: #f9f9f9;
        }
        */
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
             padding-left: 15px;
        }
        /* Estilo para celdas vacías en filas agrupadas */
        .empty-cell {
            color: #fff; /* Hacer el texto invisible si es necesario */
            /* O simplemente dejarlo vacío */
        }
        td {
           word-wrap: break-word; /* Para ajustar texto largo */
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
                <th>Confección</th>
            </tr>
        </thead>
        <tbody>
            @if (empty($data))
                <tr>
                    <td colspan="8" style="text-align: center;">No se encontraron datos para el periodo seleccionado.</td>
                </tr>
            @else
                @foreach ($data as $row)
                    {{-- Comprobar si es una fila separadora (todos los valores son '') --}}
                    @php
                        $isSeparator = true;
                        foreach ($row as $value) {
                            if ($value !== '') {
                                $isSeparator = false;
                                break;
                            }
                        }
                    @endphp

                    @if ($isSeparator)
                        <tr class="separator">
                            <td colspan="8"></td> {{-- Fila vacía para separación visual --}}
                        </tr>
                    @else
                        <tr>
                            {{-- Mostrar datos del operador solo si no están vacíos --}}
                            <td class="{{ $row['worker_client_id'] !== '' ? 'data-operator' : 'empty-cell' }}">{{ $row['worker_client_id'] }}</td>
                            <td class="{{ $row['worker_name'] !== '' ? 'data-operator' : 'empty-cell' }}">{{ $row['worker_name'] }}</td>
                            <td class="{{ $row['total_quantity_sum'] !== '' ? 'data-operator' : 'empty-cell' }}">{{ $row['total_quantity_sum'] }}</td>
                            {{-- Mostrar siempre los datos del puesto --}}
                            <td class="data-post">{{ $row['post_name'] }}</td>
                            <td class="data-post">{{ $row['post_created_at'] }}</td>
                            <td class="data-post">{{ $row['post_finish_at'] }}</td>
                            <td class="data-post">{{ $row['post_count'] }}</td>
                            <td class="data-post">{{ $row['product_name'] }}</td>
                        </tr>
                    @endif
                @endforeach
            @endif
        </tbody>
    </table>

</body>
</html>
