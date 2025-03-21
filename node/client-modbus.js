require('dotenv').config({ path: '../.env' });
const mqtt = require('mqtt');
const mysql = require('mysql2/promise');
const axios = require('axios');  // Usamos axios para las solicitudes HTTP
const { promisify } = require('util');
const setIntervalAsync = promisify(setInterval);

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
let subscribedTopics = [];
let valueCounters = {}; // Para almacenar los contadores de repetición por tópico

// Función para obtener la fecha y hora actual en formato YYYY-MM-DD HH:mm:ss
function getCurrentTimestamp() {
    return new Date().toLocaleString('en-GB').replace(',', '');
}

function connectMQTT() {
    const clientId = `mqtt_client_${Math.random().toString(16).substr(2, 8)}`;
    mqttClient = mqtt.connect(`mqtt://${mqttServer}:${mqttPort}`, {
        clientId,
        reconnectPeriod: 1000,  // Reconectar cada segundo si la conexión se pierde
        clean: false
    });

    mqttClient.on('connect', () => {
        console.log(`[${getCurrentTimestamp()}] ✅ Conectado a MQTT Server: ${mqttServer}:${mqttPort}`);
        isMqttConnected = true;
        subscribeToTopics(); // Nos suscribimos a los tópicos después de conectarnos
    
        // Solo agregar el 'on' para el manejo de mensajes después de la conexión
        mqttClient.on('message', async (topic, message) => {
            console.log(`[${getCurrentTimestamp()}] ✅ Mensaje recibido: Tópico: ${topic} | Datos: ${message.toString()}`);
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
            break; // Sale del bucle si la conexión es exitosa
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
        'SELECT mqtt_topic_modbus, rep_number FROM modbuses WHERE mqtt_topic_modbus IS NOT NULL AND mqtt_topic_modbus != ""'
      );
      return rows;
    } catch (error) {
      console.error(`[${getCurrentTimestamp()}] ❌ Error en getAllTopics: ${error.message}. Intentando reconectar a la DB...`);
      await connectToDatabase();
      return getAllTopics(); // Vuelve a intentar la consulta tras reconectar
    }
  }
  

async function subscribeToTopics() {
    if (!isMqttConnected) {
        console.log(`[${getCurrentTimestamp()}] ❌ mqttClient no está conectado. No se pueden suscribir a los tópicos.`);
        return;
    }

    const topics = await getAllTopics();

    // Suscribirse a los nuevos tópicos que no estén ya suscritos
    topics.forEach(topic => {
        if (!subscribedTopics.includes(topic.mqtt_topic_modbus)) {
            mqttClient.subscribe(topic.mqtt_topic_modbus, (err) => {
                if (err) {
                    console.log(`[${getCurrentTimestamp()}] ❌ Error al suscribirse al tópico: ${topic.mqtt_topic_modbus}`);
                } else {
                    const repNumber = parseInt(topic.rep_number, 10) + 1; // Asegurar conversión numérica
                    console.log(`[${getCurrentTimestamp()}] ✅ Suscrito al tópico: ${topic.mqtt_topic_modbus} (rep_number ajustado a: ${repNumber})`);
                    subscribedTopics.push(topic.mqtt_topic_modbus);
                    valueCounters[topic.mqtt_topic_modbus] = { count: 0, lastValue: null, repNumber, zeroCount: 0 };
                }
            });
        }
    });

    // Desuscribirse de los tópicos que ya no existen en la base de datos
    subscribedTopics.forEach((topic, index) => {
        if (!topics.some(t => t.mqtt_topic_modbus === topic)) {
            mqttClient.unsubscribe(topic, (err) => {
                if (err) {
                    console.log(`[${getCurrentTimestamp()}] ❌ Error al desuscribirse del tópico: ${topic}`);
                } else {
                    console.log(`[${getCurrentTimestamp()}] ✅ Desuscrito del tópico: ${topic}`);
                    subscribedTopics.splice(index, 1); // Eliminamos el tópico de la lista de suscritos
                    delete valueCounters[topic]; // Eliminamos el contador
                }
            });
        }
    });
}

async function updateRepNumber() {
    const topics = await getAllTopics();
    topics.forEach(topic => {
        const newRepNumber = parseInt(topic.rep_number, 10) + 1; // Asegurar conversión numérica

        // Si el rep_number ha cambiado para un tópico suscrito, lo actualizamos
        if (valueCounters[topic.mqtt_topic_modbus] && valueCounters[topic.mqtt_topic_modbus].repNumber !== newRepNumber) {
            valueCounters[topic.mqtt_topic_modbus].repNumber = topic.rep_number;
            console.log(`[${getCurrentTimestamp()}] ✅ rep_number actualizado para el tópico ${topic.mqtt_topic_modbus} a ${newRepNumber}`);
        }
    });
}

async function processCallApi(topic, data) {
    try {
        const config = await dbConnection.execute('SELECT * FROM modbuses WHERE mqtt_topic_modbus = ?', [topic]);
        const modbusConfig = config[0][0];
        const dataToSend = {
            id: modbusConfig.id,
            data: JSON.parse(data) // Asumiendo que los datos están en formato JSON
        };

        // Verificamos si el valor ha cambiado
        const parsedData = JSON.parse(data);
        const value = parsedData.value;
        const topicCounter = valueCounters[topic];

        if (topicCounter) {
            // Si el valor ha cambiado, reiniciar el contador
            if (value !== topicCounter.lastValue) {
                topicCounter.count = 0;
                topicCounter.lastValue = value;
            }

            // Si el contador alcanza el rep_number, no enviamos la API
            if (topicCounter.count >= topicCounter.repNumber) {
                console.log(`[${getCurrentTimestamp()}] ⚠️ El valor se ha repetido más de ${topicCounter.repNumber} veces para el tópico ${topic}. No se llamará a la API.`);
                return; // No procesamos más
            }

            // Incrementamos el contador si el valor sigue siendo el mismo
            topicCounter.count++;
        }

        // Verificar si LOCAL_SERVER tiene barra al final y quitarla si existe
        let apiUrl = process.env.LOCAL_SERVER;
        if (apiUrl.endsWith('/')) {
            apiUrl = apiUrl.slice(0, -1);  // Eliminar la barra final
        }

        // Agregar el endpoint
        apiUrl += '/api/modbus-process-data-mqtt';

        // Realizar la solicitud HTTP sin bloquear el flujo
        axios.post(apiUrl, dataToSend)
            .then(response => {
                // Convertir la respuesta de la API a una cadena JSON para visualizarla mejor
                console.log(`[${getCurrentTimestamp()}] ✅ Respuesta de la API para el Modbus ID ${topic}: ${JSON.stringify(response.data, null, 2)}`);
            })
            .catch(error => {
                console.error(`[${getCurrentTimestamp()}] ❌ Error al procesar los datos del Modbus ID ${topic}: ${error.message}`);
            });

    } catch (error) {
        console.error(`[${getCurrentTimestamp()}] ❌ Error al procesar los datos del Modbus ID ${topic}: ${error.message}`);
    }
}

// Función principal
async function start() {
    await connectToDatabase();
    connectMQTT();
    
    // Verificar y actualizar las suscripciones cada 1 minuto
    await setIntervalAsync(async () => {
        if (isMqttConnected) {
            console.log(`[${getCurrentTimestamp()}] ✅ MQTT conectado, actualizando suscripciones...`);
            await subscribeToTopics();  // Revisa y actualiza las suscripciones si MQTT está conectado
            await updateRepNumber();  // Actualiza el rep_number de los tópicos
        } else {
            console.log(`[${getCurrentTimestamp()}] ⚠️ Esperando reconexión a MQTT...`);
        }
    }, 60000); // Ejecutar cada 60 segundos
}

// Iniciar la aplicación
start();

// Manejo de señales
process.on('SIGINT', () => {
    console.log(`[${getCurrentTimestamp()}] 🔴 Deteniendo el proceso...`);
    mqttClient.end(() => {
        console.log(`[${getCurrentTimestamp()}] ✅ Desconectado de MQTT`);
        dbConnection.end(() => {
            console.log(`[${getCurrentTimestamp()}] ✅ Desconectado de la base de datos`);
            process.exit(0); // Salir de manera controlada
        });
    });
});
