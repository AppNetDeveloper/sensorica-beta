#!/bin/bash

# Script de backup directo para MySQL
# Este script funciona independientemente de Laravel/Spatie

# Configuración
BACKUP_DIR="/var/www/ftp"
ENV_FILE="/var/www/html/.env"
LOG_FILE="/var/www/html/storage/logs/backup_direct.log"
DATE=$(date +%Y%m%d_%H%M%S)

# Función para logging
log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"
}

log_message "=== Iniciando backup directo de MySQL ==="

# Verificar que existe el archivo .env
if [ ! -f "$ENV_FILE" ]; then
    log_message "ERROR: Archivo .env no encontrado en $ENV_FILE"
    exit 1
fi

# Leer configuración de la base de datos
DB_HOST=$(grep "^DB_HOST=" "$ENV_FILE" | cut -d'=' -f2 | tr -d '"' | tr -d "'")
DB_PORT=$(grep "^DB_PORT=" "$ENV_FILE" | cut -d'=' -f2 | tr -d '"' | tr -d "'")
DB_DATABASE=$(grep "^DB_DATABASE=" "$ENV_FILE" | cut -d'=' -f2 | tr -d '"' | tr -d "'")
DB_USERNAME=$(grep "^DB_USERNAME=" "$ENV_FILE" | cut -d'=' -f2 | tr -d '"' | tr -d "'")
DB_PASSWORD=$(grep "^DB_PASSWORD=" "$ENV_FILE" | cut -d'=' -f2 | tr -d '"' | tr -d "'")

# Valores por defecto si no se encuentran
DB_HOST=${DB_HOST:-localhost}
DB_PORT=${DB_PORT:-3306}

log_message "Configuración de BD: Host=$DB_HOST, Puerto=$DB_PORT, BD=$DB_DATABASE, Usuario=$DB_USERNAME"

# Verificar que mysqldump esté disponible
if ! command -v mysqldump &> /dev/null; then
    log_message "ERROR: mysqldump no está instalado"
    log_message "Instalando mysql-client..."
    
    apt update &> /dev/null
    apt install -y mysql-client-core-8.4 &> /dev/null
    
    if ! command -v mysqldump &> /dev/null; then
        log_message "ERROR: No se pudo instalar mysqldump"
        exit 1
    fi
    
    log_message "mysqldump instalado exitosamente"
fi

# Crear directorio de backup si no existe
mkdir -p "$BACKUP_DIR"

# Nombre del archivo de backup
BACKUP_FILE="$BACKUP_DIR/backup_${DB_DATABASE}_${DATE}.sql"

log_message "Iniciando backup de la base de datos '$DB_DATABASE'..."

# Ejecutar mysqldump
if [ -n "$DB_PASSWORD" ]; then
    # Con contraseña
    mysqldump -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" \
        --single-transaction \
        --routines \
        --triggers \
        --add-drop-table \
        --extended-insert \
        "$DB_DATABASE" > "$BACKUP_FILE" 2>/dev/null
else
    # Sin contraseña
    mysqldump -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" \
        --single-transaction \
        --routines \
        --triggers \
        --add-drop-table \
        --extended-insert \
        "$DB_DATABASE" > "$BACKUP_FILE" 2>/dev/null
fi

# Verificar si el backup fue exitoso
if [ $? -eq 0 ] && [ -s "$BACKUP_FILE" ]; then
    # Comprimir el backup
    gzip "$BACKUP_FILE"
    COMPRESSED_FILE="${BACKUP_FILE}.gz"
    
    # Obtener tamaño del archivo
    SIZE=$(du -h "$COMPRESSED_FILE" | cut -f1)
    
    log_message "Backup exitoso: $(basename "$COMPRESSED_FILE") ($SIZE)"
    
    # Limpiar backups antiguos (mantener solo los últimos 7)
    log_message "Limpiando backups antiguos..."
    find "$BACKUP_DIR" -name "backup_${DB_DATABASE}_*.sql.gz" -type f -mtime +7 -delete
    
    # Mostrar estadísticas
    BACKUP_COUNT=$(find "$BACKUP_DIR" -name "backup_${DB_DATABASE}_*.sql.gz" | wc -l)
    log_message "Backups disponibles para $DB_DATABASE: $BACKUP_COUNT"
    
    log_message "=== Backup completado exitosamente ==="
    exit 0
else
    log_message "ERROR: Falló el backup de la base de datos"
    rm -f "$BACKUP_FILE" 2>/dev/null
    exit 1
fi
