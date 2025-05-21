// server.js
// Carga las variables de entorno desde el archivo '../.env'
require('dotenv').config({ path: '../.env' }); // Ajusta la ruta si es necesario

const express = require('express');
const https = require('https'); // MODIFICADO: Usar https en lugar de http
const fs = require('fs'); // AÑADIDO: Para leer archivos de certificado
const path = require('path'); // AÑADIDO: Para construir rutas a los certificados
const mqtt = require('mqtt');
const WebSocket = require('ws');
const mysql = require('mysql2/promise');

const app = express();

// --- ⚙️ Configuración SSL ---
// Asegúrate de que estos archivos existan en la ruta especificada.
// Puedes generarlos con OpenSSL. Ejemplo:
// openssl req -x509 -newkey rsa:2048 -keyout key.pem -out cert.pem -days 365 -nodes -subj "/CN=localhost"
// Coloca key.pem y cert.pem en la misma carpeta que server.js o ajusta la ruta.
const useHttps = process.env.USE_HTTPS === 'true'; // Variable de entorno para activar/desactivar HTTPS
let httpsOptions = {};
let serverModule = require('http'); // Por defecto http

if (useHttps) {
    try {
        const privateKeyPath = path.join(__dirname, process.env.SSL_KEY_PATH || 'key.pem');
        const certificatePath = path.join(__dirname, process.env.SSL_CERT_PATH || 'cert.pem');

        if (fs.existsSync(privateKeyPath) && fs.existsSync(certificatePath)) {
            httpsOptions = {
                key: fs.readFileSync(privateKeyPath),
                cert: fs.readFileSync(certificatePath)
            };
            serverModule = https; // Cambiar a https si los archivos existen
            console.log(`[${getCurrentTimestamp()}] INFO: Certificados SSL cargados. Usando HTTPS/WSS.`);
        } else {
            console.warn(`[${getCurrentTimestamp()}] WARNING: Archivos de certificado SSL no encontrados en las rutas esperadas. Revise SSL_KEY_PATH y SSL_CERT_PATH en .env o los nombres por defecto (key.pem, cert.pem). Usando HTTP/WS.`);
            useHttps = false; // Volver a HTTP si los certificados no se encuentran
        }
    } catch (err) {
        console.error(`[${getCurrentTimestamp()}] ERROR: Error al cargar los archivos SSL: ${err.message}. Usando HTTP/WS.`);
        useHttps = false; // Volver a HTTP en caso de error
    }
} else {
    console.log(`[${getCurrentTimestamp()}] INFO: USE_HTTPS no está configurado a 'true' en .env. Usando HTTP/WS.`);
}


// MODIFICADO: Crear servidor http o https según la configuración
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

// --- 🔄 Variables Globales Simplificadas ---
let mqttClientInstance;
let isMqttClientConnected = false;
let dbConnectionInstance;
let subscribedMqttTopics = [];
let antennaDataMap = {};
let intervalIds = []; 

// --- ⏰ Funciones de Utilidad ---
function getCurrentTimestamp() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

// --- 💾 Conexión a Base de Datos ---
async function connectToDatabase() {
    if (!DB_CONFIG_FROM_ENV.host) {
        console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_WARNING}: No se ha configurado DB_HOST. La suscripción dinámica a tópicos desde la BD no funcionará.`);
        return;
    }
    while (true) {
        try {
            dbConnectionInstance = await mysql.createConnection(DB_CONFIG_FROM_ENV);
            console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: ✅ Conectado a la base de datos`);
            dbConnectionInstance.on('error', async (err) => {
                console.error(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_ERROR}: ❌ Error en la conexión a la BD después de establecida: ${err.message}`);
                if (err.code === 'PROTOCOL_CONNECTION_LOST' || err.code === 'ECONNRESET' || err.code === 'ETIMEDOUT') {
                    console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: 🔄 Intentando reconectar a la base de datos...`);
                    await connectToDatabase();
                } else {
                    console.error(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_ERROR}: Error no recuperable en BD: ${err.message}`);
                }
            });
            return;
        } catch (error) {
            console.error(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_ERROR}: ❌ Error conectando a la base de datos: ${error.message}`);
            console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: 🔄 Reintentando conexión a BD en 10s...`);
            await new Promise(resolve => setTimeout(resolve, 10000));
        }
    }
}

// --- 🌐 Servidor WebSocket ---
// MODIFICADO: El servidor WebSocket se adjunta al servidor http o https
const wss = new WebSocket.Server({ server });


wss.on('connection', (ws) => {
    console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Cliente WebSocket conectado a la pasarela`);
    ws.send(JSON.stringify({ 
        type: 'initial_data', 
        topics: subscribedMqttTopics, 
        antennas: antennaDataMap,
        history: storedGatewayMessages 
    }));

    ws.on('message', (message) => {
        console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Mensaje recibido del cliente WebSocket: ${message.toString()}`);
    });
    ws.on('close', () => {
        console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Cliente WebSocket desconectado de la pasarela`);
    });
    ws.on('error', (error) => {
        console.error(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_ERROR}: Error en el cliente WebSocket de la pasarela: ${error}`);
    });
});

function broadcastToWebSockets(messagePayload) {
    if (wss.clients.size > 0) {
        wss.clients.forEach((client) => {
            if (client.readyState === WebSocket.OPEN) {
                try {
                    client.send(JSON.stringify(messagePayload));
                } catch (e) {
                    console.error(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_ERROR}: Error al enviar mensaje por WebSocket: ${e}`);
                }
            }
        });
    }
}

