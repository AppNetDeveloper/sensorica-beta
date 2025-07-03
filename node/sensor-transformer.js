#!/usr/bin/env node
/**
 * Sensor Transformer
 * 
 * Este script se conecta a la base de datos para obtener las configuraciones de transformación de sensores,
 * se suscribe a los tópicos MQTT de entrada, transforma los valores según los parámetros min, mid y max,
 * y publica en los tópicos de salida solo cuando el valor cambia.
 */

// Dependencias
const mysql = require('mysql2/promise');
const mqtt = require('mqtt');
const dotenv = require('dotenv');
const path = require('path');

// Cargar variables de entorno
dotenv.config({ path: path.resolve(__dirname, '../.env') });

// Configuración
const DB_CONFIG = {
  host: process.env.DB_HOST || 'localhost',
  port: process.env.DB_PORT || 3306,
  user: process.env.DB_USERNAME || 'root',
  password: process.env.DB_PASSWORD || '',
  database: process.env.DB_DATABASE || 'sensorica',
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
};

const MQTT_CONFIG = {
  url: `mqtt://${process.env.MQTT_SENSORICA_SERVER || '127.0.0.1'}:${process.env.MQTT_SENSORICA_PORT || '1883'}`,
  options: {
    clientId: `sensor-transformer-${Math.random().toString(16).substring(2, 10)}`,
    clean: true,
    connectTimeout: 4000,
    reconnectPeriod: 1000
  }
};

// Variables globales
let dbConnection = null;
let mqttClient = null;
let transformations = [];
let valueCache = {}; // Cache para almacenar el último valor publicado por tópico
let isReconnecting = false; // Flag para controlar reconexiones
let lastTransformationHash = ''; // Hash para detectar cambios en las transformaciones

/**
 * Obtiene la marca de tiempo actual formateada
 * @returns {string} Marca de tiempo formateada
 */
function getCurrentTimestamp() {
  return new Date().toISOString().replace('T', ' ').substring(0, 19);
}

/**
 * Inicia la aplicación
 */
async function start() {
  console.log(`[${getCurrentTimestamp()}] 🚀 Iniciando Sensor Transformer...`);
  
  try {
    // Conectar a la base de datos
    await connectToDatabase();
    
    // Cargar transformaciones
    await loadTransformations();
    
    // Conectar a MQTT
    await connectToMQTT();
    
    // Configurar temporizador para verificar conexiones y recargar transformaciones periódicamente
    setInterval(async () => {
      try {
        // Verificar conexión a la base de datos y reconectar si es necesario
        if (!dbConnection || dbConnection.state === 'disconnected') {
          console.log(`[${getCurrentTimestamp()}] 🔄 Reconectando a la base de datos...`);
          await connectToDatabase();
        }
        
        // Recargar transformaciones (esto también verifica cambios)
        await loadTransformations();
      } catch (error) {
        console.error(`[${getCurrentTimestamp()}] ❌ Error en verificación periódica: ${error.message}`);
        // Intentar reconectar en caso de error
        if (!isReconnecting) {
          isReconnecting = true;
          try {
            await connectToDatabase();
            isReconnecting = false;
          } catch (reconnectError) {
            console.error(`[${getCurrentTimestamp()}] ❌ Error al reconectar: ${reconnectError.message}`);
            isReconnecting = false;
          }
        }
      }
    }, 30000); // Verificar cada 30 segundos
    
    console.log(`[${getCurrentTimestamp()}] ✅ Sensor Transformer iniciado correctamente.`);
  } catch (error) {
    console.error(`[${getCurrentTimestamp()}] ❌ Error al iniciar Sensor Transformer: ${error.message}`);
    await shutdown(1);
  }
}

/**
 * Conecta a la base de datos
 */
