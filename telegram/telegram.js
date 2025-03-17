require("dotenv").config();
const express = require("express");
const cors = require("cors");
const { TelegramClient } = require("telegram");
const { StringSession } = require("telegram/sessions");
const { Api } = require("telegram/tl"); // Para métodos de autenticación y otros
const qrcode = require("qrcode");
const axios = require("axios");
const fs = require("fs");
const path = require("path");
const swaggerJsdoc = require("swagger-jsdoc");
const swaggerUi = require("swagger-ui-express");

const app = express();
app.use(express.json());

let sessionStatus = {};

// Habilitar CORS
app.use(cors({
  origin: "*",
  methods: "GET,POST,PUT,DELETE,OPTIONS",
  allowedHeaders: "Content-Type,Authorization"
}));

// Variables de entorno y configuración
const apiId = parseInt(process.env.API_ID);
const apiHash = process.env.API_HASH;
const PORT = process.env.PORT || 3006;
const API_EXTERNAL = process.env.API_EXTERNAL === "true";
const API_EXTERNAL_URL = process.env.API_EXTERNAL_URL;
const API_EXTERNAL_TOKEN = process.env.API_EXTERNAL_TOKEN;
const DATA_FOLDER = process.env.DATA_FOLDER || "sessions";
const CALLBACK_BASE = process.env.CALLBACK_BASE;

console.log("API_ID:", apiId);
console.log("API_HASH:", apiHash);
console.log("PORT:", PORT);
console.log("API_EXTERNAL:", API_EXTERNAL);
console.log("API_EXTERNAL_URL:", API_EXTERNAL_URL);
console.log("API_EXTERNAL_TOKEN:", API_EXTERNAL_TOKEN);
console.log("DATA_FOLDER:", DATA_FOLDER);
console.log("CALLBACK_BASE:", CALLBACK_BASE);

// Crear la carpeta para sesiones si no existe
if (!fs.existsSync(DATA_FOLDER)) {
  fs.mkdirSync(DATA_FOLDER);
}
// Archivo para persistir los IDs de mensajes procesados (deduplicación)
const processedMessagesFile = path.join(DATA_FOLDER, "processedMessages.json");

// Objetos globales para sesiones y datos temporales
let sessions = {};
let authData = {};
let processedMessages = {};

// Objeto global para almacenar reglas de autorespuesta por usuario
// Ejemplo: { userId1: [ { id, keyword, response }, ... ] }
let autoResponseRules = {};

// Objeto para almacenar tareas programadas (en memoria)
// Ejemplo: { taskId: { timeout: setTimeout, details: { userId, peer, message, sendAt } } }
let scheduledMessages = {};

// Configuración de Swagger
const swaggerOptions = {
  definition: {
    openapi: "3.0.0",
    info: {
      title: "Telegram API",
      version: "1.0.0",
      description: "API para interactuar con Telegram usando TelegramClient",
    },
    servers: [
      {
        url: `http://localhost:${PORT}`,
        description: "Servidor local",
      },
    ],
  },
  apis: [__filename],
};

const swaggerSpec = swaggerJsdoc(swaggerOptions);
app.use("/api-docs", swaggerUi.serve, swaggerUi.setup(swaggerSpec));

/**
 * Cargar los IDs de mensajes procesados desde el archivo.
 */
function loadProcessedMessages() {
  if (fs.existsSync(processedMessagesFile)) {
    try {
      const data = JSON.parse(fs.readFileSync(processedMessagesFile));
      for (const userId in data) {
        processedMessages[userId] = new Set(data[userId]);
      }
    } catch (err) {
      console.error("Error leyendo processedMessages:", err);
      processedMessages = {};
    }
  } else {
    processedMessages = {};
  }
}

/**
 * Guardar los IDs de mensajes procesados en el archivo.
 */
function saveProcessedMessages() {
  const dataToSave = {};
  for (const userId in processedMessages) {
    dataToSave[userId] = Array.from(processedMessages[userId]);
  }
  fs.writeFileSync(processedMessagesFile, JSON.stringify(dataToSave, null, 2));
}

/**
 * Cargar sesiones guardadas en el inicio.
 */
function loadSessions() {
  const files = fs.readdirSync(DATA_FOLDER);
  files.forEach((file) => {
    if (
      file.endsWith(".json") &&
      !file.includes("_messages") &&
      file !== path.basename(processedMessagesFile)
    ) {
      const userId = file.replace(".json", "");
      const sessionData = JSON.parse(fs.readFileSync(path.join(DATA_FOLDER, file)));
      sessions[userId] = new TelegramClient(new StringSession(sessionData.session), apiId, apiHash, {
        connectionRetries: 5,
      });
      sessions[userId]
        .connect()
        .then(() => {
          console.log(`Sesión restaurada para usuario: ${userId}`);
          sessions[userId].addEventHandler((event) => handleIncomingMessage(userId, event));
        })
        .catch((err) => console.log(`Error restaurando sesión ${userId}:`, err));
    }
  });
  loadProcessedMessages();
}

/**
 * Guardar la sesión en disco.
 */
function saveSession(userId) {
  if (sessions[userId]) {
    try {
      const sessionString = sessions[userId].session.save();
      fs.writeFileSync(
        path.join(DATA_FOLDER, `${userId}.json`),
        JSON.stringify({ session: sessionString }, null, 2)
      );
      console.log(`✅ Sesión guardada para usuario ${userId}`);
    } catch (error) {
      console.error(`❌ Error guardando sesión para ${userId}:`, error);
    }
  }
}

/**
 * Borrar la sesión en disco.
 */
function deleteSession(userId) {
  const sessionPath = path.join(DATA_FOLDER, `${userId}.json`);
  if (fs.existsSync(sessionPath)) fs.unlinkSync(sessionPath);
  const messagesPath = path.join(DATA_FOLDER, `${userId}_messages.json`);
  if (fs.existsSync(messagesPath)) fs.unlinkSync(messagesPath);
}

/* ===================================================
   ENDPOINTS DE AUTORESPONSE
=================================================== */

/**
 * @openapi
 * /autoresponse/{userId}:
 *   post:
 *     summary: Agrega una regla de autorespuesta
 *     tags: [Autoresponse]
 *     parameters:
 *       - in: path
 *         name: userId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del usuario
 *     requestBody:
 *       description: Objeto JSON con la palabra clave y la respuesta
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             type: object
 *             properties:
 *               keyword:
 *                 type: string
 *               response:
 *                 type: string
 *             required:
 *               - keyword
 *               - response
 *     responses:
 *       200:
 *         description: Regla de autorespuesta agregada
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 success:
 *                   type: boolean
 *                 rule:
 *                   type: object
 */
app.post("/autoresponse/:userId", (req, res) => {
  const { userId } = req.params;
  const { keyword, response: reply } = req.body;
  if (!keyword || !reply) {
    return res.status(400).json({ error: "Se requiere 'keyword' y 'response'" });
  }
  if (!autoResponseRules[userId]) {
    autoResponseRules[userId] = [];
  }
  const newRule = {
    id: Date.now(), // Utilizamos el timestamp como ID
    keyword,
    response: reply
  };
  autoResponseRules[userId].push(newRule);
  res.json({ success: true, rule: newRule });
});

