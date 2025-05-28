// server.js
// Carga las variables de entorno desde el archivo '../.env'
require('dotenv').config({ path: '../.env' }); // Ajusta la ruta si es necesario

const express = require('express');
const https = require('https');
const fs = require('fs');
const path = require('path');
const mqtt = require('mqtt');
const WebSocket = require('ws');
const mysql = require('mysql2/promise');

const app = express();

// --- ⚙️ Configuración SSL ---
const useHttps = process.env.USE_HTTPS === 'true';
let httpsOptions = {};
let serverModule = require('http');

if (useHttps) {
    try {
        const privateKeyPath = path.join(__dirname, process.env.SSL_KEY_PATH || 'key.pem');
        const certificatePath = path.join(__dirname, process.env.SSL_CERT_PATH || 'cert.pem');

        if (fs.existsSync(privateKeyPath) && fs.existsSync(certificatePath)) {
            httpsOptions = {
                key: fs.readFileSync(privateKeyPath),
                cert: fs.readFileSync(certificatePath)
            };
            serverModule = https;
            console.log(`[${getCurrentTimestamp()}] INFO: Certificados SSL cargados. Usando HTTPS/WSS.`);
        } else {
            console.warn(`[${getCurrentTimestamp()}] WARNING: Archivos de certificado SSL no encontrados. Usando HTTP/WS.`);
            useHttps = false;
        }
    } catch (err) {
        console.error(`[${getCurrentTimestamp()}] ERROR: Error al cargar los archivos SSL: ${err.message}. Usando HTTP/WS.`);
        useHttps = false;
    }
} else {
    console.log(`[${getCurrentTimestamp()}] INFO: USE_HTTPS no está 'true' en .env. Usando HTTP/WS.`);
}

const server = serverModule.createServer(useHttps ? httpsOptions : {}, app);

// --- ⚙️ Configuración General ---
const MQTT_BROKER_URL_FROM_ENV = `mqtt://${process.env.MQTT_SENSORICA_SERVER}:${process.env.MQTT_SENSORICA_PORT}`;
const DB_CONFIG_FROM_ENV = {
    host: process.env.DB_HOST,
    port: process.env.DB_PORT || 3306,
    user: process.env.DB_USERNAME,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_DATABASE
};
const WEB_SERVER_PORT = process.env.MQTT_GATEWAY_PORT || 4003;
const ENVIRONMENT = process.env.APP_ENV || 'production';
const LOG_LEVEL_INFO = 'INFO';
const LOG_LEVEL_ERROR = 'ERROR';
const LOG_LEVEL_WARNING = 'WARNING';
const MAX_STORED_GATEWAY_MESSAGES = 100;
let storedGatewayMessages = [];

// --- 🔄 Variables Globales ---
let mqttClientInstance;
let isMqttClientConnected = false;
let dbConnectionInstance;
let subscribedMqttTopics = []; // Lista de strings de tópicos a los que el servidor MQTT está suscrito
let antennaDataMap = {}; // Mapeo de topic a { antenna_name: 'Nombre Antena' }
let intervalIds = [];

// --- ⏰ Funciones de Utilidad ---
function getCurrentTimestamp() {
    const now = new Date();
    return now.toISOString().replace('T', ' ').substring(0, 19);
}

// --- 💾 Conexión a Base de Datos ---
async function connectToDatabase() {
    if (!DB_CONFIG_FROM_ENV.host) {
        console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_WARNING}: No DB_HOST. Suscripción dinámica desde BD no funcionará.`);
        return;
    }
    while (true) {
        try {
            dbConnectionInstance = await mysql.createConnection(DB_CONFIG_FROM_ENV);
            console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: ✅ Conectado a la base de datos`);
            dbConnectionInstance.on('error', async (err) => {
                console.error(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_ERROR}: ❌ Error en BD: ${err.message}`);
                if (err.code === 'PROTOCOL_CONNECTION_LOST' || err.code === 'ECONNRESET' || err.code === 'ETIMEDOUT') {
                    console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: 🔄 Reconectando a BD...`);
                    await connectToDatabase(); // Intenta reconectar
                } else {
                    // Errores no recuperables, podría ser necesario reiniciar o investigar
                }
            });
            return; // Salir del bucle si la conexión es exitosa
        } catch (error) {
            console.error(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_ERROR}: ❌ Error conectando a BD: ${error.message}`);
            console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: 🔄 Reintentando conexión BD en 10s...`);
            await new Promise(resolve => setTimeout(resolve, 10000));
        }
    }
}

