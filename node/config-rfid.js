// config-rfid.js
// Ubicado en la carpeta 'node/' de tu proyecto.
// El archivo .env de Laravel debe estar en la raíz del proyecto Laravel (../.env)

// Variables globales
let mqttMessageHandlers = new Map();
let lastRestartAttempt = 0; // Timestamp del último intento de reinicio

// Configuración del logger
const logger = {
    // Colores para la consola
    colors: {
        reset: '\x1b[0m',
        red: '\x1b[31m',
        green: '\x1b[32m',
        yellow: '\x1b[33m',
        blue: '\x1b[34m',
        magenta: '\x1b[35m',
        cyan: '\x1b[36m',
        white: '\x1b[37m'
    },
    
    // Función base para loguear
    log: function(level, prefix, message, ...args) {
        const timestamp = new Date().toISOString();
        const formattedMessage = `[${timestamp}] [${prefix}] ${message}`;
        
        // Si hay argumentos adicionales, mostrarlos en una línea aparte
        const extraArgs = args.length > 0 ? `\n${JSON.stringify(args, null, 2)}` : '';
        
        switch(level) {
            case 'error':
                console.error(`${this.colors.red}${formattedMessage}${this.colors.reset}${extraArgs}`);
                break;
            case 'warn':
                console.warn(`${this.colors.yellow}${formattedMessage}${this.colors.reset}${extraArgs}`);
                break;
            case 'info':
                console.info(`${this.colors.cyan}${formattedMessage}${this.colors.reset}${extraArgs}`);
                break;
            case 'success':
                console.log(`${this.colors.green}${formattedMessage}${this.colors.reset}${extraArgs}`);
                break;
            default:
                console.log(`${formattedMessage}${extraArgs}`);
        }
    },
    
    // Métodos específicos para diferentes tipos de logs
    error: (message, ...args) => {
        logger.log('error', 'ERROR', message, ...args);
    },
    warn: (message, ...args) => {
        logger.log('warn', 'WARN', message, ...args);
    },
    info: (message, ...args) => {
        logger.log('info', 'INFO', message, ...args);
    },
    success: (message, ...args) => {
        logger.log('success', 'SUCCESS', message, ...args);
    },
    mqtt: (message, ...args) => {
        logger.log('info', 'MQTT', message, ...args);
    },
    reader: (message, level = 'info', ...args) => {
        logger.log(level, 'LECTOR', message, ...args);
    },
    antenna: (message, ...args) => {
        logger.log('info', 'ANTENNA', message, ...args);
    },
    monitor: (message, ...args) => {
        logger.log('info', 'MONITOR', message, ...args);
    }
};

const express = require('express');
const http = require('http');
const { Server } = require("socket.io");
const mqtt = require('mqtt');
const path = require('path');
const fs = require('fs');
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
// Intentar cargar configuración desde el archivo .env de Laravel
let READER_API_IP = '';
let READER_API_PORT = '';
let READER_API_BASE_URL = '';

try {
    // Leer el archivo .env de Laravel
    const envPath = path.resolve(__dirname, '../.env');
    if (fs.existsSync(envPath)) {
        const envContent = fs.readFileSync(envPath, 'utf8');
        const ipMatch = envContent.match(/^RFID_READER_IP=(.*)$/m);
        const portMatch = envContent.match(/^RFID_READER_PORT=(\d+)$/m);
        
        if (ipMatch && ipMatch[1]) {
            READER_API_IP = ipMatch[1].trim();
        }
        
        if (portMatch && portMatch[1]) {
            READER_API_PORT = portMatch[1].trim();
        }
        
        if (READER_API_IP && READER_API_PORT) {
            READER_API_BASE_URL = `http://${READER_API_IP}:${READER_API_PORT}/API/Task`;
            console.log(`Configuración del lector RFID cargada: ${READER_API_IP}:${READER_API_PORT}`);
        } else {
            console.warn('No se encontró la configuración completa del lector RFID en .env. Algunas funciones podrían no estar disponibles.');
        }
    } else {
        console.warn('No se encontró el archivo .env de Laravel. Algunas funciones podrían no estar disponibles.');
    }
} catch (error) {
    console.error('Error al cargar la configuración del lector RFID:', error.message);
}

// Configuración del auto-monitoreo
let autoMonitorInterval = null;
let isAutoMonitorOn = true; // Por defecto activado para monitoreo continuo

// Variables para el caché del estado MQTT
let lastMqttCheck = 0;
let lastMqttStatus = null;
const MQTT_CHECK_INTERVAL = 60000; // 60 segundos

