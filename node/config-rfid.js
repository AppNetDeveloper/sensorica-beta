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
const MQTT_COMMAND_TOPIC = 'rfid_command';
const MQTT_RESPONSE_TOPIC = 'rfid_command'; // Using the same topic for commands and responses

// Variables para el API HTTP del Lector RFID
const READER_API_IP = process.env.READER_API_IP || '192.168.123.15'; // IP del API HTTP de tu lector (puedes ponerla en .env)
const READER_API_PORT = process.env.READER_API_PORT || 1080;      // Puerto del API HTTP de tu lector (puedes ponerla en .env)
const READER_API_BASE_URL = `http://${READER_API_IP}:${READER_API_PORT}/API/Task`;

// Configuración del auto-monitoreo
let autoMonitorInterval = null;
let isAutoMonitorOn = true; // Por defecto activado para monitoreo continuo

// Iniciar el monitoreo automático al arrancar el servidor
startAutoMonitor();

// Iniciar el monitoreo automático al arrancar
function startAutoMonitor() {
    if (!autoMonitorInterval) {
        console.log('Iniciando monitoreo automático del lector cada 10 segundos');
        // Verificar estado inmediatamente
        checkReaderStatus().catch(console.error);
        // Y luego cada 10 segundos
        autoMonitorInterval = setInterval(checkReaderStatus, 10000);
    }
}

// Detener el monitoreo automático
function stopAutoMonitor() {
    if (autoMonitorInterval) {
        console.log('Deteniendo monitoreo automático del lector');
        clearInterval(autoMonitorInterval);
        autoMonitorInterval = null;
    }
}

// Función para habilitar/deshabilitar la tarea MQTT
async function setMqttTaskEnabled(enabled) {
    try {
        console.log(`${enabled ? 'Habilitando' : 'Deshabilitando'} tarea MQTT...`);
        const response = await axios.post(`${READER_API_BASE_URL}/setMQTTTask`, {
            enable: enabled
        }, {
            timeout: 5000,
            headers: { 'Content-Type': 'application/json' }
        });
        
        if (response.data && response.data.resultCode === 0) {
            console.log(`Tarea MQTT ${enabled ? 'habilitada' : 'deshabilitada'} exitosamente`);
            return true;
        } else {
            console.error(`Error al ${enabled ? 'habilitar' : 'deshabilitar'} tarea MQTT:`, response.data?.resultMsg || 'Error desconocido');
            return false;
        }
    } catch (error) {
        console.error(`Error al ${enabled ? 'habilitar' : 'deshabilitar'} tarea MQTT:`, error.message);
        return false;
    }
}

// Función para iniciar/detener la lectura RFID
async function setRfidReading(enabled) {
    return new Promise((resolve) => {
        if (!mqttClient.connected) {
            console.log('MQTT no conectado, no se puede modificar el estado de lectura');
            resolve(false);
            return;
        }
        
        const command = enabled ? 'RFID/start' : 'RFID/stop';
        console.log(`Enviando comando para ${enabled ? 'iniciar' : 'detener'} lectura RFID...`);
        
        mqttClient.publish(MQTT_COMMAND_TOPIC, JSON.stringify({ command }), { qos: 1 }, (err) => {
            if (err) {
                console.error(`Error al enviar comando para ${enabled ? 'iniciar' : 'detener'} lectura:`, err);
                resolve(false);
            } else {
                console.log(`Comando para ${enabled ? 'iniciar' : 'detener'} lectura enviado exitosamente`);
                resolve(true);
            }
        });
    });
}

