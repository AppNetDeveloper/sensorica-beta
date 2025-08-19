#!/bin/bash

# Configuración
LOG_DIR="/var/www/html/storage/logs"
MAX_SIZE_MB=100       # Tamaño máximo de cada archivo de log en MB
MAX_LOG_FILES=10      # Número máximo de archivos de log antiguos a mantener

# Fix Laravel log permissions
chown -R www-data:www-data $LOG_DIR
chmod -R 775 $LOG_DIR

# Función para convertir MB a bytes
mb_to_bytes() {
    echo $(($1 * 1024 * 1024))
}

# Rotar logs grandes
echo "Comprobando tamaño de logs..."
MAX_SIZE_BYTES=$(mb_to_bytes $MAX_SIZE_MB)

for log_file in $LOG_DIR/laravel-*.log; do
    if [ -f "$log_file" ]; then
        file_size=$(stat -c%s "$log_file")
        if [ $file_size -gt $MAX_SIZE_BYTES ]; then
            echo "Rotando log grande: $log_file ($((file_size / 1024 / 1024)) MB)"
            timestamp=$(date +"%Y%m%d-%H%M%S")
            mv "$log_file" "${log_file}.${timestamp}"
            touch "$log_file"
            chown www-data:www-data "$log_file"
            chmod 775 "$log_file"
            
            # Comprimir el archivo rotado
            gzip "${log_file}.${timestamp}"
        fi
    fi
done

# Limpiar logs antiguos si hay demasiados
echo "Limpiando logs antiguos..."
log_count=$(find $LOG_DIR -name "laravel-*.log.*" | wc -l)
if [ $log_count -gt $MAX_LOG_FILES ]; then
    echo "Hay $log_count archivos de log antiguos, eliminando los más antiguos..."
    find $LOG_DIR -name "laravel-*.log.*" -type f -printf "%T@ %p\n" | sort -n | head -n $(($log_count - $MAX_LOG_FILES)) | cut -d' ' -f2- | xargs rm -f
    echo "Logs antiguos eliminados. Quedan $(find $LOG_DIR -name "laravel-*.log.*" | wc -l) archivos."
fi

# Output the date this script was run
echo "Log permissions fixed and size managed on $(date)"
