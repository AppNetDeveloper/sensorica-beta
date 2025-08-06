<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración de Producción
    |--------------------------------------------------------------------------
    |
    | Este archivo contiene la configuración específica para el módulo de producción.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Tiempo de Pausa por Turno (en minutos)
    |--------------------------------------------------------------------------
    |
    | Este valor define el tiempo de pausa (por ejemplo, para comida) que se debe
    | considerar en cada turno al calcular los tiempos de producción.
    |
    */
    'break_time_minutes' => env('PRODUCTION_BREAK_TIME', 30),
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de OEE Histórico
    |--------------------------------------------------------------------------
    |
    | Estos valores definen cómo se calcula y aplica el OEE histórico 
    | para ajustar los tiempos estimados de producción.
    |
    */
    // Número de días para calcular el OEE promedio
    'oee_history_days' => env('PRODUCTION_OEE_HISTORY_DAYS', 30),
    
    // OEE mínimo a aplicar (porcentaje)
    'oee_minimum_percentage' => env('PRODUCTION_OEE_MINIMUM', 30),
];