// Función para verificar y configurar las antenas a través de MQTT
async function checkAndEnableAntennas() {
    return new Promise((resolve) => {
        if (!mqttClient || !mqttClient.connected) {
            console.log('MQTT no conectado, no se puede verificar antenas');
            resolve(false);
            return;
        }

        console.log('Solicitando estado de antenas...');
        
        // Configurar un manejador temporal para la respuesta
        const tempHandler = (topic, message) => {
            try {
                const data = JSON.parse(message.toString());
                if (data.resultCode === 0 && data.resultMsg && data.resultMsg.includes('get power success')) {
                    console.log('Estado de antenas recibido:', data.resultData);
                    
                    // Verificar si alguna antena está deshabilitada
                    const antennas = data.resultData || [];
                    const disabledAntennas = antennas.filter(a => !a.enable);
                    
                    if (disabledAntennas.length > 0) {
                        console.log(`Antenas deshabilitadas encontradas: ${disabledAntennas.length}`);
                        // Habilitar las antenas deshabilitadas
                        const enablePromises = disabledAntennas.map(antenna => {
                            console.log(`Habilitando antena ${antenna.index}...`);
                            return new Promise((enableResolve) => {
                                mqttClient.publish(
                                    MQTT_COMMAND_TOPIC, 
                                    JSON.stringify({
                                        command: 'RFID/setPower',
                                        index: antenna.index,
                                        power: antenna.power || 21,
                                        enable: true
                                    }),
                                    { qos: 1 },
                                    (err) => {
                                        if (err) {
                                            console.error('Error al habilitar antena:', err);
                                            enableResolve(false);
                                        } else {
                                            console.log(`Comando para habilitar antena ${antenna.index} enviado`);
                                            enableResolve(true);
                                        }
                                    }
                                );
                            });
                        });
                        
                        Promise.all(enablePromises).then(() => {
                            console.log('Proceso de habilitación de antenas completado');
                            resolve(true);
                        });
                    } else {
                        console.log('Todas las antenas están habilitadas');
                        resolve(true);
                    }
                    return;
                }
            } catch (e) {
                console.error('Error al procesar respuesta de antenas:', e);
                resolve(false);
            }
        };
        
        // Configurar timeout
        const timeout = setTimeout(() => {
            mqttClient.removeListener('message', tempHandler);
            console.log('Timeout al verificar estado de antenas');
            resolve(false);
        }, 5000);
        
        // Configurar el manejador temporal
        mqttClient.once('message', (topic, message) => {
            clearTimeout(timeout);
            tempHandler(topic, message);
        });
        
        // Solicitar estado de las antenas
        mqttClient.publish(
            MQTT_COMMAND_TOPIC, 
            JSON.stringify({ command: 'RFID/getPower' }), 
            { qos: 1 }, 
            (err) => {
                if (err) {
                    console.error('Error al solicitar estado de antenas:', err);
                    clearTimeout(timeout);
                    mqttClient.removeListener('message', tempHandler);
                    resolve(false);
                } else {
                    console.log('Solicitud de estado de antenas enviada');
                }
            }
        );
    });
}