// Variables globales para el estado del lector y antenas
let lastStatusCommand = null; // Para controlar el cooldown de comandos de estado
let lastReaderCheck = 0;
let lastReaderStatus = null;
const READER_CHECK_INTERVAL = 30000; // 30 segundos
const MONITORING_INTERVAL = 10000; // 10 segundos

let lastAntennaCheck = 0;
let lastAntennaStatus = null;
const ANTENNA_CHECK_INTERVAL = 60000; // 60 segundos

// Variables para controlar el estado del monitoreo
let isChecking = false;
let lastCheckTime = 0;
const CHECK_INTERVAL = 30000; // 30 segundos

// Función para verificar y habilitar la tarea MQTT si es necesario
async function checkAndEnableMqttTask() {
    const now = Date.now();
    
    // Usar caché si la última verificación fue hace menos de MQTT_CHECK_INTERVAL
    if (lastMqttStatus !== null && (now - lastMqttCheck) < MQTT_CHECK_INTERVAL) {
        console.log(`Usando estado MQTT en caché: ${lastMqttStatus ? 'HABILITADA' : 'DESHABILITADA'}`);
        return lastMqttStatus;
    }
    
    try {
        console.log('Verificando estado de la tarea MQTT...');
        const response = await axios.post(`${READER_API_BASE_URL}/getMQTTInfo`, {}, {
            timeout: 3000,
            headers: { 'Content-Type': 'application/json' }
        });
        
        if (response.data && response.data.resultCode === 0) {
            const isMqttEnabled = response.data.resultData?.enable === true;
            console.log(`Estado de la tarea MQTT: ${isMqttEnabled ? 'HABILITADA' : 'DESHABILITADA'}`);
            
            // Actualizar caché
            lastMqttStatus = isMqttEnabled;
            lastMqttCheck = now;
            
            if (!isMqttEnabled) {
                console.log('La tarea MQTT está deshabilitada, intentando habilitar...');
                const enabled = await setMqttTaskEnabled(true);
                if (enabled) {
                    console.log('Tarea MQTT habilitada exitosamente');
                    lastMqttStatus = true; // Actualizar caché
                }
                return enabled;
            }
            
            return true;
        } else {
            const errorMsg = response.data?.resultMsg || 'Respuesta inválida';
            console.error('Error al verificar estado MQTT:', errorMsg);
            return false;
        }
    } catch (error) {
        console.error('Error al verificar tarea MQTT:', error.message);
        return false;
    }
}

// Función para enviar comando de estado del lector
function sendStatusCommand() {
    try {
        if (!mqttClient || !mqttClient.connected) {
            logger.error('No se puede enviar comando: MQTT no conectado');
            return false;
        }

        // Verificar cooldown
        const now = Date.now();
        if (lastStatusCommand && (now - lastStatusCommand) < 1000) { // 1 segundo de cooldown
            logger.mqtt('Comando de estado ignorado: cooldown activo');
            return false;
        }
        lastStatusCommand = now;

        const command = { command: 'RFID/status' };
        logger.mqtt(`Enviando comando de estado: ${JSON.stringify(command)}`);

        mqttClient.publish(
            MQTT_COMMAND_TOPIC,
            JSON.stringify(command),
            { qos: 1, retain: false },
            (err) => {
                if (err) {
                    logger.error('Error al publicar comando de estado:', err);
                    return false;
                }
                logger.mqtt('Comando de estado enviado correctamente');
                return true;
            }
        );
        return true;
    } catch (error) {
        logger.error('Error en sendStatusCommand:', error);
        return false;
    }
}

// Función para verificar el estado de las antenas
async function checkAntennaStatus() {
    try {
        const now = Date.now();
        if (lastAntennaCheck !== 0 && (now - lastAntennaCheck) < ANTENNA_CHECK_INTERVAL) {
            logger.antenna('Usando estado en caché de antenas', 'info');
            return lastAntennaStatus;
        }

        logger.antenna('Solicitando estado de antenas...');
        
        // Enviar comando para obtener el estado de las antenas
        const command = { command: 'RFID/getPower' };
        
        const response = await new Promise((resolve, reject) => {
            mqttClient.publish(
                MQTT_COMMAND_TOPIC,
                JSON.stringify(command),
                { qos: 1, retain: false },
                (err) => {
                    if (err) {
                        reject(new Error(`Error al publicar comando de antenas: ${err.message}`));
                    } else {
                        logger.mqtt(`Comando 'RFID/getPower' reenviado, ignorando...`);
                        resolve();
                    }
                }
            );
        });

        logger.antenna('Solicitud de estado de antenas enviada');
        
        // Esperar un momento para recibir la respuesta
        await new Promise(resolve => setTimeout(resolve, 500));
        
        // El estado real se actualizará cuando llegue la respuesta MQTT
        lastAntennaCheck = now;
        return lastAntennaStatus || null;
        
    } catch (error) {
        logger.error(`Error en checkAntennaStatus: ${error}`);
        return null;
    }
}