/**
 * @openapi
 * /autoresponse/{userId}:
 *   get:
 *     summary: Lista las reglas de autorespuesta configuradas
 *     tags: [Autoresponse]
 *     parameters:
 *       - in: path
 *         name: userId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del usuario
 *     responses:
 *       200:
 *         description: Lista de reglas de autorespuesta
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 success:
 *                   type: boolean
 *                 rules:
 *                   type: array
 */
app.get("/autoresponse/:userId", (req, res) => {
  const { userId } = req.params;
  const rules = autoResponseRules[userId] || [];
  res.json({ success: true, rules });
});

/**
 * @openapi
 * /autoresponse/{userId}/{ruleId}:
 *   delete:
 *     summary: Elimina una regla de autorespuesta
 *     tags: [Autoresponse]
 *     parameters:
 *       - in: path
 *         name: userId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del usuario
 *       - in: path
 *         name: ruleId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID de la regla a eliminar
 *     responses:
 *       200:
 *         description: Regla eliminada correctamente
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 success:
 *                   type: boolean
 */
app.delete("/autoresponse/:userId/:ruleId", (req, res) => {
  const { userId, ruleId } = req.params;
  if (!autoResponseRules[userId]) {
    return res.status(404).json({ error: "No existen reglas para este usuario" });
  }
  autoResponseRules[userId] = autoResponseRules[userId].filter(rule => rule.id.toString() !== ruleId);
  res.json({ success: true, message: "Regla eliminada" });
});

/* ===================================================
   ENDPOINT PARA PROGRAMAR ENVÍO DE MENSAJE
=================================================== */
/**
 * @openapi
 * /schedule-message/{userId}:
 *   post:
 *     summary: Programa el envío de un mensaje a un chat (peer) a una hora determinada
 *     tags: [Scheduling]
 *     parameters:
 *       - in: path
 *         name: userId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del usuario
 *     requestBody:
 *       description: Objeto JSON con el peer destino, mensaje y hora de envío (timestamp en segundos)
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             type: object
 *             properties:
 *               peer:
 *                 type: string
 *               message:
 *                 type: string
 *               sendAt:
 *                 type: number
 *                 description: Timestamp en segundos para enviar el mensaje
 *             required:
 *               - peer
 *               - message
 *               - sendAt
 *     responses:
 *       200:
 *         description: Mensaje programado correctamente
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 success:
 *                   type: boolean
 *                 scheduledTask:
 *                   type: object
 */
app.post("/schedule-message/:userId", (req, res) => {
  const { userId } = req.params;
  const { peer, message, sendAt } = req.body;
  if (!sessions[userId]) {
    return res.status(404).json({ error: "Sesión no iniciada" });
  }
  if (!peer || !message || !sendAt) {
    return res.status(400).json({ error: "Se requieren 'peer', 'message' y 'sendAt'" });
  }
  const currentTime = Math.floor(Date.now() / 1000);
  const delay = (sendAt - currentTime) * 1000; // en milisegundos
  if (delay <= 0) {
    return res.status(400).json({ error: "El tiempo programado debe ser en el futuro" });
  }
  const taskId = Date.now();
  const timeout = setTimeout(async () => {
    try {
      await sessions[userId].sendMessage(peer, { message });
      console.log(`Mensaje programado enviado a ${peer} para usuario ${userId}`);
      delete scheduledMessages[taskId];
    } catch (error) {
      console.error("Error enviando mensaje programado:", error);
    }
  }, delay);
  scheduledMessages[taskId] = { timeout, details: { userId, peer, message, sendAt } };
  res.json({ success: true, scheduledTask: { taskId, ...scheduledMessages[taskId].details } });
});

/* ===================================================
   ENDPOINTS EXISTENTES (Autenticación, Chats, Mensajes, Grupos, Contactos, Multimedia, Búsqueda, etc.)
=================================================== */

/**
 * @openapi
 * /request-code/{userId}:
 *   post:
 *     summary: Solicita el código de verificación para autenticación en Telegram.
 *     tags: [Authentication]
 *     parameters:
 *       - in: path
 *         name: userId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del usuario.
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             type: object
 *             properties:
 *               phone:
 *                 type: string
 *                 description: Número de teléfono del usuario.
 *             required:
 *               - phone
 *     responses:
 *       200:
 *         description: Código enviado exitosamente.
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 success:
 *                   type: boolean
 *                 message:
 *                   type: string
 *                 phoneCodeHash:
 *                   type: string
 *       400:
 *         description: Número de teléfono requerido.
 *       500:
 *         description: Error en el servidor.
 */
app.post("/request-code/:userId", async (req, res) => {
    const { userId } = req.params;
    const { phone } = req.body;
    if (!phone) return res.status(400).json({ error: "Se requiere el número de teléfono" });
    if (!sessions[userId]) {
      sessions[userId] = new TelegramClient(new StringSession(""), apiId, apiHash, { connectionRetries: 5 });
    }
    const client = sessions[userId];
    try {
      await client.connect();
      const result = await client.invoke(new Api.auth.SendCode({
        phoneNumber: phone,
        apiId: apiId,
        apiHash: apiHash,
        settings: new Api.CodeSettings({}),
      }));
      // Marcar la sesión como no validada
      sessionStatus[userId] = false;
      authData[userId] = { phone, phoneCodeHash: result.phoneCodeHash };
      console.log(`Código solicitado para ${phone}. phoneCodeHash: ${result.phoneCodeHash}`);
      res.json({ success: true, message: "Código enviado", phoneCodeHash: result.phoneCodeHash });
    } catch (error) {
      res.status(500).json({ error: error.message });
    }
  });

/**
 * @openapi
 * /verify-code/{userId}:
 *   post:
 *     summary: Verifica el código y completa el login en Telegram.
 *     tags: [Authentication]
 *     parameters:
 *       - in: path
 *         name: userId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del usuario.
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             type: object
 *             properties:
 *               code:
 *                 type: string
 *                 description: Código de verificación.
 *               password:
 *                 type: string
 *                 description: Contraseña (opcional).
 *             required:
 *               - code
 *     responses:
 *       200:
 *         description: Sesión iniciada correctamente.
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 success:
 *                   type: boolean
 *                 message:
 *                   type: string
 *                 result:
 *                   type: object
 *       400:
 *         description: Datos de autenticación no encontrados.
 *       500:
 *         description: Error en el servidor.
 */
