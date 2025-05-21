<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visor de Mensajes del Gateway (AJAX) - Estilo Node</title>
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
            --warning-bg: #fff3cd; /* Usado para loading en este caso */
            --warning-text: #856404; /* Usado para loading en este caso */
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
        header.main-header { /* Renombrado para evitar conflicto con header de HTML5 */
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
            margin-bottom: 20px; 
            display: flex; 
            gap: 12px; 
        }
        #topic-controls button { 
            padding: 10px 15px; 
            border: none; 
            border-radius: var(--border-radius); 
            cursor: pointer; 
            background-color: var(--secondary-color); 
            color: white; 
            font-size: 0.95em;
            transition: background-color 0.2s ease;
            font-weight: 500;
        }
        #topic-controls button:hover { 
            background-color: #2980b9; 
        }
         #topic-controls button:disabled {
            background-color: #a0aec0; /* Gris */
            cursor: not-allowed;
        }
        #topic-list-info { /* Cambiado de #topic-list */
            list-style: none; 
            padding: 0; 
            margin: 0; 
            flex-grow: 1; 
            overflow-y: auto;
            text-align: center;
            padding-top: 20px;
            color: var(--text-light-color);
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
        .status-bar { /* Nombre genérico */
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

        #messages { /* ID para la lista de mensajes */
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
            max-height: 200px; /* Para evitar payloads muy largos */
            overflow-y: auto;
        }
        .message-meta { 
            font-size: 0.8em; 
            color: var(--text-light-color); 
            margin-top: 8px; 
            text-align: right;
        }
        .no-messages {
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
    <header class="main-header">Visor de Mensajes del Gateway (AJAX)</header>
    <div class="container">
        <aside id="sidebar">
            <h2>Tópicos</h2>
            <div id="topic-controls">
                <!-- Los botones de control de tópicos se dejan como visuales,
                     ya que la funcionalidad de filtrado no está implementada
                     con el endpoint AJAX actual. -->
                <button id="fetchMessagesBtn" title="Actualizar mensajes manualmente">Actualizar</button>
                <button id="autoRefreshBtn" title="Iniciar/detener auto-actualización de mensajes">Auto-Refrescar</button>
            </div>
            <div id="topic-list-info">
                <p>La lista de tópicos y el filtrado no están disponibles en esta vista (se obtienen todos los mensajes).</p>
            </div>
        </aside>
        <main id="main-content">
            <div class="status-bar" id="connectionStatus">Presiona "Actualizar" para cargar mensajes.</div>
            <ul id="messages">
                 <div class="no-messages">No hay mensajes para mostrar.</div>
            </ul>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const messagesUl = document.getElementById('messages'); // Cambiado de messagesContainer
            const fetchMessagesBtn = document.getElementById('fetchMessagesBtn');
            const autoRefreshBtn = document.getElementById('autoRefreshBtn');
            const connectionStatusDiv = document.getElementById('connectionStatus'); // Cambiado de statusBar
            const gatewayDataUrl = "{{ $gatewayDataUrl }}"; 
            
            let autoRefreshIntervalId = null;
            let isAutoRefreshing = false;
            const autoRefreshTime = 1000; // Actualizar cada 1 segundos

            function displayMessages(messages) {
                // Guardar si estaba al final del scroll antes de limpiar
                const previouslyScrolledToBottom = messagesUl.scrollHeight - messagesUl.scrollTop <= messagesUl.clientHeight + 20; // +20 de margen

                messagesUl.innerHTML = ''; // Limpiar mensajes anteriores
                if (!messages || messages.length === 0) {
                    messagesUl.innerHTML = '<div class="no-messages">No hay mensajes para mostrar.</div>';
                    return;
                }

                // Mostrar los mensajes en orden inverso (más nuevo primero)
                messages.slice().reverse().forEach(msg => {
                    const listItem = document.createElement('li'); // Crear li en lugar de div

                    let payloadDisplay = msg.payload;
                    if (typeof msg.payload === 'object') {
                        payloadDisplay = JSON.stringify(msg.payload, null, 2);
                    } else {
                        const tempDiv = document.createElement('div');
                        tempDiv.textContent = payloadDisplay;
                        payloadDisplay = tempDiv.innerHTML; // Para escapar HTML
                    }
                    
                    const antennaName = msg.antenna_name || 'N/A';
                    const receivedAt = msg.received_at ? new Date(msg.received_at).toLocaleString('es-ES', { dateStyle: 'short', timeStyle: 'medium' }) : 'Fecha desconocida';

                    listItem.innerHTML = `
                        <div class="message-header">Tópico: ${msg.topic} (Antena: ${antennaName})</div>
                        <pre class="message-payload">${payloadDisplay}</pre> <div class="message-meta">Recibido: ${receivedAt}</div>
                    `;
                    messagesUl.appendChild(listItem);
                });

                if (previouslyScrolledToBottom) { 
                    messagesUl.scrollTop = messagesUl.scrollHeight;
                }
            }

            async function fetchGatewayMessages() {
                connectionStatusDiv.textContent = 'Cargando mensajes...';
                connectionStatusDiv.className = 'status-bar loading';
                fetchMessagesBtn.disabled = true;
                if (!isAutoRefreshing) autoRefreshBtn.disabled = true;


                try {
                    const response = await fetch(gatewayDataUrl);
                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({ error: "Error desconocido al procesar la respuesta del servidor."}));
                        throw new Error(`Error del servidor: ${response.status} - ${errorData.error || response.statusText}`);
                    }
                    const data = await response.json();
                    displayMessages(data);
                    connectionStatusDiv.textContent = `Mensajes cargados. Total: ${data.length}. Actualizado: ${new Date().toLocaleTimeString('es-ES')}`;
                    connectionStatusDiv.className = 'status-bar success';
                } catch (error) {
                    console.error('Error al obtener mensajes del gateway:', error);
                    connectionStatusDiv.textContent = `Error al cargar: ${error.message}`;
                    connectionStatusDiv.className = 'status-bar error';
                    messagesUl.innerHTML = '<div class="no-messages">Error al cargar los mensajes. Revisa la consola.</div>';
                } finally {
                    fetchMessagesBtn.disabled = false;
                     if (!isAutoRefreshing) autoRefreshBtn.disabled = false;
                }
            }

            function toggleAutoRefresh() {
                if (isAutoRefreshing) {
                    clearInterval(autoRefreshIntervalId);
                    autoRefreshIntervalId = null;
                    isAutoRefreshing = false;
                    autoRefreshBtn.textContent = 'Auto-Refrescar';
                    autoRefreshBtn.title = 'Iniciar auto-actualización de mensajes';
                    connectionStatusDiv.textContent = 'Auto-actualización detenida. ' + connectionStatusDiv.textContent;
                    fetchMessagesBtn.disabled = false;
                } else {
                    fetchGatewayMessages(); // Carga inicial
                    autoRefreshIntervalId = setInterval(fetchGatewayMessages, autoRefreshTime);
                    isAutoRefreshing = true;
                    autoRefreshBtn.textContent = 'Detener Auto-R.';
                    autoRefreshBtn.title = 'Detener auto-actualización de mensajes';
                    // connectionStatusDiv.textContent = `Auto-actualización iniciada (cada ${autoRefreshTime/1000}s).`;
                    fetchMessagesBtn.disabled = true; 
                }
            }

            fetchMessagesBtn.addEventListener('click', fetchGatewayMessages);
            autoRefreshBtn.addEventListener('click', toggleAutoRefresh);

            // Opcional: Cargar mensajes al inicio si se desea
            // fetchGatewayMessages(); 
        });
    </script>
</body>
</html>
