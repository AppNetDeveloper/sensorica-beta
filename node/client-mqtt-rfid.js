// Carga las variables de entorno desde el archivo '../.env'
require('dotenv').config({ path: '../.env' });

// Importa los módulos necesarios
const mqtt = require('mqtt');                         // Cliente MQTT para conectarse al broker MQTT
const mysql = require('mysql2/promise');              // Cliente MySQL con soporte para promesas
const axios = require('axios');                       // Cliente HTTP para realizar peticiones a la API
const https = require('https');
const { promisify } = require('util');                // Permite convertir funciones basadas en callbacks a promesas
const setIntervalAsync = promisify(setInterval);      // Convierte setInterval a una versión que retorna una promesa
const environment = 'production'; // O 'local', 'staging', etc.
const info = 'INFO'; // O podría ser 'ERROR', 'INFO', etc. según el caso
const error = 'ERROR'; // O podría ser 'ERROR', 'INFO', etc. según el caso
const warning  = 'WARNING'; // O podría ser 'ERROR', 'INFO', etc. según el caso

// ⚙️ Configuración: Lee las variables de entorno y define la configuración del MQTT, API y Base de Datos
const mqttServer = process.env.MQTT_SENSORICA_SERVER;
const mqttPort = process.env.MQTT_SENSORICA_PORT;
const apiBaseUrl = process.env.LOCAL_SERVER.replace(/\/$/, '');

// Agente HTTPS para ignorar errores de certificado (solución robusta para redirecciones)
const httpsAgent = new https.Agent({ rejectUnauthorized: false });

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
let ignoredTempTIDs = new Map();      // Mapa para ignorar temp TIDs que ya han sido procesados recientemente
let antennaData = {};             // Objeto que almacena datos de antenas asociadas a cada tópico
// 🔄 Cache en memoria para almacenar production_line_id por mqtt_topic
let productionLineCache = {};
let epcReadCache = new Map();     // NUEVO: Cache para ignorar EPCs leídos recientemente por antena: Map<topic, Map<epc, expiryTimestamp>>
let currentlySubscribedTopics = []; // Lista de tópicos a los que se está suscrito actualmente

// ⏰ Función para obtener la fecha y hora actual en formato 'en-GB' y en la zona horaria UTC, sin la coma
function getCurrentTimestamp() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0'); // Meses son 0-indexados
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}