// --- 🌐 Servidor WebSocket ---
const wss = new WebSocket.Server({ server });

wss.on('connection', (ws) => {
    console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Cliente WebSocket conectado.`);
    // Enviar datos iniciales: historial de mensajes y la lista actual de tópicos/antenas
    // antennaDataMap ya tiene la forma { 'topic1': { antenna_name: 'AntenaX' }, ... }
    // Necesitamos transformarlo a un array de objetos para topics_info si es necesario,
    // o simplemente enviar los tópicos suscritos y el mapa de antenas.
    // La vista original de Node usaba `subscribedMqttTopics` y `antennaDataMap`.
    ws.send(JSON.stringify({
        type: 'initial_data',
        topics: subscribedMqttTopics, // Array de strings de tópicos
        antennas: antennaDataMap,     // Objeto { topic: { antenna_name: '...' } }
        history: storedGatewayMessages
    }));

    ws.on('message', (message) => {
        console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Mensaje WS: ${message.toString()}`);
    });
    ws.on('close', () => {
        console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Cliente WebSocket desconectado.`);
    });
    ws.on('error', (error) => {
        console.error(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_ERROR}: Error WS: ${error}`);
    });
});

function broadcastToWebSockets(messagePayload) {
    if (wss.clients.size > 0) {
        wss.clients.forEach((client) => {
            if (client.readyState === WebSocket.OPEN) {
                try {
                    client.send(JSON.stringify(messagePayload));
                } catch (e) {
                    console.error(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_ERROR}: Error broadcast WS: ${e}`);
                }
            }
        });
    }
}

// --- 📞 Cliente y Lógica MQTT ---
function connectMQTT() {
    if (!process.env.MQTT_SENSORICA_SERVER || !process.env.MQTT_SENSORICA_PORT) {
        console.error(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_ERROR}: ❌ Faltan variables MQTT. No se puede conectar.`);
        return;
    }
    console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Conectando a MQTT: ${MQTT_BROKER_URL_FROM_ENV}`);
    mqttClientInstance = mqtt.connect(MQTT_BROKER_URL_FROM_ENV, {
        clientId: `mqtt_gateway_client_${Math.random().toString(16).substr(2, 8)}`,
        reconnectPeriod: 5000,
        clean: true
    });

    mqttClientInstance.on('connect', async () => {
        console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: ✅ Conectado a MQTT Server.`);
        isMqttClientConnected = true;
        if (dbConnectionInstance) {
            await subscribeToTopicsFromDB(); // Esto actualiza subscribedMqttTopics y antennaDataMap
        } else if (subscribedMqttTopics.length > 0) { // Fallback si no hay BD pero hay tópicos hardcodeados (no es el caso actual)
            mqttClientInstance.subscribe(subscribedMqttTopics, (err) => {
                if (err) console.error(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_ERROR}: Error suscribiendo tópicos:`, err);
                else console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Suscrito a tópicos: ${subscribedMqttTopics}`);
            });
        }
    });

    mqttClientInstance.on('message', async (topic, message) => {
        const messageString = message.toString();
        const receivedAt = new Date().toISOString();
        let payloadObject;
        try {
            payloadObject = JSON.parse(messageString);
        } catch (e) {
            payloadObject = messageString; // Guardar como string si no es JSON válido
        }

        // Usar antennaDataMap para obtener el nombre de la antena
        const antennaName = antennaDataMap[topic]?.antenna_name || "Desconocida";
        const gatewayMessageData = {
            type: 'mqtt_message',
            topic: topic,
            payload: payloadObject,
            antenna_name: antennaName, // Añadido el nombre de la antena
            received_at: receivedAt
        };

        broadcastToWebSockets(gatewayMessageData);
        storedGatewayMessages.push(gatewayMessageData);
        if (storedGatewayMessages.length > MAX_STORED_GATEWAY_MESSAGES) {
            storedGatewayMessages.shift();
        }
    });

    mqttClientInstance.on('disconnect', () => console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: 🔴 Desconectado de MQTT`));
    mqttClientInstance.on('error', error => console.error(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_ERROR}: ❌ Error MQTT: ${error.message}`));
    mqttClientInstance.on('reconnect', () => console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: ⚠️ Reconectando a MQTT...`));
    mqttClientInstance.on('close', () => {
        console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Conexión MQTT cerrada.`);
        isMqttClientConnected = false;
    });
}

