require('dotenv').config({ path: '../.env' });
const mqtt = require('mqtt');
const mysql = require('mysql2/promise');
const axios = require('axios'); // Usamos axios para las solicitudes HTTP
const { promisify } = require('util');
const setIntervalAsync = promisify(setInterval); // Usar una versión async-friendly de setInterval

// Configuración de MQTT y base de datos
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
// Usar un Set para subscribedTopics hace más eficiente la comprobación de existencia
let subscribedTopics = new Set();
// Usar un Map para valueCounters. Guardará: { count, lastValue, repNumber, config }
let valueCounters = new Map();

// Función para obtener la fecha y hora actual en formato<y_bin_46>-MM-DD HH:mm:ss
function getCurrentTimestamp() {
    // Ajustado para formato ISO y zona horaria local si es necesario
    return new Date().toLocaleString('sv-SE'); // sv-SE da<y_bin_46>-MM-DD HH:MM:SS
}

function connectMQTT() {
    const clientId = `mqtt_client_${Math.random().toString(16).substr(2, 8)}`;
    console.log(`[${getCurrentTimestamp()}] ℹ️ Intentando conectar a MQTT Server: ${mqttServer}:${mqttPort} con ClientID: ${clientId}`);

    // Limpiar manejadores de eventos anteriores si existieran (importante en reconexiones)
    if (mqttClient) {
        mqttClient.removeAllListeners();
    }

    mqttClient = mqtt.connect(`mqtt://${mqttServer}:${mqttPort}`, {
        clientId,
        reconnectPeriod: 5000, // Intentar reconectar cada 5 segundos
        connectTimeout: 10000, // Timeout de conexión de 10 segundos
        clean: true // Empezar con sesión limpia para evitar mensajes antiguos y problemas de estado
    });

    mqttClient.on('connect', () => {
        console.log(`[${getCurrentTimestamp()}] ✅ Conectado a MQTT Server: ${mqttServer}:${mqttPort}`);
        isMqttConnected = true;
        // Limpiar suscripciones previas en memoria al conectar (ya que clean:true)
        subscribedTopics.clear();
        valueCounters.clear(); // Limpia contadores y caché de config
        console.log(`[${getCurrentTimestamp()}] ℹ️ Estado de suscripciones, contadores y caché limpiado debido a (re)conexión.`);
        // Suscribirse a los tópicos y cargar caché después de conectar
        synchronizeTopicsAndSubscriptions();
    });

    // Mover el manejador 'message' aquí para que se registre solo una vez
    mqttClient.on('message', async (topic, message) => {
        // console.log(`[${getCurrentTimestamp()}] ✅ Mensaje recibido: Tópico: ${topic} | Datos: ${message.toString()}`);
        await processCallApi(topic, message.toString());
    });


    mqttClient.on('disconnect', (packet) => {
        console.log(`[${getCurrentTimestamp()}] 🔴 Desconectado de MQTT. Packet: ${packet}`);
        isMqttConnected = false;
        // No limpiar aquí, esperar a la reconexión exitosa
    });

    mqttClient.on('error', (error) => {
        console.error(`[${getCurrentTimestamp()}] ❌ Error en la conexión MQTT: ${error.message}`);
        isMqttConnected = false;
    });

    mqttClient.on('reconnect', () => {
        console.log(`[${getCurrentTimestamp()}] ⚠️ Intentando reconectar a MQTT...`);
    });

    mqttClient.on('close', () => {
        console.log(`[${getCurrentTimestamp()}] ℹ️ Conexión MQTT cerrada.`);
        isMqttConnected = false;
    });

    mqttClient.on('offline', () => {
        console.log(`[${getCurrentTimestamp()}] ℹ️ Cliente MQTT offline.`);
        isMqttConnected = false;
    });
}

