#!/bin/bash

LOG_FILE="../storage/logs/laravel.log"
MAX_SIZE_MB=10

# Calcular el tamaño máximo en bytes
MAX_SIZE_BYTES=$((MAX_SIZE_MB * 1024 * 1024))

# Obtener el tamaño actual del archivo
CURRENT_SIZE=$(stat -c%s "$LOG_FILE")

if [ "$CURRENT_SIZE" -gt "$MAX_SIZE_BYTES" ]; then
    # Si el tamaño actual es mayor que el máximo, truncar el archivo
    echo "Truncando laravel.log..."
    truncate -s 100 "$LOG_FILE"  # Truncar el archivo (dejarlo vacío)
else
    echo "El tamaño de laravel.log está dentro del límite."
fi
