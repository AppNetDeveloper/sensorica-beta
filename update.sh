#!/bin/bash

# Cambiar al directorio del proyecto
echo "Cambiando al directorio /var/www/html..."
cd /var/www/html || { echo "Error: No se pudo cambiar al directorio /var/www/html"; exit 1; }

# Detener todos los procesos de Supervisor
echo "Deteniendo todos los procesos de Supervisor..."
sudo supervisorctl stop all

# Guardar cambios locales en Git antes de hacer un rebase
echo "Guardando cambios locales en Git..."
git add .
git pull origin main
#git commit -m "Guardando cambios locales antes de rebase"

# Hacer pull con rebase
echo "Actualizando el repositorio con rebase..."
#git pull --rebase origin main || { echo "Error: Falló el pull con rebase."; exit 1; }

# Resolver conflictos si los hay
#git rebase --continue || echo "No hay conflictos de rebase o ya fueron resueltos."

# Empujar los cambios al repositorio
echo "Empujando los cambios al repositorio remoto..."
#git push origin main || { echo "Error: Falló el push al repositorio remoto."; exit 1; }

# Ejecutar migraciones de Laravel
echo "Ejecutando migraciones..."
php artisan migrate --force || { echo "Error: Falló la ejecución de las migraciones."; exit 1; }

# Actualizar dependencias de NPM
echo "Actualizando dependencias de NPM..."
npm update --yes || { echo "Error: Falló la actualización de dependencias de NPM."; exit 1; }

# Actualizar dependencias de Composer
echo "Actualizando dependencias de Composer..."
export COMPOSER_ALLOW_SUPERUSER=1
composer update --no-interaction || { echo "Error: Falló la actualización de dependencias de Composer."; exit 1; }

# Configurar permisos en sudoers
echo "Configurando permisos en sudoers..."
USER="www-data"
COMMANDS=(
    "/sbin/reboot"
    "/sbin/poweroff"
    "/var/www/html/reboot-system.sh"
    "/var/www/html/poweroff-system.sh"
    "/var/www/html/update.sh"
    "/usr/bin/supervisorctl restart all"
    "/usr/bin/supervisorctl stop all"
    "/usr/bin/supervisorctl start all"
    "/usr/bin/supervisorctl reread"
    "/usr/bin/supervisorctl update"
    "/usr/bin/supervisorctl status"
    "/bin/systemctl restart 485.service"
    "/bin/systemctl is-active 485.service"
    "/bin/systemctl daemon-reload"
    "/bin/systemctl enable 485.service"
    "/bin/systemctl start 485.service"
    "/var/www/html/verne.sh"
)


for COMMAND in "${COMMANDS[@]}"; do
    if sudo grep -Fxq "$USER ALL=(ALL) NOPASSWD: $COMMAND" /etc/sudoers; then
        echo "La entrada para '$COMMAND' ya está en sudoers."
    else
        echo "Añadiendo '$COMMAND' a sudoers..."
        echo "$USER ALL=(ALL) NOPASSWD: $COMMAND" | sudo EDITOR='tee -a' visudo
    fi
done

# Crear un cron para reiniciar Supervisor después del arranque
echo "Configurando tarea cron para reiniciar Supervisor..."
CRON_TEMP=$(mktemp)
CRON_COMMAND="@reboot sleep 30 && sudo /usr/bin/supervisorctl restart all"

sudo crontab -l > "$CRON_TEMP" 2>/dev/null
if grep -Fxq "$CRON_COMMAND" "$CRON_TEMP"; then
    echo "La tarea cron ya existe."
else
    echo "$CRON_COMMAND" >> "$CRON_TEMP"
    sudo crontab "$CRON_TEMP"
    echo "Tarea cron añadida correctamente."
fi
rm -f "$CRON_TEMP"

# Actualizar el archivo .env
echo "Actualizando el archivo .env..."
declare -A ENV_VARS=(
    ["SHIFT_TIME"]="08:00:00"
    ["PRODUCTION_MIN_TIME"]="3"
    ["PRODUCTION_MAX_TIME"]="5"
    ["EXTERNAL_API_QUEUE_MODEL"]="dataToSend3"
    ["EXTERNAL_API_QUEUE_TYPE"]="put"
    ["USE_CURL"]="true"
    ["WHATSAPP_LINK"]="http://127.0.0.1:3005"
    ["WHATSAPP_PHONE_NOT"]="34619929305"
    ["TOKEN_SYSTEM"]="ZZBSFSIOHJHLKLKJHIJJAHSTG"
    ["TCP_SERVER"]="localhost"
    ["TCP_PORT"]="8000"
    ["LOCAL_SERVER"]="http://127.0.0.1/"
    ["PRODUCTION_MIN_TIME_WEIGHT"]="30"
    ["CLEAR_DB_DAY"]="30"
)

ENV_FILE=".env"
if [ ! -f "$ENV_FILE" ]; then
    echo "El archivo $ENV_FILE no existe. Creándolo..."
    touch "$ENV_FILE"
fi

for KEY in "${!ENV_VARS[@]}"; do
    if grep -q "^$KEY=" "$ENV_FILE"; then
        echo "La clave $KEY ya existe en $ENV_FILE."
    else
        echo "$KEY=${ENV_VARS[$KEY]}" >> "$ENV_FILE"
        echo "Añadida clave $KEY al archivo .env."
    fi
done

# Reiniciar Supervisor con nueva configuración
echo "Reconfigurando Supervisor..."
sudo rm -rf /etc/supervisor/conf.d/*
sudo cp laravel*.conf /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart all

echo "Proceso completado con éxito."
