#!/bin/bash

# Script para instalar dependencias y configuraciones necesarias

# Verificar si npm está instalado
if ! command -v npm &> /dev/null
then
    echo "npm no está instalado. Instalando npm..."
    sudo apt update
    sudo apt install -y npm
else
    echo "npm ya está instalado."
fi

# Instalar dependencias de npm
echo "Instalando dependencias de npm..."
npm install

# Verificar si node está instalado
if ! command -v node &> /dev/null
then
    echo "Node.js no está instalado. Instalando Node.js..."
    sudo apt install -y nodejs
else
    echo "Node.js ya está instalado."
fi
npm init -y
npm install express cors body-parser telegram dotenv sqlite3
npm install axios telegraf
npm install express telegram qrcode axios dotenv
npm install swagger-ui-express swagger-jsdoc --save
npm install cors

# Otros requisitos adicionales
echo "Verificando si hay otros requisitos..."

# Por ejemplo, si necesitas instalar Python o alguna otra herramienta
# Descomenta las siguientes líneas si necesitas instalar algo más

# echo "Instalando Python..."
# sudo apt install -y python3

# echo "Instalando otras dependencias..."
# sudo apt install -y <paquete>

echo "Instalación completada con éxito."

# Fin del script