async function connectToDatabase() {
    while (true) {
        try {
            // Si ya existe una conexión, intentar cerrarla primero
            if (dbConnection) {
                try {
                   await dbConnection.end();
                   console.log(`[${getCurrentTimestamp()}] ℹ️ Conexión DB anterior cerrada.`);
                } catch(closeErr) {
                   console.warn(`[${getCurrentTimestamp()}] ⚠️ No se pudo cerrar conexión DB anterior: ${closeErr.message}`);
                }
            }
            dbConnection = await mysql.createConnection(dbConfig);
            // Configurar listener para errores de conexión que ocurran después de conectar
            dbConnection.on('error', async (err) => {
                console.error(`[${getCurrentTimestamp()}] ❌ Error en la conexión DB: ${err.code}`);
                if (err.code === 'PROTOCOL_CONNECTION_LOST' || err.code === 'ECONNRESET' || err.code === 'ETIMEDOUT' || err.code === 'ERR_SOCKET_BAD_PORT') {
                    console.log(`[${getCurrentTimestamp()}] 🔄 Intentando reconectar a la base de datos...`);
                    isMqttConnected = false; // Mark as disconnected to avoid issues while DB is down
                    await connectToDatabase(); // Reintentar la conexión
                } else {
                     console.error(`[${getCurrentTimestamp()}] ❌ Error DB no recuperable detectado: ${err.message}. Deteniendo.`);
                     await shutdown(1); // Shutdown with error code
                }
            });
            console.log(`[${getCurrentTimestamp()}] ✅ Conectado a la base de datos ${dbConfig.database}@${dbConfig.host}`);
            break; // Sale del bucle si la conexión es exitosa
        } catch (error) {
            console.error(`[${getCurrentTimestamp()}] ❌ Error conectando a la base de datos: ${error.message}`);
             if (error.code === 'ERR_SOCKET_BAD_PORT') {
                 console.error(`[${getCurrentTimestamp()}] ❌ Puerto DB (${dbConfig.port}) inválido o no accesible. Verifique la configuración. Deteniendo.`);
                 await shutdown(1); // Exit if port is wrong
             }
            console.log(`[${getCurrentTimestamp()}] 🔄 Reintentando conexión a DB en 5 segundos...`);
            await new Promise(resolve => setTimeout(resolve, 5000));
        }
    }
}

// --- MODIFICADO: Ahora obtiene toda la configuración necesaria ---
async function getAllTopicsAndConfigsFromDB() { // Renamed for clarity
    if (!dbConnection || dbConnection.state === 'disconnected') {
        console.error(`[${getCurrentTimestamp()}] ❌ DB no conectada. No se pueden obtener tópicos y configs.`);
        await connectToDatabase();
    }
    try {
        // Selecciona todas las columnas necesarias para la caché
        const query = `
            SELECT id, mqtt_topic_modbus, rep_number, model_name,
                   variacion_number, conversion_factor, dimension_default
            FROM modbuses
            WHERE mqtt_topic_modbus IS NOT NULL AND mqtt_topic_modbus != ""
        `;
        const [rows] = await dbConnection.execute(query);

        const topicsConfigMap = new Map();
        rows.forEach(row => {
            // Validar rep_number antes de añadir
            const repNum = parseInt(row.rep_number, 10);
            if (row.mqtt_topic_modbus && !isNaN(repNum) && repNum >= 0) {
                // Almacena el objeto fila completo como configuración
                topicsConfigMap.set(row.mqtt_topic_modbus, row);
            } else {
                console.warn(`[${getCurrentTimestamp()}] ⚠️ Tópico '${row.mqtt_topic_modbus}' omitido de la caché: rep_number inválido ('${row.rep_number}') o falta tópico.`);
            }
        });
        console.log(`[${getCurrentTimestamp()}] ℹ️ Obtenidas ${topicsConfigMap.size} configuraciones de tópicos desde DB.`);
        return topicsConfigMap;
    } catch (error) {
        console.error(`[${getCurrentTimestamp()}] ❌ Error en getAllTopicsAndConfigsFromDB: ${error.message}.`);
        if (error.code === 'PROTOCOL_CONNECTION_LOST' || error.code === 'ECONNRESET' || error.code === 'ETIMEDOUT') {
            console.log(`[${getCurrentTimestamp()}] 🔄 Conexión DB perdida, intentando reconectar y reintentar consulta...`);
            await connectToDatabase();
            return getAllTopicsAndConfigsFromDB(); // Reintentar
        } else {
            console.error(`[${getCurrentTimestamp()}] ❌ Error SQL no recuperable: ${error.code}`);
            return new Map(); // Devolver mapa vacío
        }
    }
}

