#!/bin/bash
# Script para configurar el offset (tara de software) en .env

ENV_FILE="/var/www/html/232-basculas/.env"

if [ -z "$1" ]; then
    echo "================================================================"
    echo "CONFIGURAR OFFSET (TARA DE SOFTWARE)"
    echo "================================================================"
    echo ""
    echo "Uso: ./set-offset.sh <valor_offset>"
    echo ""
    echo "Ejemplo:"
    echo "  ./set-offset.sh 760000    # Resta 760000 del peso crudo"
    echo "  ./set-offset.sh 0         # Sin offset (valor por defecto)"
    echo ""
    echo "El offset se RESTA del peso crudo ANTES de aplicar la escala."
    echo ""
    echo "Fórmula: peso_final = (peso_crudo - OFFSET) * SCALE"
    echo ""
    echo "================================================================"
    echo "¿Cómo determinar el offset?"
    echo "================================================================"
    echo "1. Asegúrate de que NO haya carga en la báscula"
    echo "2. Ejecuta: node index.js | grep 'Línea cruda PROCESADA'"
    echo "3. Observa el valor que muestra (ej: 0761255)"
    echo "4. Ese valor es tu offset (ej: 761255)"
    echo "5. Ejecuta: ./set-offset.sh 761255"
    echo ""
    exit 1
fi

OFFSET="$1"

# Validar que sea un número
if ! [[ "$OFFSET" =~ ^[0-9]+(\.[0-9]+)?$ ]]; then
    echo "❌ Error: '$OFFSET' no es un número válido"
    exit 1
fi

if [ ! -f "$ENV_FILE" ]; then
    echo "❌ Error: No existe $ENV_FILE"
    exit 1
fi

# Actualizar o añadir RS232_OFFSET
if grep -q "^RS232_OFFSET=" "$ENV_FILE"; then
    # Reemplazar valor existente
    sed -i "s/^RS232_OFFSET=.*/RS232_OFFSET=$OFFSET/" "$ENV_FILE"
    echo "✅ RS232_OFFSET actualizado a $OFFSET en $ENV_FILE"
else
    # Añadir nueva variable
    echo "" >> "$ENV_FILE"
    echo "# Offset (tara de software)" >> "$ENV_FILE"
    echo "RS232_OFFSET=$OFFSET" >> "$ENV_FILE"
    echo "✅ RS232_OFFSET=$OFFSET añadido a $ENV_FILE"
fi

echo ""
echo "Configuración actual:"
grep -E "RS232_OFFSET|RS232_SCALE" "$ENV_FILE" | grep -v "^#"

echo ""
echo "================================================================"
echo "IMPORTANTE: Reinicia el servicio para aplicar cambios"
echo "================================================================"
echo "  pm2 restart 232-basculas"
echo "  # o bien:"
echo "  # Ctrl+C y luego: node index.js"
echo ""