// Función para verificar el estado del lector y la tarea MQTT y antenas
async function checkReaderStatus() {
    // Verificar si mqttClient está inicializado y conectado
    if (!mqttClient || !mqttClient.connected) {
        console.log('MQTT Client no está inicializado o no hay conexión');
        return false;
    }
    
    try {
        // Verificar conexión MQTT
        if (!mqttClient.connected) {
            console.log('MQTT no conectado, no se puede verificar el estado del lector');
            return false;
        }
        
        // Primero verificar el estado de la tarea MQTT
        let mqttStatus = false;
        let mqttConfig = null;
        
        try {
            console.log('Verificando estado de la tarea MQTT...');
            const response = await axios.post(`${READER_API_BASE_URL}/getMQTTInfo`, {}, {
                timeout: 3000,
                headers: { 'Content-Type': 'application/json' }
            });
            
            if (response.data && response.data.resultCode === 0) {
                mqttStatus = response.data.resultData?.enable === true;
                mqttConfig = response.data.resultData?.param || {};
                console.log(`Estado de la tarea MQTT: ${mqttStatus ? 'HABILITADA' : 'DESHABILITADA'}`);
                
                // Si la tarea MQTT está deshabilitada, intentar habilitarla
                if (!mqttStatus) {
                    console.log('Intentando habilitar la tarea MQTT...');
                    const enabled = await setMqttTaskEnabled(true);
                    if (enabled) {
                        mqttStatus = true;
                        console.log('Tarea MQTT habilitada exitosamente');
                    }
                }
                
                // Emitir el estado actualizado de la tarea MQTT
                io.emit('mqttTaskStatus', { 
                    enabled: mqttStatus,
                    config: mqttConfig
                });
            } else {
                console.error('Error al obtener estado MQTT:', response.data?.resultMsg || 'Respuesta inválida');
            }
        } catch (error) {
            console.error('Error al verificar tarea MQTT:', error.message);
        }
        
        // Verificar y habilitar antenas si es necesario
        await checkAndEnableAntennas();
        
        // Verificar estado del lector RFID
        let rfidStatus = false;
        let isReading = false;
        
        try {
            console.log('Verificando estado del lector RFID...');
            
            // Primero, obtener el estado actual del lector
            const statusResponse = await new Promise((resolve) => {
                // Variable para almacenar el estado de lectura
                let readerState = false;
                
                // Manejador temporal para la respuesta de estado
                const tempHandler = (topic, message) => {
                    if (topic === MQTT_RESPONSE_TOPIC) {
                        try {
                            const data = JSON.parse(message.toString());
                            if (data.resultCode === 0 && data.resultMsg && data.resultMsg.includes('get rfid status success')) {
                                readerState = data.resultData === true;
                                console.log(`Estado del lector: ${readerState ? 'LEYENDO' : 'DETENIDO'}`);
                                
                                // Si el lector está detenido, intentar iniciarlo
                                if (!readerState) {
                                    console.log('El lector está detenido, intentando iniciar lectura...');
                                    setRfidReading(true).then(() => {
                                        // Después de intentar iniciar, verificar el estado nuevamente
                                        mqttClient.publish(MQTT_COMMAND_TOPIC, JSON.stringify({ command: 'RFID/status' }), { qos: 1 });
                                    });
                                }
                                
                                resolve({ status: true, isReading: readerState });
                                return;
                            }
                        } catch (e) {
                            console.error('Error al procesar respuesta de estado:', e);
                        }
                    }
                };
                
                // Suscribirse temporalmente al topic de respuestas
                mqttClient.once('message', tempHandler);
                
                // Enviar comando de estado
                mqttClient.publish(MQTT_COMMAND_TOPIC, JSON.stringify({ command: 'RFID/status' }), { qos: 1 }, (err) => {
                    if (err) {
                        console.error('Error al enviar comando de estado:', err);
                        mqttClient.removeListener('message', tempHandler);
                        resolve({ status: false, isReading: false });
                    } else {
                        console.log('Comando de estado enviado a través de MQTT');
                        // Timeout en caso de no recibir respuesta
                        setTimeout(() => {
                            mqttClient.removeListener('message', tempHandler);
                            resolve({ status: false, isReading: false });
                        }, 3000);
                    }
                });
            });
            
            rfidStatus = statusResponse.status;
            isReading = statusResponse.isReading;
            
        } catch (error) {
            console.error('Error al verificar estado del lector RFID:', error.message);
            return false;
        }
        
        return mqttStatus && rfidStatus;
        
    } catch (error) {
        console.error('Error en checkReaderStatus:', error);
        return false;
    }
}

// Los manejadores de eventos de socket se moverán dentro del bloque de conexión de Socket.IO

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

