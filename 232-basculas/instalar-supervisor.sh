#!/bin/bash

# Script para instalar el servicio de básculas RS232 en Supervisor

echo "🔧 Instalando servicio 232-basculas-rs232 en Supervisor..."
echo ""

# Verificar si el archivo .conf existe
if [ ! -f "../232-basculas-rs232.conf" ]; then
    echo "❌ Error: No se encontró el archivo 232-basculas-rs232.conf"
    exit 1
fi

# Copiar el archivo de configuración a Supervisor
echo "📋 Copiando configuración a /etc/supervisor/conf.d/..."
sudo cp ../232-basculas-rs232.conf /etc/supervisor/conf.d/

# Verificar que se copió correctamente
if [ ! -f "/etc/supervisor/conf.d/232-basculas-rs232.conf" ]; then
    echo "❌ Error: No se pudo copiar el archivo de configuración"
    exit 1
fi

echo "✓ Configuración copiada"

# Recargar la configuración de Supervisor
echo ""
echo "🔄 Recargando configuración de Supervisor..."
sudo supervisorctl reread
sudo supervisorctl update

# Verificar el estado del servicio
echo ""
echo "📊 Estado del servicio:"
sudo supervisorctl status 232-basculas-rs232

echo ""
echo "✅ Instalación completada"
echo ""
echo "Comandos útiles:"
echo "  sudo supervisorctl start 232-basculas-rs232      # Iniciar servicio"
echo "  sudo supervisorctl stop 232-basculas-rs232       # Detener servicio"
echo "  sudo supervisorctl restart 232-basculas-rs232    # Reiniciar servicio"
echo "  sudo supervisorctl status 232-basculas-rs232     # Ver estado"
echo "  sudo tail -f /var/www/html/storage/logs/232-basculas-rs232.log  # Ver logs"
echo ""
