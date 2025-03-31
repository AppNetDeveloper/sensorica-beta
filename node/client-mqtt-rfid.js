// Carga las variables de entorno desde el archivo '../.env'
require('dotenv').config({ path: '../.env' });

// Importa los módulos necesarios
const mqtt = require('mqtt');                         // Cliente MQTT para conectarse al broker MQTT
const mysql = require('mysql2/promise');              // Cliente MySQL con soporte para promesas
const axios = require('axios');                       // Cliente HTTP para realizar peticiones a la API
const { promisify } = require('util');                // Permite convertir funciones basadas en callbacks a promesas
const setIntervalAsync = promisify(setInterval);      // Convierte setInterval a una versión que retorna una promesa

// ⚙️ Configuración: Lee las variables de entorno y define la configuración del MQTT, API y Base de Datos
const mqttServer = process.env.MQTT_SENSORICA_SERVER;
const mqttPort = process.env.MQTT_SENSORICA_PORT;
const apiBaseUrl = process.env.LOCAL_SERVER.replace(/\/$/, '');
const dbConfig = {
    host: process.env.DB_HOST,
    port: process.env.DB_PORT,
    user: process.env.DB_USERNAME,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_DATABASE
};

// 🔄 Variables Globales utilizadas en la aplicación
let mqttClient;                   // Cliente MQTT
let isMqttConnected = false;      // Indicador del estado de conexión a MQTT
let dbConnection;                 // Conexión a la base de datos MySQL
let subscribedTopics = [];        // Lista de tópicos a los que se está suscrito
let blockedEPCs = new Set();      // Conjunto de EPCs bloqueados (evitar procesamiento)
let ignoredTIDs = new Map();      // Mapa para ignorar TIDs que ya han sido procesados recientemente
let antennaData = {};             // Objeto que almacena datos de antenas asociadas a cada tópico
// 🔄 Cache en memoria para almacenar production_line_id por mqtt_topic
let productionLineCache = {};

// ⏰ Función para obtener la fecha y hora actual en formato 'en-GB' y en la zona horaria UTC, sin la coma
function getCurrentTimestamp() {
    return new Date().toLocaleString('en-GB').replace(',', '');
}

// 🔄 Función para conectar a la base de datos con reconexión automática en caso de error
async function connectToDatabase() {
    while (true) {
        try {
            // Intenta establecer la conexión a la base de datos utilizando la configuración definida
            dbConnection = await mysql.createConnection(dbConfig);
            console.log(`[${getCurrentTimestamp()}] ✅ Conectado a la base de datos`);
            return; // Sale del bucle si la conexión es exitosa
        } catch (error) {
            // Si ocurre un error, muestra el error y espera 5 segundos antes de reintentar
            console.error(`[${getCurrentTimestamp()}] ❌ Error conectando a la base de datos: ${error.message}`);
            console.log(`[${getCurrentTimestamp()}] 🔄 Reintentando en 5s...`);
            await new Promise(resolve => setTimeout(resolve, 5000));
        }
    }
}
// 🔄 Función para actualizar el cache de production_line_id
async function updateProductionLineCache() {
    try {
        // Consulta la base de datos para obtener los production_line_id asociados a los mqtt_topic
        const [rows] = await dbConnection.execute(`
            SELECT mqtt_topic, production_line_id 
            FROM rfid_ants WHERE mqtt_topic IS NOT NULL AND mqtt_topic != ""
        `);

        // Actualiza el cache con los valores obtenidos
        productionLineCache = rows.reduce((cache, row) => {
            cache[row.mqtt_topic] = row.production_line_id;
            return cache;
        }, {});

        //console.log(`[${getCurrentTimestamp()}] ✅ Cache de production_line_id actualizado`);
    } catch (error) {
        console.error(`[${getCurrentTimestamp()}] ❌ Error al actualizar el cache de production_line_id: ${error.message}`);
    }
}

// 🔄 Función para obtener el production_line_id del cache
function getProductionLineIdFromCache(mqttTopic) {
    return productionLineCache[mqttTopic] || null;
}