// --- 📞 Cliente y Lógica MQTT Simplificada ---
function connectMQTT() {
    if (!process.env.MQTT_SENSORICA_SERVER || !process.env.MQTT_SENSORICA_PORT) {
        console.error(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_ERROR}: ❌ Faltan variables de entorno MQTT_SENSORICA_SERVER o MQTT_SENSORICA_PORT. No se puede conectar a MQTT.`);
        return;
    }
    console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Intentando conectar al broker MQTT en ${MQTT_BROKER_URL_FROM_ENV}`);
    mqttClientInstance = mqtt.connect(MQTT_BROKER_URL_FROM_ENV, {
        clientId: `mqtt_visualizer_client_${Math.random().toString(16).substr(2, 8)}`,
        reconnectPeriod: 5000,
        clean: true
    });

    mqttClientInstance.on('connect', async () => {
        console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: ✅ Conectado a MQTT Server: ${MQTT_BROKER_URL_FROM_ENV}`);
        isMqttClientConnected = true;
        if (dbConnectionInstance) {
            await subscribeToTopicsFromDB();
        } else if (subscribedMqttTopics.length > 0) {
            mqttClientInstance.subscribe(subscribedMqttTopics, (err) => {
                if (err) console.error(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_ERROR}: Error al suscribirse a tópicos por defecto: ${subscribedMqttTopics}`, err);
                else console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Suscrito a tópicos por defecto: ${subscribedMqttTopics}`);
            });
        } else {
            console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: No hay tópicos configurados para suscribir (ni BD ni por defecto).`);
        }
    });

    mqttClientInstance.on('message', async (topic, message) => {
        const messageString = message.toString();
        const receivedAt = new Date().toISOString();
        let payloadObject;
        try {
            payloadObject = JSON.parse(messageString);
        } catch (e) {
            payloadObject = messageString;
        }

        const antennaName = antennaDataMap[topic]?.antenna_name || "Desconocida";
        const gatewayMessageData = {
            type: 'mqtt_message', 
            topic: topic,
            payload: payloadObject,
            antenna_name: antennaName,
            received_at: receivedAt
        };

        broadcastToWebSockets(gatewayMessageData);
        storedGatewayMessages.push(gatewayMessageData);
        if (storedGatewayMessages.length > MAX_STORED_GATEWAY_MESSAGES) {
            storedGatewayMessages.shift();
        }
    });

    mqttClientInstance.on('disconnect', () => console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: 🔴 Desconectado de MQTT`));
    mqttClientInstance.on('error', error => console.error(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_ERROR}: ❌ Error en MQTT: ${error.message}`));
    mqttClientInstance.on('reconnect', () => console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: ⚠️ Intentando reconectar a MQTT...`));
    mqttClientInstance.on('close', () => {
        console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Conexión MQTT cerrada.`);
        isMqttClientConnected = false;
    });
}

