#!/bin/bash

# Script para probar el comportamiento sin .env

echo "🧪 Probando servicio sin archivo .env..."
echo ""

# Hacer backup del .env si existe
if [ -f .env ]; then
    mv .env .env.test.backup
    echo "✓ .env respaldado temporalmente"
fi

echo ""
echo "Ejecutando servicio (presiona Ctrl+C para detener):"
echo ""

# Ejecutar el servicio
timeout 5 node index.js

# Restaurar .env
if [ -f .env.test.backup ]; then
    mv .env.test.backup .env
    echo ""
    echo "✓ .env restaurado"
fi

echo ""
echo "✅ Prueba completada"
