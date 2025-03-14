require("dotenv").config();
const express = require("express");
const cors = require("cors");
const { TelegramClient } = require("telegram");
const { StringSession } = require("telegram/sessions");
const { Api } = require("telegram/tl"); // Para métodos de autenticación
const qrcode = require("qrcode");
const axios = require("axios");
const fs = require("fs");
const path = require("path");
const swaggerJsdoc = require("swagger-jsdoc");
const swaggerUi = require("swagger-ui-express");

const app = express();
app.use(express.json());

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
  apis: [__filename], // Usamos __filename para asegurar que apunte al archivo actual
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
    const sessionString = sessions[userId].session.save();
    fs.writeFileSync(
      path.join(DATA_FOLDER, `${userId}.json`),
      JSON.stringify({ session: sessionString }, null, 2)
    );
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

/**
 * @openapi
 * /request-code/{userId}:
 *   post:
 *     summary: Solicita un código de verificación para autenticación en Telegram
 *     tags: [Authentication]
 *     parameters:
 *       - in: path
 *         name: userId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del usuario
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             type: object
 *             properties:
 *               phone:
 *                 type: string
 *                 description: Número de teléfono del usuario
 *             required:
 *               - phone
 *     responses:
 *       200:
 *         description: Código enviado exitosamente
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
 *         description: Número de teléfono requerido
 *       500:
 *         description: Error en el servidor
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
    const result = await client.invoke(
      new Api.auth.SendCode({
        phoneNumber: phone,
        apiId: apiId,
        apiHash: apiHash,
        settings: new Api.CodeSettings({}),
      })
    );
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
 *     summary: Verifica el código y completa el login en Telegram
 *     tags: [Authentication]
 *     parameters:
 *       - in: path
 *         name: userId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del usuario
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             type: object
 *             properties:
 *               code:
 *                 type: string
 *                 description: Código de verificación
 *               password:
 *                 type: string
 *                 description: Contraseña (opcional)
 *             required:
 *               - code
 *     responses:
 *       200:
 *         description: Sesión iniciada correctamente
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
 *         description: Datos de autenticación no encontrados
 *       500:
 *         description: Error en el servidor
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
    const signInResult = await client.invoke(
      new Api.auth.SignIn({
        phoneNumber: phone,
        phoneCode: code,
        phoneCodeHash: phoneCodeHash,
        password: password || null,
      })
    );
    console.log(`Sesión iniciada para ${phone}`);
    saveSession(userId);
    delete authData[userId];
    res.json({ success: true, message: "Sesión iniciada correctamente", result: signInResult });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

/**
 * @openapi
 * /logout/{userId}:
 *   post:
 *     summary: Cierra la sesión de un usuario
 *     tags: [Authentication]
 *     parameters:
 *       - in: path
 *         name: userId
 *         required: true
 *         schema:
 *           type: string
 *         description: ID del usuario
 *     responses:
 *       200:
 *         description: Sesión cerrada correctamente
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
 *         description: Sesión no encontrada
 *       500:
 *         description: Error en el servidor
 */
app.post("/logout/:userId", async (req, res) => {
  const { userId } = req.params;
  if (!sessions[userId]) return res.status(404).json({ error: "Sesión no encontrada" });
  try {
    await sessions[userId].logOut();
    delete sessions[userId];
    deleteSession(userId);
    res.json({ success: true, message: "Sesión cerrada correctamente" });
  } catch (error) {
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
app.get("/get-messages/:userId/:peer", async (req, res) => {
  const { userId, peer } = req.params;
  if (!sessions[userId]) return res.status(404).json({ error: "Sesión no iniciada" });
  try {
    const messages = await sessions[userId].getMessages(peer, { limit: 10 });
    res.json({
      success: true,
      messages: messages.map((msg) => ({
        id: msg.id,
        message:
          typeof msg.message === "string"
            ? msg.message
            : (msg.message && msg.message.message) || "Only image",
        date: msg.date,
        peer: String(peer),
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
 * Manejador de mensajes entrantes.
 * Descarga la media (si existe) y la convierte a base64.
 * Luego, si el mensaje tiene un peer válido y es reciente (menos de 2 días),
 * se notifica a la API externa y se guarda el mensaje localmente.
 * Si no tiene un peer válido, se ignora el mensaje por completo.
 */
async function handleIncomingMessage(userId, event) {
  if (!event.message) return;

  const isGroup = event.isGroup || event.isChannel;
  let imageBase64 = null;
  if (event.media) {
    try {
      const mediaBuffer = await sessions[userId].downloadMedia(event.media, { workers: 1 });
      if (mediaBuffer) {
        imageBase64 = mediaBuffer.toString("base64");
      } else {
        console.warn("downloadMedia devolvió undefined");
      }
    } catch (err) {
      console.error("Error descargando media:", err);
    }
  }

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

  let messageText = "Only image";
  if (event.message) {
    if (typeof event.message === "string") {
      messageText = event.message;
    } else if (typeof event.message === "object" && event.message.message) {
      messageText = event.message.message;
    }
  }

  let peer = null;
  if (event.message && event.message.peerId) {
    if (typeof event.message.peerId === "object") {
      if ("userId" in event.message.peerId && event.message.peerId.userId != null) {
        peer = event.message.peerId.userId.toString();
      } else if ("chatId" in event.message.peerId && event.message.peerId.chatId != null) {
        peer = event.message.peerId.chatId.toString();
      } else if ("channelId" in event.message.peerId && event.message.peerId.channelId != null) {
        peer = event.message.peerId.channelId.toString();
      }
    } else {
      peer = event.message.peerId.toString();
    }
  }
  if (!peer && !isGroup && event.senderId != null) {
    peer = event.senderId.toString();
  }

  if (!peer) {
    console.log("Mensaje sin peer válido, se ignora.");
    return;
  }

  const currentTimestamp = Math.floor(Date.now() / 1000);
  const messageTimestamp = event.date || currentTimestamp;
  const twoDays = 172800;
  if (currentTimestamp - messageTimestamp > twoDays) {
    console.log("Mensaje demasiado antiguo, se ignora.");
    return;
  }

  const messageData = {
    user_id: String(userId),
    message: messageText,
    date: messageTimestamp,
    peer: String(peer),
    status: "received",
    image: imageBase64,
  };

  console.log("Mensaje recibido:", messageData);

  if (API_EXTERNAL) {
    try {
      await axios.post(API_EXTERNAL_URL, messageData, {
        headers: { Authorization: `Bearer ${API_EXTERNAL_TOKEN}` },
      });
      console.log("Mensaje enviado a API externa:", messageData);
    } catch (error) {
      console.error("Error enviando a API externa:", error.response ? error.response.data : error.message);
    }
  }

  const userMessagesPath = path.join(DATA_FOLDER, `${userId}_messages.json`);
  let messages = fs.existsSync(userMessagesPath) ? JSON.parse(fs.readFileSync(userMessagesPath)) : [];
  messages.push(messageData);
  fs.writeFileSync(userMessagesPath, JSON.stringify(messages, null, 2));
}

/**
 * Iniciar la API y cargar sesiones y mensajes procesados.
 */
app.listen(PORT, () => {
  console.log(`API corriendo en http://localhost:${PORT}`);
  console.log(`Swagger UI disponible en http://localhost:${PORT}/api-docs`);
  loadSessions();
});