async function connectToDatabase() {
  console.log(`[${getCurrentTimestamp()}] 🔌 Conectando a la base de datos...`);
  
  try {
    // Si ya hay una conexión, intentar cerrarla primero
    if (dbConnection) {
      try {
        await dbConnection.end();
      } catch (e) {
        // Ignorar errores al cerrar
      }
    }
    
    // Crear una nueva conexión
    dbConnection = await mysql.createConnection(DB_CONFIG);
    
    // Configurar manejo de errores para la conexión
    dbConnection.on('error', async (err) => {
      console.error(`[${getCurrentTimestamp()}] ❌ Error de conexión DB: ${err.message}`);
      if (err.code === 'PROTOCOL_CONNECTION_LOST' || err.code === 'ECONNREFUSED' || 
          err.code === 'ETIMEDOUT' || err.code === 'ENOTFOUND' || 
          err.message.includes('connect') || err.message.includes('connection')) {
        
        if (!isReconnecting) {
          isReconnecting = true;
          console.log(`[${getCurrentTimestamp()}] 🔄 Intentando reconectar a la base de datos...`);
          
          // Bucle infinito de reconexión
          let reconnected = false;
          while (!reconnected && !shutdown.called) {
            try {
              console.log(`[${getCurrentTimestamp()}] 🔄 Intento de reconexión a MySQL...`);
              // Crear una nueva conexión
              dbConnection = await mysql.createConnection(DB_CONFIG);
              console.log(`[${getCurrentTimestamp()}] ✅ Reconectado a MySQL exitosamente.`);
              reconnected = true;
              
              // Recargar transformaciones después de reconectar
              try {
                await loadTransformations();
              } catch (e) {
                console.error(`[${getCurrentTimestamp()}] ❌ Error al recargar transformaciones: ${e.message}`);
              }
            } catch (e) {
              console.error(`[${getCurrentTimestamp()}] ❌ Falló la reconexión a MySQL: ${e.message}`);
              // Esperar antes de reintentar
              await new Promise(resolve => setTimeout(resolve, 5000));
            }
          }
          isReconnecting = false;
        }
      }
    });
    
    console.log(`[${getCurrentTimestamp()}] ✅ Conexión a la base de datos establecida.`);
  } catch (error) {
    console.error(`[${getCurrentTimestamp()}] ❌ Error al conectar a la base de datos: ${error.message}`);
    // En lugar de lanzar el error, intentar reconectar después de un tiempo
    if (!isReconnecting) {
      isReconnecting = true;
      console.log(`[${getCurrentTimestamp()}] 🔄 Reintentando conexión a la base de datos en 5 segundos...`);
      setTimeout(async () => {
        try {
          await connectToDatabase();
        } finally {
          isReconnecting = false;
        }
      }, 5000);
    }
  }
}

/**
 * Carga las transformaciones desde la base de datos
 */
async function loadTransformations() {
  console.log(`[${getCurrentTimestamp()}] 📋 Cargando configuraciones de transformación...`);
  
  try {
    const [rows] = await dbConnection.execute(
      'SELECT * FROM sensor_transformations WHERE active = 1'
    );
    
    // Generar un hash de las transformaciones para detectar cambios
    const newHash = JSON.stringify(rows.map(r => ({ 
      id: r.id, 
      input_topic: r.input_topic, 
      output_topic: r.output_topic,
      min_value: r.min_value,
      mid_value: r.mid_value,
      max_value: r.max_value,
      below_min_value_output: r.below_min_value_output,
      min_to_mid_value_output: r.min_to_mid_value_output,
      mid_to_max_value_output: r.mid_to_max_value_output,
      above_max_value_output: r.above_max_value_output,
      updated_at: r.updated_at
    })));
    
    // Verificar si hay cambios en las transformaciones
    if (newHash !== lastTransformationHash) {
      console.log(`[${getCurrentTimestamp()}] 🔄 Detectados cambios en las transformaciones.`);
      
      // Desuscribirse de los tópicos actuales antes de actualizar
      if (mqttClient && mqttClient.connected && transformations.length > 0) {
        const currentTopics = transformations.map(t => t.input_topic);
        await unsubscribeFromTopics(currentTopics);
      }
      
      // Actualizar transformaciones y cache
      transformations = rows;
      // Limpiar el cache para evitar problemas con transformaciones modificadas
      valueCache = {};
      
      console.log(`[${getCurrentTimestamp()}] ✅ ${transformations.length} transformaciones cargadas.`);
      
      // Suscribirse a los nuevos tópicos
      if (mqttClient && mqttClient.connected && transformations.length > 0) {
        const newTopics = transformations.map(t => t.input_topic);
        await subscribeToTopics(newTopics);
      }
      
      // Actualizar el hash
      lastTransformationHash = newHash;
    } else {
      console.log(`[${getCurrentTimestamp()}] ✓ No hay cambios en las transformaciones.`);
    }
  } catch (error) {
    console.error(`[${getCurrentTimestamp()}] ❌ Error al cargar transformaciones: ${error.message}`);
    throw error;
  }
}

