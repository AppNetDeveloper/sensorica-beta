// confi-rfid.js
// Ubicado en la carpeta 'node/' de tu proyecto.
// El archivo .env de Laravel debe estar en la raíz del proyecto Laravel (../.env)

const express = require('express');
const http = require('http');
const { Server } = require("socket.io");
const mqtt = require('mqtt');
const path = require('path');
const axios = require('axios'); // Para hacer peticiones HTTP al API del lector

// Cargar variables de entorno desde ../.env
require('dotenv').config({ path: path.resolve(__dirname, '../.env') });

const app = express();
const server = http.createServer(app);

// Configuración de Socket.IO.
const io = new Server(server); // No se necesita CORS explícito si el HTML se sirve desde el mismo origen.

// Configuración MQTT (para la conexión de este servidor Node.js)
const MQTT_HOST = process.env.MQTT_SENSORICA_SERVER;
const MQTT_PORT = process.env.MQTT_SENSORICA_PORT;
const MQTT_BROKER_URL = `mqtt://${MQTT_HOST}:${MQTT_PORT}`;
const MQTT_COMMAND_TOPIC = 'rfid_command'; // Topic para los comandos RFID que este Node.js envía

// Variables para el API HTTP del Lector RFID
const READER_API_IP = process.env.READER_API_IP || '192.168.123.15'; // IP del API HTTP de tu lector (puedes ponerla en .env)
const READER_API_PORT = process.env.READER_API_PORT || 1080;      // Puerto del API HTTP de tu lector (puedes ponerla en .env)
const READER_API_BASE_URL = `http://${READER_API_IP}:${READER_API_PORT}/API/Task`;

if (!MQTT_HOST || !MQTT_PORT) {
    console.error("ERROR CRÍTICO: MQTT_SENSORICA_SERVER o MQTT_SENSORICA_PORT no están definidos para Node.js en el archivo .env.");
    process.exit(1);
}

console.log(`Intentando conectar al Broker MQTT (Node.js): ${MQTT_BROKER_URL}`);
const mqttClient = mqtt.connect(MQTT_BROKER_URL, {
    connectTimeout: 10000,
    reconnectPeriod: 5000,
    clientId: `rfid_control_panel_${Math.random().toString(16).substr(2, 8)}`
});

// --- Manejadores de eventos MQTT (para los comandos RFID) ---
mqttClient.on('connect', () => {
    console.log('Node.js: Conectado exitosamente al Broker MQTT para comandos RFID.');
    io.emit('appStatus', { message: 'Node.js: Conectado al Broker MQTT.' });
    mqttClient.subscribe(MQTT_COMMAND_TOPIC, (err) => { // Suscribirse para recibir respuestas a los comandos RFID
        if (!err) {
            console.log(`Node.js: Suscrito al topic MQTT para respuestas RFID: ${MQTT_COMMAND_TOPIC}`);
        } else {
            console.error('Node.js: Error al suscribir al topic MQTT para respuestas RFID:', err);
            io.emit('appError', { message: 'Node.js: Error crítico al suscribir para respuestas RFID.' });
        }
    });
});

mqttClient.on('error', (err) => {
    console.error('Node.js: Error de conexión MQTT (comandos RFID):', err);
    io.emit('appError', { message: `Node.js: Error de conexión MQTT: ${err.message}` });
});

mqttClient.on('reconnect', () => {
    console.log('Node.js: Reconectando al Broker MQTT (comandos RFID)...');
    io.emit('appStatus', { message: 'Node.js: Reconectando al Broker MQTT...' });
});

mqttClient.on('offline', () => {
    console.log('Node.js: Cliente MQTT desconectado (offline) (comandos RFID).');
    io.emit('appError', { message: 'Node.js: Desconectado del Broker MQTT.' });
});

mqttClient.on('close', () => {
    console.log('Node.js: Conexión MQTT cerrada (comandos RFID).');
    io.emit('appError', { message: 'Node.js: Conexión MQTT cerrada inesperadamente.' });
});