async function subscribeToTopicsFromDB() {
    if (!isMqttClientConnected || !dbConnectionInstance) {
        console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: No se puede suscribir a tópicos desde BD (MQTT o BD no conectado).`);
        return;
    }
    try {
        const [rows] = await dbConnectionInstance.execute(
            'SELECT mqtt_topic, name FROM rfid_ants WHERE mqtt_topic IS NOT NULL AND mqtt_topic != ""'
        );
        
        const newTopics = rows.map(row => row.mqtt_topic);
        const newAntennaDataMap = {};
        rows.forEach(row => {
            newAntennaDataMap[row.mqtt_topic] = { antenna_name: row.name };
        });
        
        const currentTopicsSet = new Set(subscribedMqttTopics);
        const newTopicsSet = new Set(newTopics);

        const topicsToUnsubscribe = subscribedMqttTopics.filter(t => !newTopicsSet.has(t));
        const topicsToSubscribe = newTopics.filter(t => !currentTopicsSet.has(t));

        if (topicsToUnsubscribe.length > 0) {
            mqttClientInstance.unsubscribe(topicsToUnsubscribe, (err) => {
                if (err) console.error(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_ERROR}: Error al desuscribirse: ${topicsToUnsubscribe}`, err);
                else console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Desuscrito de: ${topicsToUnsubscribe}`);
            });
        }

        if (topicsToSubscribe.length > 0) {
            mqttClientInstance.subscribe(topicsToSubscribe, (err) => {
                if (err) console.error(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_ERROR}: Error al suscribirse: ${topicsToSubscribe}`, err);
                else console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Suscrito a: ${topicsToSubscribe}`);
            });
        }
        
        const oldSubscribedTopicsCount = subscribedMqttTopics.length;
        subscribedMqttTopics = newTopics;
        antennaDataMap = newAntennaDataMap; 

        if (topicsToSubscribe.length > 0 || topicsToUnsubscribe.length > 0 || oldSubscribedTopicsCount !== subscribedMqttTopics.length) {
            console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: ✅ Gestión de tópicos completada. Suscritos: (${subscribedMqttTopics.length})`);
            broadcastToWebSockets({
                type: 'topics_update',
                topics: subscribedMqttTopics,
                antennas: antennaDataMap
            });
        }

    } catch (error) {
        console.error(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_ERROR}: ❌ Error al actualizar tópicos desde BD: ${error.message}`);
        if (error.code === 'PROTOCOL_CONNECTION_LOST' || error.fatal) {
            await connectToDatabase();
        }
    }
}

// --- 🌐 API HTTP de la Pasarela ---
app.get('/api/gateway-messages', (req, res) => {
    res.json(storedGatewayMessages);
});

app.get('/gateway-test', (req, res) => {
    // MODIFICADO: Determinar el protocolo WebSocket (ws o wss) dinámicamente
    const wsProtocol = useHttps ? 'wss' : 'ws';
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

                // MODIFICADO: Usar la variable wsProtocol para construir la URL
                const wsUrl = \`\${'${wsProtocol}'}://\` + window.location.host;
                const ws = new WebSocket(wsUrl); 

                let clientAllMessages = []; 
                let clientSelectedTopics = new Set();
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
                
                function populateTopicSelector(topics, antennas) {
                    topicListUl.innerHTML = ''; 
                    serverAntennaData = antennas || {};
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
                            if (message.topics && message.topics.length > 0) {
                                message.topics.forEach(t => clientSelectedTopics.add(t)); 
                            }
                            populateTopicSelector(message.topics || [], message.antennas || {});
                            updateMessagesDisplay();
                            connectionStatusDiv.textContent = \`Conectado. Mostrando \${clientAllMessages.filter(msg => clientSelectedTopics.size === 0 || clientSelectedTopics.has(msg.topic)).length} de \${clientAllMessages.length} mensajes históricos. \${message.topics ? message.topics.length : 0} tópicos.\`;
                        } else if (message.type === 'mqtt_message') {
                            clientAllMessages.push(message);
                            if (clientAllMessages.length > CLIENT_MAX_MESSAGES_DISPLAY * 2) { 
                                clientAllMessages.splice(0, clientAllMessages.length - (CLIENT_MAX_MESSAGES_DISPLAY * 2));
                            }
                            updateMessagesDisplay(); 
                        } else if (message.type === 'topics_update') {
                            populateTopicSelector(message.topics || [], message.antennas || {});
                            const newTopicSet = new Set(message.topics || []);
                            clientSelectedTopics.forEach(selectedTopic => {
                                if (!newTopicSet.has(selectedTopic)) {
                                    clientSelectedTopics.delete(selectedTopic); 
                                }
                            });
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
    console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Iniciando Pasarela MQTT-WebSocket (Visualizador)...`);
    await connectToDatabase();
    connectMQTT();
    
    if (dbConnectionInstance) {
        const topicSubscriptionInterval = setInterval(async () => { 
            if (isMqttClientConnected && dbConnectionInstance) await subscribeToTopicsFromDB();
        }, 60000);
        intervalIds.push(topicSubscriptionInterval); 
    }

    // MODIFICADO: Usar el servidor (http o https) para escuchar
    server.listen(WEB_SERVER_PORT, '0.0.0.0', () => {
        const protocol = useHttps ? 'https' : 'http';
        console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: ✅ Pasarela ${protocol.toUpperCase()} y WebSocket (${useHttps ? 'WSS' : 'WS'}) escuchando en ${protocol}://0.0.0.0:${WEB_SERVER_PORT}`);
        console.log(`   Accesible en tu red local vía ${protocol}://<IP_DE_TU_MAQUINA>:${WEB_SERVER_PORT}/gateway-test`);
    });
}

