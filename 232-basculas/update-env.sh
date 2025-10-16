#!/bin/bash
# Script para actualizar .env con las nuevas variables de tara y cero

ENV_FILE="/var/www/html/232-basculas/.env"

echo "Actualizando $ENV_FILE con nuevas variables..."

# Verificar si el archivo .env existe
if [ ! -f "$ENV_FILE" ]; then
    echo "❌ Error: No existe el archivo .env"
    echo "Creando .env desde env.template..."
    cp /var/www/html/232-basculas/env.template "$ENV_FILE"
    echo "✓ Archivo .env creado"
    exit 0
fi

# Añadir variables si no existen
if ! grep -q "MQTT_TOPIC_TARA" "$ENV_FILE"; then
    echo "" >> "$ENV_FILE"
    echo "# Topic para recibir comando de TARA" >> "$ENV_FILE"
    echo "# Enviar: {\"value\": true} para hacer tara" >> "$ENV_FILE"
    echo "MQTT_TOPIC_TARA=sensorica/bascula/tara" >> "$ENV_FILE"
    echo "✓ Añadida variable MQTT_TOPIC_TARA"
fi

if ! grep -q "MQTT_TOPIC_ZERO" "$ENV_FILE"; then
    echo "" >> "$ENV_FILE"
    echo "# Topic para recibir comando de CERO" >> "$ENV_FILE"
    echo "# Enviar: {\"value\": true} para hacer cero" >> "$ENV_FILE"
    echo "MQTT_TOPIC_ZERO=sensorica/bascula/zero" >> "$ENV_FILE"
    echo "✓ Añadida variable MQTT_TOPIC_ZERO"
fi

# Asegurar que RS232_SCALE está configurado
if ! grep -q "RS232_SCALE" "$ENV_FILE"; then
    echo "" >> "$ENV_FILE"
    echo "# Factor de escala (divisor) para el peso" >> "$ENV_FILE"
    echo "# 0.001 = divide entre 1000 (1224330 → 1224.33)" >> "$ENV_FILE"
    echo "RS232_SCALE=0.001" >> "$ENV_FILE"
    echo "✓ Añadida variable RS232_SCALE"
fi

# Asegurar que RS232_DECIMALS está configurado
if ! grep -q "RS232_DECIMALS" "$ENV_FILE"; then
    echo "# Decimales en publicación MQTT (0-8 ó 'auto')" >> "$ENV_FILE"
    echo "RS232_DECIMALS=auto" >> "$ENV_FILE"
    echo "✓ Añadida variable RS232_DECIMALS"
fi

# Asegurar que RS232_ZERO_THRESHOLD está configurado
if ! grep -q "RS232_ZERO_THRESHOLD" "$ENV_FILE"; then
    echo "# Umbral de cero (convierte -0.005 a 0.005 → 0)" >> "$ENV_FILE"
    echo "RS232_ZERO_THRESHOLD=0.01" >> "$ENV_FILE"
    echo "✓ Añadida variable RS232_ZERO_THRESHOLD"
fi

# Asegurar que RS232_PUBLISH_UNCHANGED está configurado
if ! grep -q "RS232_PUBLISH_UNCHANGED" "$ENV_FILE"; then
    echo "# No publicar si el valor no cambió (reduce tráfico MQTT)" >> "$ENV_FILE"
    echo "RS232_PUBLISH_UNCHANGED=false" >> "$ENV_FILE"
    echo "✓ Añadida variable RS232_PUBLISH_UNCHANGED"
fi

# Asegurar que RS232_OFFSET está configurado
if ! grep -q "RS232_OFFSET" "$ENV_FILE"; then
    echo "" >> "$ENV_FILE"
    echo "# Offset (calibración a 0) - Valor a restar del peso crudo" >> "$ENV_FILE"
    echo "# Si sin carga lee 760000, pon RS232_OFFSET=760000" >> "$ENV_FILE"
    echo "RS232_OFFSET=0" >> "$ENV_FILE"
    echo "✓ Añadida variable RS232_OFFSET"
fi

