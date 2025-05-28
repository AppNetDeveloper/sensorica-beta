const express = require('express');
const http = require('http');
const { Server } = require("socket.io");
const mqtt = require('mqtt');
const path = require('path');

// Cargar variables de entorno desde ../.env (relativo a la ubicación de este script)
require('dotenv').config({ path: path.resolve(__dirname, '../.env') });

const app = express();
const server = http.createServer(app);

// IMPORTANTE: Configura esta variable con el origen de tu aplicación Laravel
// Ejemplo: 'http://localhost:8000' si usas 'php artisan serve'
// Ejemplo: 'http://tu-dominio-laravel.test' si usas un virtual host
const LARAVEL_APP_ORIGIN = process.env.LARAVEL_APP_URL || "http://0.0.0.0:8000"; // Puedes definir LARAVEL_APP_URL en tu .env o cambiarlo aquí directamente

const io = new Server(server, {
    cors: {
        origin: LARAVEL_APP_ORIGIN,
        methods: ["GET", "POST"],
        credentials: true
    }
});

const MQTT_HOST = process.env.MQTT_SENSORICA_SERVER;
const MQTT_PORT = process.env.MQTT_SENSORICA_PORT;
const MQTT_BROKER_URL = `mqtt://${MQTT_HOST}:${MQTT_PORT}`;
const MQTT_COMMAND_TOPIC = 'rfid_command'; // Topic para comandos y respuestas

if (!MQTT_HOST || !MQTT_PORT) {
    console.error("Error: MQTT_SENSORICA_SERVER o MQTT_SENSORICA_PORT no están definidos en el archivo .env");
    process.exit(1); // Salir si no hay configuración MQTT
}

console.log(`Intentando conectar al Broker MQTT: ${MQTT_BROKER_URL}`);
const mqttClient = mqtt.connect(MQTT_BROKER_URL, {
    connectTimeout: 10000, // Tiempo de espera para la conexión en ms
    reconnectPeriod: 5000, // Intervalo entre intentos de reconexión en ms
});

mqttClient.on('connect', () => {
    console.log('Conectado exitosamente al Broker MQTT.');
    mqttClient.subscribe(MQTT_COMMAND_TOPIC, (err) => {
        if (!err) {
            console.log(`Suscrito al topic MQTT: ${MQTT_COMMAND_TOPIC}`);
        } else {
            console.error('Error al suscribir al topic MQTT:', err);
            // Considerar emitir un error a los clientes conectados si la suscripción falla
            io.emit('appError', { message: 'Error crítico: No se pudo suscribir al topic MQTT.' });
        }
    });
});

mqttClient.on('error', (err) => {
    console.error('Error de conexión MQTT:', err);
    io.emit('appError', { message: `Error de conexión MQTT: ${err.message}` });
});

mqttClient.on('reconnect', () => {
    console.log('Reconectando al Broker MQTT...');
    io.emit('appStatus', { message: 'Reconectando al Broker MQTT...' });
});

mqttClient.on('offline', () => {
    console.log('Cliente MQTT desconectado (offline).');
    io.emit('appError', { message: 'Desconectado del Broker MQTT.' });
});

mqttClient.on('close', () => {
    console.log('Conexión MQTT cerrada.');
     io.emit('appError', { message: 'Conexión MQTT cerrada inesperadamente.' });
});

