require('dotenv').config({ path: '../.env' });  // Cargar variables de entorno desde la ruta personalizada
const mqtt = require('mqtt');
const fs = require('fs');
const path = require('path');

let mqttClient;
let isMqttConnected = false;
let intervalId;  // Variable para almacenar el ID del intervalo

// Función para obtener la fecha y hora actual en formato [YYYY-MM-DD HH:mm:ss]
function getFormattedDate() {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');
  return `[${year}-${month}-${day} ${hours}:${minutes}:${seconds}]`;
}

// Función para recargar el .env si cambia
function reloadEnv() {
  console.log(`${getFormattedDate()} 🔄 Recargando archivo .env...`);
  require('dotenv').config({ path: '../.env' });
  console.log(`${getFormattedDate()} ✅ .env recargado`);
}

// Monitorear el archivo .env cada 30 segundos
fs.watchFile('../.env', { interval: 30000 }, (curr, prev) => {
  if (curr.mtime !== prev.mtime) {
    reloadEnv();
  }
});

// Función para conectar a MQTT
function connectMQTT() {
  const MQTT_BROKER = `mqtt://${process.env.MQTT_SENSORICA_SERVER}:${process.env.MQTT_SENSORICA_PORT}`;
  const clientId = `mqtt_client_${Math.random().toString(16).substr(2, 8)}`;

  mqttClient = mqtt.connect(MQTT_BROKER, {
    clientId: clientId,
    reconnectPeriod: 1000,  // Reconexión cada 1 segundo si se pierde la conexión
    clean: false
  });

  mqttClient.on('connect', () => {
    console.log(`${getFormattedDate()} ✅ Conectado a MQTT Server: ${process.env.MQTT_SENSORICA_SERVER}:${process.env.MQTT_SENSORICA_PORT}`);
    isMqttConnected = true;
    publishData(); // Llamada inmediata al conectar
  });

  mqttClient.on('error', (error) => {
    console.error(`${getFormattedDate()} ❌ Error en la conexión MQTT:`, error);
  });

  mqttClient.on('disconnect', () => {
    console.log(`${getFormattedDate()} 🔴 Desconectado de MQTT`);
    isMqttConnected = false;
  });
}

// Definir la carpeta donde se almacenan los archivos para server1
const server1Dir = path.join(__dirname, '../storage/app/mqtt/server1');

// Asegurarse de que la carpeta existe
if (!fs.existsSync(server1Dir)) {
  fs.mkdirSync(server1Dir, { recursive: true });
}

/**
 * Función recursiva para obtener todos los archivos .json en un directorio y sus subdirectorios.
 * Retorna un array con las rutas completas de cada archivo .json encontrado.
 */
function getAllJsonFiles(dir) {
  let results = [];
  const list = fs.readdirSync(dir, { withFileTypes: true });
  list.forEach((dirent) => {
    const fullPath = path.join(dir, dirent.name);
    if (dirent.isDirectory()) {
      // Si es un directorio, lo exploramos recursivamente
      results = results.concat(getAllJsonFiles(fullPath));
    } else if (dirent.isFile() && dirent.name.endsWith('.json')) {
      // Si es un archivo .json, lo agregamos al array de resultados
      results.push(fullPath);
    }
  });
  return results;
}

/**
 * Función para obtener los mensajes (archivos) desde la carpeta server1 y sus subdirectorios.
 * Retorna un array de objetos { file, filePath, data } para cada archivo JSON encontrado.
 */
function getDataFromFiles() {
  return new Promise((resolve, reject) => {
    try {
      // Obtener recursivamente la lista de archivos .json
      const files = getAllJsonFiles(server1Dir);

      // Crear promesas para leer el contenido de cada archivo
      const dataPromises = files.map(filePath => {
        return new Promise((res, rej) => {
          fs.readFile(filePath, 'utf8', (err, content) => {
            if (err) return rej(err);
            try {
              const data = JSON.parse(content);
              // 'file' será el nombre relativo, útil para logs
              const fileName = path.relative(server1Dir, filePath);
              res({ file: fileName, filePath, data });
            } catch (e) {
              rej(e);
            }
          });
        });
      });

      Promise.all(dataPromises)
        .then(resolve)
        .catch(reject);

    } catch (error) {
      reject(error);
    }
  });
}

/**
 * Función para publicar un mensaje en MQTT y, si se publica con éxito, borrar el archivo.
 * Si hay error o no hay conexión, el archivo no se borra y se reintenta en el siguiente ciclo.
 */
async function publishToMqtt(fileEntry) {
  const { file, filePath, data } = fileEntry;
  // Se espera que 'data' tenga al menos 'topic' y 'message'
  const payload = typeof data.message === 'object' ? JSON.stringify(data.message) : data.message;

  if (!isMqttConnected) {
    console.log(`${getFormattedDate()} ⚠️ MQTT no conectado, reintentando archivo: ${file}`);
    return; // Deja el archivo para reintentar en el siguiente ciclo
  }

  mqttClient.publish(data.topic, payload, { qos: 0, retain: true }, (err) => {
    if (err) {
      console.error(`${getFormattedDate()} ❌ Error publicando ${file}:`, err);
      // No se elimina el archivo; se reintentará en el próximo ciclo
    } else {
      // Si la publicación es exitosa, se borra el archivo
      fs.unlink(filePath, (unlinkErr) => {
        if (unlinkErr) {
          console.error(`${getFormattedDate()} ❌ Error borrando el archivo ${file}:`, unlinkErr);
        } else {
          console.log(`${getFormattedDate()} ✅ Publicado y borrado archivo: ${file}`);
        }
      });
    }
  });
}

/**
 * Función para publicar todos los mensajes encontrados en la carpeta (y subcarpetas).
 * Si no hay archivos, muestra un log de que no hay datos nuevos.
 */
async function publishData() {
  try {
    const filesData = await getDataFromFiles();
    if (filesData.length === 0) {
      console.log(`${getFormattedDate()} ⚠️ No hay archivos nuevos para publicar.`);
      return;
    }
    // Publicar todos los mensajes de manera concurrente
    await Promise.all(filesData.map(publishToMqtt));
    console.log(`${getFormattedDate()} ✅ Proceso de publicación completado.`);
  } catch (error) {
    console.error(`${getFormattedDate()} ❌ Error procesando archivos:`, error);
  }
}

/**
 * Inicializar la conexión MQTT y el intervalo que revisa los archivos cada 500 ms.
 */
function initialize() {
  connectMQTT();
  // Establecer consulta periódica cada 500 ms (ajustar según la necesidad)
  intervalId = setInterval(async () => {
    if (isMqttConnected) {
      await publishData();
    } else {
      console.log(`${getFormattedDate()} ⚠️ MQTT no conectado, esperando reconexión...`);
    }
  }, 500);
}

initialize();

// Manejo de señal SIGINT para desconexión limpia
process.on('SIGINT', () => {
  console.log(`${getFormattedDate()} 🔴 Recibido SIGINT, deteniendo el proceso...`);
  clearInterval(intervalId);
  mqttClient.end(() => {
    console.log(`${getFormattedDate()} ✅ Desconectado de MQTT`);
    process.exit(0);
  });
});