async function subscribeToTopicsFromDB() {
    if (!isMqttClientConnected || !dbConnectionInstance) {
        console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: No se puede suscribir desde BD (MQTT o BD no conectado).`);
        return;
    }
    try {
        const [rows] = await dbConnectionInstance.execute(
            'SELECT mqtt_topic, name FROM rfid_ants WHERE mqtt_topic IS NOT NULL AND mqtt_topic != ""'
        );
        
        const newTopicsFromDB = rows.map(row => row.mqtt_topic); // Array de strings de tópicos
        const newAntennaDataMap = {}; // Objeto { topic: { antenna_name: 'Name' } }
        rows.forEach(row => {
            newAntennaDataMap[row.mqtt_topic] = { antenna_name: row.name };
        });
        
        const currentSubscribedSet = new Set(subscribedMqttTopics);
        const newTopicsSet = new Set(newTopicsFromDB);

        const topicsToUnsubscribe = subscribedMqttTopics.filter(t => !newTopicsSet.has(t));
        const topicsToSubscribe = newTopicsFromDB.filter(t => !currentSubscribedSet.has(t));

        if (topicsToUnsubscribe.length > 0) {
            mqttClientInstance.unsubscribe(topicsToUnsubscribe, (err) => {
                if (err) console.error(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_ERROR}: Error desuscribiendo: ${topicsToUnsubscribe}`, err);
                else console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Desuscrito de: ${topicsToUnsubscribe}`);
            });
        }

        if (topicsToSubscribe.length > 0) {
            mqttClientInstance.subscribe(topicsToSubscribe, (err) => {
                if (err) console.error(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_ERROR}: Error suscribiendo: ${topicsToSubscribe}`, err);
                else console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Suscrito a: ${topicsToSubscribe}`);
            });
        }
        
        const oldSubscribedTopicsCount = subscribedMqttTopics.length;
        subscribedMqttTopics = newTopicsFromDB; // Actualizar la lista global de tópicos suscritos
        antennaDataMap = newAntennaDataMap; // Actualizar el mapa global de datos de antena

        if (topicsToSubscribe.length > 0 || topicsToUnsubscribe.length > 0 || oldSubscribedTopicsCount !== subscribedMqttTopics.length) {
            console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: ✅ Gestión de tópicos completada. Suscritos: (${subscribedMqttTopics.length})`);
            // Enviar actualización a los clientes WebSocket
            broadcastToWebSockets({
                type: 'topics_update',
                topics: subscribedMqttTopics, // Array de strings de tópicos
                antennas: antennaDataMap     // Objeto { topic: { antenna_name: '...' } }
            });
        }

    } catch (error) {
        console.error(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_ERROR}: ❌ Error actualizando tópicos desde BD: ${error.message}`);
        if (error.code === 'PROTOCOL_CONNECTION_LOST' || error.fatal) { // Si el error es por pérdida de conexión
            await connectToDatabase(); // Intenta reconectar
        }
    }
}

// --- 🌐 API HTTP de la Pasarela ---
// MODIFICADO: Ahora este endpoint también devolverá la información de los tópicos
app.get('/api/gateway-messages', async (req, res) => {
    let topicsInfo = []; // Formato: [{ topic: "topic_string", antenna_name: "Antenna Name" }, ...]

    if (dbConnectionInstance) {
        try {
            // Usar antennaDataMap que ya se actualiza periódicamente y al inicio
            // Esto evita una consulta a la BD en cada request a esta API,
            // asumiendo que subscribeToTopicsFromDB() mantiene antennaDataMap actualizado.
            // Transformar antennaDataMap al formato deseado para topics_info
            for (const topic in antennaDataMap) {
                topicsInfo.push({
                    topic: topic,
                    antenna_name: antennaDataMap[topic].antenna_name
                });
            }
            // Opcionalmente, si se prefiere consultar la BD en cada llamada a esta API (más costoso):
            /*
            const [rows] = await dbConnectionInstance.execute(
                'SELECT mqtt_topic, name FROM rfid_ants WHERE mqtt_topic IS NOT NULL AND mqtt_topic != "" ORDER BY name'
            );
            topicsInfo = rows.map(row => ({
                topic: row.mqtt_topic,
                antenna_name: row.name
            }));
            */
        } catch (dbError) {
            console.error(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_ERROR}: Error consultando tópicos para API: ${dbError.message}`);
            // topicsInfo permanecerá vacío, el endpoint devolverá mensajes sin info de tópicos o un error.
            // Considerar devolver un error 500 si la info de tópicos es crítica para este endpoint.
        }
    } else {
        console.warn(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_WARNING}: No hay conexión a BD para obtener tópicos en /api/gateway-messages.`);
    }

    res.json({
        messages: storedGatewayMessages,
        topics_info: topicsInfo 
    });
});

app.get('/gateway-test', (req, res) => {
    const wsProtocol = useHttps ? 'wss' : 'ws';
    // El HTML de /gateway-test se mantiene igual, ya que su JS se conecta por WebSocket
    // y recibe los tópicos a través del mensaje 'initial_data' y 'topics_update'.
    res.send(`
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Visor de Mensajes MQTT</title>
            <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
            <style>
                :root {
                    --primary-color: #2c3e50; 
                    --secondary-color: #3498db; 
                    --background-color: #ecf0f1; 
                    --surface-color: #ffffff; 
                    --text-color: #34495e; 
                    --text-light-color: #7f8c8d; 
                    --border-color: #bdc3c7; 
                    --success-bg: #d4edda;
                    --success-text: #155724;
                    --error-bg: #f8d7da;
                    --error-text: #721c24;
                    --warning-bg: #fff3cd;
                    --warning-text: #856404;
                    --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                    --border-radius: 8px;
                }
                body { 
                    font-family: 'Roboto', Arial, sans-serif; 
                    margin: 0; 
                    display: flex; 
                    flex-direction: column; 
                    height: 100vh; 
                    background-color: var(--background-color); 
                    color: var(--text-color);
                    line-height: 1.6;
                }
                header { 
                    background-color: var(--primary-color); 
                    color: white; 
                    padding: 18px 25px; 
                    text-align: center; 
                    box-shadow: var(--box-shadow); 
                    font-size: 1.4em;
                    font-weight: 500;
                }
                .container { 
                    display: flex; 
                    flex: 1; 
                    overflow: hidden; 
                    padding: 15px; 
                    gap: 15px;
                }
                #sidebar { 
                    width: 300px; 
                    background-color: var(--surface-color); 
                    padding: 20px; 
                    border-radius: var(--border-radius); 
                    box-shadow: var(--box-shadow); 
                    display: flex; 
                    flex-direction: column; 
                    overflow-y: auto; 
                }
                #sidebar h2 { 
                    margin-top: 0; 
                    font-size: 1.3em; 
                    color: var(--primary-color); 
                    border-bottom: 2px solid var(--secondary-color); 
                    padding-bottom: 12px;
                    font-weight: 500;
                }
                #topic-controls { 
                    margin-bottom: 20px; 
                    display: flex; 
                    gap: 12px; 
                }
                #topic-controls button { 
                    padding: 10px 15px; 
                    border: none; 
                    border-radius: var(--border-radius); 
                    cursor: pointer; 
                    background-color: var(--secondary-color); 
                    color: white; 
                    font-size: 0.95em;
                    transition: background-color 0.2s ease;
                    font-weight: 500;
                }
                #topic-controls button:hover { 
                    background-color: #2980b9; 
                }
                #topic-list { 
                    list-style: none; 
                    padding: 0; 
                    margin: 0; 
                    flex-grow: 1; 
                    overflow-y: auto; 
                }
                #topic-list li { 
                    margin-bottom: 10px; 
                    display: flex; 
                    align-items: center;
                    padding: 8px;
                    border-radius: 4px;
                    transition: background-color 0.2s ease;
                }
                #topic-list li:hover {
                    background-color: #f8f9fa; 
                }
                #topic-list input[type="checkbox"] { 
                    margin-right: 10px; 
                    transform: scale(1.1); 
                    cursor: pointer;
                }
                #topic-list label { 
                    font-size: 1em; 
                    color: var(--text-color); 
                    cursor: pointer; 
                    word-break: break-all; 
                }
                #main-content { 
                    flex: 1; 
                    background-color: var(--surface-color); 
                    padding: 20px; 
                    border-radius: var(--border-radius); 
                    box-shadow: var(--box-shadow); 
                    display: flex; 
                    flex-direction: column; 
                    overflow: hidden;
                }
                .status { 
                    text-align: center; 
                    padding: 12px; 
                    border-radius: var(--border-radius); 
                    margin-bottom:18px; 
                    font-size: 0.95em;
                    font-weight: 500;
                    border: 1px solid transparent;
                }
                .status.connected { background-color: var(--success-bg); color: var(--success-text); border-color: var(--success-text); }
                .status.disconnected { background-color: var(--error-bg); color: var(--error-text); border-color: var(--error-text); }
                .status.error { background-color: var(--warning-bg); color: var(--warning-text); border-color: var(--warning-text); }

                #messages { 
                    flex-grow: 1; 
                    overflow-y: auto; 
                    list-style: none; 
                    padding: 0; 
                    margin: 0; 
                    border-top: 1px solid var(--border-color);
                }
                #messages li { 
                    padding: 15px; 
                    border-bottom: 1px solid var(--border-color); 
                    word-wrap: break-word; 
                }
                #messages li:last-child { 
                    border-bottom: none; 
                }
                .message-header { 
                    font-weight: 500; 
                    color: var(--primary-color); 
                    font-size: 1em; 
                    margin-bottom: 5px;
                }
                .message-payload { 
                    margin-left: 0; 
                    white-space: pre-wrap; 
                    font-size: 0.9em; 
                    background-color: #f9f9f9; 
                    padding: 10px; 
                    border-radius: 4px; 
                    margin-top: 8px;
                    border: 1px solid #e9e9e9;
                    font-family: 'Courier New', Courier, monospace; 
                }
                .message-meta { 
                    font-size: 0.8em; 
                    color: var(--text-light-color); 
                    margin-top: 8px; 
                    text-align: right;
                }
                ::-webkit-scrollbar { width: 8px; }
                ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
                ::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 10px; }
                ::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
            </style>
        </head>
        <body>
            <header>Visor de Mensajes MQTT en Tiempo Real</header>
            <div class="container">
                <aside id="sidebar">
                    <h2>Tópicos Suscritos</h2>
                    <div id="topic-controls">
                        <button id="selectAllTopics">Todos</button>
                        <button id="deselectAllTopics">Ninguno</button>
                    </div>
                    <ul id="topic-list"></ul>
                </aside>
                <main id="main-content">
                    <div class="status" id="connectionStatus">Conectando al servidor WebSocket...</div>
                    <ul id="messages"></ul>
                </main>
            </div>

            <script>
                const messagesUl = document.getElementById('messages');
                const connectionStatusDiv = document.getElementById('connectionStatus');
                const topicListUl = document.getElementById('topic-list');
                const selectAllButton = document.getElementById('selectAllTopics');
                const deselectAllButton = document.getElementById('deselectAllTopics');

                const wsUrl = \`\${'${wsProtocol}'}://\` + window.location.host;
                const ws = new WebSocket(wsUrl); 

                let clientAllMessages = []; 
                let clientSelectedTopics = new Set();
                // serverAntennaData almacenará el mapeo de topic a nombre de antena
                // recibido del servidor (formato: { 'topic1': { antenna_name: 'AntenaX' }, ... })
                let serverAntennaData = {}; 
                const CLIENT_MAX_MESSAGES_DISPLAY = ${MAX_STORED_GATEWAY_MESSAGES}; 

                function updateMessagesDisplay() {
                    const previouslyScrolledToBottom = messagesUl.scrollHeight - messagesUl.scrollTop <= messagesUl.clientHeight + 10; 
                    
                    messagesUl.innerHTML = ''; 
                    const filteredMessages = clientAllMessages.filter(msg => 
                        clientSelectedTopics.size === 0 || clientSelectedTopics.has(msg.topic)
                    );
                    const messagesToDisplay = filteredMessages.slice(-CLIENT_MAX_MESSAGES_DISPLAY);
                    messagesToDisplay.forEach(addMessageToPageInternal); 
                    
                    if (previouslyScrolledToBottom) { 
                        messagesUl.scrollTop = messagesUl.scrollHeight;
                    }
                }

                function createMessageListItem(messageData) {
                    const item = document.createElement('li');
                    let payloadDisplay = messageData.payload;
                    if (typeof messageData.payload === 'object') {
                        payloadDisplay = JSON.stringify(messageData.payload, null, 2); 
                    } else { 
                        const tempDiv = document.createElement('div');
                        tempDiv.textContent = payloadDisplay;
                        payloadDisplay = tempDiv.innerHTML;
                    }
                    // El nombre de la antena ya viene en messageData.antenna_name
                    item.innerHTML = \`
                        <div class="message-header">Tópico: \${messageData.topic} (Antena: \${messageData.antenna_name || 'N/A'})</div>
                        <div class="message-payload">\${payloadDisplay}</div>
                        <div class="message-meta">Recibido: \${new Date(messageData.received_at).toLocaleString('es-ES', { dateStyle: 'short', timeStyle: 'medium' })}</div>
                    \`;
                    return item;
                }

                function addMessageToPageInternal(messageData) {
                    const listItem = createMessageListItem(messageData);
                    messagesUl.appendChild(listItem);
                }
                
                // topics es un array de strings, antennas es un objeto { topic: { antenna_name: '...' } }
                function populateTopicSelector(topics, antennas) {
                    topicListUl.innerHTML = ''; 
                    serverAntennaData = antennas || {}; // Guardar el mapa de antenas
                    const sortedTopics = [...topics].sort(); 
                    
                    sortedTopics.forEach(topic => { 
                        const listItem = document.createElement('li');
                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.id = 'topic-' + topic.replace(/[^a-zA-Z0-9_\\-\\/]/g, "_"); 
                        checkbox.value = topic;
                        checkbox.checked = clientSelectedTopics.has(topic); 
                        
                        checkbox.addEventListener('change', (event) => {
                            if (event.target.checked) {
                                clientSelectedTopics.add(topic);
                            } else {
                                clientSelectedTopics.delete(topic);
                            }
                            updateMessagesDisplay();
                        });

                        const label = document.createElement('label');
                        label.htmlFor = checkbox.id;
                        // Obtener el nombre de la antena del mapa serverAntennaData
                        const antennaName = serverAntennaData[topic]?.antenna_name || 'N/A';
                        label.textContent = \`\${topic} (\${antennaName})\`;
                        
                        listItem.appendChild(checkbox);
                        listItem.appendChild(label);
                        topicListUl.appendChild(listItem);
                    });
                }

                selectAllButton.addEventListener('click', () => {
                    topicListUl.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                        cb.checked = true;
                        clientSelectedTopics.add(cb.value);
                    });
                    updateMessagesDisplay();
                });

                deselectAllButton.addEventListener('click', () => {
                    topicListUl.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                        cb.checked = false;
                    });
                    clientSelectedTopics.clear();
                    updateMessagesDisplay();
                });

                ws.onopen = () => {
                    console.log('Conectado al servidor WebSocket');
                    connectionStatusDiv.textContent = 'Conectado al Servidor WebSocket';
                    connectionStatusDiv.className = 'status connected';
                };

                ws.onmessage = (event) => {
                    try {
                        const message = JSON.parse(event.data);
                        if (message.type === 'initial_data') {
                            clientAllMessages = message.history || [];
                            // message.topics es un array de strings
                            // message.antennas es un objeto { topic: { antenna_name: '...' } }
                            if (message.topics && message.topics.length > 0) {
                                message.topics.forEach(t => clientSelectedTopics.add(t)); 
                            }
                            populateTopicSelector(message.topics || [], message.antennas || {});
                            updateMessagesDisplay();
                            let topicsCount = message.topics ? message.topics.length : 0;
                            connectionStatusDiv.textContent = \`Conectado. Mostrando \${clientAllMessages.filter(msg => clientSelectedTopics.size === 0 || clientSelectedTopics.has(msg.topic)).length} de \${clientAllMessages.length} mensajes. \${topicsCount} tópicos.\`;
                        } else if (message.type === 'mqtt_message') {
                            clientAllMessages.push(message);
                            if (clientAllMessages.length > CLIENT_MAX_MESSAGES_DISPLAY * 2) { 
                                clientAllMessages.splice(0, clientAllMessages.length - (CLIENT_MAX_MESSAGES_DISPLAY * 2));
                            }
                            updateMessagesDisplay(); 
                        } else if (message.type === 'topics_update') {
                            // message.topics es un array de strings
                            // message.antennas es un objeto { topic: { antenna_name: '...' } }
                            populateTopicSelector(message.topics || [], message.antennas || {});
                            const newTopicSet = new Set(message.topics || []);
                            clientSelectedTopics.forEach(selectedTopic => {
                                if (!newTopicSet.has(selectedTopic)) {
                                    clientSelectedTopics.delete(selectedTopic); 
                                }
                            });
                            // Asegurar que los checkboxes reflejen la selección actual
                            topicListUl.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                                cb.checked = clientSelectedTopics.has(cb.value);
                            });
                            updateMessagesDisplay(); 
                        }
                    } catch (e) {
                        console.error('Error parseando mensaje del servidor o formato inesperado:', e, event.data);
                    }
                };

                ws.onclose = () => {
                    console.log('Desconectado del servidor WebSocket');
                    connectionStatusDiv.textContent = 'Desconectado. Intentando reconectar...';
                    connectionStatusDiv.className = 'status disconnected';
                };

                ws.onerror = (error) => {
                    console.error('Error de WebSocket:', error);
                    connectionStatusDiv.textContent = 'Error de Conexión WebSocket.';
                    connectionStatusDiv.className = 'status error';
                };
            </script>
        </body>
        </html>
    `);
});

