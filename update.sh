#!/bin/bash

# Verificar si pip está instalado
if ! command -v pip &> /dev/null
then
    echo "Error: pip no está instalado. Instálalo primero."
    exit 1
fi

# Obtener la versión actual de pymodbus
CURRENT_VERSION=$(pip show pymodbus | grep Version | awk '{print $2}')

echo "Versión actual de pymodbus: $CURRENT_VERSION"

# Verificar si pymodbus está instalado y si la versión es superior a 3.7.4
if [ -z "$CURRENT_VERSION" ]; then
    echo "pymodbus no está instalado. Instalando pymodbus 3.7.4..."
    pip install --break-system-packages pymodbus==3.7.4
elif [ "$CURRENT_VERSION" != "3.7.4" ]; then
    echo "Desinstalando pymodbus versión $CURRENT_VERSION..."
    pip uninstall -y --break-system-packages pymodbus
    echo "Instalando pymodbus versión 3.7.4..."
    pip install --break-system-packages pymodbus==3.7.4
else
    echo "pymodbus ya está en la versión 3.7.4, no es necesario actualizar."
fi

# Verificar la instalación
echo "Verificación de la instalación..."
pip show pymodbus

pip3 install psutil --break-system-packages

pip3 install paho-mqtt --break-system-packages


# Cambiar al directorio del proyecto
echo "Cambiando al directorio /var/www/html..."
cd /var/www/html || { echo "Error: No se pudo cambiar al directorio /var/www/html"; exit 1; }
    echo 'npm install update'
    /usr/bin/npm install
    /usr/bin/npm update
    echo 'dar permiso composer root y instalar y actualizar'
    export COMPOSER_ALLOW_SUPERUSER=1
    /usr/local/bin/composer update
   # /usr/bin/npm run dev
# Detener todos los procesos de Supervisor
#echo "Deteniendo todos los procesos de Supervisor..."
#sudo supervisorctl stop all

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
    "/bin/systemctl restart mysql"
    "/var/www/html/verne.sh"
    "/var/www/html/reset-sensor.sh"
    "/var/www/html/node/*.js"
    "/var/www/html/fix_log_permissions.sh"
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
    ["RFID_AUTO_ADD"]="true"
    
    ["EMAIL_FINISH_SHIFT_LISTWORKERS"]=""
    ["EMAIL_FINISH_SHIFT_LISTCONFECCIONSIGNED"]=""
    ["SESSION_LIFETIME"]="720"

    ["REPLICA_DB_HOST"]=""
    ["REPLICA_DB_PORT"]=""
    ["REPLICA_DB_DATABASE"]=""
    ["REPLICA_DB_USERNAME"]=""
    ["REPLICA_DB_PASSWORD"]=""
    
    ["RFID_READER_IP"]=""
    ["RFID_READER_PORT"]="1080"
    ["RFID_MONITOR_URL"]=""
    ["REDIS_PREFIX"]=""
    ["PROCESS_ORDERS_OUT_OF_STOCK"]="false"
    ["CREATE_ALL_PROCESSORDERS"]="false"
    ["PRODUCTION_BREAK_TIME"]="30"
    ["PRODUCTION_OEE_HISTORY_DAYS"]="10"
    ["PRODUCTION_OEE_MINIMUM"]="30"
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



# Ruta del archivo donde se almacenará el token
TOKEN_FILE="/var/www/html/storage/api_token.txt"

# Endpoint para registrar el servidor (usando el dominio con www)
REGISTER_URL="https://www.boisolo.dev/api/register-server"

# Función para generar un token aleatorio (60 caracteres hexadecimales)
generate_token() {
    openssl rand -hex 30
}

# Verifica si el archivo existe y contiene un token
if [ -f "$TOKEN_FILE" ] && [ -s "$TOKEN_FILE" ]; then
    API_TOKEN=$(cat "$TOKEN_FILE")
    echo "Token existente: $API_TOKEN"
else
    # No existe token: generar uno nuevo
    API_TOKEN=$(generate_token)
    echo "Nuevo token generado: $API_TOKEN"
    
    # Preparar payload para registrar el servidor.
    # Se obtienen el IP y el hostname del servidor.
    HOST_IP=$(hostname -I | awk '{print $1}')
    HOST_NAME=$(hostname)
    
    JSON_PAYLOAD=$(cat <<EOF
{
  "host": "$HOST_IP",
  "name": "Servidor $HOST_NAME",
  "emails": "admin@boisolo.dev",
  "phones": "6199929205",
  "telegrams": "303129205",
  "token": "$API_TOKEN"
}
EOF
)
    echo "Payload generado:"
    echo "$JSON_PAYLOAD"
    
    echo "Registrando servidor en la API..."
    # Se usa --insecure para ignorar la verificación SSL (ideal para pruebas; en producción usa certificados válidos)
    curl -v -X POST -H "Content-Type: application/json" -d "$JSON_PAYLOAD" "$REGISTER_URL" --insecure

    # Guardar el token en el archivo
    echo "$API_TOKEN" > "$TOKEN_FILE"
    echo "Token guardado en $TOKEN_FILE"
fi

php artisan db:seed --force || { echo "Error: Falló la ejecución de las seeds."; exit 1; }

sudo sh telegram/install.sh || { echo "Error: Falló la instalación de Telegram."; exit 1; }
sudo sh node/install.sh || { echo "Error: Falló la instalación de Node.js."; exit 1; }

# Verifica si el archivo .env no existe en la carpeta telegram
if [ ! -f "telegram/.env" ]; then
    cp "telegram/.env.example" "telegram/.env"
    echo "Se ha copiado .env.example a .env"
else
    echo "El archivo .env ya existe."
fi



