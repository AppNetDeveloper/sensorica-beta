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
    /usr/bin/npm install --no-audit --no-fund --no-progress
    /usr/bin/npm update --yes --no-audit --no-fund --no-progress
    echo 'dar permiso composer root y instalar y actualizar'
    export COMPOSER_ALLOW_SUPERUSER=1
    /usr/local/bin/composer update --no-interaction --prefer-dist --no-progress
   # /usr/bin/npm run dev
# Detener todos los procesos de Supervisor
#echo "Deteniendo todos los procesos de Supervisor..."
#sudo supervisorctl stop all

# Realizar backup de seguridad antes de cambios críticos
echo "Realizando backup de seguridad antes de la actualización..."
if [ -f "/var/www/html/backup_direct.sh" ]; then
    echo "Ejecutando backup directo de seguridad..."
    /bin/bash /var/www/html/backup_direct.sh
    if [ $? -eq 0 ]; then
        echo "✅ Backup de seguridad completado exitosamente."
    else
        echo "⚠️ ADVERTENCIA: Falló el backup de seguridad, pero continuando..."
    fi
else
    echo "⚠️ Script de backup directo no encontrado, continuando sin backup..."
fi

# Actualizar el repositorio: DESCARTAR cambios locales y sincronizar con origin/main
echo "Actualizando el repositorio (descartando cambios locales)..."
git fetch --all --prune || { echo "Error: Falló git fetch."; exit 1; }
git reset --hard origin/main || { echo "Error: No se pudo resetear a origin/main."; exit 1; }
git clean -fd || { echo "Error: Falló git clean."; exit 1; }

# Ejecutar migraciones de Laravel
echo "Ejecutando migraciones..."
php artisan migrate --force -n || { echo "Error: Falló la ejecución de las migraciones."; exit 1; }

# Actualizar dependencias de NPM
echo "Actualizando dependencias de NPM..."
npm update --yes --no-audit --no-fund --no-progress || { echo "Error: Falló la actualización de dependencias de NPM."; exit 1; }

# Actualizar dependencias de Composer
echo "Actualizando dependencias de Composer..."
export COMPOSER_ALLOW_SUPERUSER=1
composer update --no-interaction --prefer-dist --no-progress || { echo "Error: Falló la actualización de dependencias de Composer."; exit 1; }

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
    "/bin/systemctl restart cloudflared.service"
    "/bin/systemctl start cloudflared.service"
    "/bin/systemctl stop cloudflared.service"
    "/bin/systemctl enable cloudflared.service"
    "/bin/systemctl is-active cloudflared.service"
    "/bin/systemctl enable cloudflare-tunnel-monitor.timer"
    "/bin/systemctl start cloudflare-tunnel-monitor.timer"
    "/bin/systemctl stop cloudflare-tunnel-monitor.timer"
    "/bin/systemctl is-active cloudflare-tunnel-monitor.timer"
    "/var/www/html/verne.sh"
    "/var/www/html/reset-sensor.sh"
    "/var/www/html/node/*.js"
    "/var/www/html/fix_log_permissions.sh"
    "/var/www/html/scripts/cloudflare-tunnel-monitor.sh"
)


for COMMAND in "${COMMANDS[@]}"; do
    if sudo grep -Fxq "$USER ALL=\(ALL\) NOPASSWD: $COMMAND" /etc/sudoers; then
        echo "La entrada para '$COMMAND' ya está en sudoers."
    else
        echo "Añadiendo '$COMMAND' a sudoers..."
        echo "$USER ALL=\(ALL\) NOPASSWD: $COMMAND" | sudo EDITOR='tee -a' visudo
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
    
    ["MAIL_MAILER"]="smtp"
    ["MAIL_HOST"]="smtpdm-ap-southeast-1.aliyuncs.com"
    ["MAIL_PORT"]="465"
    ["MAIL_USERNAME"]="info@aixmart.net"

    ["MAIL_ENCRYPTION"]="ssl"
    ["MAIL_FROM_ADDRESS"]="info@aixmart.net"
    ["MAIL_FROM_NAME"]="Xmart Notificaciones"
    
    ["RFID_READER_IP"]=""
    ["RFID_READER_PORT"]="1080"
    ["RFID_MONITOR_URL"]=""
    ["REDIS_PREFIX"]=""
    ["PROCESS_ORDERS_OUT_OF_STOCK"]="false"
    ["CREATE_ALL_PROCESSORDERS"]="false"
    ["PRODUCTION_BREAK_TIME"]="30"
    ["PRODUCTION_OEE_HISTORY_DAYS"]="10"
    ["PRODUCTION_OEE_MINIMUM"]="30"
    ["READY_AFTER_SAFETY_HOURS"]="6"
    ["AI_URL"]=""
    ["AI_TOKEN"]=""
    ["CALLBACK_MAX_ATTEMPTS"]="20"
    ["ORDER_MIN_ACTIVE_SECONDS"]="60"
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
    curl -sS -X POST -H "Content-Type: application/json" -d "$JSON_PAYLOAD" "$REGISTER_URL" --insecure

    # Guardar el token en el archivo
    echo "$API_TOKEN" > "$TOKEN_FILE"
    echo "Token guardado en $TOKEN_FILE"
