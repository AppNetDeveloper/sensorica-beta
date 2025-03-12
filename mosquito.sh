#!/bin/bash



echo "============================================="
echo "Desinstalando VerneMQ y Mosquitto (si están instalados)..."
echo "============================================="

# Detener y deshabilitar VerneMQ, si existe
sudo systemctl stop vernemq 2>/dev/null || true
sudo systemctl disable vernemq 2>/dev/null || true
sudo apt-get -y purge vernemq 2>/dev/null || true
sudo rm -rf /etc/vernemq /var/log/vernemq /var/lib/vernemq

# Detener y deshabilitar Mosquitto, si existe
sudo systemctl stop mosquitto 2>/dev/null || true
sudo systemctl disable mosquitto 2>/dev/null || true
sudo apt-get -y purge mosquitto mosquitto-clients 2>/dev/null || true
sudo rm -rf /etc/mosquitto /var/lib/mosquitto /var/log/mosquitto

echo "============================================="
echo "Instalando Mosquitto y Mosquitto-Clients..."
echo "============================================="

sudo apt-get update
sudo apt-get install -y mosquitto mosquitto-clients

echo "============================================="
echo "Configurando Mosquitto..."
echo "============================================="

# Crear directorio de configuración (si no existe) para Mosquitto
if [ ! -d "/etc/mosquitto" ]; then
    sudo mkdir -p /etc/mosquitto
fi

# Crear directorio para certificados si no existe
sudo mkdir -p /etc/mosquitto/certs

# Generar certificado autofirmado para WSS si no existe
if [ ! -f /etc/mosquitto/certs/mosquitto.crt ]; then
    echo "Generando certificado autofirmado para WSS..."
    sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
      -keyout /etc/mosquitto/certs/mosquitto.key \
      -out /etc/mosquitto/certs/mosquitto.crt \
      -subj "/CN=localhost"
fi

# Crear archivo de configuración de Mosquitto
sudo bash -c 'cat > /etc/mosquitto/mosquitto.conf <<EOF
# Listener TCP en 0.0.0.0:1883
listener 1883 0.0.0.0
protocol mqtt
allow_anonymous true

# Segundo listener TCP en 0.0.0.0:1884
listener 1884 0.0.0.0
protocol mqtt
allow_anonymous true

# Listener WSS en 0.0.0.0:8083
listener 8083 0.0.0.0
protocol websockets
certfile /etc/mosquitto/certs/mosquitto.crt
keyfile /etc/mosquitto/certs/mosquitto.key
allow_anonymous true

# Opcional: Configuración adicional
persistence true
persistence_location /var/lib/mosquitto/
log_dest syslog
log_dest stdout
log_type error
log_type warning
log_type notice
log_type information
connection_messages true
log_timestamp true
EOF'

echo "============================================="
echo "Reiniciando y habilitando el servicio Mosquitto..."
echo "============================================="

sudo chmod 644 /etc/mosquitto/certs/mosquitto.crt
sudo chmod 600 /etc/mosquitto/certs/mosquitto.key
sudo chown mosquitto:mosquitto /etc/mosquitto/certs/mosquitto.*

sudo systemctl daemon-reload
sudo systemctl restart mosquitto
sudo systemctl enable mosquitto


echo "============================================="
echo "Mosquitto instalado y configurado correctamente."
echo "============================================="