// --- MODIFICADO: Ahora guarda la configuración completa en valueCounters ---
async function synchronizeTopicsAndSubscriptions() {
    if (!isMqttConnected) {
        console.log(`[${getCurrentTimestamp()}] ⚠️ MQTT no conectado. Sincronización abortada.`);
        return;
    }
    if (!dbConnection || dbConnection.state === 'disconnected') {
        console.log(`[${getCurrentTimestamp()}] ⚠️ DB no conectada. Sincronización abortada.`);
        return;
    }

    console.log(`[${getCurrentTimestamp()}] 🔄 Sincronizando tópicos, suscripciones y caché de configuración...`);
    const topicsConfigFromDB = await getAllTopicsAndConfigsFromDB(); // Obtiene Map(topic -> {id, rep_number, model_name...})
    const currentSubscribed = new Set(subscribedTopics); // Copia de tópicos actualmente suscritos en memoria

    // 1. Suscribirse a nuevos tópicos y actualizar/cachear config para existentes
    for (const [topic, dbConfig] of topicsConfigFromDB.entries()) { // dbConfig es ahora el objeto fila completo
        // repNum ya fue validado en getAllTopicsAndConfigsFromDB
        const repNum = parseInt(dbConfig.rep_number, 10);
        const calculatedRepNumber = repNum + 1; // El límite real (+1)

        if (!currentSubscribed.has(topic)) {
            // --- Tópico Nuevo ---
            mqttClient.subscribe(topic, { qos: 1 }, (err) => {
                if (err) {
                    console.error(`[${getCurrentTimestamp()}] ❌ Error al suscribirse al tópico nuevo: ${topic} - ${err.message}`);
                } else {
                    console.log(`[${getCurrentTimestamp()}] ✅ Suscrito y cacheado config para tópico nuevo: ${topic} (repNumber=${calculatedRepNumber})`);
                    subscribedTopics.add(topic);
                    // Guardar estado inicial y la configuración completa
                    valueCounters.set(topic, {
                        count: 0,
                        lastValue: null,
                        repNumber: calculatedRepNumber,
                        config: dbConfig // Guardar el objeto de configuración completo
                    });
                }
            });
        } else {
            // --- Tópico Ya Suscrito ---
            const counterData = valueCounters.get(topic);
            let configUpdated = false;
            let repNumberUpdated = false;

            if (counterData) {
                 // Verificar si repNumber cambió
                 if (counterData.repNumber !== calculatedRepNumber) {
                     console.log(`[${getCurrentTimestamp()}] 🔄 Actualizando repNumber para ${topic} de ${counterData.repNumber} a ${calculatedRepNumber}`);
                     counterData.repNumber = calculatedRepNumber;
                     repNumberUpdated = true;
                 }
                 // Verificar si la configuración cacheada cambió (comparación simple con JSON.stringify)
                 // Es suficiente si los cambios son raros y la estructura es consistente
                 if (JSON.stringify(counterData.config) !== JSON.stringify(dbConfig)) {
                      console.log(`[${getCurrentTimestamp()}] 🔄 Actualizando configuración cacheada para ${topic}.`);
                      counterData.config = dbConfig; // Actualizar la caché de configuración
                      configUpdated = true;
                 }

            } else {
                 // Estado inconsistente: suscrito pero sin datos en caché. Re-inicializar.
                 console.warn(`[${getCurrentTimestamp()}] ⚠️ Tópico ${topic} suscrito pero sin datos en caché. Re-inicializando.`);
                 valueCounters.set(topic, {
                     count: 0,
                     lastValue: null,
                     repNumber: calculatedRepNumber,
                     config: dbConfig
                 });
            }
            // Marcar como procesado (ya sea nuevo o existente verificado)
            currentSubscribed.delete(topic);
        }
    }

    // 2. Desuscribirse de tópicos que ya no están en la DB (o eran inválidos)
    for (const topic of currentSubscribed) {
        console.log(`[${getCurrentTimestamp()}] ℹ️ Tópico ${topic} ya no está en la DB o es inválido. Desuscribiendo y limpiando caché...`);
        mqttClient.unsubscribe(topic, (err) => {
            if (err) {
                console.error(`[${getCurrentTimestamp()}] ❌ Error al desuscribirse del tópico: ${topic} - ${err.message}`);
            } else {
                console.log(`[${getCurrentTimestamp()}] ✅ Desuscrito del tópico: ${topic}`);
                subscribedTopics.delete(topic);
                valueCounters.delete(topic); // Eliminar su estado y caché
            }
        });
    }
    console.log(`[${getCurrentTimestamp()}] ✅ Sincronización completada. Tópicos activos: ${subscribedTopics.size}`);
}