fi

php artisan db:seed --force -n || { echo "Error: Falló la ejecución de las seeds."; exit 1; }

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
# Backups: 00:00 (diario) y 14:30 (backup adicional)
CRON_ENTRY1="0 0 * * * /bin/bash /var/www/html/clean_and_backup.sh >> /var/www/html/storage/logs/clean_and_backup.log 2>&1"
CRON_ENTRY2="30 14 * * * /bin/bash /var/www/html/clean_and_backup.sh >> /var/www/html/storage/logs/clean_and_backup.log 2>&1"
# Backup directo de emergencia cada 6 horas
CRON_BACKUP_EMERGENCY="0 */6 * * * /bin/bash /var/www/html/backup_direct.sh >> /var/www/html/storage/logs/backup_direct.log 2>&1"
CRON_ENTRY3="30 0 * * * python3 /var/www/html/python/entrena_shift.py >> /var/www/html/storage/logs/entrena_shift.log 2>&1"
CRON_ENTRY4="*/10 * * * * find /var/www/html/storage/app/mqtt/server2 -type f -mmin +5 -delete"
CRON_ENTRY5="*/10 * * * * find /var/www/html/storage/app/mqtt/server1 -type f -mmin +5 -delete"
CRON_ENTRY6="40 0 * * * python3 /var/www/html/python/entrenar_produccion.py >> /var/www/html/storage/logs/entrena_produccion.log 2>&1"
CRON_ENTRY7="30 3 * * * /usr/bin/php /var/www/html/artisan db:replicate-nightly >> /var/www/html/storage/logs/db_replicate.log 2>&1"
CRON_ENTRY8="0 * * * * /bin/bash /var/www/html/scripts/clean_lock_files.sh >> /var/www/html/storage/logs/clean_locks.log 2>&1"
CRON_ENTRY9="0 2 * * * /var/www/html/scripts/clean_logs.sh >> /var/www/html/storage/logs/clean_logs.log 2>&1"
# Permisos de logs - optimizado para ejecutar cada 5 minutos en lugar de 24 entradas separadas
CRON_ENTRY10="*/5 * * * * /bin/bash /var/www/html/fix_log_permissions.sh >/dev/null 2>&1"
CRON_ENTRY34="0 1 * * * /usr/bin/php /var/www/html/artisan db:replicate-nightly >> /var/www/html/storage/logs/db_replicate.log 2>&1"
CRON_ENTRY_SENSORCOUNTS="30 3 * * * /usr/bin/php /var/www/html/artisan sensorcounts:clean --days=30 >> /var/www/html/storage/logs/sensorcounts_clean.log 2>&1"

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
add_cron_entry "$CRON_BACKUP_EMERGENCY"
add_cron_entry "$CRON_ENTRY3"
add_cron_entry "$CRON_ENTRY4"
add_cron_entry "$CRON_ENTRY5"
add_cron_entry "$CRON_ENTRY6"
add_cron_entry "$CRON_ENTRY7"
add_cron_entry "$CRON_ENTRY8"
add_cron_entry "$CRON_ENTRY9"
add_cron_entry "$CRON_ENTRY10"
add_cron_entry "$CRON_ENTRY34"
add_cron_entry "$CRON_ENTRY_SENSORCOUNTS"

# Instala la nueva lista de cron
echo "$CURRENT_CRON" | crontab -
echo "Cron actualizado."