# Asegurar que RS232_TARA está configurado
if ! grep -q "RS232_TARA" "$ENV_FILE"; then
    echo "# Tara (peso de contenedor en kg) - Se resta después de escalar" >> "$ENV_FILE"
    echo "RS232_TARA=0.0" >> "$ENV_FILE"
    echo "✓ Añadida variable RS232_TARA"
fi

# Asegurar que RS232_AUTO_OFFSET está configurado
if ! grep -q "RS232_AUTO_OFFSET" "$ENV_FILE"; then
    echo "" >> "$ENV_FILE"
    echo "# Auto-calibración de offset al arrancar" >> "$ENV_FILE"
    echo "RS232_AUTO_OFFSET=true" >> "$ENV_FILE"
    echo "✓ Añadida variable RS232_AUTO_OFFSET"
fi

# Asegurar que RS232_MIN_OFFSET está configurado
if ! grep -q "RS232_MIN_OFFSET" "$ENV_FILE"; then
    echo "# Offset mínimo para activar auto-calibración" >> "$ENV_FILE"
    echo "RS232_MIN_OFFSET=100000" >> "$ENV_FILE"
    echo "✓ Añadida variable RS232_MIN_OFFSET"
fi

# Asegurar que RS232_AUTO_CORRECT_ENABLED está configurado
if ! grep -q "RS232_AUTO_CORRECT_ENABLED" "$ENV_FILE"; then
    echo "" >> "$ENV_FILE"
    echo "# Auto-corrección continua (false por defecto para evitar saltos)" >> "$ENV_FILE"
    echo "RS232_AUTO_CORRECT_ENABLED=false" >> "$ENV_FILE"
    echo "✓ Añadida variable RS232_AUTO_CORRECT_ENABLED"
fi

# Asegurar que RS232_AUTO_CORRECT_THRESHOLD está configurado
if ! grep -q "RS232_AUTO_CORRECT_THRESHOLD" "$ENV_FILE"; then
    echo "# Lecturas negativas consecutivas antes de corregir offset (50 = ~15s a 300ms)" >> "$ENV_FILE"
    echo "RS232_AUTO_CORRECT_THRESHOLD=50" >> "$ENV_FILE"
    echo "✓ Añadida variable RS232_AUTO_CORRECT_THRESHOLD"
fi

# Asegurar que RS232_LOW_WEIGHT_SAMPLES está configurado
if ! grep -q "RS232_LOW_WEIGHT_SAMPLES" "$ENV_FILE"; then
    echo "# Muestras de peso bajo antes de ajustar (100 = ~30s a 300ms)" >> "$ENV_FILE"
    echo "RS232_LOW_WEIGHT_SAMPLES=100" >> "$ENV_FILE"
    echo "✓ Añadida variable RS232_LOW_WEIGHT_SAMPLES"
fi

# Asegurar que RS232_MAX_OFFSET_CHANGE está configurado
if ! grep -q "RS232_MAX_OFFSET_CHANGE" "$ENV_FILE"; then
    echo "# Cambio máximo de offset por corrección (evita saltos grandes)" >> "$ENV_FILE"
    echo "RS232_MAX_OFFSET_CHANGE=50000" >> "$ENV_FILE"
    echo "✓ Añadida variable RS232_MAX_OFFSET_CHANGE"
fi

# Asegurar que RS232_PERSIST_OFFSET está configurado
if ! grep -q "RS232_PERSIST_OFFSET" "$ENV_FILE"; then
    echo "" >> "$ENV_FILE"
    echo "# Guardar automáticamente el offset calculado en .env" >> "$ENV_FILE"
    echo "RS232_PERSIST_OFFSET=true" >> "$ENV_FILE"
    echo "✓ Añadida variable RS232_PERSIST_OFFSET"
fi

# Asegurar que RS232_AUTO_TARA_ENABLED está configurado
if ! grep -q "RS232_AUTO_TARA_ENABLED" "$ENV_FILE"; then
    echo "" >> "$ENV_FILE"
    echo "# Auto-tara inteligente (false por defecto)" >> "$ENV_FILE"
    echo "RS232_AUTO_TARA_ENABLED=false" >> "$ENV_FILE"
    echo "✓ Añadida variable RS232_AUTO_TARA_ENABLED"
fi