// 🔄 Función para conectar a MQTT con reconexión automática
function connectMQTT() {
    // Crea el cliente MQTT utilizando la dirección del servidor y puerto configurados,
    // asigna un clientId aleatorio, establece el periodo de reconexión y evita limpiar las sesiones anteriores
    mqttClient = mqtt.connect(`mqtt://${mqttServer}:${mqttPort}`, {
        clientId: `mqtt_client_${Math.random().toString(16).substr(2, 8)}`,
        reconnectPeriod: 5000,
        clean: false
    });

    // Evento 'connect': Se ejecuta cuando la conexión MQTT es exitosa
    mqttClient.on('connect', async () => {
        console.log(`[${getCurrentTimestamp()}] ✅ Conectado a MQTT Server: ${mqttServer}:${mqttPort}`);
        isMqttConnected = true;
        // Llama a la función para suscribirse a los tópicos disponibles en la base de datos
        await subscribeToTopics();
    });

    // Evento 'message': Se ejecuta al recibir un mensaje en cualquier tópico suscrito
    mqttClient.on('message', async (topic, message) => {
        try {
            // Obtener el production_line_id desde el cache
            const productionLineId = getProductionLineIdFromCache(topic);

            if (!productionLineId) {
                console.log(`[${getCurrentTimestamp()}] ❌ No se encontró production_line_id en el cache para el topic: ${topic}`);
                return; // Si no se encuentra el production_line_id, ignorar el mensaje
            }

            // Ahora consulta shift_history para obtener el estado de ese turno
            const shift = await checkShiftHistory(productionLineId);

            if (shift && (shift.type === 'shift' && shift.action === 'start' || shift.type === 'stop' && shift.action === 'end')) {
                // Aquí procesamos el mensaje solo si el turno está activo o finalizado
                console.log(`[${getCurrentTimestamp()}] ✅ Procesando mensaje para production_line_id ${productionLineId}, tipo: ${shift.type}, acción: ${shift.action}`);
                await processCallApi(topic, message.toString());
            } else {
                console.log(`[${getCurrentTimestamp()}] ❌ Ignorando mensaje para production_line_id ${productionLineId}, tipo: ${shift.type}, acción: ${shift.action}`);
            }
        } catch (error) {
            console.error(`[${getCurrentTimestamp()}] ❌ Error procesando mensaje en MQTT: ${error.message}`);
        }
    });

    // Evento 'disconnect': Se ejecuta al perder la conexión con el servidor MQTT
    mqttClient.on('disconnect', () => {
        console.log(`[${getCurrentTimestamp()}] 🔴 Desconectado de MQTT`);
        isMqttConnected = false;
    });

    // Evento 'error': Se ejecuta al producirse un error en la conexión MQTT
    mqttClient.on('error', error => console.error(`[${getCurrentTimestamp()}] ❌ Error en MQTT: ${error.message}`));
    
    // Evento 'reconnect': Se ejecuta cuando el cliente intenta reconectarse
    mqttClient.on('reconnect', () => console.log(`[${getCurrentTimestamp()}] ⚠️ Intentando reconectar a MQTT...`));
}

// 🔄 Función para consultar la tabla shift_history y obtener la última línea con condiciones específicas
async function checkShiftHistory(productionLineId) {
    try {
        const [rows] = await dbConnection.execute(`
            SELECT * FROM shift_history 
            WHERE production_line_id = ? 
            ORDER BY id  DESC LIMIT 1
        `, [productionLineId]);

        return rows.length > 0 ? rows[0] : null; // Devuelve la última línea si cumple los criterios, sino null
    } catch (error) {
        console.error(`[${getCurrentTimestamp()}] ❌ Error al consultar shift_history: ${error.message}`);
        return null;
    }
}

// 🔄 Función para actualizar la lista de EPCs bloqueados desde la base de datos
async function updateBlockedEPCs() {
    try {
        // Ejecuta una consulta para obtener los EPCs bloqueados
        const [rows] = await dbConnection.execute('SELECT epc FROM rfid_blocked');
        // Actualiza el conjunto con los EPCs recuperados
        blockedEPCs = new Set(rows.map(row => row.epc));
        console.log(`[${getCurrentTimestamp()}] ✅ EPCs bloqueados actualizados (${blockedEPCs.size})`);
    } catch (error) {
        console.error(`[${getCurrentTimestamp()}] ❌ Error al actualizar EPCs bloqueados: ${error.message}`);
    }
}

// 🔄 Función para actualizar la lista de tópicos (y datos de antena) a los que se debe suscribir el cliente MQTT
async function subscribeToTopics() {
    // Si no está conectado a MQTT, no hace nada
    if (!isMqttConnected) return;

    try {
        // Consulta la base de datos para obtener los tópicos configurados en la tabla 'rfid_ants', ahora incluyendo el production_line_id
        const [rows] = await dbConnection.execute('SELECT mqtt_topic, rssi_min, name, production_line_id FROM rfid_ants WHERE mqtt_topic IS NOT NULL AND mqtt_topic != ""');

        // Crea un array con los nuevos tópicos obtenidos
        const newTopics = rows.map(row => row.mqtt_topic);

        // Actualiza la información de antena asociada a cada tópico
        rows.forEach(row => {
            antennaData[row.mqtt_topic] = { rssi_min: row.rssi_min, antenna_name: row.name };
        });

        // Comprueba si la lista de tópicos ha cambiado comparando el array actual con el anterior
        if (JSON.stringify(subscribedTopics) !== JSON.stringify(newTopics)) {
            // Actualiza la lista de tópicos suscritos
            subscribedTopics = newTopics;

            // Primero, cancela la suscripción a los tópicos antiguos y luego se suscribe a los nuevos
            mqttClient.unsubscribe(subscribedTopics, () => {
                mqttClient.subscribe(newTopics, err => {
                    if (!err) console.log(`[${getCurrentTimestamp()}] ✅ Tópicos actualizados (${newTopics.length})`);
                });
            });
        }
    } catch (error) {
        console.error(`[${getCurrentTimestamp()}] ❌ Error al actualizar tópicos: ${error.message}`);
        await connectToDatabase();
    }
}

