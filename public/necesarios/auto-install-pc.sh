#!/bin/bash

# Verificar si se proporcionaron los argumentos necesarios
if [ "$#" -ne 6 ]; then
    echo "Uso:  <target_ip> <target_port> <local_port> <network_id_zerotier> <token_api_boisolo>"
    exit 1
fi

# Variables redirect tcp scanner
TARGET_IP=$1
TARGET_PORT=$2
LOCAL_PORT=$3
PYTHON_SCRIPT_PATH="/usr/local/bin/port_redirect.py"
SERVICE_FILE_PATH="/etc/systemd/system/port_redirect.service"

# ZeroTier Network ID
NETWORK_ID="$4"
# API Token
TOKEN="$5"
TARGET_URL=$6


# Update the system
sudo apt update
sudo apt upgrade -y
sudo apt install wget

# Update package manager database
sudo apt full-upgrade -y

# Remove old packages
sudo apt autoremove -y
sudo apt -y install curl
# Install ZeroTier One
sudo apt -y install apt-transport-https
sudo apt -y install ca-certificates
sudo apt -y install curl
sduo apt -y install gnupg
sudo apt -y install lsb-release
sudo apt -y install sqlite3

sudo apt -y install gdebi
sudo apt install -y wget gnupg
wget https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb
sudo dpkg -i google-chrome-stable_current_amd64.deb
sudo apt --fix-broken install


# Install plugins
sudo apt -y install openssh-server

sudo apt install -y python3 python3-pip

sudo pip3 -y install paho-mqtt
sudo apt -y install python3-paho-mqtt
sudo snap -y install suligap-python3-qrcode

sudo apt -y install zbar-tools

sudo apt -y install qrencode

sudo apt -y install imagemagick

sudo apt -y install libimage-exiftool-perl

sudo apt -y install libimage-info-perl

sudo apt -y install libimage-size-perl

pip3 install -y python3-bluez --break-system-packages

sudo pip3 -y install pybluez --break-system-packages

sudo apt -y install python3-pybluez
sudo apt -y install libbluetooth-dev

sudo apt -y install bluez
sudo apt -y install bluez-tools

sudo snap -y install chromium-browser
sudo snap install chromium-browser
sudo apt -y install chromium-browser
pip install paho-mqtt --break-system-packages
sudo apt -y install python3-bluez
sudo apt-get -y install python3-dev
sudo apt-get install -y bluetooth libbluetooth-dev
pip3 install --upgrade pip setuptools wheel
sudo apt-get -y install libbluetooth-dev
sudo apt-get install python3-bluez
pip3 install git+https://github.com/pybluez/pybluez.git#egg=pybluez
pip3 install --upgrade pip setuptools wheel



curl -s 'https://raw.githubusercontent.com/zerotier/ZeroTierOne/main/doc/contact%40zerotier.com.gpg' | gpg --import && \
if z=$(curl -s 'https://install.zerotier.com/' | gpg); then echo "$z" | sudo bash; fi

#sudo snap install zerotier
sudo zerotier-cli join "$NETWORK_ID"
sudo zerotier join "$NETWORK_ID"

# Install NoMachine
sudo wget https://download.nomachine.com/download/8.11/Linux/nomachine_8.11.3_4_amd64.deb
sudo dpkg -i nomachine_8.11.3_4_amd64.deb

#Instalación de IDMVS
sudo wget https://boisolo.dev/necesarios/IDMVS-1.0.0_x86_64_20220926.deb
sudo dpkg -i IDMVS-1.0.0_x86_64_20220926.deb

sudo apt -y install gnome-startup-applications
#Open Startup Applications via the Activities overview. Alternatively you can press Alt+F2 and run the gnome-session-properties command.

# Editar la configuración de unattended-upgrades
echo "Unattended-Upgrade::Automatic-Reboot "false";" >/etc/apt/apt.conf.d/20auto-upgrades
# Desactivar actualizaciones automáticas
echo "Desactivando actualizaciones automáticas..."
sudo systemctl stop unattended-upgrades
sudo systemctl disable unattended-upgrades

# Editar la configuración de unattended-upgrades para desactivar todas las actualizaciones automáticas
sudo bash -c 'cat > /etc/apt/apt.conf.d/20auto-upgrades <<EOF
APT::Periodic::Update-Package-Lists "0";
APT::Periodic::Download-Upgradeable-Packages "0";
APT::Periodic::AutocleanInterval "0";
APT::Periodic::Unattended-Upgrade "0";
EOF'

# Editar la configuración de update-notifier para desactivar notificaciones de actualizaciones
echo "Desactivando notificaciones de actualizaciones..."
sudo bash -c 'cat > /etc/apt/apt.conf.d/10periodic <<EOF
APT::Periodic::Update-Package-Lists "0";
APT::Periodic::Download-Upgradeable-Packages "0";
APT::Periodic::AutocleanInterval "0";
APT::Periodic::Unattended-Upgrade "0";
EOF'

# Desactivar los servicios que pueden iniciar actualizaciones automáticas
sudo systemctl stop apt-daily.timer
sudo systemctl disable apt-daily.timer
sudo systemctl stop apt-daily-upgrade.timer
sudo systemctl disable apt-daily-upgrade.timer

