#!/bin/bash
#esperar 5 segundos
sleep 20

# Configuración
DIR="/var/www/ftp"
ARTISAN_PATH="/var/www/html/artisan"
BACKUP_COMMAND="php $ARTISAN_PATH backup:run --only-db"

# Crear el directorio si no existe
if [ ! -d "$DIR" ]; then
    echo "El directorio $DIR no existe. Creando..."
    mkdir -p "$DIR"
    if [ $? -eq 0 ]; then
        echo "Directorio $DIR creado exitosamente."
    else
        echo "Error al crear el directorio $DIR. Abortando."
        exit 1
    fi
fi

# Limpiar archivos .zip que tienen más de 10 días
echo "Limpiando archivos .zip en $DIR que tienen más de 10 días..."
find "$DIR" -type f -name "*.zip" -mtime +10 -exec rm -f {} \;
if [ $? -eq 0 ]; then
    echo "Archivos .zip antiguos eliminados con éxito."
else
    echo "Error al intentar eliminar archivos antiguos. Por favor, verifica permisos."
fi

# Ejecutar el backup de MySQL
echo "Ejecutando el backup de MySQL usando Laravel y Spatie..."
$BACKUP_COMMAND
if [ $? -eq 0 ]; then
    echo "Backup ejecutado exitosamente."
else
    echo "Error al ejecutar el comando de backup. Verifica el entorno de Laravel."
fi

echo "Script finalizado."