app.post("/verify-code/:userId", async (req, res) => {
    const { userId } = req.params;
    const { code, password } = req.body;
    if (!authData[userId] || !authData[userId].phone || !authData[userId].phoneCodeHash) {
      return res.status(400).json({ error: "No hay datos de autenticación para este usuario" });
    }
    const { phone, phoneCodeHash } = authData[userId];
    const client = sessions[userId];
    try {
      const signInResult = await client.invoke(new Api.auth.SignIn({
        phoneNumber: phone,
        phoneCode: code,
        phoneCodeHash: phoneCodeHash,
        password: password || null,
      }));
      console.log(`✅ Sesión iniciada para ${phone}`);
      // Marcar la sesión como validada
      sessionStatus[userId] = true;
      saveSession(userId);
      delete authData[userId];
      res.json({ success: true, message: "Sesión iniciada correctamente", result: signInResult });
    } catch (error) {
      console.error(`❌ Error verificando sesión para usuario ${userId}:`, error);
      if (error.code === 401 && error.errorMessage === "AUTH_KEY_UNREGISTERED") {
        console.log("⚠️ Clave de autenticación no registrada. Borrando sesión y reiniciando...");
        deleteSession(userId);
        delete sessions[userId];
        delete sessionStatus[userId];
      }
      res.status(500).json({ error: error.message });
    }
  });
/**
 * @openapi
 * /download-media/{userId}/{peer}/{messageId}:
 *   get:
 *     summary: Descarga el archivo multimedia (documento, video, foto o audio) de un mensaje.
 *     tags: [Media]
 *     parameters:
 *       - in: path
 *         name: userId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del usuario.
 *       - in: path
 *         name: peer
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del chat o peer donde se envió el mensaje.
 *       - in: path
 *         name: messageId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del mensaje que contiene la media a descargar.
 *     responses:
 *       200:
 *         description: Archivo multimedia descargado correctamente.
 *         content:
 *           application/octet-stream:
 *             schema:
 *               type: string
 *               format: binary
 *       400:
 *         description: El mensaje no contiene media o parámetros inválidos.
 *       404:
 *         description: Sesión o mensaje no encontrado.
 *       500:
 *         description: Error en el servidor.
 */
app.get("/download-media/:userId/:peer/:messageId", async (req, res) => {
    const { userId, peer, messageId } = req.params;

    if (!sessions[userId]) {
      return res.status(404).json({ error: "Sesión no iniciada" });
    }

    try {
      // Obtener mensajes recientes del chat (por ejemplo, los 50 últimos)
      const messages = await sessions[userId].getMessages(peer, { limit: 50 });
      const msg = messages.find(m => m.id.toString() === messageId);

      if (!msg) {
        return res.status(404).json({ error: "Mensaje no encontrado" });
      }

      if (!msg.media) {
        return res.status(400).json({ error: "El mensaje no contiene media" });
      }

      // Descargar la media
      const mediaBuffer = await sessions[userId].downloadMedia(msg.media, { workers: 1 });
      if (!mediaBuffer) {
        return res.status(500).json({ error: "Error al descargar la media" });
      }

      // Establecer encabezados para la descarga
      res.setHeader("Content-Disposition", 'attachment; filename="downloaded_media"');
      res.send(mediaBuffer);

    } catch (error) {
      console.error("Error en /download-media:", error);
      res.status(500).json({ error: error.message });
    }
  });

/**
 * @openapi
 * /logout/{userId}:
 *   post:
 *     summary: Cierra la sesión de un usuario.
 *     tags: [Authentication]
 *     parameters:
 *       - in: path
 *         name: userId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del usuario.
 *     responses:
 *       200:
 *         description: Sesión cerrada correctamente.
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 success:
 *                   type: boolean
 *                 message:
 *                   type: string
 *       404:
 *         description: Sesión no encontrada.
 *       500:
 *         description: Error en el servidor.
 */
app.post("/logout/:userId", async (req, res) => {
    const { userId } = req.params;
    if (!sessions[userId]) {
      return res.status(404).json({ error: "Sesión no encontrada" });
    }
    try {
      const client = sessions[userId];
      await client.invoke(new Api.auth.LogOut());
      delete sessions[userId];
      deleteSession(userId);
      delete sessionStatus[userId];
      console.log(`Session for user ${userId} closed successfully`);
      res.json({ success: true, message: "Sesión cerrada correctamente" });
    } catch (error) {
      console.error("Error cerrando sesión:", error);
      res.status(500).json({ error: error.message });
    }
  });

/**
 * @openapi
 * /get-chat/{userId}:
 *   get:
 *     summary: Obtiene la lista de chats individuales
 *     tags: [Chats]
 *     parameters:
 *       - in: path
 *         name: userId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del usuario
 *     responses:
 *       200:
 *         description: Lista de chats obtenida
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 success:
 *                   type: boolean
 *                 chats:
 *                   type: array
 *                   items:
 *                     type: object
 *                     properties:
 *                       id:
 *                         type: string
 *                       name:
 *                         type: string
 *       404:
 *         description: Sesión no iniciada
 *       500:
 *         description: Error en el servidor
 */