// Función para resetear contadores (sin cambios)
function resetAllRepetitionCounters() {
    // ... (igual que antes) ...
    const now = getCurrentTimestamp();
    console.log(`[${now}] ⏰ Ejecutando reseteo periódico de contadores de repetición...`);
    let resetCount = 0;
    for (const [topic, counterData] of valueCounters.entries()) {
        if (counterData.count !== 0) {
             counterData.count = 0;
             resetCount++;
        }
    }
    if (resetCount > 0) {
        console.log(`[${now}] ✅ Contadores de repetición reseteados para ${resetCount} tópicos.`);
    } else {
        console.log(`[${now}] ✅ No se necesitaron reseteos de contadores (todos estaban en 0).`);
    }
}

// --- MODIFICADO: Ahora usa la configuración cacheada ---
async function processCallApi(topic, data) {
    // Comprobar si tenemos datos para este tópico en la caché/contador
    if (!valueCounters.has(topic)) {
        console.warn(`[${getCurrentTimestamp()}] ⚠️ Recibido mensaje para tópico ${topic} pero no está en caché/contador (puede ser temporal tras reconexión). Ignorando.`);
        return;
    }

    const topicCounter = valueCounters.get(topic);

    // --- OBTENER CONFIG DESDE CACHÉ ---
    const modbusConfig = topicCounter.config; // Obtener config desde memoria
    if (!modbusConfig) {
        // Esto indica un problema si llegamos aquí, ya que valueCounters.has(topic) era true
        console.error(`[${getCurrentTimestamp()}] ❌ Error crítico: No se encontró configuración en caché para ${topic}. Forzando sincronización.`);
        await synchronizeTopicsAndSubscriptions(); // Intentar arreglar el estado
        return;
    }
    // --- FIN OBTENER CONFIG DESDE CACHÉ ---

    // --- YA NO SE NECESITA CONSULTA A DB AQUÍ ---

    // Procesar el mensaje (envuelto en try/catch para errores inesperados)
    try {
        // Parsear JSON (sin cambios)
        let parsedData;
        try {
            parsedData = JSON.parse(data);
            if (typeof parsedData.value === 'undefined' || (parsedData.value !== null && typeof parsedData.value !== 'number')) {
                 console.error(`[${getCurrentTimestamp()}] ❌ Mensaje JSON inválido para ${topic}: "value" falta, no es número o no es null. Data: ${data}`);
                 return;
            }
        } catch (e) {
            console.error(`[${getCurrentTimestamp()}] ❌ Error al parsear JSON para ${topic}: ${e.message}. Data: ${data}`);
            return;
        }

        const currentValue = parsedData.value;
        // Usar config de la caché
        const modelName = modbusConfig.model_name;

        // --- Lógica de Repetición y Variación (Usa modbusConfig de la caché) ---
        let shouldCallApi = true;
         if (currentValue === null) {
             console.warn(`[${getCurrentTimestamp()}] ⚠️ Valor recibido es null para ${topic}. Tratando como valor inválido. No se llamará a API.`);
             shouldCallApi = false;
         } else if (modelName === 'weight') {
            // ... (lógica usa modbusConfig.variacion_number, modbusConfig.conversion_factor) ...
             if (topicCounter.lastValue !== null) {
                if (currentValue === topicCounter.lastValue) {
                    topicCounter.count++;
                    console.log(`[${getCurrentTimestamp()}] 🔁 Valor repetido para ${topic}. Check: count(${topicCounter.count}) >= repNumber(${topicCounter.repNumber})? Valor: ${currentValue}`);
                    if (topicCounter.count >= topicCounter.repNumber) {
                        console.log(`[${getCurrentTimestamp()}] 🚫 Límite de repetición (${topicCounter.repNumber}) alcanzado/superado para ${topic}. No se llamará a la API.`);
                        shouldCallApi = false;
                    }
                } else {
                    const diff = Math.abs(currentValue - topicCounter.lastValue);
                    const variacionNumber = parseFloat(modbusConfig.variacion_number); // Desde caché
                    const conversionFactor = parseFloat(modbusConfig.conversion_factor) || 1; // Desde caché
                    const variacionInUnits = (!isNaN(variacionNumber) && !isNaN(conversionFactor)) ? variacionNumber * conversionFactor : 0;

                    console.log(`[${getCurrentTimestamp()}] ↔️ Valor cambiado para ${topic}. Nuevo: ${currentValue}, Anterior: ${topicCounter.lastValue}, Diff: ${diff.toFixed(3)}, Umbral: ${variacionInUnits.toFixed(3)}`);

                    if (variacionInUnits > 0 && diff > 0 && diff < variacionInUnits) {
                        console.log(`[${getCurrentTimestamp()}] 📉 Diferencia (${diff.toFixed(3)}) menor al umbral (${variacionInUnits.toFixed(3)}) para ${topic}. Se omite llamada a API y se mantiene valor anterior (${topicCounter.lastValue}).`);
                        parsedData.value = topicCounter.lastValue;
                        topicCounter.count++;
                         console.log(`[${getCurrentTimestamp()}] 🔁 Contador (por variación mínima) para ${topic}. Check: count(${topicCounter.count}) >= repNumber(${topicCounter.repNumber})?`);
                        if (topicCounter.count >= topicCounter.repNumber) {
                             console.log(`[${getCurrentTimestamp()}] 🚫 Límite de repetición (${topicCounter.repNumber}) alcanzado/superado (por variación mínima) para ${topic}. No se llamará a la API.`);
                             shouldCallApi = false;
                        }
                    } else {
                        console.log(`[${getCurrentTimestamp()}] ✅ Diferencia significativa (o umbral 0) para ${topic}. Reseteando contador a 0.`);
                        topicCounter.count = 0;
                        topicCounter.lastValue = currentValue;
                    }
                }
            } else {
                console.log(`[${getCurrentTimestamp()}] ✨ Primer valor válido recibido para ${topic}: ${currentValue}. Reseteando contador a 0.`);
                topicCounter.lastValue = currentValue;
                topicCounter.count = 0;
            }
        } else if (modelName === 'height') {
            // ... (lógica usa modbusConfig.dimension_default desde caché) ...
             const dimensionDefault = parseFloat(modbusConfig.dimension_default); // Desde caché
             if (!isNaN(dimensionDefault) && currentValue >= dimensionDefault) {
                  console.log(`[${getCurrentTimestamp()}] 📏 Valor ${currentValue} >= dimension_default (${dimensionDefault}) para ${topic}. No se llamará a la API.`);
                  shouldCallApi = false;
             } else {
                  if (topicCounter.lastValue !== null) {
                     if (currentValue === topicCounter.lastValue) {
                         topicCounter.count++;
                         console.log(`[${getCurrentTimestamp()}] 🔁 Valor repetido para ${topic} (height). Check: count(${topicCounter.count}) >= repNumber(${topicCounter.repNumber})? Valor: ${currentValue}`);
                         if (topicCounter.count >= topicCounter.repNumber) {
                             console.log(`[${getCurrentTimestamp()}] 🚫 Límite de repetición (${topicCounter.repNumber}) alcanzado/superado para ${topic} (height). No se llamará a la API.`);
                             shouldCallApi = false;
                         }
                     } else {
                         console.log(`[${getCurrentTimestamp()}] ✅ Valor cambiado para ${topic} (height). Nuevo: ${currentValue}, Anterior: ${topicCounter.lastValue}. Reseteando contador a 0.`);
                         topicCounter.count = 0;
                         topicCounter.lastValue = currentValue;
                     }
                  } else {
                     console.log(`[${getCurrentTimestamp()}] ✨ Primer valor válido recibido para ${topic} (height): ${currentValue}. Reseteando contador a 0.`);
                     topicCounter.lastValue = currentValue;
                     topicCounter.count = 0;
                  }
             }
        } else {
            // ... (lógica básica de repetición) ...
             if (topicCounter.lastValue !== null) {
                 if (currentValue === topicCounter.lastValue) {
                     topicCounter.count++;
                     console.log(`[${getCurrentTimestamp()}] 🔁 Valor repetido para ${topic} (otro). Check: count(${topicCounter.count}) >= repNumber(${topicCounter.repNumber})? Valor: ${currentValue}`);
                     if (topicCounter.count >= topicCounter.repNumber) {
                         console.log(`[${getCurrentTimestamp()}] 🚫 Límite de repetición (${topicCounter.repNumber}) alcanzado/superado para ${topic} (otro). No se llamará a la API.`);
                         shouldCallApi = false;
                     }
                 } else {
                     console.log(`[${getCurrentTimestamp()}] ✅ Valor cambiado para ${topic} (otro). Nuevo: ${currentValue}, Anterior: ${topicCounter.lastValue}. Reseteando contador a 0.`);
                     topicCounter.count = 0;
                     topicCounter.lastValue = currentValue;
                 }
             } else {
                 console.log(`[${getCurrentTimestamp()}] ✨ Primer valor válido recibido para ${topic} (otro): ${currentValue}. Reseteando contador a 0.`);
                 topicCounter.lastValue = currentValue;
                 topicCounter.count = 0;
             }
        }
        // --- Fin Lógica Repetición ---


        // Llamar a la API si es necesario (sin cambios)
        if (shouldCallApi) {
            const dataToSend = {
                id: modbusConfig.id, // Usar id desde caché
                data: parsedData
            };

            let apiUrl = process.env.LOCAL_SERVER || '';
            // ... (resto de la lógica de llamada a API sin cambios) ...
             if (!apiUrl.startsWith('http://') && !apiUrl.startsWith('https://')) {
                 console.warn(`[${getCurrentTimestamp()}] ⚠️ LOCAL_SERVER no incluye protocolo (http/https). Asumiendo http.`);
                 apiUrl = 'http://' + apiUrl;
            }
            apiUrl = apiUrl.replace(/\/$/, '');
            apiUrl += '/api/modbus-process-data-mqtt';

            console.log(`[${getCurrentTimestamp()}] 🚀 Llamando a API: ${apiUrl} con datos para Modbus ID ${modbusConfig.id} (Tópico: ${topic})`);

            try {
                const response = await axios.post(apiUrl, dataToSend, { timeout: 10000 });
                console.log(`[${getCurrentTimestamp()}] ✅ API OK para ${topic}. Status: ${response.status}. Respuesta: ${JSON.stringify(response.data)}`);
            } catch (apiError) {
                if (apiError.response) {
                    console.error(`[${getCurrentTimestamp()}] ❌ Error API (${apiError.response.status}) para ${topic}: ${JSON.stringify(apiError.response.data)}`);
                } else if (apiError.request) {
                    console.error(`[${getCurrentTimestamp()}] ❌ Error API para ${topic}: No se recibió respuesta o error de red. ${apiError.message}`);
                } else {
                    console.error(`[${getCurrentTimestamp()}] ❌ Error API para ${topic}: Error en configuración de Axios. ${apiError.message}`);
                }
            }
        }

    } catch (error) {
         // Capturar errores inesperados durante el procesamiento del mensaje
         console.error(`[${getCurrentTimestamp()}] 💥 Error procesando mensaje para ${topic}: ${error.message}`, error.stack);
    }
}