// Función para iniciar el monitoreo automático
async function startAutoMonitor() {
    if (autoMonitorInterval) {
        clearInterval(autoMonitorInterval);
        logger.monitor('Deteniendo monitoreo anterior...', 'warning');
    }
    
    logger.monitor('Iniciando monitoreo automático...', 'success');
    isAutoMonitorOn = true;
    
    // Función para realizar todas las verificaciones necesarias
    async function performChecks() {
        // Evitar ejecuciones simultáneas
        if (isChecking) {
            logger.monitor('Ya hay una verificación en curso, omitiendo este ciclo...');
            return;
        }
        
        // Verificar el tiempo desde la última verificación
        const now = Date.now();
        if (now - lastCheckTime < CHECK_INTERVAL) {
            logger.monitor(`Aún no es tiempo de verificar (${Math.floor((now - lastCheckTime)/1000)}s < ${CHECK_INTERVAL/1000}s), omitiendo...`);
            return;
        }
        
        isChecking = true;
        lastCheckTime = now;
        
        try {
            logger.monitor('Iniciando ciclo de verificación...');
            
            // Verificar estado MQTT
            try {
                const mqttStatus = await checkAndEnableMqttTask();
                if (!mqttStatus) {
                    logger.error('MQTT no está disponible');
                    throw new Error('MQTT no está disponible');
                }
                logger.monitor('MQTT verificado correctamente');
                // Pequeña pausa después de verificar MQTT
                await new Promise(resolve => setTimeout(resolve, 100));
            } catch (mqttError) {
                logger.error('Error verificando MQTT:', mqttError.message || mqttError);
                throw mqttError;
            }
            
            // Verificar estado del lector
            try {
                const readerStatus = await checkReaderStatus();
                logger.reader(`Estado del lector: ${readerStatus ? 'LEYENDO' : 'DETENIDO'}`);
                
                // Actualizar el estado en la interfaz web
                io.emit('readerStatus', { 
                    status: readerStatus ? 'reading' : 'stopped',
                    timestamp: new Date().toISOString()
                });
            } catch (readerError) {
                logger.error('Error verificando estado del lector:', readerError.message || readerError);
                throw readerError;
            }
            
            // Pausa más larga después de verificar el lector
            await new Promise(resolve => setTimeout(resolve, 200));

            // Verificar estado de las antenas
            try {
                const antennaStatus = await checkAntennaStatus();
                logger.antenna(`Estado de antenas: ${antennaStatus ? JSON.stringify(antennaStatus) : 'null'}`);
                
                // Actualizar el estado en la interfaz web
                if (antennaStatus) {
                    io.emit('antennaStatus', {
                        status: 'ok',
                        data: antennaStatus,
                        timestamp: new Date().toISOString()
                    });
                }
            } catch (antennaError) {
                logger.error('Error verificando estado de antenas:', antennaError.message || antennaError);
                // No relanzamos este error para permitir que continúe el monitoreo
                
                // Pequeña pausa después de verificar antenas
                await new Promise(resolve => setTimeout(resolve, 100));
            }
            
        } catch (error) {
            const errorMessage = error instanceof Error ? error.message : String(error);
            logger.error('Error en el ciclo de monitoreo:', errorMessage);
            
            // Notificar a la interfaz web sobre el error
            io.emit('monitorError', {
                message: 'Error en el ciclo de monitoreo',
                details: errorMessage,
                timestamp: new Date().toISOString()
            });
            
            // Intentar reiniciar el monitoreo después de un tiempo
            setTimeout(() => {
                if (isAutoMonitorOn) {
                    logger.monitor('Reintentando monitoreo después de un error...');
                    performChecks();
                }
            }, 10000);
        } finally {
            isChecking = false;
        }
    }
    
    // Enviar primer comando de estado inmediatamente
    sendStatusCommand();
    
    // Iniciar el ciclo de monitoreo
    performChecks();
    
    // Configurar el intervalo para las verificaciones periódicas
    autoMonitorInterval = setInterval(performChecks, CHECK_INTERVAL);
}

