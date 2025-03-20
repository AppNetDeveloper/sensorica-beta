require('dotenv').config({ path: '../.env' });
const mqtt = require('mqtt');
const mysql = require('mysql2/promise');
const axios = require('axios');
const { promisify } = require('util');
const setIntervalAsync = promisify(setInterval);

const mqttServer = process.env.MQTT_SENSORICA_SERVER;
const mqttPort = process.env.MQTT_SENSORICA_PORT;
const dbConfig = {
    host: process.env.DB_HOST,
    port: process.env.DB_PORT,
    user: process.env.DB_USERNAME,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_DATABASE
};

let mqttClient;
let isMqttConnected = false;
let dbConnection;
let subscribedTopics = [];
let valueCounters = {};
let blockedEPCs = new Set();  // Guardamos EPC bloqueados en memoria
let ignoredTIDs = new Map();  // TIDs ignorados temporalmente (clave = TID, valor = timestamp de expiración)
let antennaData = {};  // Almacena mqtt_topic -> { rssi_min, antenna_name }

// Obtener la fecha y hora en formato YYYY-MM-DD HH:mm:ss
function getCurrentTimestamp() {
    return new Date().toLocaleString('en-GB', { timeZone: 'UTC' }).replace(',', '');
}

function connectMQTT() {
    const clientId = `mqtt_client_${Math.random().toString(16).substr(2, 8)}`;
    mqttClient = mqtt.connect(`mqtt://${mqttServer}:${mqttPort}`, {
        clientId,
        reconnectPeriod: 1000,
        clean: false
    });

    mqttClient.on('connect', () => {
        console.log(`[${getCurrentTimestamp()}] ✅ Conectado a MQTT Server: ${mqttServer}:${mqttPort}`);
        isMqttConnected = true;
        subscribeToTopics();

        mqttClient.on('message', async (topic, message) => {
            //console.log(`[${getCurrentTimestamp()}] ✅ Mensaje recibido: Tópico: ${topic} | Datos: ${message.toString()}`);
            await processCallApi(topic, message.toString());
        });
    });

    mqttClient.on('disconnect', () => {
        console.log(`[${getCurrentTimestamp()}] 🔴 Desconectado de MQTT`);
        isMqttConnected = false;
    });

    mqttClient.on('error', (error) => {
        console.error(`[${getCurrentTimestamp()}] ❌ Error en la conexión MQTT: ${error}`);
        isMqttConnected = false;
    });

    mqttClient.on('reconnect', () => {
        console.log(`[${getCurrentTimestamp()}] ⚠️ Intentando reconectar a MQTT...`);
    });
}

async function connectToDatabase() {
    try {
        dbConnection = await mysql.createConnection(dbConfig);
        console.log(`[${getCurrentTimestamp()}] ✅ Conectado a la base de datos`);
    } catch (error) {
        console.error(`[${getCurrentTimestamp()}] ❌ Error al conectar con la base de datos:`, error);
    }
}

// Obtener todos los tópicos, rssi_min y antenna_name de la tabla `rfid_ants`
async function getAllTopics() {
    const [rows] = await dbConnection.execute('SELECT mqtt_topic, rssi_min, name FROM rfid_ants WHERE mqtt_topic IS NOT NULL AND mqtt_topic != ""');
    
    // Guardamos en la memoria para acceso rápido
    antennaData = {};
    rows.forEach(row => {
        antennaData[row.mqtt_topic] = {
            rssi_min: row.rssi_min,
            antenna_name: row.name
        };
    });

    return rows;
}

// Obtener EPCs bloqueados desde la tabla `rfid_blocked`
async function updateBlockedEPCs() {
    try {
        const [rows] = await dbConnection.execute('SELECT epc FROM rfid_blocked');
        blockedEPCs = new Set(rows.map(row => row.epc));
        console.log(`[${getCurrentTimestamp()}] ✅ Lista de EPCs bloqueados actualizada (${blockedEPCs.size} bloqueados)`);
    } catch (error) {
        console.error(`[${getCurrentTimestamp()}] ❌ Error al actualizar la lista de EPCs bloqueados: ${error.message}`);
    }
}