// --- 🚀 Función Principal de Arranque ---
async function startGateway() {
    console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Iniciando Pasarela...`);
    await connectToDatabase(); // Asegura que la BD esté conectada antes de continuar
    connectMQTT(); // Inicia la conexión MQTT (que a su vez llamará a subscribeToTopicsFromDB)
    
    // Intervalo para actualizar suscripciones de tópicos desde la BD
    if (dbConnectionInstance) { // Solo si la conexión a BD fue exitosa inicialmente
        const topicSubscriptionInterval = setInterval(async () => { 
            if (isMqttClientConnected && dbConnectionInstance) { // Doble chequeo
                 await subscribeToTopicsFromDB();
            }
        }, 60000); // Cada 60 segundos
        intervalIds.push(topicSubscriptionInterval); 
    }

    server.listen(WEB_SERVER_PORT, '0.0.0.0', () => {
        const protocol = useHttps ? 'https' : 'http';
        console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: ✅ Pasarela ${protocol.toUpperCase()} y WebSocket (${useHttps ? 'WSS' : 'WS'}) en ${protocol}://0.0.0.0:${WEB_SERVER_PORT}`);
        console.log(`   Test en: ${protocol}://<IP_DE_TU_MAQUINA>:${WEB_SERVER_PORT}/gateway-test`);
    });
}

