#!/bin/bash

# Script para eliminar archivos de bloqueo antiguos
# Ejecutar como cron cada hora

LOCK_FILE="/var/www/html/storage/app/orders_check.lock"
LOG_FILE="/var/www/html/storage/logs/clean_locks.log"
MAX_AGE=1800  # 30 minutos en segundos

# Función para registrar mensajes
log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> "$LOG_FILE"
}

# Verificar si el archivo existe
if [ -f "$LOCK_FILE" ]; then
    # Obtener la edad del archivo en segundos
    FILE_AGE=$(($(date +%s) - $(stat -c %Y "$LOCK_FILE")))
    
    log_message "Archivo de bloqueo encontrado con edad de $FILE_AGE segundos"
    
    # Si el archivo es más antiguo que MAX_AGE, eliminarlo
    if [ $FILE_AGE -gt $MAX_AGE ]; then
        if rm "$LOCK_FILE"; then
            log_message "✅ Archivo de bloqueo antiguo eliminado exitosamente"
        else
            log_message "❌ Error al eliminar archivo de bloqueo antiguo"
            # Intento alternativo: truncar el archivo
            if : > "$LOCK_FILE"; then
                log_message "✅ Archivo de bloqueo truncado como alternativa"
            else
                log_message "❌ No se pudo truncar el archivo de bloqueo"
            fi
        fi
    else
        log_message "ℹ️ Archivo de bloqueo es reciente, no se elimina"
    fi
else
    log_message "ℹ️ No se encontró archivo de bloqueo"
fi

exit 0