// Función principal (sin cambios)
async function start() {
    console.log(`[${getCurrentTimestamp()}] ▶️ Iniciando servicio MQTT Listener...`);
    await connectToDatabase();
    connectMQTT();

    const syncInterval = 60 * 1000;
    console.log(`[${getCurrentTimestamp()}] ⏱️ Configurando intervalo de sincronización de tópicos y caché cada ${syncInterval / 1000} segundos.`);
    setIntervalAsync(async () => {
        if (isMqttConnected && dbConnection && dbConnection.state !== 'disconnected') {
            await synchronizeTopicsAndSubscriptions(); // Llama a la función que ahora también cachea
        } else {
             if (!isMqttConnected) console.log(`[${getCurrentTimestamp()}] ⚠️ Sincronización periódica omitida: MQTT no conectado.`);
             if (!dbConnection || dbConnection.state === 'disconnected') console.log(`[${getCurrentTimestamp()}] ⚠️ Sincronización periódica omitida: DB no conectada.`);
             if (!dbConnection || dbConnection.state === 'disconnected') {
                 console.log(`[${getCurrentTimestamp()}] 🔌 Intentando reconectar DB desde intervalo de sincronización...`);
                 await connectToDatabase();
             }
        }
    }, syncInterval);

    const resetIntervalMinutes = 20;
    const resetInterval = resetIntervalMinutes * 60 * 1000;
    console.log(`[${getCurrentTimestamp()}] ⏱️ Configurando intervalo de reseteo de contadores cada ${resetIntervalMinutes} minutos.`);
    setInterval(() => {
        resetAllRepetitionCounters();
    }, resetInterval);


    console.log(`[${getCurrentTimestamp()}] ✅ Servicio iniciado. Esperando mensajes MQTT.`);
}