async function subscribeToTopics() {
    if (!isMqttConnected) {
        console.log(`[${getCurrentTimestamp()}] ❌ mqttClient no está conectado. No se pueden suscribir a los tópicos.`);
        return;
    }

    const topics = await getAllTopics();

    topics.forEach(topic => {
        if (!subscribedTopics.includes(topic.mqtt_topic)) {
            mqttClient.subscribe(topic.mqtt_topic, (err) => {
                if (err) {
                    console.log(`[${getCurrentTimestamp()}] ❌ Error al suscribirse al tópico: ${topic.mqtt_topic}`);
                } else {
                    console.log(`[${getCurrentTimestamp()}] ✅ Suscrito al tópico: ${topic.mqtt_topic}`);
                    subscribedTopics.push(topic.mqtt_topic);
                }
            });
        }
    });

    subscribedTopics.forEach((topic, index) => {
        if (!topics.some(t => t.mqtt_topic === topic)) {
            mqttClient.unsubscribe(topic, (err) => {
                if (err) {
                    console.log(`[${getCurrentTimestamp()}] ❌ Error al desuscribirse del tópico: ${topic}`);
                } else {
                    console.log(`[${getCurrentTimestamp()}] ✅ Desuscrito del tópico: ${topic}`);
                    subscribedTopics.splice(index, 1);
                }
            });
        }
    });
}

async function processCallApi(topic, data) {
    try {
        const parsedData = JSON.parse(data);

        for (const entry of parsedData) {
            const { epc, rssi, serialno, tid, ant } = entry;

            // Si el EPC está en la lista de bloqueados, no llamamos a la API
            if (blockedEPCs.has(epc)) {
                //console.log(`[${getCurrentTimestamp()}] ⚠️ EPC bloqueado detectado (${epc}), no se llamará a la API.`);
                continue;
            }

            // Verificamos el rssi_min para este tópico
            const antennaInfo = antennaData[topic];
            if (antennaInfo && rssi < antennaInfo.rssi_min) {
               //console.log(`[${getCurrentTimestamp()}] ⚠️ RSSI ${rssi} es menor que el mínimo ${antennaInfo.rssi_min} para el EPC ${epc}, no se llamará a la API.`);
                continue;
            }

            // Si el TID está en la lista de ignorados, no llamamos a la API
            if (ignoredTIDs.has(tid)) {
                //console.log(`[${getCurrentTimestamp()}] ⚠️ TID ${tid} ignorado, ya fue registrado recientemente.`);
                continue;
            }

            // Construir el JSON para la API
            const dataToSend = {
                epc,
                rssi,
                serialno,
                tid,
                ant,
                antenna_name: antennaInfo ? antennaInfo.antenna_name : "Unknown"
            };

            let apiUrl = process.env.LOCAL_SERVER;
            if (apiUrl.endsWith('/')) {
                apiUrl = apiUrl.slice(0, -1);
            }
            apiUrl += '/api/rfid-insert';

            axios.post(apiUrl, dataToSend)
                .then(response => {
                    console.log(`[${getCurrentTimestamp()}] ✅ Respuesta de la API para EPC ${epc} y TID ${tid}: ${JSON.stringify(response.data, null, 2)}`);
           
                    // Si la API indica que la tarjeta ya fue registrada, ignoramos este TID por 1 minuto
                    if (!response.data.success && response.data.message.includes("ya fue registrada en este ciclo")) {
                        ignoredTIDs.set(tid, Date.now());
                        setTimeout(() => ignoredTIDs.delete(tid), 60000);
                        console.log(`[${getCurrentTimestamp()}] ⏳ TID ${tid} será ignorado durante 1 minuto.`);
                    }     
                })
                .catch(error => {
                    console.error(`[${getCurrentTimestamp()}] ❌ Error al procesar EPC ${epc}: ${error.response ? error.response.data.message : error.message}`);
                    updateBlockedEPCs();
                });
        }
    } catch (error) {
        console.error(`[${getCurrentTimestamp()}] ❌ Error procesando datos de MQTT: ${error.message}`);
    }
}

// Función principal
async function start() {
    await connectToDatabase();
    connectMQTT();
    await updateBlockedEPCs();

    setIntervalAsync(async () => {
        if (isMqttConnected) {
            await subscribeToTopics();
           // await updateBlockedEPCs();
        } else {
            console.log(`[${getCurrentTimestamp()}] ⚠️ Esperando reconexión a MQTT...`);
        }
    }, 60000);
}

start();