// 🔄 Función para conectar a la base de datos con reconexión automática en caso de error
async function connectToDatabase() {
    while (true) {
        try {
            // Intenta establecer la conexión a la base de datos utilizando la configuración definida
            dbConnection = await mysql.createConnection(dbConfig);
            console.log(`[${getCurrentTimestamp()}] ${environment}.${info}: ✅ Conectado a la base de datos`);
            return; // Sale del bucle si la conexión es exitosa
        } catch (error) {
            // Si ocurre un error, muestra el error y espera 5 segundos antes de reintentar
            console.error(`[${getCurrentTimestamp()}] ${environment}.${error}: ❌ Error conectando a la base de datos: ${error.message}`);
            console.log(`[${getCurrentTimestamp()}] ${environment}.${info}: 🔄 Reintentando en 5s...`);
            await new Promise(resolve => setTimeout(resolve, 5000));
        }
    }
}
// 🔄 Función para actualizar el cache de production_line_id
async function updateProductionLineCache() {
    try {
        const [rows] = await dbConnection.execute(`
            SELECT mqtt_topic, production_line_id 
            FROM rfid_ants WHERE mqtt_topic IS NOT NULL AND mqtt_topic != ""
        `);

        // Actualiza el cache con los valores obtenidos
        productionLineCache = rows.reduce((cache, row) => {
            cache[row.mqtt_topic] = row.production_line_id;
            return cache;
        }, {});

        //console.log(`[${getCurrentTimestamp()}] ${environment}.${info}: ✅ Cache de production_line_id actualizado:`, productionLineCache);
    } catch (error) {
        console.error(`[${getCurrentTimestamp()}] ${environment}.${error}: ❌ Error al actualizar el cache de production_line_id: ${error.message}`);
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
        //clientId: `rfid-consumer-1`,   // fijo por instancia el client id que se usa en el broker
        keepalive: 45,                    // 45 s -> latencia razonable
        reconnectPeriod: 5000,
        //reconnectPeriod: 2000,            // 2 s si quieres recobro ágil
        clean: false  
    });

    // Evento 'connect': Se ejecuta cuando la conexión MQTT es exitosa
    mqttClient.on('connect', async () => {
        console.log(`[${getCurrentTimestamp()}] ${environment}.${info}: ✅ Conectado a MQTT Server: ${mqttServer}:${mqttPort}`);
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
                console.log(`[${getCurrentTimestamp()}] ${environment}.${info}: ❌ No se encontró production_line_id en el cache para el topic: ${topic}`);
                await updateProductionLineCache();
                return; // Si no se encuentra el production_line_id, ignorar el mensaje
            }

            // Ahora consulta shift_history para obtener el estado de ese turno
            const shift = await checkShiftHistory(productionLineId);

            if (shift && (shift.type === 'shift' && shift.action === 'start' || shift.type === 'stop' && shift.action === 'end')) {
                // Aquí procesamos el mensaje solo si el turno está activo o finalizado
                //console.log(`[${getCurrentTimestamp()}] ${environment}.${info}: ✅ Procesando mensaje para production_line_id ${productionLineId}, tipo: ${shift.type}, acción: ${shift.action}`);
                await processCallApi(topic, message.toString());
            } else {
                console.log(`[${getCurrentTimestamp()}] ${environment}.${info}: ❌ Ignorando mensaje para production_line_id ${productionLineId}, tipo: ${shift.type}, acción: ${shift.action}`);
            }
        } catch (error) {
            console.error(`[${getCurrentTimestamp()}] ${environment}.${error}: ❌ Error procesando mensaje en MQTT: ${error.message}`);
        }
    });

    // Evento 'disconnect': Se ejecuta al perder la conexión con el servidor MQTT
    mqttClient.on('disconnect', () => {
        console.log(`[${getCurrentTimestamp()}] ${environment}.${info}: 🔴 Desconectado de MQTT`);
        isMqttConnected = false;
    });

    // Evento 'error': Se ejecuta al producirse un error en la conexión MQTT
    mqttClient.on('error', error => console.error(`[${getCurrentTimestamp()}] ${environment}.${error}: ❌ Error en MQTT: ${error.message}`));
    
    // Evento 'reconnect': Se ejecuta cuando el cliente intenta reconectarse
    mqttClient.on('reconnect', () => console.log(`[${getCurrentTimestamp()}] ${environment}.${info}: ⚠️ Intentando reconectar a MQTT...`));
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
        console.error(`[${getCurrentTimestamp()}] ${environment}.${error}: ❌ Error al consultar shift_history: ${error.message}`);
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
        console.log(`[${getCurrentTimestamp()}] ${environment}.${info}: ✅ EPCs bloqueados actualizados (${blockedEPCs.size})`);
    } catch (error) {
        console.error(`[${getCurrentTimestamp()}] ${environment}.${error}: ❌ Error al actualizar EPCs bloqueados: ${error.message}`);
    }
}