mqttClient.on('message', (topic, messageBuffer) => {
    const message = messageBuffer.toString();
    console.log(`Node.js: Mensaje MQTT recibido (comandos RFID) - Topic: ${topic}, Mensaje: ${message}`);
    let parsedMessage;
    try {
        parsedMessage = JSON.parse(message);
    } catch (e) {
        console.error('Node.js: Error parseando JSON de MQTT (comandos RFID):', e, "Msg:", message);
        io.emit('appError', { message: 'Node.js: Error procesando respuesta RFID (JSON inválido).', details: message });
        return;
    }

    if (parsedMessage.resultMsg) {
        if (parsedMessage.resultMsg.includes("RFID/getPower:get power success")) {
            io.emit('antennaPowerData', parsedMessage);
        } else if (parsedMessage.resultMsg.includes("RFID/setPower:set power success")) {
            io.emit('antennaSetPowerStatus', { success: true, data: parsedMessage });
        } else if (parsedMessage.resultMsg.includes("RFID/start:start success")) {
            io.emit('rfidInfo', parsedMessage);
        } else if (parsedMessage.resultMsg.includes("RFID/stop:stop success")) {
            io.emit('rfidInfo', parsedMessage);
        } else if (parsedMessage.resultMsg.includes("RFID/status:get rfid status success")) {
            io.emit('rfidInfo', parsedMessage);
        } else if (parsedMessage.resultCode !== 0) {
            io.emit('rfidError', parsedMessage);
        } else {
             io.emit('rfidInfo', parsedMessage); 
        }
    } else if (parsedMessage.command && (parsedMessage.command.startsWith("RFID/")) && typeof parsedMessage.resultCode === 'undefined') {
        console.log(`Node.js: Comando '${parsedMessage.command}' re-emitido, esperando respuesta real...`);
    } else {
        console.warn("Node.js: Mensaje MQTT (comandos RFID) sin 'resultMsg' y no reconocido:", parsedMessage);
        io.emit('appWarning', { message: "Node.js: Respuesta RFID no reconocida.", details: parsedMessage });
    }
});

