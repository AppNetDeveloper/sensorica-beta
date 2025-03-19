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

function connectMQTT() {
    const clientId = `mqtt_client_${Math.random().toString(16).substr(2, 8)}`;
    mqttClient = mqtt.connect(`mqtt://${mqttServer}:${mqttPort}`, {
        clientId,
        reconnectPeriod: 1000,  // Reconectar cada segundo si la conexión se pierde
        clean: false
    });

    mqttClient.on('connect', () => {
        console.log(`✅ Conectado a MQTT Server: ${mqttServer}:${mqttPort}`);
        isMqttConnected = true;
        subscribeToTopics(); // Nos suscribimos a los tópicos después de conectarnos

        // Solo agregar el 'on' para el manejo de mensajes después de la conexión
        mqttClient.on('message', async (topic, message) => {
            console.log(`✅ Mensaje recibido: Tópico: ${topic} | Datos: ${message.toString()}`);
            await processCallApi(topic, message.toString());
        });
    });

    mqttClient.on('disconnect', () => {
        console.log('🔴 Desconectado de MQTT');
        isMqttConnected = false;
    });

    mqttClient.on('error', (error) => {
        console.error('❌ Error en la conexión MQTT:', error);
        isMqttConnected = false;
    });

    mqttClient.on('reconnect', () => {
        console.log('⚠️ Intentando reconectar a MQTT...');
    });
}

async function connectToDatabase() {
    try {
        dbConnection = await mysql.createConnection(dbConfig);
        console.log('✅ Conectado a la base de datos');
    } catch (error) {
        console.error('❌ Error al conectar con la base de datos:', error);
    }
}

async function getAllTopics() {
    const [rows] = await dbConnection.execute('SELECT mqtt_topic_modbus FROM modbuses WHERE mqtt_topic_modbus IS NOT NULL AND mqtt_topic_modbus != ""');
    return rows.map(row => row.mqtt_topic_modbus);
}

async function subscribeToTopics() {
    if (!mqttClient || !isMqttConnected) {
        console.log('❌ mqttClient no está conectado. No se pueden suscribir a los tópicos.');
        return;
    }

    const topics = await getAllTopics();

    topics.forEach(topic => {
        mqttClient.subscribe(topic, (err) => {
            if (err) {
                console.log(`❌ Error al suscribirse al tópico: ${topic}`);
            } else {
                console.log(`✅ Suscrito al tópico: ${topic}`);
            }
        });
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

        // Verificar si LOCAL_SERVER tiene barra al final y quitarla si existe
        let apiUrl = process.env.LOCAL_SERVER;
        if (apiUrl.endsWith('/')) {
            apiUrl = apiUrl.slice(0, -1);  // Eliminar la barra final
        }

        // Agregar el endpoint
        apiUrl += '/api/modbus-process-data-mqtt';

        // Aquí no usamos `await`, la llamada HTTP será asíncrona
        axios.post(apiUrl, dataToSend)
            .then(response => {
                // Convertir la respuesta de la API a una cadena JSON para visualizarla mejor
                console.log(`✅ Respuesta de la API para el Modbus ID ${topic}: ${JSON.stringify(response.data, null, 2)}`);
            })
            .catch(error => {
                console.error(`❌ Error al procesar los datos del Modbus ID ${topic}: ${error.message}`);
            });

    } catch (error) {
        console.error(`❌ Error al procesar los datos del Modbus ID ${topic}: ${error.message}`);
    }
}

// Función principal
async function start() {
    await connectToDatabase();
    connectMQTT();
    
    // Si el broker está desconectado, no hacer nada
    await setIntervalAsync(async () => {
        if (!isMqttConnected) {
            console.log('⚠️ Esperando reconexión a MQTT...');
        }
    }, 5000); // Verifica la conexión cada 5 segundos
}

// Iniciar la aplicación
start();

// Manejo de señales
process.on('SIGINT', () => {
    console.log('🔴 Deteniendo el proceso...');
    mqttClient.end(() => {
        console.log('✅ Desconectado de MQTT');
        dbConnection.end(() => {
            console.log('✅ Desconectado de la base de datos');
            process.exit(0); // Salir de manera controlada
        });
    });
});
