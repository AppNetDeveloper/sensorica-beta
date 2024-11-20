#!/bin/bash

# Directorio donde se guardan los archivos de respaldo y archivos a limpiar
mkdir /var/www/ftp
DIR="/var/www/ftp"

# Limpiar archivos .zip que tienen más de 10 días
find "$DIR" -type f -name "*.zip" -mtime +10 -exec rm -f {} \;

# Ejecutar el backup de MySQL usando Laravel y Spatie
php /var/www/html/artisan backup:run --only-db