# Asegurar que RS232_AUTO_OFFSET_MAX está configurado
if ! grep -q "RS232_AUTO_OFFSET_MAX" "$ENV_FILE"; then
    echo "# Máximo para ajuste de offset (kg) - Rango 1" >> "$ENV_FILE"
    echo "RS232_AUTO_OFFSET_MAX=0.05" >> "$ENV_FILE"
    echo "✓ Añadida variable RS232_AUTO_OFFSET_MAX"
fi

# Asegurar que RS232_AUTO_TARA_MIN está configurado
if ! grep -q "RS232_AUTO_TARA_MIN" "$ENV_FILE"; then
    echo "# Mínimo para auto-tara (kg) - Rango 2" >> "$ENV_FILE"
    echo "RS232_AUTO_TARA_MIN=0.05" >> "$ENV_FILE"
    echo "✓ Añadida variable RS232_AUTO_TARA_MIN"
fi

# Asegurar que RS232_AUTO_TARA_MAX está configurado
if ! grep -q "RS232_AUTO_TARA_MAX" "$ENV_FILE"; then
    echo "# Máximo para auto-tara (kg) - Rango 2" >> "$ENV_FILE"
    echo "RS232_AUTO_TARA_MAX=0.4" >> "$ENV_FILE"
    echo "✓ Añadida variable RS232_AUTO_TARA_MAX"
fi

# Asegurar que RS232_AUTO_TARA_TIME está configurado
if ! grep -q "RS232_AUTO_TARA_TIME" "$ENV_FILE"; then
    echo "# Tiempo de estabilidad para auto-tara (segundos)" >> "$ENV_FILE"
    echo "RS232_AUTO_TARA_TIME=30" >> "$ENV_FILE"
    echo "✓ Añadida variable RS232_AUTO_TARA_TIME"
fi

# Asegurar que RS232_APPEND_CR y RS232_APPEND_LF están configurados
if ! grep -q "RS232_APPEND_CR" "$ENV_FILE"; then
    echo "" >> "$ENV_FILE"
    echo "# Añadir terminadores al comando (detectado: CR+LF funciona)" >> "$ENV_FILE"
    echo "RS232_APPEND_CR=true" >> "$ENV_FILE"
    echo "✓ Añadida variable RS232_APPEND_CR"
fi

if ! grep -q "RS232_APPEND_LF" "$ENV_FILE"; then
    echo "RS232_APPEND_LF=true" >> "$ENV_FILE"
    echo "✓ Añadida variable RS232_APPEND_LF"
fi

# Verificar RS232_COMMAND
if ! grep -q "RS232_COMMAND" "$ENV_FILE"; then
    echo "" >> "$ENV_FILE"
    echo "# Comando para pedir peso (A funciona con CR+LF)" >> "$ENV_FILE"
    echo "RS232_COMMAND=A" >> "$ENV_FILE"
    echo "✓ Añadida variable RS232_COMMAND"
fi

# Asegurar que RS232_ADDRESSES está configurado
if ! grep -q "RS232_ADDRESSES" "$ENV_FILE"; then
    echo "" >> "$ENV_FILE"
    echo "# Direcciones de básculas a monitorizar (separadas por comas)" >> "$ENV_FILE"
    echo "RS232_ADDRESSES=1" >> "$ENV_FILE"
    echo "✓ Añadida variable RS232_ADDRESSES"
fi

# Asegurar que RS232_ADDRESS_PREFIX está configurado
if ! grep -q "RS232_ADDRESS_PREFIX" "$ENV_FILE"; then
    echo "# Prefijo del comando de dirección (opcional, vacío por defecto)" >> "$ENV_FILE"
    echo "RS232_ADDRESS_PREFIX=" >> "$ENV_FILE"
    echo "✓ Añadida variable RS232_ADDRESS_PREFIX"
fi

echo ""
echo "✅ Archivo .env actualizado correctamente"
echo ""
echo "Contenido actual de las variables principales:"
grep -E "MQTT_TOPIC_TARA|MQTT_TOPIC_ZERO|RS232_SCALE|RS232_OFFSET|RS232_AUTO|RS232_LOW|RS232_APPEND|RS232_PERSIST_OFFSET|RS232_ADDRESSES" "$ENV_FILE" | grep -v "^#"