/**
 * Conecta al broker MQTT
 */
async function connectToMQTT() {
  console.log(`[${getCurrentTimestamp()}] 🔌 Conectando a MQTT en ${MQTT_CONFIG.url}...`);
  
  return new Promise((resolve, reject) => {
    // Si ya existe un cliente, intentar cerrarlo primero
    if (mqttClient) {
      try {
        mqttClient.end(true);
      } catch (e) {
        // Ignorar errores al cerrar
      }
    }
    
    mqttClient = mqtt.connect(MQTT_CONFIG.url, MQTT_CONFIG.options);
    
    mqttClient.on('connect', async () => {
      console.log(`[${getCurrentTimestamp()}] ✅ Conexión MQTT establecida.`);
      
      // Suscribirse a los tópicos de entrada
      if (transformations.length > 0) {
        const topics = transformations.map(t => t.input_topic);
        await subscribeToTopics(topics);
      }
      
      resolve();
    });
    
    mqttClient.on('error', (error) => {
      console.error(`[${getCurrentTimestamp()}] ❌ Error MQTT: ${error.message}`);
      if (!mqttClient.reconnecting) {
        reject(error);
      }
    });
    
    mqttClient.on('message', handleMQTTMessage);
    
    mqttClient.on('reconnect', () => {
      console.log(`[${getCurrentTimestamp()}] 🔄 Reconectando a MQTT...`);
    });
    
    mqttClient.on('disconnect', () => {
      console.log(`[${getCurrentTimestamp()}] 🔌 Desconectado de MQTT.`);
    });
    
    // Manejar la pérdida de conexión
    mqttClient.on('offline', () => {
      console.log(`[${getCurrentTimestamp()}] 📵 MQTT offline. Esperando reconexión automática...`);
    });
    
    // Cuando se reconecta exitosamente
    mqttClient.on('reconnect', async () => {
      console.log(`[${getCurrentTimestamp()}] 🔄 Intentando reconexión a MQTT...`);
    });
    
    mqttClient.on('connect', async () => {
      console.log(`[${getCurrentTimestamp()}] ✅ Reconectado a MQTT exitosamente.`);
      // Re-suscribirse a los tópicos tras reconexión
      if (transformations.length > 0) {
        const topics = transformations.map(t => t.input_topic);
        await subscribeToTopics(topics);
      }
    });
  });
}

/**
 * Suscribe a los tópicos MQTT especificados
 * @param {string[]} topics - Lista de tópicos a suscribir
 */
async function subscribeToTopics(topics) {
  if (!topics || topics.length === 0) return;
  
  return new Promise((resolve, reject) => {
    mqttClient.subscribe(topics, (err) => {
      if (err) {
        console.error(`[${getCurrentTimestamp()}] ❌ Error al suscribirse a tópicos: ${err.message}`);
        reject(err);
      } else {
        console.log(`[${getCurrentTimestamp()}] ✅ Suscrito a ${topics.length} tópicos.`);
        resolve();
      }
    });
  });
}

/**
 * Desuscribe de los tópicos MQTT especificados
 * @param {string[]} topics - Lista de tópicos a desuscribir
 */
async function unsubscribeFromTopics(topics) {
  if (!topics || topics.length === 0) return;
  
  return new Promise((resolve, reject) => {
    mqttClient.unsubscribe(topics, (err) => {
      if (err) {
        console.error(`[${getCurrentTimestamp()}] ❌ Error al desuscribirse de tópicos: ${err.message}`);
        reject(err);
      } else {
        console.log(`[${getCurrentTimestamp()}] ✅ Desuscrito de ${topics.length} tópicos.`);
        resolve();
      }
    });
  });
}

/**
 * Maneja los mensajes MQTT recibidos
 * @param {string} topic - Tópico del mensaje
 * @param {Buffer} message - Contenido del mensaje
 */