// Manejador de mensajes MQTT
mqttClient.on('message', (topic, messageBuffer) => {
    const message = messageBuffer.toString();
    console.log(`Node.js: Mensaje MQTT recibido (comandos RFID) - Topic: ${topic}, Mensaje: ${message}`);
    
    // Skip empty messages
    if (!message || message.trim() === '') {
        console.log('Node.js: Mensaje MQTT vacío recibido, ignorando...');
        return;
    }
    
    let parsedMessage;
    try {
        parsedMessage = JSON.parse(message);
        
        // Re-emitir el mensaje a los clientes WebSocket
        io.emit('rfidResponse', parsedMessage);
        
        // Manejar diferentes tipos de respuestas
        if (parsedMessage.resultMsg) {
            if (parsedMessage.resultMsg.includes("RFID/status:get rfid status success")) {
                console.log('Node.js: Estado del lector recibido');
                const isReading = parsedMessage.resultData === true;
                const statusText = isReading ? 'LEYENDO' : 'APAGADO';
                console.log(`ESTADO LECTOR: ${statusText}`);
                
                // Emitir el estado a los clientes conectados
                io.emit('readerStatus', {
                    status: 'online',
                    isReading: isReading,
                    timestamp: new Date().toISOString(),
                    readerStatus: statusText
                });
                
                io.emit('rfidInfo', parsedMessage);
            } else if (parsedMessage.resultMsg.includes("RFID/getPower:get power success")) {
                io.emit('antennaPowerData', parsedMessage);
            } else if (parsedMessage.resultMsg.includes("RFID/setPower:set power success")) {
                io.emit('antennaSetPowerStatus', { success: true, data: parsedMessage });
            } else if (parsedMessage.resultMsg.includes("RFID/start:start success")) {
                io.emit('rfidInfo', parsedMessage);
            } else if (parsedMessage.resultMsg.includes("RFID/stop:stop success")) {
                io.emit('rfidInfo', parsedMessage);
            } else if (parsedMessage.resultCode !== 0) {
                io.emit('rfidError', parsedMessage);
            } else {
                io.emit('rfidInfo', parsedMessage);
            }
        } else if (parsedMessage.command && parsedMessage.command.startsWith("RFID/") && 
                 typeof parsedMessage.resultCode === 'undefined') {
            console.log(`Node.js: Comando '${parsedMessage.command}' re-emitido, esperando respuesta real...`);
        } else {
            console.warn("Node.js: Mensaje MQTT (comandos RFID) sin 'resultMsg' y no reconocido:", parsedMessage);
            io.emit('appWarning', { message: "Node.js: Respuesta RFID no reconocida.", details: parsedMessage });
        }
    } catch (e) {
        console.error('Error parseando JSON:', e);
        io.emit('appError', { message: 'Error procesando respuesta RFID (JSON inválido).', details: message });
    }
});