// 🔄 Función para actualizar la lista de tópicos (y datos de antena) a los que se debe suscribir el cliente MQTT
// 🔄 Función para actualizar la lista de tópicos a los que se debe suscribir el cliente MQTT
async function subscribeToTopics() {
    if (!isMqttConnected || !dbConnection) {
        if (!isMqttConnected) console.warn(`[${getCurrentTimestamp()}] ${environment}.${warning}: No conectado a MQTT, no se pueden actualizar tópicos.`);
        if (!dbConnection) console.warn(`[${getCurrentTimestamp()}] ${environment}.${warning}: No conectado a la BD, no se pueden obtener tópicos.`);
        return;
    }

    try {
        const [rows] = await dbConnection.execute('SELECT mqtt_topic, rssi_min, name, production_line_id, min_read_interval_ms FROM rfid_ants WHERE mqtt_topic IS NOT NULL AND mqtt_topic != ""');
        const newTopicsFromDB = rows.map(row => row.mqtt_topic);

        rows.forEach(row => {
            antennaData[row.mqtt_topic] = { rssi_min: row.rssi_min, antenna_name: row.name, min_read_interval_ms: row.min_read_interval_ms };
        });

        // Comprobar si la lista de tópicos ha cambiado (comparando listas ordenadas)
        if (JSON.stringify(currentlySubscribedTopics.sort()) !== JSON.stringify(newTopicsFromDB.sort())) {
            console.log(`[${getCurrentTimestamp()}] ${environment}.${info}: Detectado cambio en tópicos. Actuales: ${currentlySubscribedTopics.join(', ')}. Nuevos de BD: ${newTopicsFromDB.join(', ')}`);

            // Tópicos a los que hay que desuscribirse (estaban antes, ya no están)
            const topicsToUnsubscribe = currentlySubscribedTopics.filter(topic => !newTopicsFromDB.includes(topic));

            if (topicsToUnsubscribe.length > 0) {
                mqttClient.unsubscribe(topicsToUnsubscribe, (errUnsubscribe) => {
                    if (errUnsubscribe) {
                        console.error(`[${getCurrentTimestamp()}] ${environment}.${error}: ❌ Error al desuscribirse de tópicos [${topicsToUnsubscribe.join(', ')}]: ${errUnsubscribe.message}`);
                    } else {
                        console.log(`[${getCurrentTimestamp()}] ${environment}.${info}: ℹ️ Desuscrito de tópicos: [${topicsToUnsubscribe.join(', ')}]`);
                    }
                    // Continuar con la suscripción a la lista completa de la BD (newTopicsFromDB)
                    // para asegurar que todos los necesarios estén con QoS 1
                    subscribeNewAndReSubscribe(newTopicsFromDB);
                });
            } else {
                 // Si no hay nada que desuscribir, simplemente suscribir/re-suscribir a la lista de la BD
                subscribeNewAndReSubscribe(newTopicsFromDB);
            }
            // Actualizar la lista de tópicos actualmente suscritos
            currentlySubscribedTopics = [...newTopicsFromDB];
        } else {
            // Si no hay cambios en la lista de tópicos, igualmente nos aseguramos
            // de que las suscripciones existentes tengan QoS 1.
            if (newTopicsFromDB.length > 0) {
                // Esta llamada a subscribe actuará como una "re-suscripción" o "actualización de QoS"
                // para los tópicos que ya estaban suscritos.
                mqttClient.subscribe(newTopicsFromDB, { qos: 1 }, (errSubscribe) => {
                    if (errSubscribe) {
                        console.error(`[${getCurrentTimestamp()}] ${environment}.${error}: ❌ Error al re-suscribirse (verificación QoS) a tópicos [${newTopicsFromDB.join(', ')}]: ${errSubscribe.message}`);
                    } else {
                        // console.log(`[${getCurrentTimestamp()}] ${environment}.${info}: ✅ Verificación de QoS para tópicos [${newTopicsFromDB.join(', ')}] completada.`);
                    }
                });
            }
        }
    } catch (err) { // Cambiado 'error' a 'err' para consistencia
        console.error(`[${getCurrentTimestamp()}] ${environment}.${error}: ❌ Error al actualizar tópicos: ${err.message}`);
        if (err.code === 'PROTOCOL_CONNECTION_LOST' || err.code === 'ECONNREFUSED' || err.code === 'ENOTFOUND') {
            console.log(`[${getCurrentTimestamp()}] ${environment}.${info}: Intentando reconectar a la base de datos debido a error en actualización de tópicos.`);
            await connectToDatabase();
        }
    }
}

