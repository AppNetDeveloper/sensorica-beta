#!/bin/bash
# Script para limpiar logs grandes diariamente
# Creado: $(date)

# Definir umbral de tamaño (50MB)
SIZE_THRESHOLD=50000000

# Log del script
LOG_FILE="/var/log/clean_logs.log"

echo "$(date) - Iniciando limpieza de logs" >> $LOG_FILE

# Función para truncar archivos grandes
truncate_large_files() {
    local dir=$1
    
    # Encontrar archivos más grandes que el umbral
    find $dir -type f -size +${SIZE_THRESHOLD}c | while read file; do
        # Obtener tamaño antes
        size_before=$(du -h "$file" | cut -f1)
        
        # Truncar el archivo
        truncate -s 0 "$file"
        
        echo "$(date) - Truncado: $file (tamaño anterior: $size_before)" >> $LOG_FILE
    done
}

# Limpiar logs específicos que sabemos que crecen mucho
echo "$(date) - Truncando archivos específicos conocidos" >> $LOG_FILE

# Archivos específicos a truncar siempre
specific_files=(
    "/var/log/syslog"
    "/var/log/power-modbus-mqtt.log"
)

for file in "${specific_files[@]}"; do
    if [ -f "$file" ]; then
        size_before=$(du -h "$file" | cut -f1)
        truncate -s 0 "$file"
        echo "$(date) - Truncado archivo específico: $file (tamaño anterior: $size_before)" >> $LOG_FILE
    fi
done

# Limpiar archivos grandes en directorios comunes de logs
echo "$(date) - Buscando archivos grandes en directorios de logs" >> $LOG_FILE
truncate_large_files "/var/log"
truncate_large_files "/var/www/html/storage/logs"

# Comprimir logs antiguos de Laravel
if [ -d "/var/www/html/storage/logs" ]; then
    find /var/www/html/storage/logs -name "laravel-*.log" -mtime +7 -exec gzip {} \;
    echo "$(date) - Comprimidos logs antiguos de Laravel" >> $LOG_FILE
fi

# Mostrar espacio en disco después de la limpieza
df_after=$(df -h / | grep -v Filesystem)
echo "$(date) - Espacio en disco después de limpieza: $df_after" >> $LOG_FILE

echo "$(date) - Limpieza de logs completada" >> $LOG_FILE
echo "-----------------------------------" >> $LOG_FILE
