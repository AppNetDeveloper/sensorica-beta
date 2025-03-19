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
npm install express qrcode axios dotenv
npm install swagger-ui-express swagger-jsdoc --save
npm install cors
npm install mqtt mysql2
npm install @whiskeysockets/baileys express node-cache pino axios @hapi/boom dotenv qrcode
npm install swagger-ui-express swagger-jsdoc
npm install swagger-ui-express swagger-jsdoc --save
npm install multer
npm install https-agent
npm install express-fileupload
npm install @hapi/boom
npm install dotenv
npm install qrcode
npm install axios
npm install node-cache
npm install pino
npm install express
npm install node-cron
npm install express cors body-parser telegram dotenv sqlite3
npm install axios telegraf
npm install express telegram qrcode axios dotenv
npm install swagger-ui-express swagger-jsdoc --save
npm install cors
npm install mqtt mysql2 axios


npm update
npm install

echo "Instalación completada."
