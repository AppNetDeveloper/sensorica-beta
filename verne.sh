#!/bin/bash

echo "Desinstalando Mosquitto..."
systemctl stop vernemq
systemctl disable vernemq
apt-get -y purge vernemq

apt purge -y vernemq mosquitto-clients
rm -rf /etc/vernemq /var/log/vernemq /var/lib/vernemq
apt-get -y purge vernemq
rm -rf /etc/vernemq /var/lib/vernemq /var/log/vernemq

echo "Instalando Mosquitto y el cliente..."
apt install -y mosquitto-clients
apt-get -y install erlang 
apt -y install libsnappy1v5

ARCH=$(uname -m)

URL_X86="https://github.com/vernemq/vernemq/releases/download/2.0.1/vernemq-2.0.1.jammy.x86_64.deb"
URL_ARM="https://github.com/vernemq/vernemq/releases/download/2.0.1/vernemq-2.0.1.jammy.arm64.deb"

if [ "$ARCH" = "x86_64" ]; then
    wget $URL_X86 -O vernemq.deb
    dpkg -i vernemq.deb
elif [ "$ARCH" = "aarch64" ]; then
    wget $URL_ARM -O vernemq.deb
    dpkg -i vernemq.deb
else
    echo "Arquitectura no soportada: $ARCH"
    exit 1
fi

rm -f vernemq.deb

echo "Configurando VerneMQ."
cat <<EOL > /etc/vernemq/vernemq.conf
accept_eula = yes
max_inflight_messages = 200
max_online_messages = 100000
max_offline_messages = 100000
listener.tcp.name = 0.0.0.0:1883
listener.tcp.default = 0.0.0.0:1884
listener.ws.default = 0.0.0.0:8083
allow_anonymous = on
EOL

echo "Reiniciando el servicio VerneMQ."
systemctl restart vernemq
systemctl enable vernemq
