#!/bin/bash

# Script para verificar el estado de los backups

BACKUP_DIR="/var/www/ftp"
LOG_FILE="/var/www/html/storage/logs/backup.log"
DIRECT_LOG="/var/www/html/storage/logs/backup_direct.log"

echo "=== ESTADO DE BACKUPS ==="
echo "Fecha: $(date)"
echo

# Verificar directorio de backups
if [ -d "$BACKUP_DIR" ]; then
    echo "📁 Directorio de backups: $BACKUP_DIR"
    
    # Contar archivos de backup
    ZIP_COUNT=$(find "$BACKUP_DIR" -name "*.zip" 2>/dev/null | wc -l)
    SQL_COUNT=$(find "$BACKUP_DIR" -name "*.sql.gz" 2>/dev/null | wc -l)
    TOTAL_COUNT=$((ZIP_COUNT + SQL_COUNT))
    
    echo "📊 Total de backups: $TOTAL_COUNT"
    echo "   - Archivos .zip (Spatie): $ZIP_COUNT"
    echo "   - Archivos .sql.gz (Directo): $SQL_COUNT"
    echo
    
    if [ $TOTAL_COUNT -gt 0 ]; then
        echo "📋 Últimos 5 backups:"
        find "$BACKUP_DIR" -name "*.zip" -o -name "*.sql.gz" | sort -r | head -5 | while read file; do
            SIZE=$(du -h "$file" | cut -f1)
            DATE=$(stat -c %y "$file" | cut -d' ' -f1,2 | cut -d'.' -f1)
            echo "   - $(basename "$file") ($SIZE) - $DATE"
        done
        echo
        
        # Verificar backup más reciente
        LATEST=$(find "$BACKUP_DIR" -name "*.zip" -o -name "*.sql.gz" | xargs ls -t | head -1)
        if [ -n "$LATEST" ]; then
            LATEST_DATE=$(stat -c %Y "$LATEST")
            CURRENT_DATE=$(date +%s)
            HOURS_AGO=$(( (CURRENT_DATE - LATEST_DATE) / 3600 ))
            
            echo "⏰ Último backup: $(basename "$LATEST")"
            echo "   Hace $HOURS_AGO horas"
            
            if [ $HOURS_AGO -lt 24 ]; then
                echo "   ✅ Estado: RECIENTE"
            elif [ $HOURS_AGO -lt 48 ]; then
                echo "   ⚠️  Estado: ANTIGUO (>24h)"
            else
                echo "   ❌ Estado: MUY ANTIGUO (>48h)"
            fi
        fi
    else
        echo "❌ No se encontraron archivos de backup"
    fi
else
    echo "❌ Directorio de backups no existe: $BACKUP_DIR"
fi

echo
echo "=== LOGS DE BACKUP ==="

# Verificar log principal
if [ -f "$LOG_FILE" ]; then
    echo "📄 Log principal: $LOG_FILE"
    LAST_LINES=$(tail -5 "$LOG_FILE" 2>/dev/null)
    if [ -n "$LAST_LINES" ]; then
        echo "Últimas 5 líneas:"
        echo "$LAST_LINES" | sed 's/^/   /'
    fi
else
    echo "❌ Log principal no encontrado"
fi

echo

# Verificar log directo
if [ -f "$DIRECT_LOG" ]; then
    echo "📄 Log backup directo: $DIRECT_LOG"
    LAST_LINES=$(tail -3 "$DIRECT_LOG" 2>/dev/null)
    if [ -n "$LAST_LINES" ]; then
        echo "Últimas 3 líneas:"
        echo "$LAST_LINES" | sed 's/^/   /'
    fi
else
    echo "❌ Log backup directo no encontrado"
fi

echo
echo "=== HERRAMIENTAS DISPONIBLES ==="

# Verificar mysqldump
if command -v mysqldump &> /dev/null; then
    MYSQLDUMP_VERSION=$(mysqldump --version 2>/dev/null | head -1)
    echo "✅ mysqldump: $MYSQLDUMP_VERSION"
else
    echo "❌ mysqldump: No instalado"
fi

# Verificar Laravel
if [ -f "/var/www/html/artisan" ]; then
    if php /var/www/html/artisan --version &> /dev/null; then
        LARAVEL_VERSION=$(php /var/www/html/artisan --version 2>/dev/null)
        echo "✅ Laravel: $LARAVEL_VERSION"
    else
        echo "❌ Laravel: Configuración incorrecta"
    fi
else
    echo "❌ Laravel: Artisan no encontrado"
fi

# Verificar configuración de BD
if [ -f "/var/www/html/.env" ]; then
    DB_HOST=$(grep "^DB_HOST=" /var/www/html/.env | cut -d'=' -f2 | tr -d '"' | tr -d "'")
    DB_DATABASE=$(grep "^DB_DATABASE=" /var/www/html/.env | cut -d'=' -f2 | tr -d '"' | tr -d "'")
    echo "✅ Configuración BD: $DB_DATABASE @ $DB_HOST"
else
    echo "❌ Archivo .env no encontrado"
fi

echo
echo "=== ESPACIO EN DISCO ==="
df -h "$BACKUP_DIR" 2>/dev/null || df -h /var/www/

echo
echo "=== RECOMENDACIONES ==="

if [ $TOTAL_COUNT -eq 0 ]; then
    echo "🔧 Ejecutar backup manual: ./backup_direct.sh"
elif [ $HOURS_AGO -gt 24 ]; then
    echo "🔧 Backup antiguo, ejecutar: ./clean_and_backup.sh"
else
    echo "✅ Sistema de backup funcionando correctamente"
fi

echo
echo "=== FIN DEL REPORTE ==="
