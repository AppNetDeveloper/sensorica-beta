sudo cd /var/www/html
sudo supervisorctl stop all


git add .
git commit -m "Guardando cambios locales antes de rebase"
git pull --rebase origin main
# (resuelve conflictos si los hay)
git rebase --continue
git push origin main


php artisan migrate

npm update
composer update


# Definir el usuario y los comandos que permitiremos sin contraseña
USER="www-data"
COMMANDS=(
    "/sbin/reboot"
    "/sbin/poweroff"
    "/var/www/html/reboot-system.sh"
    "/var/www/html/poweroff-system.sh"
)

# Iterar sobre cada comando y verificar/agregar a sudoers
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


echo "SHIFT_TIME=08:00:00" >> .env
echo "PRODUCTION_MIN_TIME=3" >> .env
echo "PRODUCTION_MAX_TIME=5" >> .env

echo "EXTERNAL_API_QUEUE_MODEL=dataToSend3" >> .env
echo "EXTERNAL_API_QUEUE_TYPE=put" >> .env
echo "USE_CURL=true" >> .env

echo "WHATSAPP_LINK= http://127.0.0.1:3005" >> .env
echo "WHATSAPP_PHONE_NOT=34619929305" >> .env

echo "TOKEN_SYSTEM=ZZBSFSIOHJHLKLKJHIJJAHSTG" >> .env



sudo supervisorctl stop all
rm -rf /etc/supervisor/conf.d/*
cp laravel*.conf /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart all