# Define las entradas de cron que deseas agregar:
# En este ejemplo, se ejecuta el script clean_and_backup.sh a las 00:00 y a las 14:30.
CRON_ENTRY1="0 0 * * * /bin/bash /var/www/html/clean_and_backup.sh >> /var/www/html/storage/logs/clean_and_backup.log 2>&1"
CRON_ENTRY2="30 14 * * * /bin/bash /var/www/html/clean_and_backup.sh >> /var/www/html/storage/logs/clean_and_backup.log 2>&1"
CRON_ENTRY3="30 0 * * * python3 /var/www/html/python/entrena_shift.py >> /var/www/html/storage/logs/entrena_shift.log 2>&1"
CRON_ENTRY4="*/10 * * * * find /var/www/html/storage/app/mqtt/server2 -type f -mmin +5 -delete"
CRON_ENTRY5="*/10 * * * * find /var/www/html/storage/app/mqtt/server1 -type f -mmin +5 -delete"
CRON_ENTRY6="40 0 * * * python3 /var/www/html/python/entrenar_produccion.py >> /var/www/html/storage/logs/entrena_produccion.log 2>&1"
CRON_ENTRY7="30 3 * * * /usr/bin/php /var/www/html/artisan db:replicate-nightly >> /var/www/html/storage/logs/db_replicate.log 2>&1"
CRON_ENTRY8="0 * * * * /bin/bash /var/www/html/scripts/clean_lock_files.sh >> /var/www/html/storage/logs/clean_locks.log 2>&1"
CRON_ENTRY9="0 2 * * * /var/www/html/scripts/clean_logs.sh >> /var/www/html/storage/logs/clean_logs.log 2>&1"

# Obtiene la lista de cron actual
CURRENT_CRON=$(crontab -l 2>/dev/null)

# Función para agregar entrada si no existe
function add_cron_entry {
    local entry="$1"
    echo "$CURRENT_CRON" | grep -Fq "$entry"
    if [ $? -ne 0 ]; then
        echo "Agregando entrada cron: $entry"
        # Agrega la entrada a la lista existente
        CURRENT_CRON="${CURRENT_CRON}"$'\n'"$entry"
    else
        echo "La entrada ya existe: $entry"
    fi
}

add_cron_entry "$CRON_ENTRY1"
add_cron_entry "$CRON_ENTRY2"
add_cron_entry "$CRON_ENTRY3"
add_cron_entry "$CRON_ENTRY4"
add_cron_entry "$CRON_ENTRY5"
add_cron_entry "$CRON_ENTRY6"
add_cron_entry "$CRON_ENTRY7"
add_cron_entry "$CRON_ENTRY8"
add_cron_entry "$CRON_ENTRY9"

# Instala la nueva lista de cron
echo "$CURRENT_CRON" | crontab -
echo "Cron actualizado."


pip install pymysql pandas numpy scikit-learn tensorflow joblib --break-system-packages
pip install python-dotenv --break-system-package
pip3 install scikit-learn tensorflow pandas pymysql joblib --break-system-packages
pip install pymysql --break-system-packages
pip install SQLAlchemy --break-system-packages

#para que ignore los cambios de prmisos en git
git config core.fileMode false


cd /var/www/html/
echo 'limpiar cache'
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    echo 'npm install update'
    /usr/bin/npm install
    /usr/bin/npm update
    echo 'dar permiso composer root y instalar y actualizar'
    export COMPOSER_ALLOW_SUPERUSER=1
    /usr/local/bin/composer update
   # /usr/bin/npm run dev
    /usr/bin/npm run prod
    /usr/bin/npm run build
    
    echo 'dar los permisos necesario'
    cd storage || exit
    unzip logos.zip -d app
    cd /var/www/html/ || exit
    sudo rm -rf /var/www/html/public/storage
    sudo php artisan storage:link
    sudo chmod -R 777 /var/www
    sudo chmod -R 777 *
    sudo chmod -R 777 storage
    sudo chmod -R 777 app/Models
    sudo chmod 777 /var/www/html/storage/logs/
    sudo chmod 777 /var/www/html/storage/framework/sessions
    sudo chmod 777 /var/www/html/storage/framework/views
    sudo chown www-data:www-data /var/www/html/baileys_auth_info
    sudo chown www-data:www-data /var/www/html/wa-logs.txt
sudo chmod 664 /var/www/html/wa-logs.txt
sudo chown -R www-data:www-data /var/www/html/baileys_auth_info
sudo chmod -R 700 /var/www/html/baileys_auth_info
sudo chown www-data:www-data /var/www/html/baileys_store_multi.json
sudo chmod 600 /var/www/html/baileys_store_multi.json
sudo chown -R www-data:www-data /var/www/html/whatsapp
sudo chmod -R 755 /var/www/html/whatsapp

sudo chown -R :nginx /var/www/html
sudo chown -R :www-data /var/www/html
sudo chmod -R g+rwx /var/www/html
sudo chown -R :nginx /var/www/html
sudo chmod -R g+rwx /var/www/html
sudo chown -R www-data:www-data /var/www/html/whatsapp/media
sudo chmod -R 755 /var/www/html/whatsapp/media

sudo apt -y purge apache
sudo apt -y purge apache2
sudo apt -y autoremove


# Reiniciar Supervisor con nueva configuración
echo "Reconfigurando Supervisor..."
sudo rm -rf /etc/supervisor/conf.d/*
sudo cp laravel*.conf /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart all




sudo chown -R www-data:www-data /var/www/html/storage


chmod +x /var/www/html/update.sh

php artisan db:seed --class=DatabaseSeeder
php artisan db:seed --class=ProductionLineProcessesPermissionSeeder
php artisan db:seed --class=ProductionLineOrdersKanbanPermissionSeeder
php artisan db:seed --class=WorkCalendarPermissionSeeder
echo "Proceso completado con éxito."