// Y necesitarías también la función auxiliar:
function subscribeNewAndReSubscribe(topicsToEnsureSubscription) {
    if (topicsToEnsureSubscription.length > 0) {
        mqttClient.subscribe(topicsToEnsureSubscription, { qos: 1 }, (errSubscribe) => {
            if (!errSubscribe) {
                console.log(`[${getCurrentTimestamp()}] ${environment}.${info}: ✅ Suscrito/Actualizado tópicos [${topicsToEnsureSubscription.join(', ')}] con QoS 1.`);
            } else {
                console.error(`[${getCurrentTimestamp()}] ${environment}.${error}: ❌ Error al suscribirse/actualizar tópicos [${topicsToEnsureSubscription.join(', ')}]: ${errSubscribe.message}`);
            }
        });
    } else {
        console.log(`[${getCurrentTimestamp()}] ${environment}.${info}: No hay tópicos para suscribir/actualizar.`);
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

// 🔄 Función para limpiar el mapa de ignoredTIDs, eliminando entradas que hayan estado más de 5 minutos (300000 ms)
function cleanupIgnoredTempTIDs() {
    const now = Date.now();
    // Recorre cada TID y elimina los que excedan el tiempo límite
    ignoredTempTIDs.forEach((timestamp, tid) => {
        if (now - timestamp > 10000) ignoredTempTIDs.delete(tid);
    });
}
// 🔄 Función para limpiar el cache de EPCs leídos recientemente por antena
function cleanupEpcReadCache() {
    const now = Date.now();
    epcReadCache.forEach((topicCache, topic) => {
        topicCache.forEach((expiry, epc) => {
            if (now >= expiry) {
                topicCache.delete(epc); // Elimina el EPC expirado del mapa del tópico
            }
        });
        // Opcional: Si el mapa para un tópico queda vacío, eliminar el tópico del caché principal
        if (topicCache.size === 0) {
            epcReadCache.delete(topic);
        }
    });
}

// 🔄 Función para procesar los datos recibidos de MQTT y llamar a la API correspondiente en paralelo
async function processCallApi(topic, data) {
    try {
        // Se intenta parsear el JSON recibido en el mensaje
        const parsedData = JSON.parse(data);

        const now = Date.now(); // <-- 'now' definido ANTES del map
        
        // Se crean promesas para cada entrada y se ejecutan en paralelo
        const apiCalls = parsedData.map(async entry => {
            const { epc, rssi, serialno, tid, ant } = entry;

            // Validaciones básicas: que epc y tid existan y que rssi sea un número
            if (!epc || !tid || typeof rssi !== 'number') {
                console.warn(`[${getCurrentTimestamp()}] ${environment}.${info}: ⚠️ Datos incompletos o inválidos: epc: ${epc}, tid: ${tid}, rssi: ${rssi}. Se omite la llamada a la API.`);
                return;
            }

            // Validación: Si el EPC está bloqueado, se omite la llamada
            if (blockedEPCs.has(epc)) {
               // console.warn(`[${getCurrentTimestamp()}] ${environment}.${info}: ⚠️ EPC bloqueado: ${epc}. Se omite la llamada a la API.`);
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
                //console.log(`[${getCurrentTimestamp()}] ${environment}.${info}: ⚠️ TID ya registrado recientemente: tid: ${tid}. Se omite la llamada a la API.`);
                return;
            }

            // Validación: Si el TID ya fue registrado recientemente, se omite la llamada
            if (ignoredTempTIDs.has(tid)) {
                console.log(`[${getCurrentTimestamp()}] ${environment}.${info}: ⚠️ TID : ${tid}. en proceso de bloqueo temporal por duplicación.`);
                return;
            }else{
                const ignoreTimeTemp = 10000;
                ignoredTempTIDs.set(tid, Date.now());
                setTimeout(() => ignoredTempTIDs.delete(tid), ignoreTimeTemp);
                console.log(`[${getCurrentTimestamp()}] ${environment}.${info}: ⚠️ TID : ${tid}. Bloqueado Temporal para no duplicar.`);
            }

            // Obtener el intervalo configurado para esta antena (asumimos que está en segundos)
            const intervalInSeconds = antennaInfo?.min_read_interval_ms;
            // --- INICIO: COMPROBACIÓN DE CACHÉ epcReadCache ---
            // Aplicar el chequeo solo si el intervalo es numérico y >= 1 segundo
            if (typeof intervalInSeconds === 'number' && intervalInSeconds >= 1) {
                // Intentar obtener el caché específico para este 'topic'
                const topicCache = epcReadCache.get(topic);
                // Si existe caché para este tópico...
                if (topicCache) {
                    // ...intentar obtener la expiración para este EPC específico
                    const expiry = topicCache.get(epc);
                    // Si se encontró una expiración (expiry) Y aún es futura (el momento actual 'now' es anterior a 'expiry')...
                    if (expiry && now < expiry) {
                        // ...entonces el EPC está registrado y activo, hay que omitirlo.
                        console.log(`[${getCurrentTimestamp()}] ${environment}.${info}: ⚠️ EPC ${epc} en topic ${topic} omitido (intervalo activo: ${intervalInSeconds}s).`);
                        // ---> HACEMOS EL RETURN AQUÍ <---
                        return; // Salir del procesamiento para este EPC específico
                    }
                    // Si no se encontró 'expiry' o si 'now >= expiry', no hacemos nada aquí y simplemente continuamos.
                }
                // Si no existe 'topicCache' (es la primera vez para este topic), tampoco hacemos nada aquí y continuamos.
            }
            // --- FIN: COMPROBACIÓN DE CACHÉ epcReadCache ---
            // --- INICIO: REGISTRO EN CACHÉ epcReadCache ---
            // Solo registramos si el intervalo es numérico y >= 1 segundo
            if (typeof intervalInSeconds === 'number' && intervalInSeconds >= 1) {
                // Convertir los segundos a milisegundos para el cálculo del tiempo
                const intervalInMillis = intervalInSeconds * 1000;
                // Calcular el timestamp exacto de cuándo expirará esta entrada
                const expiryTimestamp = now + intervalInMillis;

                // Asegurarse de que el mapa para el 'topic' exista en el caché principal
                // Si no existe, lo crea vacío.
                if (!epcReadCache.has(topic)) {
                    epcReadCache.set(topic, new Map());
                }
                // Ahora que estamos seguros de que existe, registrar (o actualizar) el EPC
                // en el mapa del tópico con su nueva fecha de expiración.
                epcReadCache.get(topic).set(epc, expiryTimestamp);

                // Log opcional para confirmar el registro
                console.log(`[${getCurrentTimestamp()}] ${environment}.${info}: ⏳ EPC ${epc} en topic ${topic} registrado/actualizado en caché (expira en ${intervalInSeconds}s).`);
            }
            // Si el intervalo no era válido, simplemente no se registra nada en este caché.
            // --- FIN: REGISTRO EN CACHÉ epcReadCache ---

            // Prepara los datos a enviar a la API, incluyendo el nombre de la antena (si está disponible)
            const dataToSend = { epc, rssi, serialno, tid, ant, antenna_name: antennaInfo?.antenna_name || "Unknown" };

            try {

                const response = await axios.post(`${apiBaseUrl}/api/rfid-insert`, dataToSend, { httpsAgent });
                console.log(`[${getCurrentTimestamp()}] ${environment}.${info}: ✅ API Respuesta EPC ${epc} TID ${tid} y RSSI ${rssi}: ${JSON.stringify(response.data, null, 2)}`);

                // Define el tiempo durante el cual se ignorará el TID según el éxito de la operación (300000 ms o 180000 ms) OJO ANTEAS EL 1000 era 180000
                const ignoreTime = response.data.success ? 800000 : 5000;
                ignoredTIDs.set(tid, Date.now());
                setTimeout(() => ignoredTIDs.delete(tid), ignoreTime);
                console.log(`[${getCurrentTimestamp()}] ${environment}.${info}: ⏳ TID ${tid} ignorado por ${ignoreTime / 60000} min.`);
            } catch (error) {
                // Log detallado del error
                const errorTimestamp = getCurrentTimestamp();
                console.error(`[${errorTimestamp}] ${environment}.ERROR: ❌ Error al llamar a la API para EPC ${epc}.`);
                console.error(`[${errorTimestamp}] ${environment}.ERROR:   - URL: ${apiBaseUrl}/api/rfid-insert`);
                console.error(`[${errorTimestamp}] ${environment}.ERROR:   - Datos enviados: ${JSON.stringify(dataToSend)}`);

                if (error.response) {
                    // El servidor respondió con un código de estado fuera del rango 2xx
                    console.error(`[${errorTimestamp}] ${environment}.ERROR:   - Status: ${error.response.status}`);
                    console.error(`[${errorTimestamp}] ${environment}.ERROR:   - Respuesta (Datos): ${JSON.stringify(error.response.data)}`);
                } else if (error.request) {
                    // La petición se hizo pero no se recibió respuesta
                    console.error(`[${errorTimestamp}] ${environment}.ERROR:   - Petición enviada, pero no se recibió respuesta.`);
                } else {
                    // Error en la configuración de la petición
                    console.error(`[${errorTimestamp}] ${environment}.ERROR:   - Mensaje de error: ${error.message}`);
                }
                // Para un diagnóstico completo, se muestra el objeto de error entero
                console.error(`[${errorTimestamp}] ${environment}.ERROR:   - Objeto de error completo:`, error);

                updateBlockedEPCs();
            }
        });

        // Se esperan todas las promesas en paralelo
        await Promise.all(apiCalls);
    } catch (error) {
        console.error(`[${getCurrentTimestamp()}] ${environment}.${error}: ❌ Error procesando datos de MQTT: ${error.message}`);
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

    // Programa la limpieza de TIDs ignorados cada 5 segundos, antes 60 segundos
    setInterval(cleanupIgnoredTIDs, 5000);
    setInterval(cleanupIgnoredTempTIDs, 5000);

    // ---- NUEVO: Intervalo para limpiar el caché de EPCs por intervalo de antena ----
    setInterval(cleanupEpcReadCache, 1000); // Limpia cada 1 segundos (ajusta si es necesario)
    // ---- FIN NUEVO ----
}

// Inicia la aplicación
start();
