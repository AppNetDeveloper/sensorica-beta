<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración Antena RFID</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f7f6; color: #333; }
        .container { max-width: 700px; margin: 20px auto; background-color: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 15px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; text-align: center; margin-bottom: 25px;}
        label { display: block; margin-top: 15px; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="number"], input[type="text"] { width: calc(100% - 24px); padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 1em; }
        input[type="checkbox"] { margin-right: 5px; vertical-align: middle; }
        button { padding: 12px 18px; margin-top: 20px; margin-right: 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; transition: background-color 0.3s ease; }
        .btn-get { background-color: #3498db; color: white; }
        .btn-get:hover { background-color: #2980b9; }
        .btn-set { background-color: #2ecc71; color: white; }
        .btn-set:hover { background-color: #27ae60; }
        #statusMessages { margin-top: 20px; padding: 12px; border-radius: 4px; text-align: center; font-weight: bold;}
        .status-success { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .status-error { background-color: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }
        .status-info { background-color: #e3f2fd; color: #1565c0; border: 1px solid #90caf9; }
        .status-warning { background-color: #fff3e0; color: #ef6c00; border: 1px solid #ffcc80; }
        #antennaDataContainer { margin-top: 25px; border-top: 1px solid #eee; padding-top: 20px; }
        .antenna-block { border: 1px solid #e0e0e0; padding: 15px; margin-top:15px; border-radius: 6px; background-color: #f9f9f9; }
        .antenna-block h2 { margin-top: 0; color: #34495e; font-size: 1.2em; border-bottom: 1px solid #eee; padding-bottom: 8px;}
        #rawResponseContainer { margin-top: 20px; padding: 10px; background-color: #2c3e50; color: #ecf0f1; border-radius: 4px; font-family: 'Courier New', Courier, monospace; font-size: 0.9em; white-space: pre-wrap; word-break: break-all; max-height: 200px; overflow-y: auto; }
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Configuración de Antena RFID</h1>

        <div id="statusMessages" class="status-info">Conectando al servidor...</div>

        <div class="antenna-block" id="primaryAntennaConfigBlock">
            <h2>Antena Principal (para Set)</h2>
            <label for="configAntennaIndex">Índice de Antena:</label>
            <input type="number" id="configAntennaIndex" value="1" min="1">

            <label for="configAntennaPower">Potencia (0-35 dBm):</label>
            <input type="number" id="configAntennaPower" min="0" max="35">

            <label for="configAntennaEnable">Habilitada:</label>
            <input type="checkbox" id="configAntennaEnable" checked>
        </div>

        <div>
            <button id="getPowerBtn" class="btn-get">Obtener Potencia de Antenas</button>
            <button id="setPowerBtn" class="btn-set">Establecer Potencia (Antena Principal)</button>
        </div>

        <div id="antennaDataContainer">
            </div>
        
        <h3>Respuesta Cruda del Servidor:</h3>
        <div id="rawResponseContainer" class="hidden"></div>

    </div>

    <script src="https://cdn.socket.io/4.7.5/socket.io.min.js" xintegrity="sha384-2huaZvOR9iDzHqslqwpR87isEmrfxqyWOF7hr7BY6SOCKETIOCHECKSUM" crossorigin="anonymous"></script>
    <script>
        // IMPORTANTE: Asegúrate que esta URL coincida con la de tu servidor Node.js
        const NODE_SERVER_URL = 'http://localhost:3000'; 
        const socket = io(NODE_SERVER_URL, {
            reconnectionAttempts: 5,
            reconnectionDelay: 3000,
        });

        const getPowerBtn = document.getElementById('getPowerBtn');
        const setPowerBtn = document.getElementById('setPowerBtn');
        
        const configAntennaIndexInput = document.getElementById('configAntennaIndex');
        const configAntennaPowerInput = document.getElementById('configAntennaPower');
        const configAntennaEnableCheckbox = document.getElementById('configAntennaEnable');

        const statusMessagesDiv = document.getElementById('statusMessages');
        const antennaDataContainer = document.getElementById('antennaDataContainer');
        const rawResponseContainer = document.getElementById('rawResponseContainer');

        function displayStatus(message, type = 'info') { // types: info, success, error, warning
            statusMessagesDiv.textContent = message;
            statusMessagesDiv.className = `status-${type}`;
        }

        function displayRawResponse(data) {
            rawResponseContainer.textContent = JSON.stringify(data, null, 2);
            rawResponseContainer.classList.remove('hidden');
        }

        // --- Manejadores de eventos de botones ---
        getPowerBtn.addEventListener('click', () => {
            displayStatus('Solicitando potencia de antenas...', 'info');
            rawResponseContainer.classList.add('hidden');
            socket.emit('getAntennaPower');
        });

        setPowerBtn.addEventListener('click', () => {
            const antennaIndex = parseInt(configAntennaIndexInput.value);
            const power = parseInt(configAntennaPowerInput.value);
            const enable = configAntennaEnableCheckbox.checked;

            if (isNaN(antennaIndex) || antennaIndex < 1) {
                displayStatus('Índice de antena inválido.', 'error');
                return;
            }
            if (isNaN(power) || power < 0 || power > 35) {
                displayStatus('Valor de potencia inválido (debe ser entre 0 y 35).', 'error');
                return;
            }
            displayStatus(`Enviando configuración para antena ${antennaIndex}...`, 'info');
            rawResponseContainer.classList.add('hidden');
            socket.emit('setAntennaPower', { index: antennaIndex, power: power, enable: enable });
        });

        // --- Manejadores de eventos de Socket.IO ---
        socket.on('connect', () => {
            displayStatus('Conectado al servidor Node.js.', 'success');
            console.log("Conectado al servidor Node.js vía Socket.IO. ID:", socket.id);
        });

        socket.on('connect_error', (err) => {
            displayStatus(`Error de conexión con el servidor Node.js: ${err.message}. Verifica que el servidor Node.js esté corriendo en ${NODE_SERVER_URL} y que CORS esté bien configurado.`, 'error');
            console.error('Error de conexión Socket.IO:', err);
        });

        socket.on('disconnect', (reason) => {
            displayStatus(`Desconectado del servidor Node.js: ${reason}`, 'warning');
            if (reason === 'io server disconnect') {
                socket.connect(); // Intenta reconectar si el servidor lo desconectó
            }
        });
        
        socket.on('appStatus', (data) => {
            console.info('AppStatus:', data.message);
            displayStatus(data.message, 'info');
        });

        socket.on('antennaPowerData', (response) => {
            console.log('Datos de potencia recibidos:', response);
            displayRawResponse(response);

            if (response.resultCode === 0 && response.resultData) {
                displayStatus('Potencia de antenas recibida exitosamente.', 'success');
                antennaDataContainer.innerHTML = '<h3>Detalle de Antenas:</h3>'; // Limpiar y preparar para nuevos datos

                if (response.resultData.length === 0) {
                    antennaDataContainer.innerHTML += '<p>No se encontraron datos de configuración para ninguna antena.</p>';
                }

                // Actualizar el bloque de configuración principal con la primera antena
                if (response.resultData.length > 0) {
                    const firstAntenna = response.resultData[0];
                    configAntennaIndexInput.value = firstAntenna.index;
                    configAntennaPowerInput.value = firstAntenna.power;
                    configAntennaEnableCheckbox.checked = firstAntenna.enable;
                }

                response.resultData.forEach((antenna, i) => {
                    const block = document.createElement('div');
                    block.className = 'antenna-block';
                    block.innerHTML = `
                        <h2>Antena ${antenna.index} (Obtenida)</h2>
                        <p><strong>Índice:</strong> ${antenna.index}</p>
                        <p><strong>Potencia:</strong> ${antenna.power} dBm</p>
                        <p><strong>Habilitada:</strong> ${antenna.enable ? 'Sí' : 'No'}</p>
                    `;
                    // Si quieres que cada bloque sea editable y tenga su propio botón "Set",
                    // necesitarías añadir inputs y un botón aquí, y manejar sus eventos.
                    // Por ahora, solo se muestran los datos.
                    antennaDataContainer.appendChild(block);
                });
            } else {
                displayStatus(`Error al obtener potencia: ${response.resultMsg || 'Respuesta no exitosa del dispositivo.'}`, 'error');
            }
        });

        socket.on('antennaSetPowerStatus', (response) => {
            console.log('Estado de Set Power:', response);
            displayRawResponse(response.data || response);
            if (response.success && response.data && response.data.resultCode === 0) {
                displayStatus(response.data.resultMsg || 'Configuración de antena establecida exitosamente.', 'success');
                // Opcional: Volver a pedir los datos para ver los cambios reflejados
                // setTimeout(() => getPowerBtn.click(), 1000); 
            } else {
                const errorMsg = (response.data && response.data.resultMsg) ? response.data.resultMsg : (response.message || 'Error desconocido en la configuración.');
                displayStatus(`Error al configurar antena: ${errorMsg}`, 'error');
            }
        });

        socket.on('rfidError', (response) => {
            console.error('Error específico del dispositivo RFID:', response);
            displayRawResponse(response);
            displayStatus(`Error del dispositivo RFID: ${response.resultMsg || JSON.stringify(response)}`, 'error');
        });

        socket.on('appError', (error) => {
            console.error('Error de la aplicación Node.js/MQTT:', error);
            displayRawResponse(error);
            displayStatus(`Error en la aplicación: ${error.message}`, 'error');
        });
        
        socket.on('appWarning', (warning) => {
            console.warn('Advertencia de la aplicación Node.js/MQTT:', warning);
            displayRawResponse(warning);
            displayStatus(`Advertencia: ${warning.message}`, 'warning');
        });

    </script>
</body>
</html>