// Manejo de cierre (sin cambios)
async function shutdown(exitCode = 0) {
    // ... (igual que antes) ...
    console.log(`[${getCurrentTimestamp()}] 🔴 Iniciando cierre ordenado (exit code ${exitCode})...`);
    if (shutdown.called) return;
    shutdown.called = true;
    try { if (mqttClient && mqttClient.connected) { console.log(`[${getCurrentTimestamp()}] ℹ️ Cerrando conexión MQTT...`); await new Promise((resolve, reject) => { const timeout = setTimeout(() => reject(new Error("MQTT close timeout")), 3000); mqttClient.end(true, { reasonString: 'Service shutting down' }, () => { clearTimeout(timeout); console.log(`[${getCurrentTimestamp()}] ✅ Cliente MQTT desconectado.`); resolve(); }); }); } else if (mqttClient) { console.log(`[${getCurrentTimestamp()}] ℹ️ Cliente MQTT no conectado, no se necesita cerrar.`); } } catch (e) { console.warn(`[${getCurrentTimestamp()}] ⚠️ Error al cerrar MQTT: ${e.message}`); }
    try { if (dbConnection && dbConnection.state !== 'disconnected') { console.log(`[${getCurrentTimestamp()}] ℹ️ Cerrando conexión DB...`); await dbConnection.end(); console.log(`[${getCurrentTimestamp()}] ✅ Conexión DB cerrada.`); } else if (dbConnection) { console.log(`[${getCurrentTimestamp()}] ℹ️ Conexión DB ya cerrada o no establecida.`); } } catch (e) { console.warn(`[${getCurrentTimestamp()}] ⚠️ Error al cerrar DB: ${e.message}`); }
    console.log(`[${getCurrentTimestamp()}] 👋 Servicio detenido.`);
    process.exit(exitCode);
}
shutdown.called = false;

// Capturar señales y errores (sin cambios)
process.on('SIGINT', () => shutdown(0));
process.on('SIGTERM', () => shutdown(0));
process.on('uncaughtException', (error, origin) => { console.error(`[${getCurrentTimestamp()}] 💥 EXCEPCIÓN NO CAPTURADA (${origin}): ${error.message}`, error.stack); shutdown(1); });
process.on('unhandledRejection', (reason, promise) => { console.error(`[${getCurrentTimestamp()}] 💥 RECHAZO DE PROMESA NO MANEJADO:`, reason); shutdown(1); });

// Iniciar la aplicación
start();
