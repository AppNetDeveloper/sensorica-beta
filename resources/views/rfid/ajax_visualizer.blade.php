<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visor de Mensajes del Gateway (AJAX) - Con Filtros</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50; 
            --secondary-color: #3498db; 
            --background-color: #ecf0f1; 
            --surface-color: #ffffff; 
            --text-color: #34495e; 
            --text-light-color: #7f8c8d; 
            --border-color: #bdc3c7; 
            --success-bg: #d4edda;
            --success-text: #155724;
            --error-bg: #f8d7da;
            --error-text: #721c24;
            --warning-bg: #fff3cd;
            --warning-text: #856404;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --border-radius: 8px;
        }
        body { 
            font-family: 'Roboto', Arial, sans-serif; 
            margin: 0; 
            display: flex; 
            flex-direction: column; 
            height: 100vh; 
            background-color: var(--background-color); 
            color: var(--text-color);
            line-height: 1.6;
        }
        header.main-header {
            background-color: var(--primary-color); 
            color: white; 
            padding: 18px 25px; 
            text-align: center; 
            box-shadow: var(--box-shadow); 
            font-size: 1.4em;
            font-weight: 500;
        }
        .container { 
            display: flex; 
            flex: 1; 
            overflow: hidden; 
            padding: 15px; 
            gap: 15px;
        }
        #sidebar { 
            width: 300px; 
            background-color: var(--surface-color); 
            padding: 20px; 
            border-radius: var(--border-radius); 
            box-shadow: var(--box-shadow); 
            display: flex; 
            flex-direction: column; 
            overflow-y: auto; 
        }
        #sidebar h2 { 
            margin-top: 0; 
            font-size: 1.3em; 
            color: var(--primary-color); 
            border-bottom: 2px solid var(--secondary-color); 
            padding-bottom: 12px;
            font-weight: 500;
        }
        #topic-controls { 
            margin-bottom: 15px; 
            display: flex; 
            flex-wrap: wrap;
            gap: 10px; 
        }
        #topic-controls button { 
            padding: 8px 12px; 
            border: none; 
            border-radius: var(--border-radius); 
            cursor: pointer; 
            background-color: var(--secondary-color); 
            color: white; 
            font-size: 0.9em;
            transition: background-color 0.2s ease;
            font-weight: 500;
            flex-grow: 1;
        }
        #topic-controls button:hover { 
            background-color: #2980b9; 
        }
         #topic-controls button:disabled {
            background-color: #a0aec0;
            cursor: not-allowed;
        }
        #topic-list { /* Cambiado de #topic-list-info */
            list-style: none; 
            padding: 0; 
            margin: 0; 
            flex-grow: 1; 
            overflow-y: auto; 
        }
        #topic-list li { 
            margin-bottom: 8px; 
            display: flex; 
            align-items: center;
            padding: 6px;
            border-radius: 4px;
            transition: background-color 0.2s ease;
        }
        #topic-list li:hover {
            background-color: #f8f9fa; 
        }
        #topic-list input[type="checkbox"] { 
            margin-right: 10px; 
            transform: scale(1.1); 
            cursor: pointer;
            accent-color: var(--secondary-color);
        }
        #topic-list label { 
            font-size: 0.95em; 
            color: var(--text-color); 
            cursor: pointer; 
            word-break: break-all; 
        }
        
        #main-content { 
            flex: 1; 
            background-color: var(--surface-color); 
            padding: 20px; 
            border-radius: var(--border-radius); 
            box-shadow: var(--box-shadow); 
            display: flex; 
            flex-direction: column; 
            overflow: hidden;
        }
        .status-bar {
            text-align: center; 
            padding: 12px; 
            border-radius: var(--border-radius); 
            margin-bottom:18px; 
            font-size: 0.95em;
            font-weight: 500;
            border: 1px solid transparent;
        }
        .status-bar.loading { background-color: var(--warning-bg); color: var(--warning-text); border-color: var(--warning-text); }
        .status-bar.success { background-color: var(--success-bg); color: var(--success-text); border-color: var(--success-text); }
        .status-bar.error { background-color: var(--error-bg); color: var(--error-text); border-color: var(--error-text); }

        #messages { 
            flex-grow: 1; 
            overflow-y: auto; 
            list-style: none; 
            padding: 0; 
            margin: 0; 
            border-top: 1px solid var(--border-color);
        }
        #messages li { 
            padding: 15px; 
            border-bottom: 1px solid var(--border-color); 
            word-wrap: break-word; 
        }
        #messages li:last-child { 
            border-bottom: none; 
        }
        .message-header { 
            font-weight: 500; 
            color: var(--primary-color); 
            font-size: 1em; 
            margin-bottom: 5px;
        }
        .message-payload { 
            margin-left: 0; 
            white-space: pre-wrap; 
            font-size: 0.9em; 
            background-color: #f9f9f9; 
            padding: 10px; 
            border-radius: 4px; 
            margin-top: 8px;
            border: 1px solid #e9e9e9;
            font-family: 'Courier New', Courier, monospace; 
            max-height: 200px;
            overflow-y: auto;
        }
        .message-meta { 
            font-size: 0.8em; 
            color: var(--text-light-color); 
            margin-top: 8px; 
            text-align: right;
        }
        .no-messages, .no-topics {
            text-align: center;
            padding: 20px;
            color: var(--text-light-color);
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
    </style>
</head>
<body>
    <header class="main-header">Visor de Mensajes del Gateway (Filtrado)</header>
    <div class="container">
        <aside id="sidebar">
            <h2>Tópicos Suscritos</h2>
            <div id="topic-controls">
                <button id="fetchMessagesBtn" title="Actualizar mensajes y tópicos">Actualizar</button>
                <button id="autoRefreshBtn" title="Iniciar/detener auto-actualización">Auto-Refrescar</button>
                <button id="selectAllTopicsBtn" title="Seleccionar todos los tópicos">Todos</button>
                <button id="deselectAllTopicsBtn" title="Deseleccionar todos los tópicos">Ninguno</button>
            </div>
            <ul id="topic-list">
                <div class="no-topics">Cargando tópicos...</div>
            </ul>
        </aside>
        <main id="main-content">
            <div class="status-bar" id="connectionStatus">Presiona "Actualizar" para cargar datos.</div>
            <ul id="messages">
                 <div class="no-messages">No hay mensajes para mostrar.</div>
            </ul>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const messagesUl = document.getElementById('messages');
            const topicListUl = document.getElementById('topic-list');
            const fetchMessagesBtn = document.getElementById('fetchMessagesBtn');
            const autoRefreshBtn = document.getElementById('autoRefreshBtn');
            const selectAllTopicsBtn = document.getElementById('selectAllTopicsBtn');
            const deselectAllTopicsBtn = document.getElementById('deselectAllTopicsBtn');
            const connectionStatusDiv = document.getElementById('connectionStatus');
            
            // Define la URL de tu API de Laravel que llama al Node.js
            // Asegúrate que esta ruta exista en routes/web.php y apunte al método correcto en RfidController
            const gatewayDataUrl = "/rfid-mqtt/api/gateway-data"; 
            // Si pasas la URL desde el controlador Laravel (descomentar si es el caso):
            // const gatewayDataUrl = "{{-- isset($gatewayDataUrl) ? $gatewayDataUrl : '/rfid-mqtt/api/gateway-data' --}}";

            let autoRefreshIntervalId = null;
            let isAutoRefreshing = false;
            const autoRefreshTime = 7000; 

            let allMessages = []; 
            let availableTopics = []; 
            let selectedTopics = new Set(); 

            function populateTopicList() {
                topicListUl.innerHTML = '';
                if (!Array.isArray(availableTopics) || availableTopics.length === 0) {
                    topicListUl.innerHTML = '<div class="no-topics">No hay tópicos disponibles.</div>';
                    return;
                }

                availableTopics.forEach(topicInfo => {
                    const listItem = document.createElement('li');
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.id = 'topic-' + topicInfo.topic.replace(/[^a-zA-Z0-9_\\-\\/]/g, "_");
                    checkbox.value = topicInfo.topic;
                    checkbox.checked = selectedTopics.has(topicInfo.topic);
                    
                    checkbox.addEventListener('change', (event) => {
                        if (event.target.checked) {
                            selectedTopics.add(topicInfo.topic);
                        } else {
                            selectedTopics.delete(topicInfo.topic);
                        }
                        renderMessages(); 
                    });

                    const label = document.createElement('label');
                    label.htmlFor = checkbox.id;
                    label.textContent = `${topicInfo.topic} (${topicInfo.antenna_name || 'N/A'})`;
                    
                    listItem.appendChild(checkbox);
                    listItem.appendChild(label);
                    topicListUl.appendChild(listItem);
                });
            }

            function renderMessages() {
                const previouslyScrolledToBottom = messagesUl.scrollHeight - messagesUl.scrollTop <= messagesUl.clientHeight + 20;
                messagesUl.innerHTML = '';
                
                // Asegurarse que allMessages es un array antes de filtrar
                if (!Array.isArray(allMessages)) {
                    console.error("renderMessages: allMessages no es un array. Forzando a array vacío.", allMessages);
                    allMessages = [];
                }

                const filteredMessages = allMessages.filter(msg => 
                    selectedTopics.size === 0 || (msg && typeof msg.topic === 'string' && selectedTopics.has(msg.topic))
                );

                if (filteredMessages.length === 0) {
                    if (allMessages.length > 0 && selectedTopics.size > 0) {
                        messagesUl.innerHTML = '<div class="no-messages">No hay mensajes para los tópicos seleccionados.</div>';
                    } else if (allMessages.length === 0) {
                         messagesUl.innerHTML = '<div class="no-messages">No hay mensajes para mostrar.</div>';
                    } else { 
                        messagesUl.innerHTML = '<div class="no-messages">Selecciona un tópico para ver mensajes.</div>';
                    }
                    return;
                }

                filteredMessages.slice().reverse().forEach(msg => { // .slice() se usa en filteredMessages, que es un array
                    const listItem = document.createElement('li');
                    let payloadDisplay = msg.payload;
                    if (typeof msg.payload === 'object') {
                        payloadDisplay = JSON.stringify(msg.payload, null, 2);
                    } else {
                        const tempDiv = document.createElement('div');
                        tempDiv.textContent = String(payloadDisplay); // Asegurar que es string
                        payloadDisplay = tempDiv.innerHTML;
                    }
                    const antennaName = msg.antenna_name || 'N/A'; 
                    const receivedAt = msg.received_at ? new Date(msg.received_at).toLocaleString('es-ES', { dateStyle: 'short', timeStyle: 'medium' }) : 'Fecha desconocida';

                    listItem.innerHTML = `
                        <div class="message-header">Tópico: ${msg.topic || 'Desconocido'} (Antena: ${antennaName})</div>
                        <pre class="message-payload">${payloadDisplay}</pre>
                        <div class="message-meta">Recibido: ${receivedAt}</div>
                    `;
                    messagesUl.appendChild(listItem);
                });

                if (previouslyScrolledToBottom) { 
                    messagesUl.scrollTop = messagesUl.scrollHeight;
                }
            }

            async function fetchData() {
                connectionStatusDiv.textContent = 'Cargando datos...';
                connectionStatusDiv.className = 'status-bar loading';
                fetchMessagesBtn.disabled = true;
                if (!isAutoRefreshing) autoRefreshBtn.disabled = true;
                selectAllTopicsBtn.disabled = true;
                deselectAllTopicsBtn.disabled = true;

                try {
                    const response = await fetch(gatewayDataUrl);
                    if (!response.ok) {
                        let errorDetails = response.statusText;
                        try {
                            const errorData = await response.json();
                            errorDetails = errorData.error || errorData.message || response.statusText;
                        } catch (e) { /* No es JSON, usar statusText */ }
                        throw new Error(`Error del servidor: ${response.status} - ${errorDetails}`);
                    }
                    const data = await response.json();
                    console.log("Datos recibidos:", data); // Para depuración

                    if (data.error_messages) { 
                        connectionStatusDiv.textContent = `${data.error_messages}. Tópicos pueden estar cargados desde BD.`;
                        connectionStatusDiv.className = 'status-bar warning'; 
                        allMessages = []; 
                    } else {
                        allMessages = Array.isArray(data.messages) ? data.messages : [];
                    }
                    
                    availableTopics = Array.isArray(data.topics_info) ? data.topics_info : [];

                    const currentAvailableTopicValues = new Set(availableTopics.map(t => t.topic));
                    const newSelectedTopics = new Set();
                    selectedTopics.forEach(selected => {
                        if (currentAvailableTopicValues.has(selected)) {
                            newSelectedTopics.add(selected);
                        }
                    });
                    selectedTopics = newSelectedTopics;
                    
                     if (selectedTopics.size === 0 && availableTopics.length > 0) {
                         availableTopics.forEach(t => selectedTopics.add(t.topic));
                     }

                    populateTopicList();
                    renderMessages();
                    
                    if (!data.error_messages) {
                        connectionStatusDiv.textContent = `Datos cargados. ${allMessages.length} mensajes, ${availableTopics.length} tópicos. Actualizado: ${new Date().toLocaleTimeString('es-ES')}`;
                        connectionStatusDiv.className = 'status-bar success';
                    }

                } catch (error) {
                    console.error('Error al obtener datos:', error);
                    connectionStatusDiv.textContent = `Error al cargar: ${error.message}`;
                    connectionStatusDiv.className = 'status-bar error';
                    messagesUl.innerHTML = '<div class="no-messages">Error al cargar los mensajes. Revisa la consola.</div>';
                    topicListUl.innerHTML = '<div class="no-topics">Error al cargar tópicos.</div>';
                } finally {
                    fetchMessagesBtn.disabled = false;
                    if (!isAutoRefreshing) autoRefreshBtn.disabled = false;
                    selectAllTopicsBtn.disabled = availableTopics.length === 0;
                    deselectAllTopicsBtn.disabled = availableTopics.length === 0;
                }
            }

            function toggleAutoRefresh() {
                if (isAutoRefreshing) {
                    clearInterval(autoRefreshIntervalId);
                    autoRefreshIntervalId = null;
                    isAutoRefreshing = false;
                    autoRefreshBtn.textContent = 'Auto-Refrescar';
                    autoRefreshBtn.title = 'Iniciar auto-actualización';
                    fetchMessagesBtn.disabled = false;
                } else {
                    fetchData(); 
                    autoRefreshIntervalId = setInterval(fetchData, autoRefreshTime);
                    isAutoRefreshing = true;
                    autoRefreshBtn.textContent = 'Detener Auto-R.';
                    autoRefreshBtn.title = 'Detener auto-actualización';
                    fetchMessagesBtn.disabled = true; 
                }
            }

            selectAllTopicsBtn.addEventListener('click', () => {
                topicListUl.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                    cb.checked = true;
                    if (cb.value) selectedTopics.add(cb.value);
                });
                renderMessages();
            });

            deselectAllTopicsBtn.addEventListener('click', () => {
                topicListUl.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                    cb.checked = false;
                });
                selectedTopics.clear();
                renderMessages();
            });

            fetchMessagesBtn.addEventListener('click', fetchData);
            autoRefreshBtn.addEventListener('click', toggleAutoRefresh);

            fetchData(); 
        });
    </script>
</body>
</html>
