#!/bin/bash

# Configuración
DIR="/var/www/ftp"
ARTISAN_PATH="/var/www/html/artisan"
LOG_FILE="/var/www/html/storage/logs/backup.log"

# Función para logging
log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"
}

log_message "=== Iniciando script de limpieza y backup ==="

# Esperar 20 segundos para estabilización del sistema
log_message "Esperando 20 segundos para estabilización del sistema..."
sleep 20

# Crear el directorio si no existe
if [ ! -d "$DIR" ]; then
    log_message "El directorio $DIR no existe. Creando..."
    mkdir -p "$DIR"
    if [ $? -eq 0 ]; then
        log_message "Directorio $DIR creado exitosamente."
    else
        log_message "ERROR: No se pudo crear el directorio $DIR. Abortando."
        exit 1
    fi
fi

# Limpiar archivos .zip que tienen más de 10 días
log_message "Limpiando archivos .zip en $DIR que tienen más de 10 días..."
DELETED_COUNT=$(find "$DIR" -type f -name "*.zip" -mtime +10 -print | wc -l)
find "$DIR" -type f -name "*.zip" -mtime +10 -exec rm -f {} \;
if [ $? -eq 0 ]; then
    log_message "Archivos .zip antiguos eliminados con éxito. Total eliminados: $DELETED_COUNT"
else
    log_message "ADVERTENCIA: Error al intentar eliminar archivos antiguos. Verificar permisos."
fi

# Verificar si mysqldump está disponible
if ! command -v mysqldump &> /dev/null; then
    log_message "ADVERTENCIA: mysqldump no está instalado. Intentando instalar mysql-client..."
    
    # Intentar instalar mysql-client
    if command -v apt &> /dev/null; then
        apt update &> /dev/null && apt install -y mysql-client &> /dev/null
        if [ $? -eq 0 ]; then
            log_message "mysql-client instalado exitosamente."
        else
            log_message "ERROR: No se pudo instalar mysql-client. El backup puede fallar."
        fi
    else
        log_message "ERROR: Sistema no compatible con apt. Instalar mysqldump manualmente."
    fi
fi

# Verificar que Laravel esté disponible
if [ ! -f "$ARTISAN_PATH" ]; then
    log_message "ERROR: Artisan no encontrado en $ARTISAN_PATH"
    exit 1
fi

# Verificar configuración de Laravel
cd /var/www/html
if ! php artisan --version &> /dev/null; then
    log_message "ERROR: Laravel no está configurado correctamente"
    exit 1
fi

# Ejecutar el backup de MySQL
log_message "Ejecutando el backup de MySQL..."

# Intentar primero con Laravel/Spatie
log_message "Probando backup con Laravel/Spatie..."
BACKUP_OUTPUT=$(php "$ARTISAN_PATH" backup:run --only-db 2>&1)
BACKUP_EXIT_CODE=$?

if [ $BACKUP_EXIT_CODE -eq 0 ]; then
    log_message "Backup con Laravel/Spatie ejecutado exitosamente."
    log_message "Salida del backup: $BACKUP_OUTPUT"
else
    log_message "ADVERTENCIA: Falló el backup con Laravel/Spatie (código: $BACKUP_EXIT_CODE)"
    log_message "Error: $BACKUP_OUTPUT"
    
    # Usar backup directo como alternativa
    log_message "Ejecutando backup directo como alternativa..."
    
    if [ -f "/var/www/html/backup_direct.sh" ]; then
        DIRECT_OUTPUT=$(bash /var/www/html/backup_direct.sh 2>&1)
        DIRECT_EXIT_CODE=$?
        
        if [ $DIRECT_EXIT_CODE -eq 0 ]; then
            log_message "Backup directo exitoso."
            log_message "Salida: $DIRECT_OUTPUT"
        else
            log_message "ERROR: También falló el backup directo (código: $DIRECT_EXIT_CODE)"
            log_message "Error: $DIRECT_OUTPUT"
        fi
    else
        log_message "ERROR: Script de backup directo no encontrado"
    fi
fi

# Mostrar estadísticas finales
BACKUP_FILES=$(find "$DIR" -name "*.zip" -o -name "*.sql.gz" | wc -l)
log_message "Archivos de backup disponibles: $BACKUP_FILES"

if [ $BACKUP_FILES -gt 0 ]; then
    log_message "Últimos backups:"
    find "$DIR" -name "*.zip" -o -name "*.sql.gz" | head -5 | while read file; do
        SIZE=$(du -h "$file" | cut -f1)
        log_message "  - $(basename "$file") ($SIZE)"
    done
fi

log_message "=== Script finalizado ==="