// 🔄 Función para limpiar el mapa de ignoredTIDs, eliminando entradas que hayan estado más de 5 minutos (300000 ms)
function cleanupIgnoredTIDs() {
    const now = Date.now();
    // Recorre cada TID y elimina los que excedan el tiempo límite
    ignoredTIDs.forEach((timestamp, tid) => {
        if (now - timestamp > 300000) ignoredTIDs.delete(tid);
    });
}

// 🔄 Función auxiliar para realizar la llamada a la API con reintentos y backoff exponencial
async function callApiWithRetries(dataToSend, maxRetries = 5, initialDelay = 1000) {
    let attempt = 0;
    let delay = initialDelay;

    while (attempt < maxRetries) {
        try {
            // Intenta realizar la llamada a la API
            const response = await axios.post(`${apiBaseUrl}/api/rfid-insert`, dataToSend);
            return response;
        } catch (error) {
            attempt++;
            if (attempt >= maxRetries) {
                throw error;
            }
            console.warn(`[${getCurrentTimestamp()}] ⚠️ Error en llamada a API, reintentando en ${delay}ms (Intento ${attempt} de ${maxRetries})`);
            await new Promise(resolve => setTimeout(resolve, delay));
            delay *= 2; // Backoff exponencial
        }
    }
}

// 🔄 Función para procesar los datos recibidos de MQTT y llamar a la API correspondiente en paralelo
async function processCallApi(topic, data) {
    try {
        // Se intenta parsear el JSON recibido en el mensaje
        const parsedData = JSON.parse(data);

        // Se crean promesas para cada entrada y se ejecutan en paralelo
        const apiCalls = parsedData.map(async entry => {
            const { epc, rssi, serialno, tid, ant } = entry;

            // Validaciones básicas: que epc y tid existan y que rssi sea un número
            if (!epc || !tid || typeof rssi !== 'number') {
                console.warn(`[${getCurrentTimestamp()}] ⚠️ Datos incompletos o inválidos: epc: ${epc}, tid: ${tid}, rssi: ${rssi}. Se omite la llamada a la API.`);
                return;
            }
            
            // Validación: Si el EPC está bloqueado, se omite la llamada
            if (blockedEPCs.has(epc)) {
                return;
            }

            // Se obtiene la información de la antena asociada al tópico
            const antennaInfo = antennaData[topic];
            // Validación: Si existe información de la antena y el valor RSSI es menor al mínimo, se omite la llamada
            if (antennaInfo && rssi < antennaInfo.rssi_min) {
                return;
            }

            // Validación: Si el TID ya fue registrado recientemente, se omite la llamada
            if (ignoredTIDs.has(tid)) {
                return;
            }

            // Prepara los datos a enviar a la API, incluyendo el nombre de la antena (si está disponible)
            const dataToSend = { epc, rssi, serialno, tid, ant, antenna_name: antennaInfo?.antenna_name || "Unknown" };

            try {
                // Realiza la llamada a la API con reintentos en caso de error
                //const response = await callApiWithRetries(dataToSend);

                //llamada por directo
                const response = await axios.post(`${apiBaseUrl}/api/rfid-insert`, dataToSend);
                console.log(`[${getCurrentTimestamp()}] ✅ API Respuesta EPC ${epc} TID ${tid} y RSSI ${rssi}: ${JSON.stringify(response.data, null, 2)}`);

                // Define el tiempo durante el cual se ignorará el TID según el éxito de la operación (300000 ms o 180000 ms)
                const ignoreTime = response.data.success ? 300000 : 180000;
                ignoredTIDs.set(tid, Date.now());
                setTimeout(() => ignoredTIDs.delete(tid), ignoreTime);
                console.log(`[${getCurrentTimestamp()}] ⏳ TID ${tid} ignorado por ${ignoreTime / 60000} min.`);
            } catch (error) {
                console.error(`[${getCurrentTimestamp()}] ❌ Error API EPC ${epc}: ${error.response?.data?.message || error.message}`);
                updateBlockedEPCs();
            }
        });

        // Se esperan todas las promesas en paralelo
        await Promise.all(apiCalls);
    } catch (error) {
        console.error(`[${getCurrentTimestamp()}] ❌ Error procesando datos de MQTT: ${error.message}`);
    }
}

// 🔄 Función principal que inicia la aplicación
async function start() {
    // Conecta a la base de datos
    await connectToDatabase();
    // Conecta al servidor MQTT
    connectMQTT();
    // Actualiza la lista de EPCs bloqueados
    await updateBlockedEPCs();

    // Programa la actualización de la suscripción a tópicos cada 60 segundos (60000 ms) de forma asíncrona
    setIntervalAsync(async () => {
        if (isMqttConnected) await subscribeToTopics();
    }, 60000);

    setInterval(async () => {
        await updateProductionLineCache();
    }, 60000); // Actualización cada 60 segundos

    // Programa la limpieza de TIDs ignorados cada 60 segundos
    setInterval(cleanupIgnoredTIDs, 60000);
}

// Inicia la aplicación
start();
