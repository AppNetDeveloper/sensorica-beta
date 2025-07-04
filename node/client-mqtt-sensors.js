require('dotenv').config({ path: '../.env' });
const mqtt = require('mqtt');
const mysql = require('mysql2/promise');
const axios = require('axios');  // Usamos axios para las solicitudes HTTP
const { promisify } = require('util');
const setIntervalAsync = promisify(setInterval);

// Configuración de MQTT y base de datos
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

// Variables globales
let mqttClient;
let isMqttConnected = false;
let dbConnection;
let subscribedTopics = [];  // Array de tópicos a los que estamos suscritos
let sensorsCache = {};      // Objeto: { mqtt_topic_sensor: { id, sensor_type } }

// Función para obtener la fecha y hora actual
function getCurrentTimestamp() {
    return new Date().toLocaleString('en-GB').replace(',', '');
}

function connectMQTT() {
  const clientId = `mqtt_client_${Math.random().toString(16).substr(2, 8)}`;
  mqttClient = mqtt.connect(`mqtt://${mqttServer}:${mqttPort}`, {
    clientId,
    reconnectPeriod: 1000, // Reconectar cada segundo si se pierde la conexión
    clean: false
  });

  mqttClient.on('connect', () => {
    console.log(`[${getCurrentTimestamp()}] ✅ Conectado a MQTT Server: ${mqttServer}:${mqttPort}`);
    isMqttConnected = true;
    subscribeToTopics(); // Actualiza el cache y se suscribe a los tópicos

    // Procesamiento inmediato de cada mensaje recibido
    mqttClient.on('message', async (topic, message) => {
     // console.log(`[${getCurrentTimestamp()}] ✅ Mensaje recibido: Tópico: ${topic} | Datos: ${message.toString()}`);
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
  while (true) {
    try {
      dbConnection = await mysql.createConnection(dbConfig);
      console.log(`[${getCurrentTimestamp()}] ✅ Conectado a la base de datos`);
      break;
    } catch (error) {
      console.error(`[${getCurrentTimestamp()}] ❌ Error conectando a la base de datos: ${error.message}`);
      console.log(`[${getCurrentTimestamp()}] 🔄 Reintentando en 5 segundos...`);
      await new Promise(resolve => setTimeout(resolve, 5000));
    }
  }
}

async function getAllTopics() {
  try {
    const [rows] = await dbConnection.execute(
      'SELECT id, mqtt_topic_sensor, sensor_type, invers_sensors, json_api FROM sensors WHERE mqtt_topic_sensor IS NOT NULL AND mqtt_topic_sensor != ""'
    );
    return rows;
  } catch (error) {
    console.error(`[${getCurrentTimestamp()}] ❌ Error en getAllTopics: ${error.message}. Intentando reconectar a la DB...`);
    await connectToDatabase();
    return getAllTopics();
  }
}

async function subscribeToTopics() {
  if (!isMqttConnected) {
    console.log(`[${getCurrentTimestamp()}] ❌ mqttClient no está conectado. No se pueden suscribir a los tópicos.`);
    return;
  }

  const topicsData = await getAllTopics();
  const newTopics = [];
  sensorsCache = {};  // Reinicia el cache

  topicsData.forEach(row => {
    newTopics.push(row.mqtt_topic_sensor);
    sensorsCache[row.mqtt_topic_sensor] = {
      id: row.id,
      sensor_type: row.sensor_type,
      invers_sensors: row.invers_sensors,
      json_api: row.json_api
    };
  });

  // Suscribirse a nuevos tópicos
  newTopics.forEach(topic => {
    if (!subscribedTopics.includes(topic)) {
      mqttClient.subscribe(topic, (err) => {
        if (err) {
          console.log(`[${getCurrentTimestamp()}] ❌ Error al suscribirse al tópico: ${topic}`);
        } else {
          console.log(`[${getCurrentTimestamp()}] ✅ Suscrito al tópico: ${topic}`);
          subscribedTopics.push(topic);
        }
      });
    }
  });

  // Desuscribirse de tópicos que ya no existen
  subscribedTopics.forEach((topic, index) => {
    if (!newTopics.includes(topic)) {
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

// 🔄 Función auxiliar para realizar la llamada a la API con reintentos y backoff exponencial
async function callApiWithRetries(dataToSend, maxRetries = 5, initialDelay = 5000) {
    let attempt = 0;
    let delay = initialDelay;
  
    while (attempt < maxRetries) {
      try {
        const response = await axios.post(`${apiBaseUrl}/api/sensor-insert`, dataToSend);
        return response;
      } catch (error) {
        attempt++;
        // Extraemos el status, si existe
        const status = error.response ? error.response.status : 'No status';
        if (attempt >= maxRetries) {
          console.error(`[${getCurrentTimestamp()}] ❌ Error final en llamada a API después de ${maxRetries} intentos: ${error.message} (Status: ${status})`);
          throw error;
        }
        console.warn(`[${getCurrentTimestamp()}] ⚠️ Error en llamada a API (Status: ${status}), reintentando en ${delay}ms (Intento ${attempt} de ${maxRetries})`);
        await new Promise(resolve => setTimeout(resolve, delay));
        delay *= 2;
      }
    }
  }
  

// Función para extraer valores usando rutas JSON
function extractValueFromJson(jsonData, path) {
  if (!path) return jsonData.value; // Valor por defecto si no se especifica ruta
  
  try {
    // Para rutas simples (sin filtros)
    if (!path.includes('[?') && !path.includes('.*')) {
      return getNestedValue(jsonData, path);
    }
    
    // Para rutas con filtros por flag
    if (path.includes('[?(@.flag==')) {
      const match = path.match(/\[\?\(@\.flag=="([^"]+)"\)\]\.([\w]+)/);
      if (match && match.length === 3) {
        const flagToFind = match[1];
        const fieldToExtract = match[2];
        
        // Extraer la parte antes del filtro
        const arrayPath = path.split('[?')[0];
        const array = getNestedValue(jsonData, arrayPath);
        
        if (Array.isArray(array)) {
          const item = array.find(item => item.flag === flagToFind);
          return item ? item[fieldToExtract] : null;
        }
      }
    }
    
    // Si no se pudo extraer con las reglas anteriores, intentar con valor por defecto
    return jsonData.value || null;
  } catch (error) {
    console.error(`[${getCurrentTimestamp()}] ❌ Error extracting value using path ${path}: ${error.message}`);
    return null;
  }
}

// Función auxiliar para obtener valores anidados en un objeto
function getNestedValue(obj, path) {
  // Soporta rutas como "data.readings[0].temperature"
  const keys = path.replace(/\[(\d+)\]/g, '.$1').split('.');
  let result = obj;
  
  for (const key of keys) {
    if (result === null || result === undefined) return null;
    result = result[key];
  }
  
  return result;
}

async function processCallApi(topic, data) {
  try {
    const parsed = JSON.parse(data);
    const sensorConfig = sensorsCache[topic];
    if (!sensorConfig) {
      console.error(`[${getCurrentTimestamp()}] ❌ No se encontró configuración en el cache para el tópico ${topic}`);
      return;
    }
    
    // Extraer el valor usando la ruta configurada o 'value' por defecto
    const extractedValue = extractValueFromJson(parsed, sensorConfig.json_api);
    
    // Si no se pudo extraer un valor, registrar error y salir
    if (extractedValue === null || extractedValue === undefined) {
      console.error(`[${getCurrentTimestamp()}] ❌ No se pudo extraer valor usando la ruta ${sensorConfig.json_api || 'value'} para el tópico ${topic}`);
      return;
    }
    
    // Aplicar inversión si es necesario
    let newValue = sensorConfig.invers_sensors === 1 ? -extractedValue : extractedValue;

    // Si sensor_type es 0 y el value es 0, se omite el procesamiento
    if (sensorConfig.sensor_type === 0 && newValue === 0) {
      //console.log(`[${getCurrentTimestamp()}] ℹ️ Sensor ID ${sensorConfig.id} con sensor_type=0 y value=0, se omite el procesamiento.`);
      return;
    }
    
    const dataToSend = {
      value: newValue,
      id: sensorConfig.id
    };

    let apiUrl = process.env.LOCAL_SERVER;
    if (apiUrl.endsWith('/')) {
      apiUrl = apiUrl.slice(0, -1);
    }
    apiUrl += '/api/sensor-insert';

    // Llamada inmediata a la API
   // axios.post(apiUrl, dataToSend)
    //  .then(response => {
    //    console.log(`[${getCurrentTimestamp()}] ✅ Respuesta de la API para el Sensor ID ${sensorConfig.id}: ${JSON.stringify(response.data, null, 2)}`);
    //  })
    //  .catch(error => {
    //    console.error(`[${getCurrentTimestamp()}] ❌ Error al procesar los datos del Sensor ID ${sensorConfig.id}: ${error.message}`);
     // });

     //llamada api con cola de 5 intentos si falla 1
     const response = await callApiWithRetries(dataToSend);
     console.log(`[${getCurrentTimestamp()}] ✅ Respuesta de la API para el Sensor ID ${sensorConfig.id} y tópico ${topic}: ${JSON.stringify(response.data, null, 2)}`);
  } catch (error) {
    console.error(`[${getCurrentTimestamp()}] ❌ Error al procesar los datos del Sensor para el tópico ${topic}: ${error.message}`);
  }
}

async function start() {
  await connectToDatabase();
  connectMQTT();

  // Actualiza la suscripción y el cache cada 60 segundos
  await setIntervalAsync(async () => {
    if (isMqttConnected) {
      console.log(`[${getCurrentTimestamp()}] ✅ MQTT conectado, actualizando suscripciones y cache...`);
      await subscribeToTopics();
    } else {
      console.log(`[${getCurrentTimestamp()}] ⚠️ Esperando reconexión a MQTT...`);
    }
  }, 60000);
}

start();

process.on('SIGINT', () => {
  console.log(`[${getCurrentTimestamp()}] 🔴 Deteniendo el proceso...`);
  mqttClient.end(() => {
    console.log(`[${getCurrentTimestamp()}] ✅ Desconectado de MQTT`);
    dbConnection.end(() => {
      console.log(`[${getCurrentTimestamp()}] ✅ Desconectado de la base de datos`);
      process.exit(0);
    });
  });
});