app.get("/get-chat/:userId", async (req, res) => {
  const { userId } = req.params;
  if (!sessions[userId]) return res.status(404).json({ error: "Sesión no iniciada" });
  try {
    const dialogs = await sessions[userId].getDialogs();
    res.json({
      success: true,
      chats: dialogs.map((chat) => ({
        id: chat.id,
        name: chat.title || chat.username,
      })),
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

/**
 * @openapi
 * /get-messages/{userId}/{peer}:
 *   get:
 *     summary: Obtiene mensajes de un chat individual
 *     tags: [Messages]
 *     parameters:
 *       - in: path
 *         name: userId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del usuario
 *       - in: path
 *         name: peer
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del peer (chat)
 *     responses:
 *       200:
 *         description: Mensajes obtenidos
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 success:
 *                   type: boolean
 *                 messages:
 *                   type: array
 *                   items:
 *                     type: object
 *                     properties:
 *                       messageId:
 *                         type: string
 *                       user_id:
 *                         type: string
 *                       sender:
 *                         type: string
 *                       chatPeer:
 *                         type: string
 *                       message:
 *                         type: string
 *                       date:
 *                         type: integer
 *                       status:
 *                         type: string
 *                       base64:
 *                         type: string
 *                         nullable: true
 *       404:
 *         description: Sesión no iniciada
 *       500:
 *         description: Error en el servidor
 */
app.get("/get-messages/:userId/:peer", async (req, res) => {
    const { userId, peer } = req.params;
    if (!sessions[userId]) return res.status(404).json({ error: "Sesión no iniciada" });

    try {
      const userMessagesPath = path.join(DATA_FOLDER, `${userId}_messages.json`);
      const storedMessages = fs.existsSync(userMessagesPath)
        ? JSON.parse(fs.readFileSync(userMessagesPath))
        : [];

      // Filtrar mensajes cuya propiedad chatPeer coincida con peer.
      const messages = storedMessages.filter(m => m.chatPeer === peer);

      // Mapear la propiedad "Base64" a "base64" en la respuesta
      const messagesMapped = messages.map(m => ({
        ...m,
        base64: m.Base64
      }));

      res.json({
        success: true,
        messages: messagesMapped
      });
    } catch (error) {
      res.status(500).json({ error: error.message });
    }
  });




/**
 * @openapi
 * /send-message/{userId}/{peer}/{message}:
 *   post:
 *     summary: Envía un mensaje a un chat individual
 *     tags: [Messages]
 *     parameters:
 *       - in: path
 *         name: userId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del usuario
 *       - in: path
 *         name: peer
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del peer (chat)
 *       - in: path
 *         name: message
 *         required: true
 *         schema:
 *           type: string
 *         description: Mensaje a enviar
 *     responses:
 *       200:
 *         description: Mensaje enviado correctamente
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 success:
 *                   type: boolean
 *                 message:
 *                   type: string
 *                 user_id:
 *                   type: string
 *                 peer:
 *                   type: string
 *                 status:
 *                   type: string
 *       404:
 *         description: Sesión no iniciada
 *       500:
 *         description: Error en el servidor
 */
app.post("/send-message/:userId/:peer/:message", async (req, res) => {
  const { userId, peer, message } = req.params;
  if (!sessions[userId]) return res.status(404).json({ error: "Sesión no iniciada" });
  try {
    await sessions[userId].sendMessage(peer, { message });
    const payload = {
      user_id: String(userId),
      message: message,
      date: Math.floor(Date.now() / 1000),
      peer: String(peer),
      status: "sended",
      image: null,
    };
    if (API_EXTERNAL) {
      try {
        await axios.post(API_EXTERNAL_URL, payload, {
          headers: { Authorization: `Bearer ${API_EXTERNAL_TOKEN}` },
        });
        console.log("Notificación enviada a API externa:", payload);
      } catch (error) {
        console.error("Error notificando a API externa:", error.response ? error.response.data : error.message);
      }
    }
    res.json({
      success: true,
      message: "Mensaje enviado correctamente",
      user_id: userId,
      peer: String(peer),
      status: "sended",
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

/**
 * @openapi
 * /get-groups/{userId}:
 *   get:
 *     summary: Obtiene la lista de grupos y canales
 *     tags: [Groups]
 *     parameters:
 *       - in: path
 *         name: userId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del usuario
 *     responses:
 *       200:
 *         description: Lista de grupos obtenida
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 success:
 *                   type: boolean
 *                 groups:
 *                   type: array
 *                   items:
 *                     type: object
 *                     properties:
 *                       id:
 *                         type: string
 *                       name:
 *                         type: string
 *                       is_channel:
 *                         type: boolean
 *       404:
 *         description: Sesión no iniciada
 *       500:
 *         description: Error en el servidor
 */
app.get("/get-groups/:userId", async (req, res) => {
  const { userId } = req.params;
  if (!sessions[userId]) return res.status(404).json({ error: "Sesión no iniciada" });
  try {
    const dialogs = await sessions[userId].getDialogs();
    const groups = dialogs.filter((chat) => chat.isGroup || chat.isChannel);
    res.json({
      success: true,
      groups: groups.map((g) => ({
        id: g.id,
        name: g.title,
        is_channel: g.isChannel,
      })),
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

/**
 * @openapi
 * /get-group-messages/{userId}/{groupId}:
 *   get:
 *     summary: Obtiene mensajes de un grupo o canal
 *     tags: [Groups]
 *     parameters:
 *       - in: path
 *         name: userId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del usuario
 *       - in: path
 *         name: groupId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del grupo o canal
 *     responses:
 *       200:
 *         description: Mensajes obtenidos
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 success:
 *                   type: boolean
 *                 messages:
 *                   type: array
 *                   items:
 *                     type: object
 *                     properties:
 *                       id:
 *                         type: string
 *                       message:
 *                         type: string
 *                       date:
 *                         type: integer
 *                       peer:
 *                         type: string
 *                       status:
 *                         type: string
 *                       image:
 *                         type: string
 *                         nullable: true
 *       404:
 *         description: Sesión no iniciada
 *       500:
 *         description: Error en el servidor
 */
app.get("/get-group-messages/:userId/:groupId", async (req, res) => {
  const { userId, groupId } = req.params;
  if (!sessions[userId]) return res.status(404).json({ error: "Sesión no iniciada" });
  try {
    const messages = await sessions[userId].getMessages(Number(groupId), { limit: 10 });
    res.json({
      success: true,
      messages: messages.map((msg) => ({
        id: msg.id,
        message:
          typeof msg.message === "string"
            ? msg.message
            : (msg.message && msg.message.message) || "Only image",
        date: msg.date,
        peer: String(groupId),
        status: "received",
        image: msg.image || null,
      })),
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

/**
 * @openapi
 * /send-group-message/{userId}/{groupId}/{message}:
 *   post:
 *     summary: Envía un mensaje a un grupo o canal
 *     tags: [Groups]
 *     parameters:
 *       - in: path
 *         name: userId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del usuario
 *       - in: path
 *         name: groupId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del grupo o canal
 *       - in: path
 *         name: message
 *         required: true
 *         schema:
 *           type: string
 *         description: Mensaje a enviar
 *     responses:
 *       200:
 *         description: Mensaje enviado correctamente
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 success:
 *                   type: boolean
 *                 message:
 *                   type: string
 *                 user_id:
 *                   type: string
 *                 group_id:
 *                   type: string
 *                 status:
 *                   type: string
 *       404:
 *         description: Sesión no iniciada
 *       500:
 *         description: Error en el servidor
 */
app.post("/send-group-message/:userId/:groupId/:message", async (req, res) => {
  const { userId, groupId, message } = req.params;
  if (!sessions[userId]) return res.status(404).json({ error: "Sesión no iniciada" });
  try {
    await sessions[userId].sendMessage(Number(groupId), { message });
    const payload = {
      user_id: String(userId),
      message: message,
      date: Math.floor(Date.now() / 1000),
      peer: String(groupId),
      status: "sended",
      image: null,
    };
    if (API_EXTERNAL) {
      try {
        await axios.post(API_EXTERNAL_URL, payload, {
          headers: { Authorization: `Bearer ${API_EXTERNAL_TOKEN}` },
        });
        console.log("Notificación de mensaje de grupo enviada a API externa:", payload);
      } catch (error) {
        console.error("Error notificando a API externa:", error.response ? error.response.data : error.message);
      }
    }
    res.json({
      success: true,
      message: "Mensaje enviado correctamente",
      user_id: userId,
      group_id: groupId,
      status: "sended",
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

/**
 * @openapi
 * /get-contacts/{userId}:
 *   get:
 *     summary: Obtiene el listado de contactos del usuario
 *     tags: [Contacts]
 *     parameters:
 *       - in: path
 *         name: userId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del usuario
 *     responses:
 *       200:
 *         description: Lista de contactos obtenida
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 success:
 *                   type: boolean
 *                 contacts:
 *                   type: array
 *                   items:
 *                     type: object
 *                     properties:
 *                       id:
 *                         type: string
 *                       first_name:
 *                         type: string
 *                       last_name:
 *                         type: string
 *                       phone:
 *                         type: string
 *                       username:
 *                         type: string
 *       404:
 *         description: Sesión no iniciada
 *       500:
 *         description: Error en el servidor
 */
app.get("/get-contacts/:userId", async (req, res) => {
  const { userId } = req.params;
  if (!sessions[userId]) return res.status(404).json({ error: "Sesión no iniciada" });
  try {
    const result = await sessions[userId].invoke(new Api.contacts.GetContacts({ hash: 0 }));
    const contacts = result.users || [];
    const formattedContacts = contacts.map(contact => ({
      peer: contact.id,
      first_name: contact.firstName,
      last_name: contact.lastName,
      phone: contact.phone,
      username: contact.username || null,
    }));
    res.json({ success: true, contacts: formattedContacts });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

/**
 * @openapi
 * /delete-message/{userId}/{peer}/{messageId}:
 *   delete:
 *     summary: Borra un mensaje específico en un chat individual
 *     tags: [Messages]
 *     parameters:
 *       - in: path
 *         name: userId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del usuario
 *       - in: path
 *         name: peer
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del chat o peer
 *       - in: path
 *         name: messageId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del mensaje a borrar
 *     responses:
 *       200:
 *         description: Mensaje borrado correctamente
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 success:
 *                   type: boolean
 *                 message:
 *                   type: string
 *       404:
 *         description: Sesión no iniciada
 *       500:
 *         description: Error en el servidor
 */
app.delete("/delete-message/:userId/:peer/:messageId", async (req, res) => {
  const { userId, messageId } = req.params;
  if (!sessions[userId]) return res.status(404).json({ error: "Sesión no iniciada" });
  try {
    await sessions[userId].invoke(new Api.messages.DeleteMessages({
      id: [parseInt(messageId)],
      revoke: true,
    }));
    res.json({ success: true, message: "Mensaje borrado correctamente" });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

/**
 * @openapi
 * /delete-chat/{userId}/{peer}:
 *   delete:
 *     summary: Borra el historial completo de un chat (elimina todos los mensajes)
 *     tags: [Chats]
 *     parameters:
 *       - in: path
 *         name: userId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del usuario
 *       - in: path
 *         name: peer
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del chat o peer
 *     responses:
 *       200:
 *         description: Chat borrado correctamente
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 success:
 *                   type: boolean
 *                 message:
 *                   type: string
 *       404:
 *         description: Sesión no iniciada
 *       500:
 *         description: Error en el servidor
 */
app.delete("/delete-chat/:userId/:peer", async (req, res) => {
  const { userId, peer } = req.params;
  if (!sessions[userId]) return res.status(404).json({ error: "Sesión no iniciada" });
  try {
    const inputPeer = await sessions[userId].getInputEntity(peer);
    await sessions[userId].invoke(new Api.messages.DeleteHistory({
      peer: inputPeer,
      maxId: Number.MAX_SAFE_INTEGER,
      revoke: true,
    }));
    res.json({ success: true, message: "Chat borrado correctamente" });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

/**
 * @openapi
 * /create-group/{userId}:
 *   post:
 *     summary: Crea un nuevo grupo privado en Telegram
 *     tags: [Groups]
 *     parameters:
 *       - in: path
 *         name: userId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del usuario que crea el grupo
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             type: object
 *             properties:
 *               title:
 *                 type: string
 *                 description: Título del grupo
 *               members:
 *                 type: array
 *                 items:
 *                   type: string
 *                 description: Lista de IDs de usuarios a agregar al grupo (excluyendo al creador)
 *             required:
 *               - title
 *               - members
 *     responses:
 *       200:
 *         description: Grupo creado correctamente
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 success:
 *                   type: boolean
 *                 message:
 *                   type: string
 *                 result:
 *                   type: object
 *       404:
 *         description: Sesión no iniciada
 *       500:
 *         description: Error en el servidor
 */
app.post("/create-group/:userId", async (req, res) => {
  const { userId } = req.params;
  const { title, members } = req.body;
  if (!sessions[userId]) return res.status(404).json({ error: "Sesión no iniciada" });
  if (!title || !Array.isArray(members) || members.length === 0) {
    return res.status(400).json({ error: "Se requiere un título y una lista de miembros" });
  }
  try {
    const inputUsers = await Promise.all(members.map(async (memberId) => {
      return await sessions[userId].getInputEntity(memberId);
    }));
    const result = await sessions[userId].invoke(new Api.messages.CreateChat({
      users: inputUsers,
      title: title,
    }));
    console.log(`✅ Grupo "${title}" creado para el usuario ${userId}`);
    res.json({ success: true, message: "Grupo creado correctamente", result });
  } catch (error) {
    console.error("Error creando grupo:", error);
    res.status(500).json({ error: error.message });
  }
});

/**
 * @openapi
 * /forward-message/{userId}/{fromPeer}/{toPeer}/{messageId}:
 *   post:
 *     summary: Reenvía un mensaje desde un chat a otro
 *     tags: [Messages]
 *     parameters:
 *       - in: path
 *         name: userId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del usuario
 *       - in: path
 *         name: fromPeer
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del chat de origen
 *       - in: path
 *         name: toPeer
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del chat de destino
 *       - in: path
 *         name: messageId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del mensaje a reenviar
 *     responses:
 *       200:
 *         description: Mensaje reenviado correctamente
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 success:
 *                   type: boolean
 *                 message:
 *                   type: string
 *                 result:
 *                   type: object
 *       404:
 *         description: Sesión no iniciada
 *       500:
 *         description: Error en el servidor
 */
app.post("/forward-message/:userId/:fromPeer/:toPeer/:messageId", async (req, res) => {
  const { userId, fromPeer, toPeer, messageId } = req.params;
  if (!sessions[userId]) return res.status(404).json({ error: "Sesión no iniciada" });
  try {
    const inputFromPeer = await sessions[userId].getInputEntity(fromPeer);
    const inputToPeer = await sessions[userId].getInputEntity(toPeer);
    const randomId = [BigInt(Math.floor(Math.random() * 1000000000))];
    const result = await sessions[userId].invoke(new Api.messages.ForwardMessages({
      fromPeer: inputFromPeer,
      toPeer: inputToPeer,
      id: [parseInt(messageId)],
      randomId: randomId,
      dropAuthor: false,
    }));
    res.json({ success: true, message: "Mensaje reenviado correctamente", result });
  } catch (error) {
    console.error("Error reenviando mensaje:", error);
    res.status(500).json({ error: error.message });
  }
});

/**
 * @openapi
 * /search-contact/{userId}:
 *   get:
 *     summary: Busca un contacto por teléfono o nombre
 *     tags: [Contacts]
 *     parameters:
 *       - in: path
 *         name: userId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del usuario
 *       - in: query
 *         name: phone
 *         schema:
 *           type: string
 *         description: Número de teléfono a buscar
 *       - in: query
 *         name: name
 *         schema:
 *           type: string
 *         description: Nombre (o parte de él) a buscar en first_name, last_name o username
 *     responses:
 *       200:
 *         description: Contactos filtrados obtenidos
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 success:
 *                   type: boolean
 *                 contacts:
 *                   type: array
 *                   items:
 *                     type: object
 *                     properties:
 *                       id:
 *                         type: string
 *                       first_name:
 *                         type: string
 *                       last_name:
 *                         type: string
 *                       phone:
 *                         type: string
 *                       username:
 *                         type: string
 *       404:
 *         description: Sesión no iniciada
 *       500:
 *         description: Error en el servidor
 */
app.get("/search-contact/:userId", async (req, res) => {
  const { userId } = req.params;
  const { phone, name } = req.query;
  if (!sessions[userId]) {
    return res.status(404).json({ error: "Sesión no iniciada" });
  }
  try {
    const result = await sessions[userId].invoke(new Api.contacts.GetContacts({ hash: 0 }));
    const contacts = result.users || [];
    const formattedContacts = contacts.map(contact => ({
      id: contact.id,
      first_name: contact.firstName || "",
      last_name: contact.lastName || "",
      phone: contact.phone || "",
      username: contact.username || "",
    }));
    const filteredContacts = formattedContacts.filter(contact => {
      let phoneMatch = true, nameMatch = true;
      if (phone) {
        phoneMatch = contact.phone.includes(phone);
      }
      if (name) {
        const searchName = name.toLowerCase();
        nameMatch =
          contact.first_name.toLowerCase().includes(searchName) ||
          contact.last_name.toLowerCase().includes(searchName) ||
          contact.username.toLowerCase().includes(searchName);
      }
      return phoneMatch && nameMatch;
    });
    res.json({ success: true, contacts: filteredContacts });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

/**
 * @openapi
 * /export-contacts/{userId}:
 *   get:
 *     summary: Exporta los contactos del usuario en formato CSV
 *     tags: [Contacts]
 *     parameters:
 *       - in: path
 *         name: userId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del usuario
 *     responses:
 *       200:
 *         description: Archivo CSV generado con los contactos
 *         content:
 *           text/csv:
 *             schema:
 *               type: string
 *       404:
 *         description: Sesión no iniciada
 *       500:
 *         description: Error en el servidor
 */
app.get("/export-contacts/:userId", async (req, res) => {
  const { userId } = req.params;
  if (!sessions[userId]) return res.status(404).json({ error: "Sesión no iniciada" });
  try {
    const result = await sessions[userId].invoke(new Api.contacts.GetContacts({ hash: 0 }));
    const contacts = result.users || [];
    const formattedContacts = contacts.map(contact => ({
      id: contact.id,
      first_name: contact.firstName || "",
      last_name: contact.lastName || "",
      phone: contact.phone || "",
      username: contact.username || ""
    }));
    let csvContent = "id,first_name,last_name,phone,username\n";
    formattedContacts.forEach(c => {
      csvContent += `${c.id},"${c.first_name}","${c.last_name}","${c.phone}","${c.username}"\n`;
    });
    res.header("Content-Type", "text/csv");
    res.attachment("contacts.csv");
    res.send(csvContent);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

/**
 * @openapi
 * /import-contacts/{userId}:
 *   post:
 *     summary: Importa contactos a la cuenta del usuario desde un JSON
 *     tags: [Contacts]
 *     parameters:
 *       - in: path
 *         name: userId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del usuario
 *     requestBody:
 *       description: JSON con el arreglo de contactos a importar. Cada contacto debe tener phone, first_name y last_name.
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             type: object
 *             properties:
 *               contacts:
 *                 type: array
 *                 items:
 *                   type: object
 *                   properties:
 *                     phone:
 *                       type: string
 *                     first_name:
 *                       type: string
 *                     last_name:
 *                       type: string
 *             example:
 *               contacts:
 *                 - phone: "+123456789"
 *                   first_name: "Juan"
 *                   last_name: "Pérez"
 *                 - phone: "+987654321"
 *                   first_name: "María"
 *                   last_name: "García"
 *     responses:
 *       200:
 *         description: Contactos importados correctamente
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 success:
 *                   type: boolean
 *                 imported:
 *                   type: object
 *       404:
 *         description: Sesión no iniciada
 *       500:
 *         description: Error en el servidor
 */
app.post("/import-contacts/:userId", async (req, res) => {
  const { userId } = req.params;
  const { contacts } = req.body;
  if (!sessions[userId]) return res.status(404).json({ error: "Sesión no iniciada" });
  if (!contacts || !Array.isArray(contacts) || contacts.length === 0) {
    return res.status(400).json({ error: "Debe proporcionarse un arreglo de contactos válido" });
  }
  try {
    const inputContacts = contacts.map(contact => ({
      _: "inputPhoneContact",
      clientId: BigInt(Math.floor(Math.random() * 1000000000)),
      phone: contact.phone,
      firstName: contact.first_name,
      lastName: contact.last_name || ""
    }));
    const result = await sessions[userId].invoke(new Api.contacts.ImportContacts({
      contacts: inputContacts
    }));
    res.json({ success: true, imported: result });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

/**
 * @openapi
 * /send-media/{userId}/{peer}:
 *   post:
 *     summary: Envía un archivo multimedia (video, audio, imagen o documento) a un chat
 *     tags: [Messages]
 *     parameters:
 *       - in: path
 *         name: userId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del usuario
 *       - in: path
 *         name: peer
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del chat o peer destino
 *     requestBody:
 *       description: Objeto JSON con la ruta del archivo a enviar y un caption opcional
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             type: object
 *             properties:
 *               filePath:
 *                 type: string
 *                 description: "Ruta local del archivo en el servidor (por ejemplo, './media/imagen.jpg')"
 *               caption:
 *                 type: string
 *                 description: Mensaje o pie de foto opcional
 *             required:
 *               - filePath
 *     responses:
 *       200:
 *         description: Archivo enviado correctamente
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 success:
 *                   type: boolean
 *                 message:
 *                   type: string
 *                 result:
 *                   type: object
 *       400:
 *         description: Parámetros inválidos
 *       404:
 *         description: Sesión no iniciada
 *       500:
 *         description: Error en el servidor
 */
app.post("/send-media/:userId/:peer", async (req, res) => {
    const { userId, peer } = req.params;
    const { filePath, caption } = req.body;
    if (!filePath) {
      return res.status(400).json({ error: "El parámetro filePath es obligatorio" });
    }
    if (!sessions[userId]) {
      return res.status(404).json({ error: "Sesión no iniciada" });
    }
    try {
      const result = await sessions[userId].sendFile(peer, {
        file: filePath,
        caption: caption || ""
      });
      res.json({ success: true, message: "Archivo enviado correctamente", result });
    } catch (error) {
      console.error("Error enviando multimedia:", error);
      res.status(500).json({ error: error.message });
    }
  });


/**
 * @openapi
 * /search-messages/{userId}/{peer}:
 *   get:
 *     summary: Busca mensajes en un chat a partir de un parámetro de búsqueda
 *     tags: [Messages]
 *     parameters:
 *       - in: path
 *         name: userId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del usuario
 *       - in: path
 *         name: peer
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del chat donde buscar
 *       - in: query
 *         name: query
 *         required: true
 *         schema:
 *           type: string
 *         description: Texto a buscar en los mensajes
 *       - in: query
 *         name: limit
 *         schema:
 *           type: integer
 *         description: Número máximo de mensajes a retornar (por defecto 20)
 *     responses:
 *       200:
 *         description: Lista de mensajes que coinciden con la búsqueda
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 success:
 *                   type: boolean
 *                 messages:
 *                   type: array
 *       404:
 *         description: Sesión no iniciada
 *       500:
 *         description: Error en el servidor
 */
app.get("/search-messages/:userId/:peer", async (req, res) => {
  const { userId, peer } = req.params;
  const { query, limit } = req.query;
  if (!sessions[userId]) {
    return res.status(404).json({ error: "Sesión no iniciada" });
  }
  if (!query) {
    return res.status(400).json({ error: "El parámetro 'query' es obligatorio" });
  }
  try {
    const inputPeer = await sessions[userId].getInputEntity(peer);
    const result = await sessions[userId].invoke(new Api.messages.Search({
      peer: inputPeer,
      q: query,
      filter: new Api.InputMessagesFilterEmpty(),
      minDate: 0,
      maxDate: 0,
      offsetId: 0,
      addOffset: 0,
      limit: limit ? parseInt(limit) : 20,
      maxId: 0,
      minId: 0
    }));
    res.json({ success: true, messages: result.messages });
  } catch (error) {
    console.error("Error buscando mensajes:", error);
    res.status(500).json({ error: error.message });
  }
});
/**
 * @openapi
 * /send-media/{userId}/{peer}:
 *   post:
 *     summary: Envía un archivo multimedia (video, audio, imagen o documento) a un chat
 *     tags: [Messages]
 *     parameters:
 *       - in: path
 *         name: userId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del usuario
 *       - in: path
 *         name: peer
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del chat o peer destino
 *     requestBody:
 *       description: Objeto JSON con la ruta del archivo a enviar y un caption opcional
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             type: object
 *             properties:
 *               filePath:
 *                 type: string
 *                 description: "Ruta local del archivo en el servidor (por ejemplo, './media/imagen.jpg')"
 *               caption:
 *                 type: string
 *                 description: Mensaje o pie de foto opcional
 *             required:
 *               - filePath
 *     responses:
 *       200:
 *         description: Archivo enviado correctamente
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 success:
 *                   type: boolean
 *                 message:
 *                   type: string
 *                 result:
 *                   type: object
 *       400:
 *         description: Parámetros inválidos
 *       404:
 *         description: Sesión no iniciada
 *       500:
 *         description: Error en el servidor
 */
app.post("/send-media/:userId/:peer", async (req, res) => {
    const { userId, peer } = req.params;
    const { filePath, caption } = req.body;
    if (!filePath) {
      return res.status(400).json({ error: "El parámetro filePath es obligatorio" });
    }
    if (!sessions[userId]) {
      return res.status(404).json({ error: "Sesión no iniciada" });
    }
    try {
      const result = await sessions[userId].sendFile(peer, {
        file: filePath,
        caption: caption || ""
      });
      res.json({ success: true, message: "Archivo enviado correctamente", result });
    } catch (error) {
      console.error("Error enviando multimedia:", error);
      res.status(500).json({ error: error.message });
    }
  });

/**
 * @openapi
 * /session-status/{userId}:
 *   get:
 *     summary: Obtiene el estado de la sesión (validada o no) y el userId.
 *     tags: [Authentication]
 *     parameters:
 *       - in: path
 *         name: userId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del usuario.
 *     responses:
 *       200:
 *         description: Estado de la sesión.
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 userId:
 *                   type: string
 *                 isValidated:
 *                   type: boolean
 *       404:
 *         description: Sesión no encontrada.
 */
app.get("/session-status/:userId", async (req, res) => {
    const { userId } = req.params;
    if (!sessions[userId]) {
      return res.status(404).json({ error: "Sesión no encontrada" });
    }
    try {
      // Usamos getMe para verificar si la sesión está autenticada
      const user = await sessions[userId].getMe();
      const isConnected = !!user; // true si getMe retorna un objeto válido
      res.json({ userId, isConnected });
    } catch (err) {
      console.error(`Error verificando sesión para usuario ${userId}:`, err);
      res.status(500).json({ error: err.message });
    }
  });
/**
 * @openapi
 * /all-session-status:
 *   get:
 *     summary: Obtiene el estado de todas las sesiones (si han sido validadas o no)
 *     tags: [Authentication]
 *     responses:
 *       200:
 *         description: Lista de sesiones con su estado.
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 success:
 *                   type: boolean
 *                 sessions:
 *                   type: array
 *                   items:
 *                     type: object
 *                     properties:
 *                       userId:
 *                         type: string
 *                       isValidated:
 *                         type: boolean
 */
app.get("/all-session-status", async (req, res) => {
    try {
      const allStatus = await Promise.all(
        Object.keys(sessions).map(async (userId) => {
          try {
            const user = await sessions[userId].getMe();
            return { userId, isValidated: !!user };
          } catch (error) {
            return { userId, isValidated: false };
          }
        })
      );
      res.json({ success: true, sessions: allStatus });
    } catch (error) {
      res.status(500).json({ error: error.message });
    }
  });

  /**
 * @openapi
 * /active-sessions:
 *   get:
 *     summary: Obtiene las sesiones activas en el sistema
 *     tags: [Sessions]
 *     responses:
 *       200:
 *         description: Lista de sesiones activas
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 success:
 *                   type: boolean
 *                 sessions:
 *                   type: array
 *                   items:
 *                     type: object
 *                     properties:
 *                       userId:
 *                         type: string
 *                       isConnected:
 *                         type: boolean
 *       500:
 *         description: Error en el servidor
 */
app.get("/active-sessions", async (req, res) => {
    try {
      const activeSessions = await Promise.all(
        Object.keys(sessions).map(async (userId) => {
          try {
            const user = await sessions[userId].getMe(); // Comprobamos si está autenticado
            return {
              userId,
              isConnected: user ? true : false, // Solo es `true` si getMe() no falla
            };
          } catch (err) {
            console.error(`Error verificando sesión para usuario ${userId}:`, err);
            return { userId, isConnected: false };
          }
        })
      );

      res.json({ success: true, sessions: activeSessions });
    } catch (error) {
      res.status(500).json({ error: error.message });
    }
  });
/**
 * Manejador de mensajes entrantes.
 * Descarga la media (si existe) y la convierte a base64.
 * Además, revisa las reglas de autorespuesta configuradas para enviar respuestas automáticas.
 */
async function handleIncomingMessage(userId, event) {
    // 1) Verificar que exista el mensaje
    if (!event.message) return;

    console.log("Evento recibido:", event);

    // 2) Obtener referencia al cliente
    const client = sessions[userId];
    if (!client) {
      console.error(`No existe sesión para userId: ${userId}`);
      return;
    }

    // 3) Obtener mi propio ID (para identificar si soy el sender en mensajes out = true)
    let myId = null;
    try {
      const me = await client.getMe();
      myId = me.id.toString();
    } catch (err) {
      console.error("Error haciendo getMe():", err);
    }

    // Variable de entorno para autosave Base64
    const autosaveBase64 = process.env.AUTOSAVE_BASE64 === "true";

    // 4) Determinar el ID real del remitente (realFromId)
    let realFromId = null;
    if (event.message && event.message.fromId) {
      if (typeof event.message.fromId === "object" && event.message.fromId.userId) {
        realFromId = event.message.fromId.userId.toString();
      } else {
        realFromId = event.message.fromId.toString();
      }
    } else if (event.fromId) {
      realFromId = event.fromId.toString();
    } else if (event.senderId) {
      realFromId = event.senderId.toString();
    } else if (event.userId) {
      if (typeof event.userId === "object" && event.userId.value != null) {
        realFromId = event.userId.value.toString();
      } else {
        realFromId = event.userId.toString();
      }
    }

    // 5) Determinar si es saliente (out) o entrante (in)
    let isOut = false;
    if (event.message && typeof event.message.out === "boolean") {
      isOut = event.message.out;
    } else if (typeof event.out === "boolean") {
      isOut = event.out;
    } else if (realFromId && realFromId === myId) {
      isOut = true;
    }

    // 6) Evitar duplicados y obtener el messageId
    let msgId = null;
    if (event.id) {
      msgId = event.id.toString();
    } else if (event.message && event.message.id) {
      msgId = event.message.id.toString();
    }
    if (msgId) {
      if (!processedMessages[userId]) {
        processedMessages[userId] = new Set();
      }
      if (processedMessages[userId].has(msgId)) {
        console.log(`Mensaje duplicado ignorado: ${msgId}`);
        return;
      }
      processedMessages[userId].add(msgId);
      saveProcessedMessages();
    }

    // 7) Extraer el texto
    let messageText = "";
    if (typeof event.message === "string") {
      messageText = event.message;
    } else if (event.message && typeof event.message.message === "string") {
      messageText = event.message.message;
    }

    // 8) Buscar la media
    let theMedia = null;
    if (event.media) {
      theMedia = event.media;
    } else if (event.message && event.message.media) {
      theMedia = event.message.media;
    }
    let imageBase64 = null;
    let hasMedia = false;
    let mediaType = null; // "image" | "video" | "audio" | "document" | etc.
    if (theMedia) {
      if (
        theMedia.className === "MessageMediaGeo" ||
        theMedia.className === "MessageMediaVenue"
      ) {
        mediaType = "location";
        hasMedia = false;
      } else {
        try {
    // Si se desea guardar el contenido Base64, se procede a descargar
        if (autosaveBase64) {
            try {
            const mediaBuffer = await client.downloadMedia(theMedia, { workers: 1 });
            if (mediaBuffer) {
                imageBase64 = mediaBuffer.toString("base64");
                hasMedia = true;
            }
            } catch (err) {
            console.error("Error descargando media:", err);
            }
        } else {
            // No descargar, pero se sabe que hay media
            hasMedia = true;
        }
        } catch (err) {
          console.error("Error descargando media:", err);
        }
        if (theMedia.className === "MessageMediaPhoto") {
          mediaType = "image";
        } else if (theMedia.className === "MessageMediaDocument" && theMedia.document) {
          const mime = theMedia.document.mimeType || "";
          if (mime.startsWith("image/")) {
            mediaType = "image";
          } else if (mime.startsWith("video/")) {
            mediaType = "video";
          } else if (mime.startsWith("audio/")) {
            mediaType = "audio";
          } else {
            mediaType = "document";
          }
        } else {
          mediaType = "document";
        }
      }
    }
    if (!messageText.trim() && hasMedia) {
      messageText = "Only " + (mediaType || "media");
    }

    // 9) Determinar sender y chatPeer
    let sender = "desconocido";
    let chatPeer = "desconocido";

    // Si el mensaje tiene la propiedad peerId, lo usamos para obtener el id del chat
    if (event.message && event.message.peerId) {
      const p = event.message.peerId;
      if (p.userId != null) {
        chatPeer = p.userId.toString();
      } else if (p.chatId != null) {
        chatPeer = p.chatId.toString();
      } else if (p.channelId != null) {
        chatPeer = p.channelId.toString();
      }
      // Para sender, usamos el fromId si existe
      if (event.message.fromId) {
        if (typeof event.message.fromId === "object" && event.message.fromId.userId) {
          sender = event.message.fromId.userId.toString();
        } else {
          sender = event.message.fromId.toString();
        }
      } else {
        sender = isOut ? (myId || "desconocido") : chatPeer;
      }
    }
    // Para UpdateShortMessage u otros eventos sin peerId
    else if (event.userId != null) {
      if (typeof event.userId === "object" && event.userId.value != null) {
        chatPeer = event.userId.value.toString();
      } else {
        chatPeer = event.userId.toString();
      }
      // En estos casos, si el mensaje es saliente, sender es myId; si no, sender es el id del remitente (igual al chatPeer)
      sender = isOut ? (myId || "desconocido") : chatPeer;
    }
    // Si no se encontró ninguna de las anteriores, usar realFromId como fallback para ambos
    else {
      chatPeer = realFromId || "desconocido";
      sender = realFromId || "desconocido";
    }

    // 10) Fecha y descartar si es muy antiguo
    const currentTimestamp = Math.floor(Date.now() / 1000);
    const msgTimestamp = event.date || currentTimestamp;
    if (currentTimestamp - msgTimestamp > 172800) {
      console.log("Mensaje muy antiguo, se ignora.");
      return;
    }

    // 11) status
    const status = isOut ? "sent" : "received";

    // 12) Construir objeto final, incluyendo el messageId
    const messageData = {
      messageId: msgId,
      user_id: String(userId),
      sender,      // Peer del usuario que envía (en mensajes salientes, myId)
      chatPeer,    // Id del chat
      message: messageText,
      date: msgTimestamp,
      status,
      Base64: autosaveBase64 ? imageBase64 : undefined,
      hasMedia,
      mediaType
    };

    console.log("Mensaje procesado:", messageData);

    // 13) Enviar a API externa (opcional)
    if (API_EXTERNAL) {
      try {
        const https = require("https");
        const agent = new https.Agent({ rejectUnauthorized: false });
        await axios.post(API_EXTERNAL_URL, messageData, {
          headers: { Authorization: `Bearer ${API_EXTERNAL_TOKEN}` },
          httpsAgent: agent
        });
        console.log("Mensaje enviado a API externa.");
      } catch (error) {
        console.error(
          "Error enviando a API externa:",
          error.response ? error.response.data : error.message
        );
      }
    }

    // 14) Guardar en disco/BD
    const userMessagesPath = path.join(DATA_FOLDER, `${userId}_messages.json`);
    let storedMsgs = [];
    if (fs.existsSync(userMessagesPath)) {
      storedMsgs = JSON.parse(fs.readFileSync(userMessagesPath));
    }
    storedMsgs.push(messageData);
    fs.writeFileSync(userMessagesPath, JSON.stringify(storedMsgs, null, 2));

    // 15) Auto-respuestas (opcional)
    if (status === "received" && autoResponseRules[userId]) {
      for (const rule of autoResponseRules[userId]) {
        if (messageText.toLowerCase().includes(rule.keyword.toLowerCase())) {
          try {
            await client.sendMessage(chatPeer, { message: rule.response });
            console.log(`Auto-respuesta enviada: "${rule.response}" (keyword: ${rule.keyword})`);
            break;
          } catch (err) {
            console.error("Error enviando auto-respuesta:", err);
          }
        }
      }
    }
  }


/**
 * Iniciar la API y cargar sesiones y mensajes procesados.
 */
app.listen(PORT, () => {
  console.log(`API corriendo en http://localhost:${PORT}`);
  console.log(`Swagger UI disponible en http://localhost:${PORT}/api-docs`);
  loadSessions();
});