// --- Manejadores de eventos Socket.IO ---
io.on('connection', (socket) => {
    console.log(`Cliente web conectado: ${socket.id}`);
    socket.emit('appStatus', { message: 'Conectado al servidor Node.js.' });
    if (mqttClient.connected) {
        socket.emit('appStatus', { message: 'Broker MQTT (Node.js) conectado.' });
    } else {
        socket.emit('appStatus', { message: 'Esperando conexión con Broker MQTT (Node.js)...' });
    }

    // Comandos RFID (vía MQTT que maneja este Node.js)
    socket.on('getAntennaPower', () => {
        console.log(`Petición 'getAntennaPower' de ${socket.id}`);
        if (!mqttClient.connected) {
            socket.emit('appError', { message: 'Node.js no conectado al Broker MQTT para comandos RFID.'});
            return;
        }
        const command = { "command": "RFID/getPower" };
        mqttClient.publish(MQTT_COMMAND_TOPIC, JSON.stringify(command), { qos: 1 }, (err) => {
            if (err) socket.emit('appError', { message: 'Error enviando GetPower al broker.' });
            else socket.emit('appStatus', { message: 'Comando GetPower enviado.' });
        });
    });

    socket.on('setAntennaPower', (allAntennaConfigs) => {
        console.log(`Petición 'setAntennaPower' de ${socket.id}:`, allAntennaConfigs);
        if (!mqttClient.connected) {
            socket.emit('appError', { message: 'Node.js no conectado al Broker MQTT.' });
            return;
        }
        if (!Array.isArray(allAntennaConfigs)) {
            socket.emit('appError', { message: 'Datos inválidos para SetPower: no es un array.' });
            return;
        }
        // Validación básica (puedes añadir más)
        for (const cfg of allAntennaConfigs) {
            if (typeof cfg.index === 'undefined' || typeof cfg.power === 'undefined' || typeof cfg.enable === 'undefined') {
                socket.emit('appError', { message: 'Datos incompletos en una configuración de SetPower.' });
                return;
            }
        }
        const command = { "command": "RFID/setPower", "data": allAntennaConfigs };
        mqttClient.publish(MQTT_COMMAND_TOPIC, JSON.stringify(command), { qos: 1 }, (err) => {
            if (err) socket.emit('appError', { message: 'Error enviando SetPower al broker.' });
            else socket.emit('appStatus', { message: 'Comando SetPower enviado.' });
        });
    });

    socket.on('rfidStart', (startPayload) => {
        console.log(`Petición 'rfidStart' de ${socket.id}:`, startPayload);
        if (!mqttClient.connected) {
            socket.emit('appError', { message: 'Node.js no conectado al Broker MQTT.' });
            return;
        }
        mqttClient.publish(MQTT_COMMAND_TOPIC, JSON.stringify(startPayload), { qos: 1 }, (err) => {
            if (err) socket.emit('appError', { message: 'Error enviando Start al broker.' });
            else socket.emit('appStatus', { message: 'Comando Start enviado.' });
        });
    });

    socket.on('rfidStop', (stopPayload) => {
        console.log(`Petición 'rfidStop' de ${socket.id}:`, stopPayload);
        if (!mqttClient.connected) {
            socket.emit('appError', { message: 'Node.js no conectado al Broker MQTT.' });
            return;
        }
        mqttClient.publish(MQTT_COMMAND_TOPIC, JSON.stringify(stopPayload), { qos: 1 }, (err) => {
            if (err) socket.emit('appError', { message: 'Error enviando Stop al broker.' });
            else socket.emit('appStatus', { message: 'Comando Stop enviado.' });
        });
    });

    socket.on('rfidStatus', (statusPayload) => {
        console.log(`Petición 'rfidStatus' de ${socket.id}:`, statusPayload);
        if (!mqttClient.connected) {
            socket.emit('appError', { message: 'Node.js no conectado al Broker MQTT.' });
            return;
        }
        mqttClient.publish(MQTT_COMMAND_TOPIC, JSON.stringify(statusPayload), { qos: 1 }, (err) => {
            if (err) socket.emit('appError', { message: 'Error enviando Status al broker.' });
            else socket.emit('appStatus', { message: 'Comando Status enviado.' });
        });
    });

    // Comandos para la Tarea MQTT del Lector (vía API HTTP del lector)
    socket.on('getReaderMqttSettings', async () => {
        console.log(`Petición 'getReaderMqttSettings' (API Lector) de ${socket.id}`);
        try {
            const response = await axios.post(`${READER_API_BASE_URL}/getMQTTInfo`);
            console.log("Respuesta API Lector (getMQTTInfo):", response.data);
            socket.emit('readerMqttSettingsData', response.data);
        } catch (error) {
            console.error('Error API Lector (getMQTTInfo):', error.response ? error.response.data : error.message);
            socket.emit('appError', { 
                message: 'Error obteniendo config. MQTT del lector.',
                details: error.response ? JSON.stringify(error.response.data) : error.code 
            });
        }
    });

    socket.on('setReaderMqttSettings', async (settingsPayload) => {
        console.log(`Petición 'setReaderMqttSettings' (API Lector) de ${socket.id}:`, settingsPayload);
        try {
            const response = await axios.post(`${READER_API_BASE_URL}/setMQTTInfo`, settingsPayload, {
                headers: { 'Content-Type': 'application/json' }
            });
            console.log("Respuesta API Lector (setMQTTInfo):", response.data);
            socket.emit('readerMqttSettingsStatus', response.data);

            if (response.data && response.data.resultCode === 0) {
                setTimeout(async () => { // Refrescar UI
                    try {
                        const updatedSettings = await axios.post(`${READER_API_BASE_URL}/getMQTTInfo`);
                        socket.emit('readerMqttSettingsData', updatedSettings.data);
                    } catch (e) { console.error("Error API Lector (re-obteniendo settings):", e);}
                }, 1000);
            }
        } catch (error) {
            console.error('Error API Lector (setMQTTInfo):', error.response ? error.response.data : error.message);
            socket.emit('appError', { 
                message: 'Error estableciendo config. MQTT del lector.',
                details: error.response ? JSON.stringify(error.response.data) : error.code
            });
        }
    });

    socket.on('disconnect', (reason) => {
        console.log(`Cliente web desconectado: ${socket.id}. Razón: ${reason}`);
    });
});

// --- Servidor HTTP para la interfaz web ---
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'index.html')); 
});

app.use(express.static(__dirname)); // Para servir socket.io.js y otros assets si los hubiera

const PORT = process.env.NODE_RFID_PORT || 3000; 
server.listen(PORT, () => {
    console.log(`Servidor Node.js para control RFID escuchando en http://localhost:${PORT}`);
    console.log(`Broker MQTT (Node.js) configurado: ${MQTT_BROKER_URL}`);
    console.log(`API HTTP del Lector RFID configurada para: ${READER_API_BASE_URL}`);
    console.log(`La interfaz web se sirve en la ruta principal (/).`);
});