function handleMQTTMessage(topic, message) {
  try {
    // Buscar la transformación correspondiente al tópico
    const transformation = transformations.find(t => t.input_topic === topic);
    
    if (!transformation) {
      console.warn(`[${getCurrentTimestamp()}] ⚠️ Recibido mensaje en tópico no configurado: ${topic}`);
      return;
    }
    
    // Parsear el mensaje
    const payload = JSON.parse(message.toString());
    
    if (typeof payload.value === 'undefined') {
      console.warn(`[${getCurrentTimestamp()}] ⚠️ Mensaje sin campo 'value': ${message.toString()}`);
      return;
    }
    
    // Transformar el valor usando los campos de salida personalizados
    const inputValue = parseFloat(payload.value);
    const transformedValue = transformValue(
      inputValue,
      transformation.min_value,
      transformation.mid_value,
      transformation.max_value,
      transformation.below_min_value_output,
      transformation.min_to_mid_value_output,
      transformation.mid_to_max_value_output,
      transformation.above_max_value_output
    );
    
    // Verificar si el valor ha cambiado antes de publicar
    const cacheKey = transformation.output_topic;
    if (valueCache[cacheKey] !== transformedValue) {
      // Convertir el valor a número si es posible
      const numericValue = !isNaN(parseFloat(transformedValue)) ? parseFloat(transformedValue) : transformedValue;
      
      // Publicar el valor transformado
      const outputPayload = JSON.stringify({ value: numericValue });
      mqttClient.publish(transformation.output_topic, outputPayload);
      
      console.log(`[${getCurrentTimestamp()}] 📤 Publicado en ${transformation.output_topic}: ${outputPayload} (original: ${payload.value})`);
      
      // Actualizar el cache
      valueCache[cacheKey] = transformedValue;
    } else {
      console.log(`[${getCurrentTimestamp()}] 🔄 Valor sin cambios para ${transformation.output_topic}: ${transformedValue}`);
    }
  } catch (error) {
    console.error(`[${getCurrentTimestamp()}] ❌ Error al procesar mensaje MQTT: ${error.message}`);
  }
}

/**
 * Transforma un valor según los parámetros min, mid y max y devuelve el valor de salida personalizado
 * @param {number} value - Valor a transformar
 * @param {number} min - Valor mínimo
 * @param {number} mid - Valor intermedio
 * @param {number} max - Valor máximo
 * @param {string} belowMinOutput - Valor de salida cuando es menor o igual al mínimo
 * @param {string} minToMidOutput - Valor de salida cuando está entre mínimo y medio
 * @param {string} midToMaxOutput - Valor de salida cuando está entre medio y máximo
 * @param {string} aboveMaxOutput - Valor de salida cuando es mayor que el máximo
 * @returns {number|string} - Valor de salida personalizado según el rango (preferentemente número)
 */
function transformValue(value, min, mid, max, belowMinOutput, minToMidOutput, midToMaxOutput, aboveMaxOutput) {
  // Asegurarse de que los valores son números
  min = parseFloat(min);
  mid = parseFloat(mid);
  max = parseFloat(max);
  
  // Validar que los parámetros son números válidos
  if (isNaN(min) || isNaN(mid) || isNaN(max)) {
    console.warn(`[${getCurrentTimestamp()}] ⚠️ Parámetros de transformación inválidos: min=${min}, mid=${mid}, max=${max}`);
    return parseFloat(belowMinOutput || '0');
  }
  
  // Aplicar la transformación con valores de salida personalizados
  let outputValue;
  
  if (value <= min) {
    outputValue = belowMinOutput || '0';
  } else if (value <= mid) {
    outputValue = minToMidOutput || '1';
  } else if (value <= max) {
    outputValue = midToMaxOutput || '2';
  } else {
    outputValue = aboveMaxOutput || '3';
  }
  
  // Intentar convertir a número si es posible
  const numericValue = parseFloat(outputValue);
  return !isNaN(numericValue) ? numericValue : outputValue;
}

/**
 * Detiene la aplicación de forma segura
 * @param {number} exitCode - Código de salida
 */
