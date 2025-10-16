#!/usr/bin/env bash
set -euo pipefail

# Instalador para servicio RS232 de básculas (Node.js)
# - Sistema objetivo: Debian/Ubuntu
# - Crea/usa un proyecto Node local y añade dependencias necesarias
# - Idempotente: puedes ejecutarlo varias veces sin romper el entorno

PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$PROJECT_DIR"

need_cmd() { command -v "$1" >/dev/null 2>&1; }

# Herramientas de compilación para serialport (por si requiere build)
echo "[3/5] Instalando toolchain de compilación..."
apt install build-essential python3 make g++

# Asegurar acceso al puerto serie para el usuario actual (grupo dialout)
echo "[4/5] Comprobando pertenencia al grupo 'dialout'..."
if id -nG "$USER" | grep -qw dialout; then
  echo "Usuario ya pertenece a 'dialout'."
else
  echo "Añadiendo usuario a 'dialout' (puede requerir re-login)..."
  sudo usermod -a -G dialout "$USER" || true
fi

# Inicializar proyecto Node si no existe package.json
echo "[5/5] Preparando proyecto Node en ${PROJECT_DIR}..."
if [ ! -f package.json ]; then
  npm init -y
fi

# Dependencias de la app
# - serialport: comunicación RS232
# - mqtt: publicación/suscripción MQTT
# - modbus-serial: opcional si el protocolo sobre RS232 es Modbus RTU
# - dotenv: configuración mediante .env

echo "Instalando dependencias npm locales..."
npm install --save serialport mqtt modbus-serial dotenv

# Opcional: PM2 para ejecutar como servicio
if ! need_cmd pm2; then
  echo "Instalando PM2 global (opcional, para servicio)..."
  sudo npm install -g pm2 || true
fi

# Crear .env si no existe (desde env.template)
if [ ! -f .env ]; then
  if [ -f env.template ]; then
    echo "Creando .env desde env.template..."
    cp env.template .env
    echo "✓ Archivo .env creado"
  else
    echo "⚠ Warning: No existe env.template, creando .env básico..."
    cat > .env<<'EOF'
# Configuración básica
SERIAL_PORT=/dev/ttyUSB0
BAUD_RATE=115200
DATA_BITS=8
STOP_BITS=1
PARITY=none

RS232_COMMAND=A
RS232_APPEND_CR=true
RS232_APPEND_LF=true
RS232_SCALE=0.1
POLL_INTERVAL_MS=300

MQTT_BROKER_URL=mqtt://localhost
MQTT_TOPIC_BASE=sensorica/bascula/peso
MQTT_TOPIC_TARA=sensorica/bascula/tara
MQTT_TOPIC_ZERO=sensorica/bascula/zero

LOG_VERBOSE=true
EOF
    echo "✓ Archivo .env básico creado"
  fi
else
  echo "Archivo .env ya existe"
  # Actualizar .env con nuevas variables si existe update-env.sh
  if [ -f update-env.sh ]; then
    echo "Actualizando .env con nuevas variables..."
    chmod +x update-env.sh
    ./update-env.sh
  fi
fi

# Mensaje final
echo ""
echo "====================================="
echo "✅ INSTALACIÓN COMPLETADA"
echo "====================================="
echo ""
echo "Configuración:"
echo "  - Puerto: $(grep SERIAL_PORT .env | cut -d= -f2 || echo '/dev/ttyUSB0')"
echo "  - Baudios: $(grep BAUD_RATE .env | cut -d= -f2 || echo '115200')"
echo "  - Factor escala: $(grep RS232_SCALE .env | cut -d= -f2 || echo '0.1')"
echo ""
echo "Topics MQTT:"
echo "  📤 Peso: sensorica/bascula/peso/smart_utilcell"
echo "  📥 Tara: sensorica/bascula/tara/smart_utilcell"
echo "  📥 Cero: sensorica/bascula/zero/smart_utilcell"
echo ""
echo "Para gestionar como servicio:"
echo "  pm2 start index.js --name 232-basculas"
echo "  pm2 save"
echo "  pm2 logs 232-basculas"
echo ""
echo "====================================="
echo "Iniciando servicio..."
echo "====================================="
echo ""

# Ejecutar el servicio
if [ -f index.js ]; then
  node index.js
else
  echo "❌ Error: No se encuentra index.js"
  exit 1
fi