// Detener el monitoreo automático
function stopAutoMonitor() {
    if (autoMonitorInterval) {
        logger.monitor('Deteniendo monitoreo automático del lector');
        clearInterval(autoMonitorInterval);
        autoMonitorInterval = null;
    }
}

// Función para habilitar/deshabilitar la tarea MQTT
async function setMqttTaskEnabled(enabled) {
    try {
        logger.mqtt(`${enabled ? 'Habilitando' : 'Deshabilitando'} tarea MQTT...`);
        const response = await axios.post(`${READER_API_BASE_URL}/setMQTTTask`, {
            enable: enabled
        }, {
            timeout: 5000,
            headers: { 'Content-Type': 'application/json' }
        });
        
        if (response.data && response.data.resultCode === 0) {
            logger.mqtt(`Tarea MQTT ${enabled ? 'habilitada' : 'deshabilitada'} exitosamente`);
            return true;
        } else {
            const errorMsg = response.data?.resultMsg || 'Error desconocido';
            logger.error(`Error al ${enabled ? 'habilitar' : 'deshabilitar'} tarea MQTT: ${errorMsg}`);
            return false;
        }
    } catch (error) {
        logger.error(`Error al ${enabled ? 'habilitar' : 'deshabilitar'} tarea MQTT: ${error.message}`);
        return false;
    }
}

// Función para iniciar/detener la lectura RFID
async function setRfidReading(enabled) {
    return new Promise((resolve) => {
        if (!mqttClient.connected) {
            logger.error('MQTT no conectado, no se puede modificar el estado de lectura');
            resolve(false);
            return;
        }
        
        const command = enabled ? 'RFID/start' : 'RFID/stop';
        logger.reader(`Enviando comando para ${enabled ? 'iniciar' : 'detener'} lectura RFID...`);
        
        mqttClient.publish(MQTT_COMMAND_TOPIC, JSON.stringify({ command }), { qos: 1 }, (err) => {
            if (err) {
                logger.error(`Error al enviar comando para ${enabled ? 'iniciar' : 'detener'} lectura: ${err}`);
                resolve(false);
            } else {
                logger.reader(`Comando para ${enabled ? 'iniciar' : 'detener'} lectura enviado exitosamente`);
                resolve(true);
            }
        });
    });
}

// Función para verificar el estado del lector RFID
async function checkReaderStatus() {
    const now = Date.now();
    
    // Usar caché si la última verificación fue hace menos de READER_CHECK_INTERVAL
    if (lastReaderStatus !== null && (now - lastReaderCheck) < READER_CHECK_INTERVAL) {
        const cacheAge = Math.floor((now - lastReaderCheck) / 1000);
        logger.reader(`Usando estado en caché (hace ${cacheAge}s)`, 'info');
        return lastReaderStatus;
    }
    
    if (!mqttClient || !mqttClient.connected) {
        logger.error('Lector: MQTT no conectado, no se puede verificar el estado');
        return false;
    }
    
    logger.reader('Verificando estado...');
    
    // Asegurarse de que mqttMessageHandlers esté inicializado
    if (!mqttMessageHandlers) {
        logger.reader('Inicializando mqttMessageHandlers');
        mqttMessageHandlers = new Map();
    }
    
    try {
        // Verificar estado del lector RFID
        const statusResponse = await new Promise((resolve) => {
            const handlerId = `status_${Date.now()}`;
            let timeoutId;
            let responseHandled = false;
            
            const cleanup = () => {
                if (timeoutId) clearTimeout(timeoutId);
                if (mqttMessageHandlers.has(handlerId)) {
                    mqttMessageHandlers.delete(handlerId);
                }
            };
            
            const handler = (topic, data) => {
                if (responseHandled) return;
                
                if (data.resultCode === 0 && data.resultMsg && data.resultMsg.includes('get rfid status success')) {
                    responseHandled = true;
                    cleanup();
                    
                    const readerState = data.resultData === true;
                    logger.reader(`Estado actual: ${readerState ? 'LEYENDO' : 'DETENIDO'}`);
                    
                    // Actualizar caché
                    lastReaderStatus = readerState;
                    lastReaderCheck = now;
                    
                    // Si el lector está detenido, intentar iniciarlo
                    if (!readerState) {
                        logger.reader('Intentando iniciar lectura...');
                        setRfidReading(true).then((success) => {
                            if (success) {
                                logger.reader('Comando de inicio enviado exitosamente');
                            }
                        }).catch(err => {
                            logger.error('Error al iniciar lectura:', err);
                        });
                    }
                    
                    resolve(readerState);
                }
            };
            
            // Registrar el manejador
            if (mqttMessageHandlers) {
                mqttMessageHandlers.set(handlerId, handler);
            } else {
                logger.error('mqttMessageHandlers no está inicializado');
                cleanup();
                resolve(false);
                return;
            }
            
            // Configurar timeout
            timeoutId = setTimeout(() => {
                if (responseHandled) return;
                responseHandled = true;
                logger.reader('Timeout al verificar estado');
                cleanup();
                // No actualizamos el estado global aquí para evitar sobrescribir una respuesta válida
                // que podría haber llegado justo antes del timeout
                resolve(lastReaderStatus !== null ? lastReaderStatus : false);
            }, 5000);
            
            // Enviar comando de estado
            try {
                mqttClient.publish(
                    MQTT_COMMAND_TOPIC, 
                    JSON.stringify({ command: 'RFID/status' }), 
                    { qos: 1, retain: false }, 
                    (err) => {
                        if (err) {
                            logger.error('Error al enviar comando de estado:', err);
                            if (!responseHandled) {
                                responseHandled = true;
                                cleanup();
                                resolve(false);
                            }
                        }
                    }
                );
            } catch (err) {
                logger.error('Error al publicar comando de estado:', err);
                if (!responseHandled) {
                    responseHandled = true;
                    cleanup();
                    resolve(false);
                }
            }
        });
        
        return statusResponse;
        
    } catch (error) {
        logger.error('Error en checkReaderStatus:', error);
        return false;
    }
}

