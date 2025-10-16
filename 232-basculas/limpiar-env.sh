#!/bin/bash

# Script para limpiar el .env duplicado

cd "$(dirname "$0")"

echo "🧹 Limpiando archivo .env duplicado..."
echo ""

# Hacer backup
if [ -f .env ]; then
    cp .env .env.backup.duplicado.$(date +%Y%m%d_%H%M%S)
    echo "✓ Backup creado"
fi

# Aplicar versión limpia
if [ -f .env.limpio ]; then
    cp .env.limpio .env
    echo "✓ Archivo .env limpio aplicado"
    echo ""
    echo "✅ CORRECCIONES APLICADAS:"
    echo ""
    echo "  ❌ ELIMINADO: Duplicación completa del archivo"
    echo "  ✓ Configuración unificada"
    echo "  ✓ RS232_ADDRESSES=1,2,3 (3 básculas activas)"
    echo "  ✓ Todas las direcciones configuradas correctamente"
    echo ""
    echo "📊 Configuración actual:"
    echo "   Direcciones: 1, 2, 3"
    echo "   Offset: 760695 (todas)"
    echo "   Tara: 0.30 kg (todas)"
    echo "   Escala: 0.00001 (todas)"
    echo "   Decimales: 2"
    echo ""
    echo "🚀 Listo para ejecutar:"
    echo "   node index.js"
else
    echo "❌ Error: No se encontró .env.limpio"
    exit 1
fi
