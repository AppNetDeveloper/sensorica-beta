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
  // Usar override: true para asegurar que las variables se actualicen
  require('dotenv').config({ path: '../.env', override: true });
  console.log(`${getFormattedDate()} ✅ .env recargado`);
  // Considerar reconectar MQTT si las credenciales han cambiado
  if (mqttClient && isMqttConnected) {
    console.log(`${getFormattedDate()} ℹ️  Considera reiniciar el cliente MQTT si las credenciales cambiaron.`);
  }
}

// Monitorear el archivo .env cada 30 segundos
fs.watchFile('../.env', { interval: 30000 }, (curr, prev) => {
  if (curr.mtime !== prev.mtime && curr.ino !== 0) {
    reloadEnv();
  } else if (curr.ino === 0) {
    console.log(`${getFormattedDate()} ⚠️  El archivo .env parece haber sido eliminado. No se puede recargar.`);
  }
});

// Función para conectar a MQTT
function connectMQTT() {
  // Utilizar las variables de entorno específicas para server2 si son diferentes
  const MQTT_BROKER = `mqtt://${process.env.MQTT_SERVER}:${process.env.MQTT_PORT}`;
  const clientId = `mqtt_client_server2_${Math.random().toString(16).substr(2, 8)}`;

  if (mqttClient) {
    mqttClient.end(true, () => {
        console.log(`${getFormattedDate()} ℹ️ Cliente MQTT anterior (server2) cerrado antes de reconectar.`);
        createNewMqttClient(MQTT_BROKER, clientId);
    });
  } else {
    createNewMqttClient(MQTT_BROKER, clientId);
  }
}

function createNewMqttClient(brokerUrl, clientId) {
  mqttClient = mqtt.connect(brokerUrl, {
    clientId: clientId,
    reconnectPeriod: 1000,  // Reconexión cada 1 segundo
    connectTimeout: 5000,   // Tiempo de espera para la conexión en ms
    clean: false            // Mantener sesiones y mensajes en cola
  });

  mqttClient.on('connect', () => {
    console.log(`${getFormattedDate()} ✅ Conectado a MQTT Server (Server2): ${process.env.MQTT_SERVER}:${process.env.MQTT_PORT}`);
    isMqttConnected = true;
  });

  mqttClient.on('error', (error) => {
    console.error(`${getFormattedDate()} ❌ Error en la conexión MQTT (Server2):`, error.message);
  });

  mqttClient.on('reconnect', () => {
    console.log(`${getFormattedDate()} 🔄 Intentando reconectar a MQTT (Server2)...`);
  });

  mqttClient.on('close', () => {
    console.log(`${getFormattedDate()} 🔴 Conexión MQTT (Server2) cerrada.`);
    isMqttConnected = false;
  });

  mqttClient.on('offline', () => {
    console.log(`${getFormattedDate()} 📴 Cliente MQTT (Server2) está offline.`);
    isMqttConnected = false;
  });
}

// Definir la carpeta donde se almacenan los archivos para server2
const server2Dir = path.join(__dirname, '../storage/app/mqtt/server2');

// Asegurarse de que la carpeta existe
if (!fs.existsSync(server2Dir)) {
  try {
    fs.mkdirSync(server2Dir, { recursive: true });
    console.log(`${getFormattedDate()} ✅ Directorio creado: ${server2Dir}`);
  } catch (error) {
    console.error(`${getFormattedDate()} ❌ Error creando directorio ${server2Dir}:`, error);
    process.exit(1); // Salir si no se puede crear el directorio base
  }
}

/**
 * Función recursiva para obtener todos los archivos .json en un directorio y sus subdirectorios.
 */
function getAllJsonFiles(dir) {
  let results = [];
  try {
    const list = fs.readdirSync(dir, { withFileTypes: true });
    list.forEach((dirent) => {
      const fullPath = path.join(dir, dirent.name);
      if (dirent.isDirectory()) {
        results = results.concat(getAllJsonFiles(fullPath));
      } else if (dirent.isFile() && dirent.name.endsWith('.json')) {
        results.push(fullPath);
      }
    });
  } catch (error) {
    console.error(`${getFormattedDate()} ❌ Error leyendo directorio ${dir}:`, error);
  }
  return results;
}

/**
 * Función para obtener los mensajes (archivos) desde la carpeta server2 y sus subdirectorios.
 * Retorna un array de objetos { file, filePath, data } para cada archivo JSON encontrado.
 * Si un archivo JSON es inválido, se intentará eliminar.
 */