// Los manejadores de eventos de socket se moverán dentro del bloque de conexión de Socket.IO

if (!MQTT_HOST || !MQTT_PORT) {
    logger.error("ERROR CRÍTICO: MQTT_SENSORICA_SERVER o MQTT_SENSORICA_PORT no están definidos para Node.js en el archivo .env.");
    process.exit(1);
}

logger.mqtt(`Intentando conectar al Broker MQTT (Node.js): ${MQTT_BROKER_URL}`);
const mqttClient = mqtt.connect(MQTT_BROKER_URL, {
    connectTimeout: 10000,
    reconnectPeriod: 5000,
    clientId: `rfid_control_panel_${Math.random().toString(16).substr(2, 8)}`
});

// Configurar el manejador de conexión MQTT
mqttClient.on('connect', () => {
    logger.mqtt('Node.js: Conectado exitosamente al Broker MQTT para comandos RFID.');
    io.emit('appStatus', { message: 'Node.js: Conectado al Broker MQTT.' });
    
    // Suscribirse a los topics necesarios
    mqttClient.subscribe([MQTT_COMMAND_TOPIC, MQTT_RESPONSE_TOPIC], { qos: 1 }, (err) => {
        if (err) {
            logger.error('Error al suscribirse a los topics MQTT:', err);
            io.emit('appError', { message: 'Node.js: Error crítico al suscribirse a los topics MQTT.' });
            return;
        }
        logger.mqtt(`Node.js: Suscrito a los topics MQTT: ${MQTT_COMMAND_TOPIC}, ${MQTT_RESPONSE_TOPIC}`);
        
        // Iniciar el monitoreo automático solo después de estar suscrito
        startAutoMonitor();
    });
    
    // Configurar el manejador de mensajes
    mqttClient.on('message', handleMqttMessage);
});

mqttClient.on('error', (err) => {
    logger.error('Node.js: Error de conexión MQTT (comandos RFID):', err);
    io.emit('appError', { message: `Node.js: Error de conexión MQTT: ${err.message}` });
});

mqttClient.on('reconnect', () => {
    logger.mqtt('Node.js: Reconectando al Broker MQTT (comandos RFID)...');
    io.emit('appStatus', { message: 'Node.js: Reconectando al Broker MQTT...' });
});

mqttClient.on('offline', () => {
    logger.mqtt('Node.js: Cliente MQTT desconectado (offline) (comandos RFID).');
    io.emit('appError', { message: 'Node.js: Desconectado del Broker MQTT.' });
});

mqttClient.on('close', () => {
    logger.mqtt('Node.js: Conexión MQTT cerrada (comandos RFID).');
    io.emit('appError', { message: 'Node.js: Conexión MQTT cerrada inesperadamente.' });
});