async function shutdown(exitCode = 0) {
  // Evitar llamadas múltiples
  if (shutdown.called) return;
  shutdown.called = true;
  
  console.log(`[${getCurrentTimestamp()}] 🚫 Deteniendo Sensor Transformer...`);
  
  try {
    // Cerrar conexión MQTT
    if (mqttClient && mqttClient.connected) {
      console.log(`[${getCurrentTimestamp()}] ℹ️ Cerrando conexión MQTT...`);
      await new Promise((resolve, reject) => {
        const timeout = setTimeout(() => reject(new Error("MQTT close timeout")), 3000);
        mqttClient.end(true, { reasonString: 'Service shutting down' }, () => {
          clearTimeout(timeout);
          console.log(`[${getCurrentTimestamp()}] ✅ Cliente MQTT desconectado.`);
          resolve();
        });
      });
    } else if (mqttClient) {
      console.log(`[${getCurrentTimestamp()}] ℹ️ Cliente MQTT no conectado, no se necesita cerrar.`);
    }
  } catch (e) {
    console.warn(`[${getCurrentTimestamp()}] ⚠️ Error al cerrar MQTT: ${e.message}`);
  }
  
  try {
    // Cerrar conexión a la base de datos
    if (dbConnection) {
      console.log(`[${getCurrentTimestamp()}] ℹ️ Cerrando conexión DB...`);
      await dbConnection.end();
      console.log(`[${getCurrentTimestamp()}] ✅ Conexión DB cerrada.`);
    }
  } catch (e) {
    console.warn(`[${getCurrentTimestamp()}] ⚠️ Error al cerrar DB: ${e.message}`);
  }
  
  console.log(`[${getCurrentTimestamp()}] 👋 Servicio detenido.`);
  
  // En lugar de terminar el proceso, reiniciamos el servicio si no es un cierre intencional (SIGINT/SIGTERM)
  if (exitCode !== 0 && !shutdown.intentional) {
    console.log(`[${getCurrentTimestamp()}] 🔄 Reiniciando el servicio automáticamente...`);
    shutdown.called = false;
    setTimeout(() => {
      console.log(`[${getCurrentTimestamp()}] 🚀 Reiniciando Sensor Transformer...`);
      start().catch(err => {
        console.error(`[${getCurrentTimestamp()}] ❌ Error al reiniciar: ${err.message}`);
        // Intentar nuevamente después de un tiempo
        setTimeout(() => {
          shutdown.called = false;
          start();
        }, 10000); // Esperar 10 segundos antes de reintentar
      });
    }, 5000); // Esperar 5 segundos antes de reiniciar
  } else {
    // Solo terminamos el proceso si es un cierre intencional
    if (shutdown.intentional) {
      process.exit(exitCode);
    } else {
      // Reiniciar de todos modos para mayor robustez
      shutdown.called = false;
      console.log(`[${getCurrentTimestamp()}] 🔄 Reiniciando el servicio por seguridad...`);
      setTimeout(() => start(), 5000);
    }
  }
}
shutdown.called = false;
shutdown.intentional = false;

// Capturar señales y errores
process.on('SIGINT', () => {
  shutdown.intentional = true;
  shutdown(0);
});
process.on('SIGTERM', () => {
  shutdown.intentional = true;
  shutdown(0);
});
process.on('uncaughtException', (error, origin) => {
  console.error(`[${getCurrentTimestamp()}] 💥 EXCEPCIÓN NO CAPTURADA (${origin}): ${error.message}`, error.stack);
  // No detener el proceso, intentar recuperarse
  try {
    // Si es un error de conexión, intentar reconectar
    if (error.message.includes('connect') || error.message.includes('connection') || 
        error.message.includes('ECONNREFUSED') || error.message.includes('ETIMEDOUT')) {
      console.log(`[${getCurrentTimestamp()}] 🔄 Detectado error de conexión, intentando recuperar...`);
      // Intentar reconectar en lugar de apagar
      if (!isReconnecting) {
        isReconnecting = true;
        setTimeout(async () => {
          try {
            // Intentar reconectar a la base de datos
            await connectToDatabase();
            // Intentar reconectar a MQTT
            await connectToMQTT();
            isReconnecting = false;
          } catch (e) {
            isReconnecting = false;
            console.error(`[${getCurrentTimestamp()}] ❌ Error al recuperar conexiones: ${e.message}`);
          }
        }, 5000);
      }
    } else {
      // Para otros errores, intentar reiniciar el servicio
      shutdown(1);
    }
  } catch (e) {
    console.error(`[${getCurrentTimestamp()}] ❌ Error al manejar excepción: ${e.message}`);
    shutdown(1);
  }
});
process.on('unhandledRejection', (reason, promise) => {
  console.error(`[${getCurrentTimestamp()}] 💥 RECHAZO DE PROMESA NO MANEJADO:`, reason);
  // No detener el proceso, registrar y continuar
  console.log(`[${getCurrentTimestamp()}] 🔄 Continuando después de rechazo de promesa no manejado...`);
});

// Iniciar la aplicación
start();