// --- Manejo de Cierre Elegante ---
let shuttingDown = false;
async function gracefulShutdown() {
    if (shuttingDown) {
        console.warn(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_WARNING}: Apagado ya en progreso.`);
        return; 
    }
    shuttingDown = true;
    console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Recibido SIGINT/SIGTERM. Cerrando conexiones...`);

    intervalIds.forEach(clearInterval);
    intervalIds = []; 
    console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Intervalos limpiados.`);

    const closePromises = [];

    if (wss) {
        console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Cerrando clientes WebSocket (${wss.clients.size})...`);
        wss.clients.forEach(client => client.terminate());
        closePromises.push(new Promise((resolve) => wss.close(() => {
            console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Servidor WebSocket cerrado.`);
            resolve();
        })));
    }
    
    // El servidor HTTP/HTTPS debe cerrarse después del WebSocket server que depende de él.
    if (server && server.listening) {
        console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Cerrando servidor ${useHttps ? 'HTTPS' : 'HTTP'}...`);
        closePromises.push(new Promise((resolve, reject) => {
            server.close((err) => {
                if (err) {
                    console.error(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_ERROR}: Error cerrando servidor: ${err.message}`);
                    return reject(err);
                }
                console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Servidor ${useHttps ? 'HTTPS' : 'HTTP'} cerrado.`);
                resolve();
            });
        }));
    }


    if (mqttClientInstance && mqttClientInstance.connected) {
        console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Desconectando cliente MQTT...`);
        closePromises.push(new Promise((resolve) => {
            mqttClientInstance.end(false, () => { // false para no forzar, permitir que envíe mensajes pendientes
                console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Cliente MQTT desconectado.`);
                resolve();
            });
        }));
    } else if (mqttClientInstance) {
         console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Cliente MQTT ya desconectado o no conectado.`);
    }


    if (dbConnectionInstance) {
        console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Cerrando conexión BD...`);
        closePromises.push(dbConnectionInstance.end().then(() => {
            console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Conexión BD cerrada.`);
        }).catch(err => {
            console.error(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_ERROR}: Error cerrando BD: ${err.message}`);
        }));
    }

    try {
        await Promise.all(closePromises.map(p => p.catch(e => console.error(`Error en cierre: ${e.message || e}`))));
        console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Todas las operaciones de cierre completadas. Saliendo.`);
        process.exit(0);
    } catch (error) {
        console.error(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_ERROR}: Error crítico durante apagado: ${error.message}`);
        process.exit(1); 
    }
}

process.on('SIGINT', gracefulShutdown);
process.on('SIGTERM', gracefulShutdown);

startGateway();