function getDataFromFiles() {
  return new Promise((resolve, reject) => {
    try {
      const files = getAllJsonFiles(server2Dir); // Usar server2Dir
      if (files.length === 0) {
        return resolve([]);
      }

      const dataPromises = files.map(filePath => {
        return new Promise((res, rej) => {
          fs.readFile(filePath, 'utf8', (err, content) => {
            if (err) {
              // Si el archivo no existe (ENOENT) u otro error de lectura, se loguea y se rechaza la promesa para este archivo.
              console.error(`${getFormattedDate()} ❌ Error leyendo archivo ${filePath}:`, err.code, err.message);
              return rej(err);
            }
            try {
              const data = JSON.parse(content);
              const fileName = path.relative(server2Dir, filePath); // Usar server2Dir
              res({ file: fileName, filePath, data });
            } catch (e) {
              console.error(`${getFormattedDate()} ❌ Error parseando JSON en archivo: ${filePath}. Error: ${e.message}.`);
              console.log(`${getFormattedDate()} 🗑️  Intentando eliminar archivo JSON inválido: ${filePath}`);
              
              fs.unlink(filePath, (unlinkErr) => {
                if (unlinkErr) {
                  console.error(`${getFormattedDate()} ❌ Error eliminando archivo JSON inválido ${filePath}:`, unlinkErr);
                } else {
                  console.log(`${getFormattedDate()} ✅ Archivo JSON inválido eliminado: ${filePath}`);
                }
                // Se rechaza la promesa igualmente, ya que el archivo no se pudo procesar.
                const parsingError = new Error(`Error en JSON del archivo ${filePath} (eliminado o intento de eliminación): ${e.message}`);
                parsingError.file = filePath;
                parsingError.originalError = e;
                rej(parsingError);
              });
            }
          });
        });
      });

      Promise.allSettled(dataPromises)
        .then(results => {
            const successfullyReadFiles = [];
            results.forEach(result => {
                if (result.status === 'fulfilled') {
                    successfullyReadFiles.push(result.value);
                }
                // Los errores (incluidos los de parseo donde se intentó eliminar o errores de lectura como ENOENT) ya se loguearon.
                // No es necesario loguear de nuevo aquí a menos que se quiera un resumen específico.
            });
            resolve(successfullyReadFiles);
        });

    } catch (error) { // Este catch es para errores en getAllJsonFiles o errores síncronos iniciales.
      console.error(`${getFormattedDate()} ❌ Error general en getDataFromFiles (Server2):`, error);
      reject(error); // Esto haría que publishData falle si hay un error aquí.
    }
  });
}

/**
 * Función para publicar un mensaje en MQTT y, si se publica con éxito, borrar el archivo.
 */
async function publishToMqtt(fileEntry) {
  const { file, filePath, data } = fileEntry;

  if (!data || typeof data.topic !== 'string' || typeof data.message === 'undefined') {
    console.error(`${getFormattedDate()} ❌ Datos inválidos o faltantes (topic/message) en el archivo (Server2): ${file}. Contenido:`, data);
    // Considerar mover este archivo a una carpeta de 'errores' en lugar de simplemente no procesarlo.
    // fs.renameSync(filePath, path.join(path.dirname(filePath), '../errores_server2', path.basename(file)));
    throw new Error(`Datos inválidos en archivo ${file}`); // Lanzar error para que sea capturado en el bucle de publishData
  }

  const payload = typeof data.message === 'object' ? JSON.stringify(data.message) : String(data.message);

  if (!isMqttConnected) {
    console.log(`${getFormattedDate()} ⚠️ MQTT (Server2) no conectado, reintentando publicar archivo más tarde: ${file}`);
    // No se borra el archivo, se reintentará en la siguiente ejecución de publishData.
    throw new Error(`MQTT no conectado para archivo ${file}`); // Lanzar error para que sea capturado
  }

  return new Promise((resolve, reject) => {
    mqttClient.publish(data.topic, payload, { qos: 0, retain: true }, (err) => {
      if (err) {
        console.error(`${getFormattedDate()} ❌ Error publicando ${file} al topic ${data.topic} (Server2):`, err);
        reject(err); 
      } else {
        fs.unlink(filePath, (unlinkErr) => {
          if (unlinkErr) {
            console.error(`${getFormattedDate()} ❌ Error borrando el archivo ${file} (${filePath}) después de publicar (Server2):`, unlinkErr);
            reject(unlinkErr); 
          } else {
            // console.log(`${getFormattedDate()} ✅ Publicado y borrado archivo (Server2): ${file}`);
            resolve(); 
          }
        });
      }
    });
  });
}