pip install pymysql pandas numpy scikit-learn tensorflow joblib --break-system-packages
pip install python-dotenv --break-system-packages
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
    /usr/bin/npm install --no-audit --no-fund --no-progress
    /usr/bin/npm update --yes --no-audit --no-fund --no-progress
    echo 'dar permiso composer root y instalar y actualizar'
    export COMPOSER_ALLOW_SUPERUSER=1
    /usr/local/bin/composer update --no-interaction --prefer-dist --no-progress
   # /usr/bin/npm run dev
    /usr/bin/npm run prod
    # "build" no existe en package.json; si se añade en el futuro, descomenta la línea siguiente
    # /usr/bin/npm run build
    
    echo 'dar los permisos necesario'
    cd storage || exit
    if [ -f logos.zip ]; then
        unzip -o logos.zip -d app
    else
        echo "logos.zip no existe, omitiendo unzip"
    fi
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
    # Crear archivos y directorios si no existen para evitar errores de chown/chmod
    sudo mkdir -p /var/www/html/baileys_auth_info
    sudo touch /var/www/html/wa-logs.txt
    sudo touch /var/www/html/baileys_store_multi.json
    sudo mkdir -p /var/www/html/whatsapp /var/www/html/whatsapp/media
    # Asignar permisos/propietarios
    sudo chown www-data:www-data /var/www/html/baileys_auth_info
    sudo chown www-data:www-data /var/www/html/wa-logs.txt
    sudo chmod 664 /var/www/html/wa-logs.txt
    sudo chown -R www-data:www-data /var/www/html/baileys_auth_info
    sudo chmod -R 700 /var/www/html/baileys_auth_info
    sudo chown www-data:www-data /var/www/html/baileys_store_multi.json
    sudo chmod 600 /var/www/html/baileys_store_multi.json
    sudo chown -R www-data:www-data /var/www/html/whatsapp
    sudo chmod -R 755 /var/www/html/whatsapp
    
    # Asignar grupo si existe 'nginx'; de lo contrario usar www-data
    if getent group nginx >/dev/null 2>&1; then
        sudo chown -R :nginx /var/www/html
        sudo chmod -R g+rwx /var/www/html
    fi
    sudo chown -R :www-data /var/www/html
    sudo chmod -R g+rwx /var/www/html
    sudo chown -R www-data:www-data /var/www/html/whatsapp/media
    sudo chmod -R 755 /var/www/html/whatsapp/media

    sudo DEBIAN_FRONTEND=noninteractive apt -y -o Dpkg::Options::="--force-confnew" purge apache || true
    sudo DEBIAN_FRONTEND=noninteractive apt -y -o Dpkg::Options::="--force-confnew" purge apache2 || true
    sudo DEBIAN_FRONTEND=noninteractive apt -y autoremove || true


    # Reiniciar Supervisor con nueva configuración
    echo "Reconfigurando Supervisor..."
    sudo rm -rf /etc/supervisor/conf.d/*
    sudo cp laravel*.conf /etc/supervisor/conf.d/
    sudo supervisorctl reread
    sudo supervisorctl update
    sudo supervisorctl restart all

    # Configurar y arrancar el monitor de túnel Cloudflare
    echo "Configurando monitor de túnel Cloudflare..."
    
    # Verificar si el script de monitoreo existe
    if [ -f "/var/www/html/scripts/cloudflare-tunnel-monitor.sh" ]; then
        echo "✅ Script de monitoreo encontrado"
        chmod +x /var/www/html/scripts/cloudflare-tunnel-monitor.sh
        
        # Verificar si el timer está habilitado
        if systemctl is-enabled cloudflare-tunnel-monitor.timer >/dev/null 2>&1; then
            echo "✅ Timer de monitoreo ya está habilitado"
        else
            echo "🔧 Habilitando timer de monitoreo..."
            sudo systemctl daemon-reload
            sudo systemctl enable cloudflare-tunnel-monitor.timer
        fi
        
        # Verificar si el timer está ejecutándose
        if systemctl is-active cloudflare-tunnel-monitor.timer >/dev/null 2>&1; then
            echo "✅ Timer de monitoreo ya está ejecutándose"
        else
            echo "🚀 Iniciando timer de monitoreo..."
            sudo systemctl start cloudflare-tunnel-monitor.timer
        fi
        
        # Verificar estado final
        if systemctl is-active cloudflare-tunnel-monitor.timer >/dev/null 2>&1; then
            echo "✅ Monitor de túnel Cloudflare configurado y ejecutándose correctamente"
            echo "📊 Estado del monitor:"
            /var/www/html/scripts/cloudflare-tunnel-monitor.sh status
        else
            echo "⚠️ ADVERTENCIA: No se pudo iniciar el monitor de túnel Cloudflare"
        fi
    else
        echo "⚠️ ADVERTENCIA: Script de monitoreo de Cloudflare no encontrado en /var/www/html/scripts/"
        echo "💡 El monitor se puede instalar manualmente después de la actualización"
    fi




    sudo chown -R www-data:www-data /var/www/html/storage


    chmod +x /var/www/html/update.sh
    
    # Asegurar que los scripts de backup tengan permisos de ejecución
    echo "Configurando permisos de scripts de backup..."
    chmod +x /var/www/html/clean_and_backup.sh 2>/dev/null || echo "clean_and_backup.sh no encontrado"
    chmod +x /var/www/html/backup_direct.sh 2>/dev/null || echo "backup_direct.sh no encontrado"
    chmod +x /var/www/html/check_backups.sh 2>/dev/null || echo "check_backups.sh no encontrado"
    
    # Crear directorios de logs si no existen
    mkdir -p /var/www/html/storage/logs
    touch /var/www/html/storage/logs/backup.log
    touch /var/www/html/storage/logs/backup_direct.log
    touch /var/www/html/storage/logs/clean_and_backup.log
    chown -R www-data:www-data /var/www/html/storage/logs

    php artisan db:seed --class=DatabaseSeeder --force -n
    php artisan db:seed --class=ProductionLineProcessesPermissionSeeder --force -n
    php artisan db:seed --class=ProductionLineOrdersKanbanPermissionSeeder --force -n
    php artisan db:seed --class=WorkCalendarPermissionSeeder --force -n
    php artisan db:seed --class=OriginalOrderFilePermissionSeeder --force -n
    php artisan db:seed --class=ProductionOrderCallbackPermissionsSeeder --force -n
    php artisan db:seed --class=FleetPermissionsSeeder --force -n
    php artisan db:seed --class=CustomerClientsPermissionsSeeder --force -n
    php artisan db:seed --class=RoutePlanPermissionsSeeder --force -n
    php artisan db:seed --class=RouteNamePermissionsSeeder --force -n
    echo "Proceso completado con éxito..."
