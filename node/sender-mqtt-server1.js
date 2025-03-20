require('dotenv').config({ path: '../.env' });  // Cargar archivo .env desde la ruta personalizada
const mqtt = require('mqtt');
const mysql = require('mysql2/promise');
const fs = require('fs'); // Usamos fs para monitorear el archivo .env

let mqttClient;
let dbConnection;
let isMqttConnected = false;  // Estado de la conexión MQTT

// Función para cargar de nuevo el archivo .env cuando cambie
function reloadEnv() {
    console.log('🔄 Recargando archivo .env...');
    require('dotenv').config({ path: '../.env' });  // Recargar el archivo .env
    console.log('✅ .env recargado');
}

// Monitorear el archivo .env cada 5 segundos para detectar cambios
fs.watchFile('../.env', { interval: 5000 }, (curr, prev) => {
    if (curr.mtime !== prev.mtime) {
        reloadEnv();  // Recargar el archivo .env si ha cambiado
    }
});

// Función para conectar a MQTT con los valores actuales de .env
function connectMQTT() {
    const MQTT_BROKER = `mqtt://${process.env.MQTT_SENSORICA_SERVER}:${process.env.MQTT_SENSORICA_PORT}`;
    const clientId = `mqtt_client_${Math.random().toString(16).substr(2, 8)}`;
    
    mqttClient = mqtt.connect(MQTT_BROKER, {
        clientId: clientId,
        reconnectPeriod: 1000,  // Reconexión cada 1 segundo si la conexión se pierde
        clean: false  // Mantener el estado de suscripción incluso si el cliente se desconecta
    });

    mqttClient.on('connect', () => {
        console.log(`✅ Conectado a MQTT Server: ${process.env.MQTT_SENSORICA_SERVER}:${process.env.MQTT_SENSORICA_PORT}`);
        isMqttConnected = true;  // Marcar que estamos conectados al broker MQTT
        publishData(); // Publicar datos al conectarse
    });

    mqttClient.on('error', (error) => {
        console.error('❌ Error en la conexión MQTT:', error);
    });

    mqttClient.on('disconnect', () => {
        console.log('🔴 Desconectado de MQTT');
        isMqttConnected = false;  // Marcar que estamos desconectados del broker MQTT
    });
}

// Obtener las variables de entorno para la base de datos y MQTT
const DB_HOST = process.env.DB_HOST;
const DB_PORT = process.env.DB_PORT;
const DB_DATABASE = process.env.DB_DATABASE;
const DB_USERNAME = process.env.DB_USERNAME;
const DB_PASSWORD = process.env.DB_PASSWORD;

// Configuración de la base de datos
const dbConfig = {
    host: DB_HOST,
    port: DB_PORT,
    user: DB_USERNAME,
    password: DB_PASSWORD,
    database: DB_DATABASE
};

// Mantener la conexión persistente a la base de datos
async function connectToDatabase() {
    try {
        // Conectar a la base de datos y mantener la conexión abierta
        dbConnection = await mysql.createConnection(dbConfig);
        console.log('✅ Conectado a la base de datos');
    } catch (error) {
        console.error('❌ Error al conectar con la base de datos:', error);
    }
}

// Obtener los datos de la base de datos sin cerrar la conexión
async function getDataFromDatabase() {
    try {
        const [rows] = await dbConnection.execute('SELECT * FROM mqtt_send_server1 ORDER BY id ASC LIMIT 100');
        return rows;
    } catch (error) {
        console.error('❌ Error al obtener datos de la base de datos:', error);
        return [];
    }
}

// Función para publicar en MQTT
async function publishToMqtt(entry) {
    try {
        const payload = typeof entry.json_data === 'object' ? JSON.stringify(entry.json_data) : entry.json_data;
        
        if (isMqttConnected) {
            // Publicamos en MQTT solo si estamos conectados
            mqttClient.publish(entry.topic, payload, { qos: 0, retain: true });
            console.log(`✅ Publicado en MQTT: Tópico: ${entry.topic} | Datos: ${payload}`);
            // Solo eliminar el registro si la publicación fue exitosa
            deleteProcessedEntries([entry.id]);
        } else {
            console.log('⚠️ No hay conexión MQTT, esperando reconexión...');
        }
    } catch (error) {
        console.error(`❌ Error al publicar en MQTT: ${error.message}`);
    }
}

// Eliminar los registros procesados después de la publicación
async function deleteProcessedEntries(ids) {
    try {
        // Convertir los IDs a enteros y desestructurarlos en la consulta
        const integerIds = ids.map(id => parseInt(id, 10)); // Convertir a enteros
        const placeholders = integerIds.map(() => '?').join(', '); // Crear los placeholders para cada ID

        // Ejecutar la consulta de eliminación con los IDs como parámetros individuales
        await dbConnection.execute(`DELETE FROM mqtt_send_server1 WHERE id IN (${placeholders})`, integerIds);
        console.log(`✅ Eliminaron los registros con IDs: ${integerIds.join(', ')}`);
    } catch (error) {
        console.error('❌ Error al eliminar registros de la base de datos:', error);
    }
}

// Publicar los datos obtenidos de la base de datos
async function publishData() {
    const entries = await getDataFromDatabase();

    if (entries.length === 0) {
        console.log('⚠️ No hay datos nuevos para publicar.');
        return;
    }

    const ids = entries.map(entry => entry.id);  // Obtener los IDs de los registros

    // Publicar los datos de todos los registros
    const publishPromises = entries.map(entry => publishToMqtt(entry));
    await Promise.all(publishPromises);

    // Eliminar los registros procesados de la base de datos
    await deleteProcessedEntries(ids);

    console.log('✅ Todos los datos han sido publicados y eliminados.');
}

// Inicializar la conexión MQTT y la base de datos
async function initialize() {
    await connectToDatabase();  // Conectar a la base de datos
    connectMQTT();  // Conectar a MQTT

    // Realizamos una consulta periódica cada 1 segundo para obtener nuevos datos
    setInterval(async () => {
        if (isMqttConnected) {  // Solo consultamos si estamos conectados a MQTT
            await publishData();  // Consultamos y publicamos los datos
        } else {
            console.log('⚠️ No hay conexión a MQTT, esperando reconexión...');
        }
    }, 500);  // 1000 ms = 1 segundo
}

// Ejecutar inicialización
initialize();

// Manejo de señales y desconexión limpia
process.on('SIGINT', () => {
    console.log('🔴 Recibido SIGINT, deteniendo el proceso...');
    
    // Detener el intervalo de ejecución para evitar más consultas
    clearInterval(intervalId);  // Detener las consultas periódicas

    // Desconectar de MQTT y la base de datos de manera ordenada
    mqttClient.end(() => {
        console.log('✅ Desconectado de MQTT');
        
        // Cerrar la conexión de base de datos de forma ordenada
        dbConnection.end(() => {
            console.log('✅ Desconectado de la base de datos');
            process.exit(0); // Salir del proceso de forma limpia
        });
    });
});