// Manejador de mensajes MQTT
mqttClient.on('message', (topic, message) => {
    console.log(`Mensaje MQTT recibido - Topic: ${topic}, Mensaje: ${message.toString()}`);
    let parsedMessage;
    try {
        parsedMessage = JSON.parse(message.toString());
    } catch (e) {
        console.error('Error parseando mensaje JSON de MQTT:', e);
        io.emit('appError', { message: 'Error procesando respuesta del dispositivo (JSON inválido).', details: message.toString() });
        return;
    }

    if (parsedMessage.resultMsg) {
        if (parsedMessage.resultMsg.includes("RFID/getPower:get power success")) {
            console.log("Respuesta de getPower recibida, emitiendo 'antennaPowerData'");
            io.emit('antennaPowerData', parsedMessage);
        } else if (parsedMessage.resultMsg.includes("RFID/setPower:set power success")) {
            console.log("Respuesta de setPower recibida, emitiendo 'antennaSetPowerStatus'");
            io.emit('antennaSetPowerStatus', { success: true, data: parsedMessage });
        } else if (parsedMessage.resultCode !== 0) { // Error específico del dispositivo RFID
            console.log("Respuesta con error desde RFID, emitiendo 'rfidError'");
            io.emit('rfidError', parsedMessage);
        } else {
            // Otro tipo de mensaje exitoso que no es getPower ni setPower
             console.log("Mensaje RFID no reconocido específicamente pero con resultCode 0:", parsedMessage);
             io.emit('rfidInfo', parsedMessage); // Para depuración o manejo genérico
        }
    } else {
        console.warn("Mensaje MQTT recibido sin 'resultMsg':", parsedMessage);
        io.emit('appWarning', { message: "Respuesta del dispositivo sin 'resultMsg'.", details: parsedMessage });
    }
});

// Manejador de conexiones Socket.IO
io.on('connection', (socket) => {
    console.log(`Cliente web conectado: ${socket.id}`);
    socket.emit('appStatus', { message: 'Conectado al servidor Node.js. Esperando conexión MQTT...' });
    if (mqttClient.connected) {
        socket.emit('appStatus', { message: 'Conectado al servidor Node.js y al Broker MQTT.' });
    }


    socket.on('getAntennaPower', () => {
        console.log(`Petición 'getAntennaPower' recibida de ${socket.id}`);
        if (!mqttClient.connected) {
            socket.emit('appError', { message: 'No se puede enviar comando: Desconectado del Broker MQTT.' });
            return;
        }
        const command = { "command": "RFID/getPower" };
        mqttClient.publish(MQTT_COMMAND_TOPIC, JSON.stringify(command), { qos: 1 }, (err) => {
            if (err) {
                console.error('Error publicando comando getPower MQTT:', err);
                socket.emit('appError', { message: 'Error enviando comando Get Power al broker.' });
            } else {
                console.log("Comando getPower publicado a MQTT.");
                socket.emit('appStatus', { message: 'Comando Get Power enviado.' });
            }
        });
    });

    socket.on('setAntennaPower', (antennaConfig) => {
        console.log(`Petición 'setAntennaPower' recibida de ${socket.id}:`, antennaConfig);

        if (!mqttClient.connected) {
            socket.emit('appError', { message: 'No se puede enviar comando: Desconectado del Broker MQTT.' });
            return;
        }

        if (typeof antennaConfig.index === 'undefined' ||
            typeof antennaConfig.power === 'undefined' ||
            typeof antennaConfig.enable === 'undefined') {
            console.warn("Datos incompletos para Set Power:", antennaConfig);
            socket.emit('appError', { message: 'Datos incompletos para Set Power. Se requiere: index, power, enable.' });
            return;
        }

        const command = {
            "command": "RFID/setPower",
            "data": [
                {
                    "index": parseInt(antennaConfig.index),
                    "power": parseInt(antennaConfig.power),
                    "enable": antennaConfig.enable === true || String(antennaConfig.enable).toLowerCase() === 'true'
                }
            ]
        };
        mqttClient.publish(MQTT_COMMAND_TOPIC, JSON.stringify(command), { qos: 1 }, (err) => {
            if (err) {
                console.error('Error publicando comando setPower MQTT:', err);
                socket.emit('appError', { message: 'Error enviando comando Set Power al broker.' });
            } else {
                console.log("Comando setPower publicado a MQTT.");
                socket.emit('appStatus', { message: `Comando Set Power para antena ${antennaConfig.index} enviado.` });
            }
        });
    });

    socket.on('disconnect', (reason) => {
        console.log(`Cliente web desconectado: ${socket.id}. Razón: ${reason}`);
    });
});

// Este script ya no sirve el index.html. Laravel lo hará.

const PORT = process.env.NODE_PORT || 3000; // Puedes configurar NODE_PORT en .env
server.listen(PORT, () => {
    console.log(`Servidor Node.js (Socket.IO y MQTT Gateway) escuchando en http://localhost:${PORT}`);
    console.log(`Permitiendo conexiones WebSocket desde el origen: ${LARAVEL_APP_ORIGIN}`);
});