// --- Manejo de Cierre Elegante ---
let shuttingDown = false;
process.on('SIGINT', async () => {
    if (shuttingDown) {
        console.warn(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_WARNING}: Apagado ya en progreso. Forzando salida si es necesario...`);
        return; 
    }
    shuttingDown = true;
    console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Recibido SIGINT. Cerrando conexiones de la pasarela elegantemente...`);

    console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Limpiando ${intervalIds.length} intervalos programados...`);
    intervalIds.forEach(clearInterval);
    intervalIds = []; 

    const closePromises = [];

    if (server && server.listening) {
        console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Cerrando servidor ${useHttps ? 'HTTPS' : 'HTTP'}...`);
        closePromises.push(new Promise((resolve, reject) => {
            server.close((err) => {
                if (err) {
                    console.error(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_ERROR}: Error cerrando servidor ${useHttps ? 'HTTPS' : 'HTTP'}: ${err.message}`);
                    reject(err); // Mantener reject para que Promise.all lo capture
                } else {
                    console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Servidor ${useHttps ? 'HTTPS' : 'HTTP'} cerrado.`);
                    resolve();
                }
            });
        }));
    }
    
    if (wss) {
        console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Cerrando clientes WebSocket (${wss.clients.size})...`);
        wss.clients.forEach(client => {
            if (client.readyState === WebSocket.OPEN) {
                client.terminate(); 
            }
        });
        // El cierre del servidor WebSocket está ligado al servidor HTTP/HTTPS,
        // pero podemos añadir una promesa para su callback si es necesario, aunque wss.close() es síncrono en su llamada.
        // Para mayor robustez, se puede envolver en una promesa si se quiere asegurar el log.
        closePromises.push(new Promise((resolve) => {
            wss.close(() => { 
                console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Servidor WebSocket cerrado.`);
                resolve();
            });
        }));
    }

    if (mqttClientInstance) {
        console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Desconectando cliente MQTT...`);
        closePromises.push(new Promise((resolve) => { // No usar reject aquí para no detener Promise.all
            const mqttTimeout = setTimeout(() => {
                console.warn(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_WARNING}: Timeout al cerrar cliente MQTT.`);
                resolve(); 
            }, 3000); 

            mqttClientInstance.end(true, (error) => {
                clearTimeout(mqttTimeout);
                if (error) {
                    console.error(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_ERROR}: Error al desconectar cliente MQTT: ${error.message}`);
                } else {
                    console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Cliente MQTT desconectado.`);
                }
                resolve();
            });
        }));
    }

    if (dbConnectionInstance) {
        console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Cerrando conexión a la base de datos...`);
        closePromises.push(new Promise((resolve) => { // No usar reject aquí
            dbConnectionInstance.end(err => {
                if (err) {
                    console.error(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_ERROR}: Error al cerrar conexión DB: ${err.message}`);
                } else {
                    console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Conexión a base de datos cerrada.`);
                }
                resolve();
            });
        }));
    }

    try {
        // Esperar a que todas las promesas de cierre se completen (o fallen y se maneje el error)
        await Promise.all(closePromises.map(p => p.catch(e => {
            // Loguear el error individual de la promesa pero no dejar que detenga el Promise.all
            console.error(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_ERROR}: Error durante una operación de cierre:`, e.message || e);
            return e; // Devolver el error para que Promise.all no lo trate como no resuelto
        })));
        console.log(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_INFO}: Todas las operaciones de cierre intentadas. Saliendo.`);
        process.exit(0);
    } catch (error) { // Este catch es por si Promise.all mismo lanza un error (poco probable con .map(p => p.catch))
        console.error(`[${getCurrentTimestamp()}] ${ENVIRONMENT}.${LOG_LEVEL_ERROR}: Error crítico durante el proceso de apagado: ${error.message}`);
        process.exit(1); 
    }
});


startGateway();