// Manejador de mensajes MQTT
const handleMqttMessage = (topic, messageBuffer) => {
    try {
        const message = messageBuffer.toString();
        let data;
        
        try {
            data = JSON.parse(message);
        } catch (e) {
            logger.mqtt(`Mensaje no es JSON válido: ${message}`, 'warning');
            return;
        }
        
        // Ignorar mensajes que son comandos reenviados
        if (data.command && (data.command.startsWith('RFID/') || data.command.startsWith('set'))) {
            logger.mqtt(`Comando '${data.command}' reenviado, ignorando...`);
            return;
        }
        
        logger.mqtt(`Mensaje MQTT recibido (comandos RFID) - Topic: ${topic}, Mensaje: ${message}`);
        
        // Procesar mensajes de estado del lector
        if (topic === MQTT_RESPONSE_TOPIC) {
            // Manejar respuesta de estado del lector
            if (data.resultCode === 0 && data.resultMsg) {
                let isReading = null;
                let status = 'online';
                let message = '';
                let shouldUpdateStatus = true;
                
                if (data.resultMsg.includes('get rfid status success')) {
                    isReading = data.resultData === true;
                    message = isReading ? 'Leyendo etiquetas' : 'Lector detenido';
                    logger.reader(`ESTADO LECTOR: ${isReading ? 'LEYENDO' : 'DETENIDO'}`);
                    
                    // Si el lector está apagado, intentar encenderlo
                    if (!isReading) {
                        logger.reader('El lector está detenido, verificando si necesitamos reiniciar...');
                        // Solo intentar reiniciar si no hemos intentado recientemente
                        const now = Date.now();
                        if (!lastRestartAttempt || (now - lastRestartAttempt) > 30000) { // 30 segundos entre intentos
                            lastRestartAttempt = now;
                            logger.reader('Intentando iniciar el lector...');
                            setRfidReading(true);
                        } else {
                            logger.reader('Reinicio reciente detectado, omitiendo reintento por ahora');
                        }
                    } else {
                        // Si el lector está leyendo, reiniciamos el contador de intentos
                        lastRestartAttempt = 0;
                    }
                } else if (data.resultMsg.includes('RFID/start:start success')) {
                    isReading = true;
                    message = 'Leyendo etiquetas';
                    logger.reader('ESTADO LECTOR: LEYENDO (confirmado por start)');
                    lastRestartAttempt = 0; // Resetear el contador de reintentos
                } else if (data.resultMsg.includes('RFID/stop:stop success')) {
                    isReading = false;
                    message = 'Lector detenido';
                    logger.reader('ESTADO LECTOR: DETENIDO (confirmado por stop)');
                }
                
                // Emitir el estado a los clientes web si se pudo determinar el estado
                if (isReading !== null) {
                    const statusUpdate = {
                        status: status,
                        isReading: isReading,
                        readerStatus: isReading ? 'LEYENDO' : 'DETENIDO',
                        timestamp: new Date().toISOString(),
                        message: message,
                        success: true,
                        rawMessage: data
                    };
                    
                    // Actualizar estado en caché
                    lastReaderStatus = isReading;
                    lastReaderCheck = Date.now();
                    
                    // Emitir solo un evento unificado
                    io.emit('rfidStatus', statusUpdate);
                    
                    // También emitir el mensaje crudo para depuración
                    io.emit('rawReaderStatus', data);
                    
                    return; // No continuar con el procesamiento adicional
                }
            }
        }
        
        // Skip empty messages
        if (!message || message.trim() === '') {
            logger.mqtt('Mensaje MQTT vacío recibido, ignorando...');
            return;
        }
        
        // Re-emitir el mensaje a los clientes WebSocket
        io.emit('rfidResponse', data);
        
        // Manejar diferentes tipos de respuestas
        if (data.resultMsg) {
            // Manejar estado del lector - múltiples formatos posibles
            if (data.resultMsg.includes("RFID/status:get rfid status success") || 
                data.resultMsg.includes("RFID/start:start success") ||
                data.resultMsg.includes("RFID/stop:stop success")) {
                
                logger.reader('Estado del lector recibido');
                // Determinar si está leyendo basado en el tipo de mensaje
                let isReading = false;
                if (data.resultMsg.includes("start success")) {
                    isReading = true;
                } else if (data.resultMsg.includes("stop success")) {
                    isReading = false;
                } else {
                    // Para otros mensajes, usar resultData si está disponible
                    isReading = data.resultData === true;
                }
                
                const statusText = isReading ? 'LEYENDO' : 'APAGADO';
                logger.reader(`ESTADO LECTOR: ${statusText}`);
                
                // Emitir el estado a los clientes conectados
                io.emit('rfidStatus', {
                    status: isReading ? 'reading' : 'stopped',
                    isReading: isReading,
                    timestamp: new Date().toISOString(),
                    readerStatus: statusText,
                    rawMessage: data // Incluir el mensaje completo para depuración
                });
                
                io.emit('rfidInfo', data);
            } else if (data.resultMsg.includes("RFID/getPower:get power success")) {
                io.emit('antennaPowerData', data);
            } else if (data.resultMsg.includes("RFID/setPower:set power success")) {
                io.emit('antennaSetPowerStatus', { success: true, data: data });
            } else if (data.resultMsg.includes("RFID/start:start success")) {
                io.emit('rfidInfo', data);
            } else if (data.resultMsg.includes("RFID/stop:stop success")) {
                io.emit('rfidInfo', data);
            } else if (data.resultCode !== 0) {
                io.emit('rfidError', data);
            } else {
                io.emit('rfidInfo', data);
            }
        } else if (data.command && data.command.startsWith("RFID/") && 
                 typeof data.resultCode === 'undefined') {
            logger.mqtt(`Comando '${data.command}' re-emitido, esperando respuesta real...`);
        } else {
            logger.mqtt("Mensaje MQTT (comandos RFID) sin 'resultMsg' y no reconocido:", data);
            io.emit('appWarning', { message: "Node.js: Respuesta RFID no reconocida.", details: data });
        }
    } catch (e) {
        logger.error('Error parseando JSON:', e);
        io.emit('appError', { message: 'Error procesando respuesta RFID (JSON inválido).', details: message });
    }
};

