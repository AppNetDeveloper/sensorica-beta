#!/bin/bash

LOG_FILE="storage/logs/laravel.log"
MAX_SIZE=200 # Tamaño máximo en MB

current_size=$(du -m "$LOG_FILE" | cut -f1)

if [ $current_size -gt $MAX_SIZE ]; then
    echo "" > "$LOG_FILE"
    echo "Laravel log file cleaned (was $current_size MB)."
else
    echo "Laravel log file is under the size limit ($current_size MB)."
fi
