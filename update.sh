sudo cd /var/www/html
sudo supervisorctl stop all


git add .
git commit -m "Guardando cambios locales antes de rebase"
git pull --rebase origin main
# (resuelve conflictos si los hay)
git rebase --continue
git push origin main


#!/bin/bash

# Ejecutar migraciones de Laravel
echo "Ejecutando migraciones..."
php artisan migrate --force
if [ $? -eq 0 ]; then
    echo "Migraciones ejecutadas correctamente."
else
    echo "Error al ejecutar migraciones."
    exit 1
fi

# Actualizar dependencias de NPM
echo "Actualizando dependencias de NPM..."
npm update --yes
if [ $? -eq 0 ]; then
    echo "Dependencias de NPM actualizadas correctamente."
else
    echo "Error al actualizar dependencias de NPM."
    exit 1
fi

# Actualizar dependencias de Composer
echo "Actualizando dependencias de Composer..."
composer update --no-interaction
if [ $? -eq 0 ]; then
    echo "Dependencias de Composer actualizadas correctamente."
else
    echo "Error al actualizar dependencias de Composer."
    exit 1
fi

echo "Proceso completado con éxito."


#!/bin/bash

# Definir el usuario y el comando que permitiremos sin contraseña
USER="www-data"
COMMANDS=(
    "/sbin/reboot"
    "/sbin/poweroff"
    "/var/www/html/reboot-system.sh"
    "/var/www/html/poweroff-system.sh"
    "/usr/bin/supervisorctl restart all"
)

# Archivo temporal para manipular cron
CRON_TEMP=$(mktemp)

# Comando de cron que reinicia Supervisor 30 segundos después del arranque
CRON_COMMAND="@reboot sleep 30 && sudo /usr/bin/supervisorctl restart all"

# Agregar permisos para comandos necesarios en sudoers
for COMMAND in "${COMMANDS[@]}"; do
    if sudo grep -Fxq "$USER ALL=(ALL) NOPASSWD: $COMMAND" /etc/sudoers; then
        echo "La entrada para '$COMMAND' ya está en sudoers. No se requiere ninguna acción."
    else
        # Añadir la entrada a sudoers
        echo "Añadiendo la entrada para '$COMMAND' a sudoers..."
        echo "$USER ALL=(ALL) NOPASSWD: $COMMAND" | sudo EDITOR='tee -a' visudo
        echo "Entrada añadida correctamente para '$COMMAND'."
    fi
done

# Verificar si la tarea cron ya existe
sudo crontab -l > "$CRON_TEMP" 2>/dev/null
if grep -Fxq "$CRON_COMMAND" "$CRON_TEMP"; then
    echo "La tarea cron ya existe. No se requiere ninguna acción."
else
    # Añadir la tarea cron
    echo "Añadiendo tarea cron para reiniciar Supervisor..."
    echo "$CRON_COMMAND" >> "$CRON_TEMP"
    sudo crontab "$CRON_TEMP"
    echo "Tarea cron añadida correctamente."
fi

# Limpiar archivo temporal
rm -f "$CRON_TEMP"


# Definir las claves y valores a añadir
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
)


# Ruta al archivo .env
ENV_FILE=".env"

# Verificar si el archivo .env existe
if [ ! -f "$ENV_FILE" ]; then
    echo "El archivo $ENV_FILE no existe. Creándolo..."
    touch "$ENV_FILE"
fi

# Añadir las claves si no existen
for KEY in "${!ENV_VARS[@]}"; do
    if grep -q "^$KEY=" "$ENV_FILE"; then
        echo "La clave $KEY ya existe en $ENV_FILE. No se requiere ninguna acción."
    else
        echo "Añadiendo $KEY=${ENV_VARS[$KEY]} al archivo $ENV_FILE..."
        echo "$KEY=${ENV_VARS[$KEY]}" >> "$ENV_FILE"
    fi
done

echo "Actualización del archivo .env completada."



sudo supervisorctl stop all
rm -rf /etc/supervisor/conf.d/*
cp laravel*.conf /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart all