/**
 * Función para publicar todos los mensajes encontrados en la carpeta (y subcarpetas) de forma secuencial.
 */
async function publishData() {
  try {
    const filesData = await getDataFromFiles(); // Obtiene solo los archivos que se pudieron leer y parsear correctamente
    
    if (filesData.length === 0) {
      // console.log(`${getFormattedDate()} ℹ️ No hay archivos nuevos para publicar (Server2).`);
      return;
    }
    console.log(`${getFormattedDate()} ⏳ Procesando ${filesData.length} archivo(s) para publicar (Server2) secuencialmente...`);

    let successCount = 0;
    let failureCount = 0;

    // Procesar cada archivo secuencialmente
    for (const fileEntry of filesData) {
      try {
        // publishToMqtt ahora es una promesa que se resuelve o rechaza.
        // Si se rechaza (p.ej., error de publicación, error al borrar), se captura en el catch de abajo.
        await publishToMqtt(fileEntry);
        successCount++;
      } catch (error) {
        // El error específico ya debería haber sido logueado dentro de publishToMqtt o si es por datos inválidos/MQTT no conectado.
        // Aquí solo contamos el fallo.
        console.error(`${getFormattedDate()} ⚠️ Fallo al procesar/publicar el archivo ${fileEntry.file}: ${error.message}`);
        failureCount++;
        // El archivo no se borró si la publicación falló o si el borrado mismo falló.
        // Se reintentará en la próxima ejecución de publishData si el archivo aún existe y es válido.
      }
    }

    if (filesData.length > 0) { // Solo loguear si hubo archivos para procesar
         console.log(`${getFormattedDate()} ✅ Proceso de publicación secuencial (Server2) completado. Éxitos: ${successCount}, Fallos/Reintentos en próximo ciclo: ${failureCount} de ${filesData.length} archivos.`);
    }

  } catch (error) {
    // Este catch es para errores que ocurran en getDataFromFiles() si este rechaza su promesa principal
    // (p.ej. un error síncrono inesperado dentro de getDataFromFiles antes de Promise.allSettled).
    console.error(`${getFormattedDate()} ❌ Error crítico procesando archivos para publicar (Server2):`, error);
  }
}

/**
 * Inicializar la conexión MQTT y el intervalo que revisa los archivos.
 */
function initialize() {
  connectMQTT();

  const checkInterval = parseInt(process.env.MQTT_SERVER2_CHECK_INTERVAL_MS, 10) || 
                        parseInt(process.env.MQTT_CHECK_INTERVAL_MS, 10) || 500;
  console.log(`${getFormattedDate()} ⏱️  Intervalo de revisión de archivos (Server2) configurado a ${checkInterval} ms.`);

  intervalId = setInterval(async () => {
    if (!isMqttConnected) {
      console.log(`${getFormattedDate()} ⚠️ MQTT (Server2) no conectado, esperando reconexión...`);
      if (!mqttClient || !mqttClient.reconnecting) {
        connectMQTT();
      }
      return;
    }
    // Solo llamar a publishData si está conectado.
    // publishData ahora es secuencial, por lo que no debería haber superposición masiva de llamadas.
    await publishData();
  }, checkInterval);
}

initialize();

// Manejo de señales SIGINT y SIGTERM para desconexión limpia
process.on('SIGINT', () => {
  console.log(`\n${getFormattedDate()} 🔴 Recibido SIGINT, deteniendo el proceso (Server2)...`);
  clearInterval(intervalId);
  if (mqttClient) {
    mqttClient.end(false, () => { // false para no forzar, permite que los mensajes en vuelo se envíen si es posible
      console.log(`${getFormattedDate()} ✅ Desconectado de MQTT (Server2).`);
      process.exit(0);
    });
  } else {
    process.exit(0);
  }
});

process.on('SIGTERM', () => {
    console.log(`\n${getFormattedDate()} 🔴 Recibido SIGTERM, deteniendo el proceso (Server2)...`);
    clearInterval(intervalId);
    if (mqttClient) {
      mqttClient.end(false, () => {
        console.log(`${getFormattedDate()} ✅ Desconectado de MQTT (Server2).`);
        process.exit(0);
      });
    } else {
      process.exit(0);
    }
});
