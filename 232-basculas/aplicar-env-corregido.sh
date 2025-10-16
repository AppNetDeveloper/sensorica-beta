#!/bin/bash

# Script para aplicar el .env corregido

cd "$(dirname "$0")"

echo "🔧 Aplicando correcciones al archivo .env..."
echo ""

# Hacer backup del .env actual
if [ -f .env ]; then
    cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
    echo "✓ Backup creado: .env.backup.$(date +%Y%m%d_%H%M%S)"
fi

# Copiar el archivo corregido
if [ -f .env.corregido ]; then
    cp .env.corregido .env
    echo "✓ Archivo .env actualizado"
    echo ""
    echo "✅ Cambios aplicados correctamente"
    echo ""
    echo "Principales cambios realizados:"
    echo "  1. ✓ MODBUS_ENABLED=false (desactivado)"
    echo "  2. ✓ Añadidas variables por dirección (RS232_OFFSET_1, RS232_TARA_1, RS232_SCALE_1)"
    echo "  3. ✓ Eliminada variable duplicada RS232_AUTO_TARA_THRESHOLD"
    echo "  4. ✓ Archivo reorganizado y comentado"
    echo ""
    echo "📝 Configuración actual:"
    echo "   - Dirección: 1"
    echo "   - Offset: 760695"
    echo "   - Tara: 0.52 kg"
    echo "   - Escala: 0.00001"
    echo "   - Decimales: 2"
    echo ""
    echo "🚀 Para ejecutar el servicio:"
    echo "   node index.js"
else
    echo "❌ Error: No se encontró el archivo .env.corregido"
    exit 1
fi