// --- Manejadores de eventos Socket.IO ---
// Manejar conexiones de clientes WebSocket
io.on('connection', (socket) => {
    logger.monitor(`Cliente web conectado: ${socket.id}`);
    
    // Enviar estado inicial al cliente recién conectado
    socket.emit('appStatus', { message: 'Conectado al servidor Node.js.' });
    socket.emit('autoMonitorStatus', { enabled: isAutoMonitorOn });
    
    // Si el auto-monitoreo está activo, forzar una verificación del estado
    if (isAutoMonitorOn) {
        checkReaderStatus().catch(logger.error);
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
        logger.monitor(`Petición 'getAntennaPower' de ${socket.id}`);
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
        logger.monitor(`Petición 'setAntennaPower' de ${socket.id}:`, allAntennaConfigs);
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
        logger.monitor(`Petición 'rfidStart' de ${socket.id}:`, startPayload);
        if (!mqttClient.connected) {
            socket.emit('appError', { message: 'Node.js no conectado al Broker MQTT.' });
            return;
        }
        mqttClient.publish(MQTT_COMMAND_TOPIC, JSON.stringify(startPayload), { qos: 1 }, (err) => {
            if (err) socket.emit('appError', { message: 'Error enviando Start al broker.' });
            else socket.emit('appStatus', { message: 'Comando Start enviado.' });
        });
    });

    socket.on('rfidStatus', async (statusPayload) => {
        try {
            logger.monitor(`Petición 'rfidStatus' de ${socket.id}:`, statusPayload);
            
            if (!mqttClient || !mqttClient.connected) {
                socket.emit('appError', { message: 'Node.js no conectado al Broker MQTT para comandos RFID.' });
                return;
            }

            // Validar el comando
            if (!statusPayload || statusPayload.command !== 'RFID/status') {
                const msg = `Comando '${statusPayload?.command || 'undefined'}' recibido, esperando respuesta...`;
                logger.mqtt(msg);
                socket.emit('appStatus', {
                    message: msg,
                    type: 'info',
                    timestamp: new Date().toISOString()
                });
                return;
            }

            // Verificar cooldown
            const now = Date.now();
            if (lastStatusCommand && (now - lastStatusCommand) < 1000) {
                const msg = 'Comando de estado ya enviado recientemente';
                logger.mqtt(msg);
                socket.emit('appStatus', {
                    message: msg,
                    type: 'info',
                    timestamp: new Date().toISOString()
                });
                return;
            }
            lastStatusCommand = now;

            // Crear una promesa para manejar la respuesta MQTT
            const checkStatus = () => {
                return new Promise((resolve) => {
                    const handlerId = `status_${Date.now()}`;
                    let resolved = false;
                    let timeoutId;

                    const cleanup = () => {
                        if (timeoutId) clearTimeout(timeoutId);
                        if (mqttMessageHandlers && mqttMessageHandlers.has(handlerId)) {
                            mqttMessageHandlers.delete(handlerId);
                        }
                    };

                    const handler = (topic, data) => {
                        if (resolved) return;
                        
                        if (data.resultCode === 0 && data.resultMsg && data.resultMsg.includes('get rfid status success')) {
                            resolved = true;
                            cleanup();
                            const isReading = data.resultData === true;
                            logger.reader(`ESTADO LECTOR: ${isReading ? 'LEYENDO' : 'DETENIDO'}`);
                            
                            // Actualizar caché
                            lastReaderStatus = isReading;
                            lastReaderCheck = now;
                            
                            resolve({
                                success: true,
                                isReading: isReading,
                                status: isReading ? 'reading' : 'stopped',
                                readerStatus: isReading ? 'LEYENDO' : 'DETENIDO',
                                rawMessage: data
                            });
                        }
                    };

                    // Configurar timeout
                    timeoutId = setTimeout(() => {
                        if (resolved) return;
                        resolved = true;
                        cleanup();
                        logger.reader('Timeout al verificar estado del lector');
                        resolve({
                            success: false,
                            message: 'Timeout al verificar el estado del lector',
                            status: 'error'
                        });
                    }, 15000); // 15 segundos de timeout

                    // Registrar el manejador
                    if (mqttMessageHandlers) {
                        mqttMessageHandlers.set(handlerId, handler);
                    } else {
                        cleanup();
                        resolve({
                            success: false,
                            message: 'Error interno: mqttMessageHandlers no está inicializado',
                            status: 'error'
                        });
                        return;
                    }

                    // Enviar comando de estado
                    mqttClient.publish(
                        MQTT_COMMAND_TOPIC,
                        JSON.stringify({ command: 'RFID/status' }),
                        { qos: 1, retain: false },
                        (err) => {
                            if (err) {
                                if (resolved) return;
                                resolved = true;
                                cleanup();
                                logger.error('Error enviando comando de estado:', err);
                                resolve({
                                    success: false,
                                    message: 'Error al enviar comando de estado',
                                    details: err.message,
                                    status: 'error'
                                });
                            } else {
                                logger.mqtt('Solicitud de estado enviada correctamente');
                            }
                        }
                    );
                });
            };

            // Ejecutar la verificación de estado
            const result = await checkStatus();
            
            // Si el lector está detenido, intentar iniciarlo
            if (result.success && !result.isReading) {
                logger.reader('Lector detenido, intentando iniciar...');
                await setRfidReading(true);
                // Esperar un momento y verificar de nuevo
                await new Promise(resolve => setTimeout(resolve, 1000));
                const newStatus = await checkStatus();
                if (newStatus.success) {
                    Object.assign(result, newStatus);
                }
            }

            // Emitir el estado actual al cliente que lo solicitó
            const response = {
                status: result.status || (result.isReading ? 'reading' : 'stopped'),
                isReading: !!result.isReading,
                timestamp: new Date().toISOString(),
                readerStatus: result.readerStatus || (result.isReading ? 'LEYENDO' : 'DETENIDO'),
                rawMessage: result.rawMessage || null,
                success: result.success !== false
            };

            logger.reader(`Enviando estado al cliente: ${JSON.stringify(response)}`);
            socket.emit('rfidStatus', response);
            
            // También emitir el mensaje crudo para depuración
            if (result.rawMessage) {
                socket.emit('rawReaderStatus', result.rawMessage);
            }
        } catch (error) {
            logger.error('Error en rfidStatus:', error);
            const errorResponse = { 
                status: 'error',
                message: 'Error al verificar el estado del lector',
                details: error.message || 'Error desconocido',
                timestamp: new Date().toISOString(),
                success: false,
                stack: process.env.NODE_ENV === 'development' ? error.stack : undefined
            };
            socket.emit('rfidStatus', errorResponse);
            socket.emit('appError', errorResponse);
        }
    });

    // Comandos para la Tarea MQTT del Lector (vía API HTTP del lector)
    socket.on('getReaderMqttSettings', async () => {
        logger.monitor(`Petición 'getReaderMqttSettings' (API Lector) de ${socket.id}`);
        try {
            const response = await axios.post(`${READER_API_BASE_URL}/getMQTTInfo`, {}, {
                timeout: 5000,
                validateStatus: null // Para manejar manualmente los códigos de estado
            });
            
            logger.monitor("Respuesta API Lector (getMQTTInfo):", response.data);
            
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
            logger.error('Error al obtener configuración MQTT:', error.message);
            
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
            
            logger.monitor('Usando configuración MQTT por defecto:', defaultConfig);
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
