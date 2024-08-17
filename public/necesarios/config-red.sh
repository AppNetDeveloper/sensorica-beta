#!/bin/bash

# Definir el archivo de configuración de netplan
NETPLAN_CONFIG="/etc/netplan/01-netcfg.yaml"

# Crear y escribir la configuración en el archivo de netplan
echo "Creando archivo de configuración de Netplan..."
sudo bash -c "cat > $NETPLAN_CONFIG <<EOL
network:
  version: 2
  renderer: networkd
  ethernets:
    enp1s0:
      dhcp4: no
      addresses:
        - 192.168.55.10/24
      routes:
        - to: default
          via: 192.168.55.1
      nameservers:
        addresses:
          - 8.8.8.8
          - 8.8.4.4
EOL"

# Ajustar permisos del archivo de configuración de Netplan
echo "Ajustando permisos del archivo de configuración..."
sudo chmod 600 /etc/netplan/01-netcfg.yaml
sudo chmod 600 /etc/netplan/01-network-manager-all.yaml

# Aplicar la configuración de Netplan
echo "Aplicando configuración de Netplan..."
sudo netplan apply

# Subir la interfaz enp1s0
echo "Subiendo la interfaz enp1s0..."
sudo ip link set enp1s0 up

# Mostrar el estado de la interfaz enp1s0
echo "Estado de la interfaz enp1s0:"
ip addr show enp1s0

# Crear un archivo de servicio systemd para asegurar que enp1s0 esté UP después de un reinicio
SYSTEMD_SERVICE="/etc/systemd/system/enp1s0-up.service"

echo "Creando archivo de servicio systemd para enp1s0..."
sudo bash -c "cat > $SYSTEMD_SERVICE <<EOL
[Unit]
Description=Ensure enp1s0 is up
After=network.target

[Service]
Type=oneshot
ExecStart=/sbin/ip link set enp1s0 up

[Install]
WantedBy=multi-user.target
EOL"

# Recargar la configuración de systemd
echo "Recargando configuración de systemd..."
sudo systemctl daemon-reload

# Habilitar el servicio para que se ejecute al iniciar el sistema
echo "Habilitando servicio enp1s0-up.service..."
sudo systemctl enable enp1s0-up.service

# Iniciar el servicio ahora para probarlo
echo "Iniciando servicio enp1s0-up.service..."
sudo systemctl start enp1s0-up.service

# Mostrar el estado del firewall
echo "Estado actual del firewall (UFW):"
sudo ufw status

# Permitir tráfico desde la subred 192.168.55.0/24
echo "Permitiendo tráfico desde la subred 192.168.55.0/24..."
sudo ufw allow from 192.168.55.0/24

echo "Configuración completada."