// --- Manejadores de eventos Socket.IO ---
// Manejar conexiones de clientes WebSocket
io.on('connection', (socket) => {
    console.log(`Cliente web conectado: ${socket.id}`);
    
    // Enviar estado inicial al cliente recién conectado
    socket.emit('appStatus', { message: 'Conectado al servidor Node.js.' });
    socket.emit('autoMonitorStatus', { enabled: isAutoMonitorOn });
    
    // Si el auto-monitoreo está activo, forzar una verificación del estado
    if (isAutoMonitorOn) {
        checkReaderStatus().catch(console.error);
    }
    
    // Manejar la solicitud de toggle del auto-monitoreo desde el frontend
    socket.on('toggleAutoMonitor', (data, callback) => {
        const newStatus = typeof data === 'boolean' ? data : data.enabled;
        
        // Actualizar el estado del auto-monitoreo
        if (newStatus) {
            startAutoMonitor();
        } else {
            stopAutoMonitor();
        }
        isAutoMonitorOn = newStatus;
        
        // Notificar a todos los clientes del cambio
        io.emit('autoMonitorStatus', { enabled: newStatus });
        
        // Responder al callback si se proporcionó
        if (typeof callback === 'function') {
            callback({ success: true, enabled: newStatus });
        }
    });
    
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
        
        // Ensure we have a valid payload
        const payload = statusPayload && typeof statusPayload === 'object' ? statusPayload : { command: "RFID/status" };
        
        try {
            mqttClient.publish(MQTT_COMMAND_TOPIC, JSON.stringify(payload), { qos: 1 }, (err) => {
                if (err) {
                    console.error('Error al publicar en MQTT:', err);
                    socket.emit('appError', { 
                        message: 'Error enviando Status al broker.',
                        details: err.message 
                    });
                } else {
                    console.log('Comando Status enviado:', payload);
                    socket.emit('appStatus', { message: 'Comando Status enviado.' });
                }
            });
        } catch (error) {
            console.error('Error al procesar rfidStatus:', error);
            socket.emit('appError', { 
                message: 'Error al procesar la solicitud de estado.',
                details: error.message 
            });
        }
    });

    // Comandos para la Tarea MQTT del Lector (vía API HTTP del lector)
    socket.on('getReaderMqttSettings', async () => {
        console.log(`Petición 'getReaderMqttSettings' (API Lector) de ${socket.id}`);
        try {
            const response = await axios.post(`${READER_API_BASE_URL}/getMQTTInfo`, {}, {
                timeout: 5000,
                validateStatus: null // Para manejar manualmente los códigos de estado
            });
            
            console.log("Respuesta API Lector (getMQTTInfo):", response.data);
            
            // Adaptar la respuesta al formato esperado por el frontend
            const formattedResponse = {
                resultCode: response.data.resultCode || 0,
                resultData: {
                    enable: response.data.resultData?.enable || false,
                    server: response.data.resultData?.param?.host || '',
                    port: response.data.resultData?.param?.port || 1883,
                    username: response.data.resultData?.param?.username || '',
                    password: response.data.resultData?.param?.password || '',
                    topic: response.data.resultData?.param?.topic || '',
                    clientId: response.data.resultData?.param?.clientId || '',
                    qos: response.data.resultData?.param?.qos || 1,
                    ssl: response.data.resultData?.param?.ssl || 0,
                    // Otros campos que podrían ser necesarios
                    ...response.data.resultData?.param
                },
                resultMsg: response.data.resultMsg || 'Configuración MQTT obtenida correctamente'
            };
            
            socket.emit('readerMqttSettingsData', formattedResponse);
            
        } catch (error) {
            console.error('Error al obtener configuración MQTT:', error.message);
            
            // En caso de error, devolver configuración por defecto
            const defaultConfig = {
                resultCode: 0,
                resultData: {
                    enable: true,
                    server: process.env.MQTT_SENSORICA_SERVER || 'localhost',
                    port: parseInt(process.env.MQTT_SENSORICA_PORT || '1883', 10),
                    username: process.env.MQTT_USERNAME || '',
                    password: process.env.MQTT_PASSWORD ? '********' : '',
                    topic: process.env.MQTT_TOPIC || 'rfid_reader',
                    clientId: `rfid_reader_${Math.random().toString(36).substr(2, 8)}`,
                    qos: 1,
                    ssl: 0,
                    interval: 10,
                    maxtags: 999,
                    mergetags: true,
                    cachetags: true
                },
                resultMsg: 'Configuración MQTT cargada desde valores por defecto (error: ' + (error.message || 'desconocido') + ')'
            };
            
            console.log('Usando configuración MQTT por defecto:', defaultConfig);
            socket.emit('readerMqttSettingsData', defaultConfig);
        }
    });

    socket.on('setReaderMqttSettings', async (settingsPayload) => {
        console.log(`Petición 'setReaderMqttSettings' (API Lector) de ${socket.id}:`, settingsPayload);
        
        // Primero validar los datos recibidos
        if (!settingsPayload || typeof settingsPayload !== 'object') {
            socket.emit('appError', {
                message: 'Datos de configuración MQTT inválidos',
                details: 'El payload no es un objeto válido'
            });
            return;
        }

        try {
            // Primero obtener la configuración actual para mantener los campos que no se envían desde el frontend
            let currentConfig = {};
            try {
                const currentResponse = await axios.post(`${READER_API_BASE_URL}/getMQTTInfo`, {}, {
                    timeout: 3000,
                    validateStatus: null
                });
                
                if (currentResponse.data && currentResponse.data.resultCode === 0) {
                    currentConfig = currentResponse.data.resultData?.param || {};
                }
            } catch (e) {
                console.warn('No se pudo obtener la configuración actual:', e.message);
            }
            
            // Preparar el payload para la API del lector
            const apiPayload = {
                enable: settingsPayload.enable !== undefined ? settingsPayload.enable : true,
                name: "task.name.const.mqtt",
                param: {
                    ...currentConfig, // Mantener la configuración actual
                    host: settingsPayload.server || currentConfig.host || 'localhost',
                    port: settingsPayload.port || currentConfig.port || 1883,
                    topic: settingsPayload.topic || currentConfig.topic || 'rfid_reader',
                    qos: settingsPayload.qos !== undefined ? settingsPayload.qos : (currentConfig.qos || 1),
                    ssl: settingsPayload.ssl !== undefined ? settingsPayload.ssl : (currentConfig.ssl || 0),
                    username: settingsPayload.username !== undefined ? settingsPayload.username : (currentConfig.username || ''),
                    password: settingsPayload.password !== undefined ? settingsPayload.password : (currentConfig.password || ''),
                    clientId: settingsPayload.clientId || currentConfig.clientId || `rfid_${Math.random().toString(36).substr(2, 8)}`,
                    // Mantener otros campos de la configuración actual
                    ...(settingsPayload.param || {}) // Sobrescribir con cualquier parámetro adicional
                },
                type: "mqtt"
            };
            
            // Si se está deshabilitando, mantener todos los parámetros actuales
            if (settingsPayload.enable === false) {
                apiPayload.param = { ...currentConfig, enable: false };
            }
            
            console.log('Enviando configuración MQTT al lector:', JSON.stringify(apiPayload, null, 2));
            
            // Enviar la configuración al lector
            const response = await axios.post(`${READER_API_BASE_URL}/setMQTTInfo`, apiPayload, {
                headers: { 'Content-Type': 'application/json' },
                timeout: 10000,
                validateStatus: null
            });
            
            console.log("Respuesta API Lector (setMQTTInfo):", response.data);
            
            // Notificar a los clientes sobre el resultado
            socket.emit('readerMqttSettingsStatus', response.data);
            
            // Si la operación fue exitosa, actualizar la configuración mostrada
            if (response.data && response.data.resultCode === 0) {
                // Obtener la configuración actualizada para asegurarnos de que todo se guardó correctamente
                try {
                    const updatedResponse = await axios.post(`${READER_API_BASE_URL}/getMQTTInfo`, {}, {
                        timeout: 3000,
                        validateStatus: null
                    });
                    
                    if (updatedResponse.data && updatedResponse.data.resultCode === 0) {
                        const updatedSettings = updatedResponse.data.resultData;
                        // Formatear la respuesta para el frontend
                        const formattedResponse = {
                            resultCode: 0,
                            resultData: {
                                enable: updatedSettings.enable,
                                server: updatedSettings.param?.host,
                                port: updatedSettings.param?.port,
                                username: updatedSettings.param?.username,
                                password: updatedSettings.param?.password ? '********' : '',
                                topic: updatedSettings.param?.topic,
                                clientId: updatedSettings.param?.clientId,
                                qos: updatedSettings.param?.qos,
                                ssl: updatedSettings.param?.ssl
                            },
                            resultMsg: 'Configuración MQTT actualizada correctamente'
                        };
                        
                        socket.emit('readerMqttSettingsData', formattedResponse);
                    }
                } catch (e) {
                    console.error('Error al verificar la configuración actualizada:', e.message);
                    // Usar la configuración que acabamos de enviar como confirmación
                    socket.emit('readerMqttSettingsData', {
                        resultCode: 0,
                        resultData: {
                            enable: apiPayload.enable,
                            server: apiPayload.param.host,
                            port: apiPayload.param.port,
                            username: apiPayload.param.username,
                            password: apiPayload.param.password ? '********' : '',
                            topic: apiPayload.param.topic,
                            clientId: apiPayload.param.clientId,
                            qos: apiPayload.param.qos,
                            ssl: apiPayload.param.ssl
                        },
                        resultMsg: 'Configuración MQTT actualizada (no se pudo verificar)'
                    });
                }
                
                // Mostrar mensaje de éxito
                socket.emit('appStatus', {
                    message: 'Configuración MQTT actualizada correctamente',
                    type: 'success'
                });
            } else {
                // Si hubo un error en la operación
                socket.emit('appError', {
                    message: 'Error al actualizar la configuración MQTT',
                    details: response.data?.resultMsg || 'Error desconocido'
                });
            }
        } catch (error) {
            console.error('Error en setReaderMqttSettings:', error.message);
            socket.emit('appError', { 
                message: 'Error estableciendo configuración MQTT del lector',
                details: error.response ? JSON.stringify(error.response.data) : error.code || error.message
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
