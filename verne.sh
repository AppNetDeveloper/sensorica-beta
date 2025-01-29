#!/bin/bash

    echo "Desinstalando Mosquitto..."
    sudo systemctl stop vernemq
sudo systemctl disable vernemq
sudo apt-get -y purge vernemq

    sudo apt purge -y vernemq mosquitto-clients
    sudo rm -rf /etc/vernemq /var/log/vernemq /var/lib/vernemq
    sudo apt-get -y purge vernemq
    sudo rm -rf /etc/vernemq
sudo rm -rf /var/lib/vernemq
sudo rm -rf /var/log/vernemq



# Instalar Mosquitto y el cliente Mosquitto en Ubuntu, ahora instalamos la version 2.0.1
echo "Instalando Mosquitto y el cliente..."
sudo apt install -y  mosquitto-clients
sudo apt-get -y install erlang 
sudo apt -y  install libsnappy1v5

# Detecta la arquitectura
ARCH=$(uname -m)

# Define las URLs para cada arquitectura
URL_X86="https://github.com/vernemq/vernemq/releases/download/2.0.1/vernemq-2.0.1.jammy.x86_64.deb"
URL_ARM="https://github.com/vernemq/vernemq/releases/download/2.0.1/vernemq-2.0.1.jammy.arm64.deb"

# Descarga y instala según la arquitectura detectada
if [ "$ARCH" == "x86_64" ]; then
    wget $URL_X86 -O vernemq.deb
    sudo dpkg -i vernemq.deb
elif [ "$ARCH" == "aarch64" ]; then
    wget $URL_ARM -O vernemq.deb
    sudo dpkg -i vernemq.deb
else
    echo "Arquitectura no soportada: $ARCH"
    exit 1
fi

# Limpia el archivo descargado
rm -f vernemq.deb

# Configurar el archivo de configuración de vernemq
echo "Configurando VerneMQ."
cat <<EOL | sudo tee -a /etc/vernemq/vernemq.conf
accept_eula = yes
max_inflight_messages = 200
max_online_messages = 100000
max_offline_messages = 100000
listener.tcp.name = 0.0.0.0:1883
listener.tcp.default = 0.0.0.0:1884
listener.ws.default = 0.0.0.0:8083
allow_anonymous = on

EOL



# Reiniciar el servicio Mosquitto
echo "Reiniciando el servicio vernemq."
sudo systemctl restart vernemq
sudo systemctl enable vernemq