echo "Las actualizaciones automáticas y las notificaciones de actualizaciones han sido desactivadas."
# Stop and disable services that may initiate automatic updates
sudo systemctl stop apt-daily.timer
sudo systemctl disable apt-daily.timer
sudo systemctl stop apt-daily-upgrade.timer
sudo systemctl disable apt-daily-upgrade.timer

echo "Automatic updates and update notifications have been disabled."

# Restart services to apply changes
sudo systemctl daemon-reload
# Reiniciar el servicio para aplicar los cambios
sudo systemctl restart unattended-upgrades



# Recargar systemd para reconocer el nuevo servicio
echo "Recargando systemd para reconocer el nuevo servicio"
systemctl daemon-reload

# desabilitar el servicio para evitar que se inicie automáticamente al arrancar si esta creado antes
echo "Habilitando y arrancando el servicio"
sudo systemctl disable port_redirect.service
sudo systemctl stop port_redirect.service
sudo rm -rf ${PYTHON_SCRIPT_PATH}
sudo rm -rf ${SERVICE_FILE_PATH}


echo "Creando el script de Python en ${PYTHON_SCRIPT_PATH}"
cat << EOF > ${PYTHON_SCRIPT_PATH}
import socket
import threading
import time

def handle_client(client_socket, target_host, target_port):
    while True:
        try:
            target_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            target_socket.connect((target_host, target_port))

            def forward_data(source, destination):
                while True:
                    data = source.recv(4096)
                    if not data:
                        break
                    destination.sendall(data)

            client_to_target = threading.Thread(target=forward_data, args=(client_socket, target_socket))
            target_to_client = threading.Thread(target=forward_data, args=(target_socket, client_socket))
            client_to_target.start()
            target_to_client.start()
            client_to_target.join()
            target_to_client.join()
        except (socket.error, ConnectionResetError, BrokenPipeError) as e:
            print(f"Error de conexión: {e}. Reintentando en 1 segundo...")
            time.sleep(1)
            continue
        break

def main():
    local_host = '0.0.0.0'
    local_port = ${LOCAL_PORT}
    target_host = '${TARGET_IP}'
    target_port = ${TARGET_PORT}

    server_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    server_socket.bind((local_host, local_port))
    server_socket.listen(5)

    print(f"Redirigiendo {local_host}:{local_port} a {target_host}:{target_port}")

    while True:
        client_socket, addr = server_socket.accept()
        print(f"Conexión entrante de {addr}")
        threading.Thread(target=handle_client, args=(client_socket, target_host, target_port)).start()

if __name__ == "__main__":
    main()
EOF


# Hacer el script de Python ejecutable
echo "Haciendo el script de Python ejecutable"
chmod +x ${PYTHON_SCRIPT_PATH}

# Crear el archivo de servicio systemd
echo "Creando el archivo de servicio systemd en ${SERVICE_FILE_PATH}"
cat << EOF > ${SERVICE_FILE_PATH}
[Unit]
Description=Port Redirection Service
After=network.target

[Service]
ExecStart=/usr/bin/python3 ${PYTHON_SCRIPT_PATH}
Restart=always

[Install]
WantedBy=multi-user.target
EOF

# Recargar systemd para reconocer el nuevo servicio
echo "Recargando systemd para reconocer el nuevo servicio"
systemctl daemon-reload

# Habilitar y arrancar el servicio
echo "Habilitando y arrancando el servicio"
systemctl enable port_redirect.service
systemctl start port_redirect.service

# Crear un servicio systemd para lanzar Google Chrome en modo quiosco
cat << EOF > /etc/systemd/system/chrome-kiosk.service
[Unit]
Description=Google Chrome Kiosk Mode
After=network.target

[Service]
ExecStart=/usr/bin/google-chrome --kiosk --no-first-run --suppress-message-center-popups --block-new-web-contents --no-default-browser-check --password-store=basic --ignore-certificate-errors --ignore-ssl-errors --simulate-outdated-no-au='Tue, 31 Dec 2199 23:59:59 GMT' "$TARGET_URL"
Restart=always
User=tu_usuario # Reemplazar con el usuario adecuado
Environment=DISPLAY=:0

[Install]
WantedBy=multi-user.target
EOF

# Recargar systemd para reconocer el nuevo servicio
sudo systemctl daemon-reload

# Habilitar y arrancar el servicio de Google Chrome en modo quiosco
sudo systemctl enable chrome-kiosk.service
sudo systemctl start chrome-kiosk.service

echo "Configuración completada."

# Get ZeroTier IPv4 address using zerotier-cli
IPV4_ADDRESS=$(sudo zerotier-cli get "$NETWORK_ID" ip | grep -oE '[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+')

# Check if IPv4 address was found
if [ -n "$IPV4_ADDRESS" ]; then
    echo "ZeroTier IPv4 address for network $NETWORK_ID is: $IPV4_ADDRESS"
    # Send POST request to the API
    RESPONSE=$(curl -s -X POST https://boisolo.dev/api/ip-zerotier \
        -H "Content-Type: application/json" \
        -d "{\"token\":\"$TOKEN\", \"ipZerotier\":\"$IPV4_ADDRESS\"}")

    echo "API Response: $RESPONSE"

else
    echo "Failed to find ZeroTier IPv4 address for network $NETWORK_ID. Please make sure you are joined and authorized to the network."
fi

echo "Puedes cerrar este script y reiniciar el sistema."
