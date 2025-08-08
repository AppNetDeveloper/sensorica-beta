# SENSORICA - Sistema Integral de Gestión de Producción Industrial

<p align="center">
  <img src="public/img/logo.png" alt="Logo Sensorica" width="300">
</p>

## 📋 Índice

- [Descripción General](#descripción-general)
- [Características Principales](#características-principales)
- [Arquitectura del Sistema](#arquitectura-del-sistema)
- [Módulos Principales](#módulos-principales)
  - [Sistema Kanban](#sistema-kanban)
  - [Monitoreo OEE](#monitoreo-oee)
  - [Gestión de Sensores](#gestión-de-sensores)
  - [Integración con APIs Externas](#integración-con-apis-externas)
  - [Gestión de Incidencias](#gestión-de-incidencias)
- [Tecnologías Utilizadas](#tecnologías-utilizadas)
- [Requisitos del Sistema](#requisitos-del-sistema)
- [Instalación y Configuración](#instalación-y-configuración)
- [Estructura de la Base de Datos](#estructura-de-la-base-de-datos)
- [Servicios en Segundo Plano](#servicios-en-segundo-plano)
- [Licencia](#licencia)

## 📄 Descripción General

Sensorica es una plataforma integral para la gestión y monitorización de procesos industriales en tiempo real. El sistema permite la visualización, seguimiento y control de líneas de producción a través de tableros Kanban, monitoreo OEE (Overall Equipment Effectiveness), integración con sensores IoT, y gestión completa de órdenes de producción.

Diseñado para entornos industriales exigentes, Sensorica ofrece una interfaz intuitiva que permite a los operadores y gerentes de producción optimizar los flujos de trabajo, identificar cuellos de botella, y mejorar la eficiencia general de la planta.

## 🌟 Características Principales

- **Sistema Kanban Avanzado**: Gestión visual de órdenes de producción con arrastrar y soltar (drag & drop) entre diferentes estados.
- **Monitoreo OEE en Tiempo Real**: Cálculo y visualización de métricas de eficiencia global de equipos.
- **Integración IoT**: Conexión con sensores industriales a través de MQTT para monitoreo en tiempo real.
- **Gestión de Clientes Multiempresa**: Soporte para múltiples clientes con configuraciones independientes.
- **Mapeo de Campos Personalizable**: Sistema flexible para mapear campos de APIs externas a estructuras internas.
- **Gestión de Incidencias**: Sistema completo para registro y seguimiento de problemas en la producción.
- **Calendario Laboral**: Configuración de días laborables y turnos para cálculos precisos de producción.
- **Integración con ERPs**: Conexión bidireccional con sistemas ERP externos.
- **Panel de Control en Tiempo Real**: Visualización de estadísticas y KPIs de producción.

## 🏗️ Arquitectura del Sistema

Sensorica está construido sobre el framework Laravel, siguiendo una arquitectura MVC (Modelo-Vista-Controlador) con las siguientes capas:

1. **Capa de Presentación**: Interfaces de usuario basadas en Blade y JavaScript.
2. **Capa de Aplicación**: Controladores Laravel que gestionan la lógica de negocio.
3. **Capa de Dominio**: Modelos Eloquent que representan las entidades del sistema.
4. **Capa de Infraestructura**: Servicios de integración con MQTT, bases de datos y APIs externas.

El sistema utiliza un enfoque de microservicios para los componentes críticos, con procesos en segundo plano gestionados por Supervisor para tareas como:
- Monitoreo continuo de sensores
- Cálculo de métricas OEE
- Sincronización con APIs externas
- Procesamiento de datos en tiempo real

## 📦 Módulos Principales

### Sistema Kanban

El corazón de Sensorica es su sistema Kanban para la gestión visual de órdenes de producción. Características principales:

- **Tablero Interactivo**: Interfaz drag & drop para mover órdenes entre estados (Pendientes, En Curso, Finalizadas, Incidencias).
- **Filtrado Avanzado**: Búsqueda y filtrado de órdenes por múltiples criterios.
- **Indicadores Visuales**: Sistema de iconos para identificar órdenes urgentes, problemas de stock, y prioridades.
- **Actualización en Tiempo Real**: Sincronización automática del estado del tablero.
- **Restricciones de Flujo de Trabajo**: Reglas configurables para el movimiento de tarjetas (ej. tarjetas finalizadas solo pueden moverse a incidencias).
- **Gestión de Posiciones**: Algoritmo inteligente para mantener el orden correcto de las tarjetas.
- **Menú Contextual**: Acciones rápidas para cada tarjeta mediante menú de tres puntos.

### Monitoreo OEE

Sistema completo para el cálculo y visualización de la Eficiencia General de los Equipos:

- **Cálculo en Tiempo Real**: Actualización continua de métricas de disponibilidad, rendimiento y calidad.
- **Configuración por Línea**: Parámetros OEE personalizables para cada línea de producción.
- **Integración con MQTT**: Recepción de datos directamente desde sensores y PLCs.
- **Visualización de Tendencias**: Gráficos históricos de evolución del OEE.
- **Alertas Configurables**: Notificaciones cuando los valores caen por debajo de umbrales definidos.

### Gestión de Sensores

Módulo completo para la configuración y monitoreo de sensores industriales:

- **Múltiples Tipos de Sensores**: Soporte para sensores de producción, calidad, tiempo, etc.
- **Transformación de Datos**: Sistema para transformar y normalizar lecturas de sensores.
- **Tópicos MQTT Configurables**: Asignación flexible de tópicos para cada sensor.
- **Histórico de Lecturas**: Almacenamiento y consulta de datos históricos.
- **Calibración de Sensores**: Herramientas para ajustar y calibrar sensores.

### Integración con APIs Externas

Sistema flexible para la integración con sistemas externos:

- **Mapeo de Campos Personalizable**: Configuración visual de mapeos entre sistemas.
- **Transformaciones de Datos**: Funciones para transformar datos durante la importación/exportación.
- **Validación de Datos**: Verificación de integridad y formato de los datos.
- **Procesamiento por Lotes**: Importación eficiente de grandes volúmenes de datos.
- **Registro Detallado**: Logs completos de todas las operaciones de integración.

### Sistemas de Control y Transformación de Datos

#### Transformación de Sensores

El componente `sensor-transformer.js` es un servicio Node.js crítico para el procesamiento y transformación de datos de sensores en tiempo real. Este servicio actúa como un middleware entre los sensores físicos y la aplicación, permitiendo la normalización y transformación de valores según reglas configurables.

**Características principales:**

- **Transformación configurable**: Transforma valores de sensores según rangos configurados (min, mid, max) y valores de salida personalizados.
- **Persistencia en base de datos**: Las configuraciones de transformación se almacenan en la tabla `sensor_transformations`.
- **Comunicación MQTT**: Se suscribe a tópicos de entrada y publica en tópicos de salida mediante el protocolo MQTT.
- **Caché de valores**: Implementa un sistema de caché para evitar publicaciones redundantes cuando los valores no cambian.
- **Reconexión automática**: Manejo robusto de reconexiones tanto para MySQL como para MQTT.
- **Actualizaciones en tiempo real**: Detecta cambios en las configuraciones de transformación sin necesidad de reiniciar el servicio.

**Flujo de trabajo:**

1. Se conecta a la base de datos MySQL para obtener las configuraciones de transformación activas.
2. Se suscribe a los tópicos MQTT especificados en las configuraciones.
3. Al recibir un mensaje en un tópico suscrito, aplica la transformación correspondiente según los rangos configurados.
4. Publica el valor transformado en el tópico de salida solo si el valor ha cambiado desde la última publicación.

**Configuración de transformaciones:**

Cada transformación en la tabla `sensor_transformations` incluye:

- `input_topic`: Tópico MQTT de entrada donde se reciben los valores del sensor.
- `output_topic`: Tópico MQTT de salida donde se publican los valores transformados.
- `min_value`, `mid_value`, `max_value`: Valores que definen los rangos para la transformación.
- `below_min_value_output`: Valor de salida cuando el valor de entrada es menor que `min_value`.
- `min_to_mid_value_output`: Valor de salida cuando el valor está entre `min_value` y `mid_value`.
- `mid_to_max_value_output`: Valor de salida cuando el valor está entre `mid_value` y `max_value`.
- `above_max_value_output`: Valor de salida cuando el valor es mayor que `max_value`.

Este componente es esencial para la interpretación de datos de sensores industriales, permitiendo convertir valores crudos (como voltajes o resistencias) en valores significativos para la aplicación (como estados "on"/"off" o niveles "bajo"/"medio"/"alto").

#### Sistema de Integración RFID

El componente `mqtt-rfid-to-api.js` es un gateway que conecta el sistema RFID físico con la aplicación Sensorica, actuando como puente entre los lectores RFID y el backend de la aplicación.

**Características principales:**

- **Arquitectura Gateway**: Funciona como un puente bidireccional entre el protocolo MQTT (usado por los lectores RFID) y la API REST de Sensorica.
- **WebSockets en tiempo real**: Proporciona una interfaz WebSocket para monitorizar lecturas RFID en tiempo real.
- **Gestión dinámica de antenas**: Carga y actualiza automáticamente la configuración de antenas RFID desde la base de datos.
- **Interfaz de monitoreo**: Incluye una interfaz web en `/gateway-test` para visualizar y filtrar lecturas RFID en tiempo real.
- **Seguridad configurable**: Soporte opcional para HTTPS/WSS mediante certificados SSL configurables.
- **Resiliencia**: Implementa reconexión automática tanto para MQTT como para la base de datos.
- **Almacenamiento temporal**: Mantiene un historial de las últimas 100 lecturas RFID para análisis inmediato.

**Flujo de trabajo:**

1. Se conecta a la base de datos MySQL para obtener la configuración de antenas RFID (`rfid_ants` tabla).
2. Se suscribe a los tópicos MQTT correspondientes a cada antena RFID configurada.
3. Al recibir una lectura RFID a través de MQTT:
   - La procesa y almacena temporalmente.
   - La transmite en tiempo real a todos los clientes WebSocket conectados.
   - La hace disponible a través de la API REST.
4. Periódicamente verifica y actualiza la configuración de antenas desde la base de datos.

**Integración con el sistema:**

- **API REST**: Expone un endpoint `/api/gateway-messages` que proporciona las últimas lecturas RFID y la información de las antenas.
- **WebSockets**: Permite a las interfaces de usuario recibir actualizaciones en tiempo real de las lecturas RFID.
- **Monitoreo**: La interfaz web en `/gateway-test` permite visualizar y filtrar lecturas RFID por antena.
- **Base de datos**: Se integra con la tabla `rfid_ants` que almacena la configuración de las antenas RFID.

Este componente es fundamental para la funcionalidad de seguimiento RFID en tiempo real, permitiendo el monitoreo de productos y operarios equipados con tags RFID a lo largo del proceso de producción.

#### Sistema de Control SCADA/Modbus

El componente `client-modbus.js` es un servicio Node.js especializado que gestiona la comunicación con sistemas industriales SCADA (Supervisory Control And Data Acquisition) mediante el protocolo Modbus, enfocado principalmente en el control de pesaje y dosificación industrial.

**Características principales:**

- **Integración MQTT-SCADA**: Actuúa como puente entre el protocolo MQTT y los sistemas SCADA/Modbus industriales.
- **Filtrado inteligente**: Implementa algoritmos avanzados para filtrar lecturas repetitivas o con variaciones mínimas.
- **Caché de configuración**: Mantiene en memoria la configuración de cada dispositivo Modbus para optimizar el rendimiento.
- **Modos especializados**: Soporta diferentes modos de operación según el tipo de dispositivo (`weight`, `height` u otros).
- **Control de repeticiones**: Sistema configurable para limitar el envío de datos repetidos según un umbral definido por dispositivo.
- **Control de variaciones mínimas**: Para dispositivos de pesaje, filtra cambios menores según un factor de variación configurable.
- **Sincronización dinámica**: Actualiza automáticamente la configuración de dispositivos desde la base de datos.
- **Resiliencia**: Implementa mecanismos robustos de reconexión tanto para MQTT como para la base de datos.

**Flujo de trabajo:**

1. Se conecta a la base de datos MySQL para obtener la configuración de dispositivos Modbus (`modbuses` tabla).
2. Se suscribe a los tópicos MQTT correspondientes a cada dispositivo Modbus configurado.
3. Al recibir datos de un dispositivo a través de MQTT:
   - Aplica lógica de filtrado según el tipo de dispositivo (peso, altura, etc.).
   - Controla repeticiones mediante contadores específicos para cada tópico.
   - Para dispositivos de pesaje, aplica lógica de variación mínima con factor de conversión.
   - Para dispositivos de altura, compara con dimensiones predeterminadas.
   - Si el valor supera los filtros, lo envía a la API REST de Sensorica.
4. Periódicamente resetea los contadores de repetición y sincroniza la configuración desde la base de datos.

**Integración con el sistema:**

- **Pesaje industrial**: Procesa datos de básculas y sistemas de pesaje con filtrado de variaciones mínimas.
- **Control de altura**: Monitoriza alturas en procesos industriales con comparación contra valores predeterminados.
- **Dosificación**: Facilita el control preciso de sistemas de dosificación mediante la gestión de valores repetidos.
- **Base de datos**: Se integra con la tabla `modbuses` que almacena la configuración de cada dispositivo.
- **API REST**: Envía los datos filtrados a endpoints específicos de la API de Sensorica.

Este componente es crucial para la integración con maquinaria industrial, permitiendo un control preciso de sistemas de pesaje, dosificación y medición en entornos de producción.

#### Comandos Gestionados por Supervisor

Sensorica utiliza Supervisor para gestionar y mantener en ejecución una serie de procesos críticos para el funcionamiento del sistema. Estos procesos incluyen comandos Artisan de Laravel y servidores Node.js que realizan tareas específicas de monitoreo, comunicación y procesamiento de datos.

**Principales comandos y sus funciones:**

1. **Cálculo de OEE (`calculate-monitor-oee`):**
   - **Archivo:** `CalculateProductionMonitorOeev2.php`
   - **Descripción:** Calcula y gestiona las métricas OEE (Overall Equipment Effectiveness) en tiempo real.
   - **Funcionalidad:**
     - Monitorea el estado de las líneas de producción activas
     - Calcula tiempos de actividad, parada y rendimiento
     - Procesa datos de sensores y dispositivos Modbus
     - Actualiza contadores de producción por turno y semanales
     - Calcula métricas de disponibilidad, rendimiento y calidad
     - Genera estadísticas de OEE en tiempo real

2. **Suscriptor MQTT Local (`subscribe-local`):**
   - **Archivo:** `MqttSubscriberLocal.php`
   - **Descripción:** Gestiona la comunicación MQTT para eventos locales del sistema.
   - **Funcionalidad:**
     - Se suscribe a tópicos MQTT locales como `production/+/+/status`
     - Procesa mensajes relacionados con cambios de estado en líneas de producción
     - Actualiza el estado de órdenes de producción en tiempo real
     - Registra eventos de inicio/fin de turnos y paradas
     - Sincroniza el estado del sistema con la base de datos

3. **Verificación de Órdenes desde API (`orders-check`):**
   - **Archivo:** `CheckOrdersFromApi.php`
   - **Descripción:** Sincroniza órdenes de producción desde sistemas externos vía API.
   - **Funcionalidad:**
     - Consulta APIs externas para obtener nuevas órdenes
     - Transforma datos de órdenes según mapeo de campos configurado
     - Crea o actualiza órdenes en el sistema Sensorica
     - Gestiona la sincronización de artículos y procesos asociados
     - Mantiene un registro de auditoría de sincronización

4. **Lectura de Sensores (`read-sensors`):**
   - **Archivo:** `ReadSensors.php`
   - **Descripción:** Gestiona la lectura y procesamiento de datos de sensores industriales.
   - **Funcionalidad:**
     - Lee datos de sensores conectados al sistema
     - Procesa y filtra lecturas según configuración
     - Actualiza contadores de producción y tiempos de actividad
     - Detecta paradas y eventos especiales
     - Almacena datos históricos para análisis

5. **Lectura RFID (`read-rfid`):**
   - **Archivo:** `ReadRfidReadings.php`
   - **Descripción:** Procesa lecturas de tags RFID y las asocia con operarios y productos.
   - **Funcionalidad:**
     - Lee datos de antenas RFID configuradas en el sistema
     - Asocia lecturas con operarios y productos mediante EPC/TID
     - Registra eventos de entrada/salida de zonas de trabajo
     - Actualiza estado de asignaciones de puestos
     - Mantiene un historial de lecturas para trazabilidad

6. **Integración Modbus (`modbus-subscriber`):**
   - **Archivo:** `ReadModbus.php`
   - **Descripción:** Gestiona la comunicación con dispositivos industriales mediante protocolo Modbus.
   - **Funcionalidad:**
     - Lee registros de dispositivos Modbus configurados
     - Procesa datos de pesaje, altura y otros parámetros industriales
     - Aplica filtros y transformaciones a las lecturas
     - Envía datos procesados al sistema central
     - Gestiona la reconexión automática en caso de fallos

7. **Servidor WhatsApp (`connect-whatsapp`):**
   - **Archivo:** `ConnectWhatsApp.php` (gestor Laravel) y `connect-whatsapp.js` (servidor Node.js)
   - **Descripción:** Gestiona la comunicación bidireccional con WhatsApp para notificaciones y comandos.
   - **Funcionalidad:**
     - Mantiene conexión con la API de WhatsApp
     - Envía notificaciones automáticas sobre eventos del sistema
     - Procesa comandos recibidos vía WhatsApp
     - Gestiona la autenticación y sesión de WhatsApp
     - Permite la interacción remota con el sistema

8. **Transformación de Sensores (`sensor-transformers`):**
   - **Archivo:** `sensor-transformer.js` (servidor Node.js)
   - **Descripción:** Procesa y transforma datos de sensores para su uso en el sistema.
   - **Funcionalidad:**
     - Aplica algoritmos de transformación a lecturas de sensores
     - Convierte unidades y formatos según configuración
     - Filtra lecturas erróneas o fuera de rango
     - Optimiza el flujo de datos para reducción de tráfico
     - Gestiona la calibración virtual de sensores

Todos estos comandos son gestionados por Supervisor, que garantiza su ejecución continua, reinicio automático en caso de fallo, y registro adecuado de su actividad en archivos de log dedicados. La configuración de cada comando se encuentra en archivos `.conf` individuales en el directorio raíz del proyecto.

#### Servidores Node.js

Sensorica implementa varios servidores Node.js especializados que complementan la funcionalidad del backend Laravel, proporcionando capacidades de comunicación en tiempo real, integración con dispositivos industriales y procesamiento de datos.

**1. Servidores MQTT (`sender-mqtt-server1.js` y `sender-mqtt-server2.js`):**

- **Descripción:** Gestionan la comunicación MQTT entre diferentes componentes del sistema, actuando como puentes entre el almacenamiento local y los brokers MQTT.
- **Características principales:**
  - **Arquitectura de publicación por lotes:** Procesan archivos JSON almacenados localmente y los publican en brokers MQTT.
  - **Tolerancia a fallos:** Implementan mecanismos de reconexión automática y manejo de errores.
  - **Configuración dinámica:** Monitorean y recargan automáticamente cambios en la configuración (.env).
  - **Procesamiento secuencial:** Garantizan la entrega ordenada de mensajes mediante publicación secuencial.
  - **Limpieza automática:** Eliminan archivos procesados correctamente para evitar duplicados.
  - **Registro detallado:** Mantienen logs detallados de todas las operaciones para diagnóstico.

**Flujo de trabajo:**

1. Monitorizan directorios específicos (`../storage/app/mqtt/server1` y `../storage/app/mqtt/server2`).
2. Procesan archivos JSON encontrados en estos directorios y sus subdirectorios.
3. Extraen el tópico MQTT y el contenido del mensaje de cada archivo.
4. Publican los mensajes en los brokers MQTT configurados.
5. Eliminan los archivos procesados correctamente.
6. Registran todas las operaciones y errores en logs detallados.

**Diferencias entre servidores:**

- `sender-mqtt-server1.js`: Se conecta al broker MQTT principal (MQTT_SENSORICA_SERVER).
- `sender-mqtt-server2.js`: Se conecta al broker MQTT secundario (MQTT_SERVER), utilizado para comunicación con sistemas externos.

**2. Transformador de Sensores (`sensor-transformer.js`):**

- **Descripción:** Procesa y transforma datos de sensores industriales para su uso en el sistema.
- **Características principales:**
  - **Transformación configurable:** Aplica algoritmos de transformación específicos para cada tipo de sensor.
  - **Filtrado inteligente:** Elimina lecturas erróneas, duplicadas o fuera de rango.
  - **Conversión de unidades:** Normaliza las lecturas a unidades estándar del sistema.
  - **Calibración virtual:** Permite ajustar las lecturas mediante factores de calibración.
  - **Integración MQTT:** Recibe datos de sensores vía MQTT y publica los datos transformados.

**3. Cliente MQTT para Sensores (`client-mqtt-sensors.js`):**

- **Descripción:** Gestiona la comunicación con sensores industriales mediante protocolo MQTT.
- **Características principales:**
  - **Descubrimiento automático:** Detecta y configura nuevos sensores conectados a la red.
  - **Monitoreo en tiempo real:** Supervisa el estado y las lecturas de los sensores.
  - **Gestión de alarmas:** Detecta y notifica condiciones anormales en los sensores.
  - **Almacenamiento local:** Guarda temporalmente lecturas cuando la conexión está interrumpida.
  - **Sincronización:** Actualiza la configuración de sensores desde la base de datos.

**4. Cliente MQTT para RFID (`client-mqtt-rfid.js`):**

- **Descripción:** Gestiona la comunicación con lectores RFID mediante protocolo MQTT.
- **Características principales:**
  - **Procesamiento de tags:** Decodifica y procesa datos de tags RFID (EPC, TID, etc.).
  - **Filtrado de lecturas:** Elimina lecturas duplicadas o no válidas.
  - **Asociación de tags:** Vincula tags RFID con operarios, productos o ubicaciones.
  - **Detección de eventos:** Identifica eventos de entrada/salida de zonas de trabajo.
  - **Integración con API:** Envía datos procesados a la API REST de Sensorica.

**5. Configuración RFID (`config-rfid.js`):**

- **Descripción:** Proporciona configuración centralizada para el sistema RFID.
- **Características principales:**
  - **Definición de antenas:** Configura parámetros de antenas RFID (ubicación, potencia, etc.).
  - **Mapeo de zonas:** Define zonas de trabajo y su asociación con antenas RFID.
  - **Filtros de tags:** Configura filtros para tipos específicos de tags RFID.
  - **Parámetros de lectura:** Define intervalos de lectura, potencia y otros parámetros.
  - **Integración con base de datos:** Sincroniza configuración con la tabla `rfid_ants`.

Estos servidores Node.js son componentes críticos de la arquitectura de Sensorica, proporcionando capacidades de comunicación en tiempo real, procesamiento de datos y integración con dispositivos industriales que complementan el backend Laravel principal.

#### Vistas Blade Principales

Las vistas Blade son componentes fundamentales de la interfaz de usuario de Sensorica, proporcionando interfaces interactivas para la gestión de producción, monitoreo OEE y organización de órdenes. A continuación se detallan las vistas más importantes del sistema.

**1. Organizador de Órdenes (`order-organizer.blade.php`):**

- **Descripción:** Proporciona una vista general de los procesos de producción disponibles para un cliente específico.
- **Características principales:**
  - **Agrupación por procesos:** Muestra los procesos disponibles agrupados por categoría.
  - **Navegación intuitiva:** Permite acceder rápidamente al tablero Kanban de cada proceso.
  - **Visualización de líneas:** Muestra el número de líneas de producción asociadas a cada proceso.
  - **Diseño responsive:** Adapta la visualización a diferentes tamaños de pantalla mediante Bootstrap.
  - **Integración con rutas:** Utiliza rutas nombradas de Laravel para la navegación entre vistas.

**Estructura de la vista:**

- **Cabecera:** Incluye título, migas de pan y navegación contextual.
- **Tarjetas de procesos:** Cada proceso se muestra como una tarjeta con su descripción y número de líneas.
- **Botón de acceso:** Enlace directo al tablero Kanban específico de cada proceso.

**2. Tablero Kanban (`order-kanban.blade.php`):**

- **Descripción:** Implementa un sistema Kanban completo para la gestión visual de órdenes de producción.
- **Características principales:**
  - **Drag & Drop:** Permite mover órdenes entre columnas mediante interacción drag & drop.
  - **Columnas dinámicas:** Genera columnas basadas en líneas de producción y estados finales.
  - **Filtrado avanzado:** Incluye búsqueda en tiempo real por ID de orden, cliente y otros campos.
  - **Indicadores visuales:** Muestra estados de líneas de producción, prioridad de órdenes y alertas.
  - **Menús contextuales:** Proporciona acciones rápidas para cada orden y columna.
  - **Actualización en tiempo real:** Sincroniza el estado del tablero periódicamente con el servidor.
  - **Modo pantalla completa:** Permite visualizar el tablero en modo pantalla completa.

**Estructura de la vista:**

- **Barra de filtros:** Controles para búsqueda, pantalla completa y navegación.
- **Tablero Kanban:** Contenedor principal con columnas para cada línea de producción y estados finales.
- **Tarjetas de órdenes:** Representación visual de cada orden con información relevante.
- **Leyenda visual:** Explicación de los iconos y colores utilizados en las tarjetas.
- **Modales:** Interfaces para editar notas, gestionar incidencias y configurar disponibilidad.

**Interacción JavaScript:**

- **Gestión de eventos:** Manejo de eventos de arrastrar y soltar para las tarjetas.
- **Validación de movimientos:** Lógica para permitir o restringir movimientos según el estado de las órdenes.
- **Actualización asíncrona:** Comunicación con el servidor mediante AJAX para guardar cambios.
- **Filtrado en tiempo real:** Búsqueda dinámica sin necesidad de recargar la página.
- **Gestión de estados:** Manejo del estado de las líneas de producción (activa, pausada, detenida).

**3. Vistas de Monitoreo OEE (`oee/index.blade.php`, `oee/create.blade.php`, `oee/edit.blade.php`):**

- **Descripción:** Conjunto de vistas para configurar, visualizar y analizar métricas OEE (Overall Equipment Effectiveness).
- **Características principales:**
  - **Gestión de monitores:** Interfaz CRUD completa para configurar monitores OEE por línea de producción.
  - **Integración MQTT:** Configuración de tópicos MQTT para la recolección de datos en tiempo real.
  - **Integración Modbus:** Activación/desactivación de conexiones Modbus para sensores industriales.
  - **Configuración de turnos:** Definición de horarios de inicio de turnos para cálculos precisos.
  - **Visualización tabular:** Presentación de monitores configurados mediante DataTables.
  - **Navegación contextual:** Migas de pan (breadcrumbs) para facilitar la navegación entre secciones relacionadas.

**Estructura de las vistas:**

- **Vista de índice (`index.blade.php`):**
  - Tabla responsive con DataTables para listar todos los monitores OEE.
  - Columnas para ID, línea de producción, tópicos MQTT, estado de sensores y Modbus.
  - Acciones para editar y eliminar monitores.
  - Integración con rutas nombradas de Laravel para la navegación.

- **Vista de creación (`create.blade.php`):**
  - Formulario para configurar nuevos monitores OEE.
  - Generación automática de tópicos MQTT basados en el nombre de la línea de producción.
  - Opciones para activar/desactivar sensores y conexiones Modbus.
  - Selector de fecha/hora para configurar inicio de turnos.

- **Vista de edición (`edit.blade.php`):**
  - Formulario prellenado con la configuración actual del monitor.
  - Opciones para modificar tópicos MQTT, estado de sensores y configuración de turnos.
  - Validación de formularios para garantizar datos correctos.

**4. Vistas de Gestión de Incidencias:**

- **Descripción:** Interfaces para registrar, visualizar y gestionar incidencias en la producción.
- **Características principales:**
  - **Listado filtrable:** Tabla de incidencias con filtros por fecha, tipo y estado.
  - **Detalles completos:** Vista detallada de cada incidencia con información contextual.
  - **Registro de notas:** Capacidad para añadir notas y seguimiento a cada incidencia.
  - **Integración con Kanban:** Vinculación directa con el tablero Kanban para visualizar órdenes afectadas.
  - **Gestión de estados:** Flujo de trabajo para la resolución de incidencias.

Estas vistas Blade constituyen la interfaz principal de Sensorica, proporcionando una experiencia de usuario intuitiva y funcional para la gestión de producción industrial. La combinación de Laravel Blade con JavaScript moderno permite crear interfaces dinámicas y reactivas que facilitan la visualización y manipulación de datos complejos en tiempo real.

### Gestión de Incidencias

Sistema para el registro y seguimiento de problemas en la producción:

- **Registro**: Alta de incidencias vinculadas a órdenes de producción (vía UI/API). El Kanban incluye una columna "Incidencias" que centraliza las órdenes en estado de incidencia.
- **Categorización**: Clasificación por motivo (reason) y estado de la orden afectada.
- **Asignación**: Posibilidad de asociar creador/responsable (campo `created_by`).
- **Seguimiento**: Fechas de creación/actualización, estado activo/finalizado y notas.
- **Análisis**: Listados filtrables y relación con el Kanban para detectar cuellos de botella.

#### Vistas Blade de Incidencias

- **Listado (`resources/views/customers/production-order-incidents/index.blade.php`)**
  - Ruta: `customers.production-order-incidents.index`.
  - Tabla con columnas: `#`, `ORDER ID`, `REASON`, `STATUS`, `CREATED BY`, `CREATED AT`, `ACTIONS`.
  - Estado visual:
    - `Incidencia activa` si `productionOrder.status == 3` (badge rojo).
    - `Incidencia finalizada` en caso contrario (badge gris).
  - Acciones: Ver detalle y eliminar (eliminación protegida por permisos `@can('delete', $customer)`).
  - Acceso rápido: Botón a `Order Organizer` (`customers.order-organizer`).

- **Detalle (`resources/views/customers/production-order-incidents/show.blade.php`)**
  - Ruta: `customers.production-order-incidents.show`.
  - Muestra: ID de orden, motivo, creador, `created_at`, `updated_at`, estado de la orden y estado de incidencia.
  - Acciones: Volver al listado y eliminar (con confirmación y control de permisos).
  - Sección de notas: listado/gestión de notas asociadas a la incidencia.

- **Integración con Kanban**
  - En `customers/order-kanban.blade.php` se define la columna `paused` con etiqueta `Incidencias`, integrando visualmente las órdenes afectadas en el flujo operativo.

### Otras Vistas Blade Relevantes

- **`resources/views/productionlines/liststats.blade.php`**
  - Panel de estadísticas por línea de producción con estados y KPI operativos.
  - Usa badges para estados: `Incidencia` (rojo), entre otros.
  - Integra tablas y componentes JS para filtrado y visualización.

- **`resources/views/productionlines/status-legend.blade.php`**
  - Leyenda compacta de estados utilizados en los paneles (incluye `Incidencia`).

- **`resources/views/dashboard/homepage.blade.php`**
  - Dashboard general con tarjetas/resúmenes. Incluye bloques para "estado de líneas con incidencias".

Estas vistas complementan el Kanban y OEE, ofreciendo un panorama operativo con foco en estados y alertas.

### Vistas Blade de Clientes, Líneas y Sensores

- **Clientes (`resources/views/customers/*.blade.php`)**
  - `index/create/edit`: Gestión CRUD de clientes, navegación hacia organizador/kanban por cliente.

- **Líneas de Producción (`resources/views/modbuses/*.blade.php`, `resources/views/oee/*.blade.php`)**
  - `modbuses/index/create/edit`: Configuración de endpoints Modbus por línea.
  - `oee/index/create/edit`: Alta y administración de monitores OEE por línea.

- **Sensores**
  - Listado/detalle accesible desde breadcrumbs de OEE: `route('sensors.index', ['id' => $production_line_id])`.

Estas pantallas soportan el flujo de alta y configuración técnica de cada centro/línea y su instrumentación (sensores, Modbus, OEE).

### Usuarios, Roles y Permisos

Sensorica usa Spatie Laravel Permission para control de acceso basado en roles/permisos.

- **Modelo de Usuario**: `app/Models/User.php` usa `Spatie\Permission\Traits\HasRoles`.
- **Configuración**: `config/permission.php` define los modelos `Role` y `Permission`.
- **Seeders de permisos**:
  - `database/seeders/DatabaseSeeder.php` (registro genérico de permisos).
  - `database/seeders/OriginalOrderPermissionsTableSeeder.php` (permisos de órdenes originales).
  - `database/seeders/ProductionLineProcessesPermissionSeeder.php` (permisos de procesos por línea).
  - `database/seeders/ProductionLineOrdersKanbanPermissionSeeder.php` (permisos de tablero Kanban).
  - `database/seeders/WorkCalendarPermissionSeeder.php` (permisos de calendario laboral).

- **Controladores con middleware `permission:`**:
  - `CustomerOriginalOrderController`: `original-order-list|original-order-create|original-order-edit|original-order-delete`.
  - `ProcessController`: `process-show|process-create|process-edit|process-delete`.
  - `ProductionLineProcessController`: `productionline-process-view|create|edit|delete`.
  - `ProductionOrderIncidentController`: `productionline-orders` (index/show), `productionline-delete` (destroy).
  - `WorkCalendarController`: `workcalendar-list|create|edit|delete`.
  - Gestión de roles/permisos: `RoleController` (`manage-role|create-role|edit-role|delete-role`), `PermissionController`, `PermissionManageController`.

- **Patrón de uso**:
  - Middleware: `->middleware('permission:perm-a|perm-b', ['only' => ['index','show']])`.
  - Asignación típica: usuarios reciben roles; roles agrupan permisos definidos por los seeders.

Este esquema garantiza control de acceso granular en vistas y endpoints, alineado con los módulos de producción, procesos, Kanban e incidencias.

## 🔧 Tecnologías Utilizadas

- **Backend**: Laravel (PHP), MySQL/Percona
- **Frontend**: Blade, JavaScript, Bootstrap, SweetAlert2
- **Comunicación en Tiempo Real**: MQTT, WebSockets
- **Servicios en Segundo Plano**: Supervisor, Laravel Commands
- **Integración IoT**: Protocolos MQTT, Modbus
- **Contenedores**: Docker (opcional)
- **Monitoreo**: Sistema propio de logs y alertas

## 💻 Requisitos del Sistema

- PHP 7.4 o superior
- MySQL 5.7 o Percona equivalente
- Servidor web compatible con PHP (Apache, Nginx)
- Supervisor para procesos en segundo plano
- Broker MQTT (como Mosquitto)
- Conexión a Internet para integraciones externas

## 🚀 Instalación y Configuración

1. **Clonar el repositorio**:
   ```bash
   git clone [url-del-repositorio]
   ```

2. **Instalar dependencias**:
   ```bash
   composer install
   npm install
   ```

3. **Configurar variables de entorno**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configurar base de datos en .env**:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=sensorica
   DB_USERNAME=usuario
   DB_PASSWORD=contraseña
   ```

5. **Configurar zona horaria**:
   ```
   APP_TIMEZONE=Europe/Madrid
   ```

6. **Ejecutar migraciones y seeders**:
   ```bash
   php artisan migrate --seed
   ```

7. **Compilar assets**:
   ```bash
   npm run dev
   ```

8. **Configurar Supervisor**:
   ```bash
   cp laravel-*.conf /etc/supervisor/conf.d/
   supervisorctl reread
   supervisorctl update
   ```

## Estructura de la Base de Datos

Sensorica utiliza una arquitectura de base de datos relacional con MySQL/MariaDB, implementando el ORM Eloquent de Laravel para gestionar las relaciones entre entidades. A continuación se describen los modelos principales y sus relaciones:

### Modelos Principales y Relaciones

#### Sistema de Producción

- **ProductionOrder**: Representa una orden de producción en el tablero Kanban.
  - Pertenece a (`belongsTo`) una `ProductionLine`
  - Pertenece a (`belongsTo`) un `OriginalOrder`
  - Pertenece a (`belongsTo`) un `OriginalOrderProcess`
  - Tiene muchos (`hasMany`) `BarcodeScan`
  - Tiene muchos (`hasMany`) `ProductionOrderIncident`

- **OriginalOrder**: Representa una orden original recibida desde un sistema ERP externo.
  - Pertenece a (`belongsTo`) un `Customer`
  - Tiene muchos (`hasMany`) `OriginalOrderProcess`
  - Tiene muchos (`hasMany`) `ProductionOrder`
  - Tiene muchos a través de (`hasManyThrough`) `OriginalOrderArticle`

- **OriginalOrderProcess**: Tabla pivote que relaciona órdenes originales con procesos.
  - Extiende la clase `Pivot` de Laravel
  - Pertenece a (`belongsTo`) un `OriginalOrder`
  - Pertenece a (`belongsTo`) un `Process`
  - Tiene muchos (`hasMany`) `OriginalOrderArticle`
  - Tiene muchos (`hasMany`) `ProductionOrder`

- **Process**: Define un proceso de producción estándar.
  - Pertenece a muchos (`belongsToMany`) `ProductionLine` a través de `production_line_process`
  - Métodos para navegación secuencial: `nextProcess()` y `previousProcess()`

- **ProductionLine**: Representa una línea de producción física.
  - Pertenece a (`belongsTo`) un `Customer`
  - Pertenece a muchos (`belongsToMany`) `Process` a través de `production_line_process`
  - Tiene muchos (`hasMany`) `ProductionOrder`
  - Tiene muchos (`hasMany`) `Sensor`
  - Tiene uno (`hasOne`) `Barcode`

#### Monitoreo OEE

- **MonitorOee**: Almacena métricas OEE calculadas para líneas de producción.
  - Pertenece a (`belongsTo`) una `ProductionLine`
  - Incluye métricas de disponibilidad, rendimiento, calidad y OEE global

- **OrderStat**: Registra estadísticas de producción por orden.
  - Pertenece a (`belongsTo`) una `ProductionOrder`
  - Pertenece a (`belongsTo`) un `Operator`
  - Pertenece a (`belongsTo`) un `ShiftList`
  - Almacena unidades producidas, peso, tiempo, etc.

#### Sensores y Dispositivos

- **Sensor**: Representa un sensor físico en una línea de producción.
  - Pertenece a (`belongsTo`) una `ProductionLine`
  - Tiene muchos (`hasMany`) `SensorReading`
  - Tiene muchos (`hasMany`) `SensorTransformer`

- **RfidReading**: Registra lecturas de dispositivos RFID.
  - Pertenece a (`belongsTo`) un `RfidAnt` (antena)
  - Almacena EPC, TID, RSSI y timestamp

- **Modbus**: Configura conexiones Modbus/SCADA.
  - Tiene muchos (`hasMany`) `ModbusHistory`
  - Define registros, direcciones, tipos de datos, etc.

#### Usuarios y Permisos

- **User**: Usuarios del sistema (extiende el modelo base de Laravel).
  - Pertenece a muchos (`belongsToMany`) `Role`
  - Pertenece a muchos (`belongsToMany`) `Permission`
  - Pertenece a muchos (`belongsToMany`) `Customer`

- **Role**: Roles de usuario (ej: Administrador, Supervisor, Operario).
  - Pertenece a muchos (`belongsToMany`) `Permission`
  - Pertenece a muchos (`belongsToMany`) `User`

- **Permission**: Permisos individuales del sistema.
  - Pertenece a muchos (`belongsToMany`) `Role`
  - Pertenece a muchos (`belongsToMany`) `User`

### Diagrama Simplificado de Relaciones

```
Customer 1 → * ProductionLine 1 → * ProductionOrder
    |
    ↓
    1
OriginalOrder 1 → * OriginalOrderProcess * ← 1 Process
    |                    |
    |                    ↓
    |                    *
    ↓                OriginalOrderArticle
    *
ProductionOrder * ← 1 ProductionLine 1 → * Sensor
    |
    ↓
    *
ProductionOrderIncident
```

### Campos Clave

Los siguientes campos son fundamentales para entender el flujo de datos:

- **ProductionOrder.status**: Define el estado de una orden en el tablero Kanban:
  - 0: Pendiente
  - 1: En proceso
  - 2: Finalizada
  - 3: Incidencia

- **ProductionOrder.orden**: Número secuencial que determina el orden de procesamiento dentro de una línea.

- **OriginalOrderProcess.in_stock**: Indica si hay stock disponible para este proceso (0: sin stock, 1: con stock).

- **Process.sequence**: Define el orden secuencial de los procesos en el flujo de producción.

- **ProductionLine.token**: Identificador único usado en endpoints API para identificar líneas de producción.

El sistema utiliza una base de datos relacional con las siguientes entidades principales:

- **Customers**: Clientes del sistema
- **ProductionLines**: Líneas de producción asociadas a clientes
- **ProductionOrders**: Órdenes en el sistema Kanban
- **OriginalOrders**: Órdenes importadas de sistemas externos
- **OriginalOrderProcesses**: Procesos asociados a órdenes originales
- **OriginalOrderArticles**: Artículos asociados a procesos
- **Sensors**: Configuración de sensores
- **SensorHistory**: Lecturas históricas de sensores
- **MonitorOee**: Configuración de monitoreo OEE
- **ProductionOrderIncidents**: Registro de incidencias
- **WorkCalendar**: Calendario laboral para cálculos de producción

### 🔬 Detalle de Modelos y Eventos (Eloquent)

Esta sección documenta los modelos principales, sus campos críticos, relaciones y eventos de ciclo de vida según la implementación actual en `app/Models/`.

#### ProductionOrder (`app/Models/ProductionOrder.php`)

- __Tabla__: `production_orders`
- __Fillable__: `has_stock`, `production_line_id`, `original_production_line_id`, `barcoder_id`, `order_id`, `json`, `status`, `box`, `units_box`, `number_of_pallets`, `units`, `orden`, `theoretical_time`, `accumulated_time`, `process_category`, `delivery_date`, `customerId`, `original_order_id`, `original_order_process_id`, `grupo_numero`, `processes_to_do`, `processes_done`, `is_priority`, `finished_at`, `fecha_pedido_erp`, `estimated_start_datetime`, `estimated_end_datetime`, `note`
- __Casts__: `json: array`, `processed: boolean`, `orden: integer`, `delivery_date: datetime`, `status: integer`, `theoretical_time: float`, `is_priority: boolean`, `finished_at: datetime`, `fecha_pedido_erp: datetime`, `estimated_start_datetime: datetime`, `estimated_end_datetime: datetime`
- __Relaciones__:
  - `originalOrder()` → `belongsTo(OriginalOrder, original_order_id)`
  - `originalOrderProcess()` → `belongsTo(OriginalOrderProcess, original_order_process_id)`
  - `productionLine()` → `belongsTo(ProductionLine)`
  - `originalProductionLine()` → `belongsTo(ProductionLine, original_production_line_id)`
  - `barcode()` → `belongsTo(Barcode)`
  - `barcodeScans()` → `hasMany(BarcodeScan)`
- __Eventos__:
  - `creating`:
    - Calcula `orden` incremental por `production_line_id`.
    - Establece `status = 0` si viene nulo.
    - Si existe una orden con mismo `order_id` y misma `production_line_id`, la archiva modificando su `order_id` a `order_id-<process_category>-<grupo_numero>` y guarda.
  - `saving`:
    - Si `status` cambia a 2 y `finished_at` está vacío, asigna `finished_at = now()`.
    - Si cambia `production_line_id`, busca `Barcode` de esa línea y asigna `barcoder_id` (loggea cuando no encuentra).
  - `saved`:
    - Si `status` cambió y es 2, marca el `OriginalOrderProcess` relacionado como finalizado (`finished = 1`, `finished_at = now()`).

Estados Kanban utilizados: `status = 0 (Pendiente)`, `1 (En proceso)`, `2 (Finalizada)`, `3 (Incidencia)`.

#### OriginalOrder (`app/Models/OriginalOrder.php`)

- __Fillable__: `order_id`, `customer_id`, `client_number`, `order_details`, `processed`, `finished_at`, `delivery_date`, `in_stock`, `fecha_pedido_erp`
- __Casts__: `order_details: json`, `processed: boolean`, `finished_at: datetime`
- __Relaciones__:
  - `processes()` → `belongsToMany(Process, 'original_order_processes')` usando pivot `OriginalOrderProcess` con `pivot: id, time, created, finished, finished_at, grupo_numero`
  - `customer()` → `belongsTo(Customer)`
  - `articles()` → `hasManyThrough(OriginalOrderArticle, OriginalOrderProcess, ...)`
  - `orderProcesses()` / `originalOrderProcesses()` → `hasMany(OriginalOrderProcess)`
  - `productionOrders()` → `hasMany(ProductionOrder)`
- __Lógica clave__:
  - `allProcessesFinished()` comprueba si todos los pivots están `finished = true`.
  - `updateInStockStatus()` establece `in_stock` a 0 si algún proceso tiene `in_stock = 0`, o 1 si todos son 1.
  - `updateFinishedStatus()` fija/borra `finished_at` según resultado de `allProcessesFinished()`, usando `saveQuietly()` para evitar eventos recursivos.

#### OriginalOrderProcess (`app/Models/OriginalOrderProcess.php`)

- __Extiende__: `Pivot` (tabla `original_order_processes`)
- __Fillable__: `original_order_id`, `process_id`, `time`, `box`, `units_box`, `number_of_pallets`, `created`, `finished`, `finished_at`, `grupo_numero`, `in_stock`
- __Casts__: `time: decimal:2`, `box: integer`, `units_box: integer`, `number_of_pallets: integer`, `created: boolean`, `finished: boolean`, `finished_at: datetime`, `in_stock: integer`
- __Relaciones__:
  - `articles()` → `hasMany(OriginalOrderArticle, 'original_order_process_id')`
  - `originalOrder()` → `belongsTo(OriginalOrder, 'original_order_id')`
  - `process()` → `belongsTo(Process)`
  - `productionOrders()` → `hasMany(ProductionOrder, 'original_order_process_id')`
- __Eventos__:
  - `saving`: si `finished` cambia, sincroniza `finished_at`. Si `in_stock` cambia en creación, precarga `articles`.
  - `saved`: actualiza primero su propio `in_stock` en base a artículos (`updateStockStatus()`), luego:
    - `originalOrder?->updateFinishedStatus()`
    - `originalOrder?->updateInStockStatus()`

#### Process (`app/Models/Process.php`)

- __Fillable__: `code`, `name`, `sequence`, `description`, `factor_correccion` (cast `decimal:2`, default 1.00)
- __Relaciones__:
  - `productionLines()` → `belongsToMany(ProductionLine)` con `order` en pivot
  - `nextProcess()` / `previousProcess()` por `sequence`

#### ProductionLine (`app/Models/ProductionLine.php`)

- __Fillable__: `customer_id`, `name`, `token`
- __Relaciones__:
  - `processes()` → `belongsToMany(Process)` con `order` en pivot
  - `customer()` → `belongsTo(Customer)`
  - `barcodes()` → `hasMany(Barcode)`
  - `sensors()` → `hasMany(Sensor, 'production_line_id')`
  - `orderStats()` → `hasMany(OrderStat, 'production_line_id')`
  - `lastShiftHistory()` → `hasOne(ShiftHistory)->latest()`
  - `barcodeScans()` → `hasMany(BarcodeScan)`

#### Operator (`app/Models/Operator.php`)

- __Fillable__: `client_id`, `name`, `password`, `email`, `phone`, `count_shift`, `count_order`
- __Hidden__: `password`
- __Relaciones__:
  - `client()` → `belongsTo(Client)`
  - `operatorPosts()` → `hasMany(OperatorPost, 'operator_id')`
  - `shiftHistories()` → `hasMany(ShiftHistory, 'operator_id')`
  - `barcodeScans()` → `hasMany(BarcodeScan)`
  - `orderStats()` → `belongsToMany(OrderStat, 'order_stats_operators')` con pivote `shift_history_id`, `time_spent`, `notes`

#### OrderStat (`app/Models/OrderStat.php`)

- __Tabla__: `order_stats`
- __Fillable__: métricas de producción y peso por orden/turno/línea (p. ej. `production_line_id`, `order_id`, `units`, `oee`, `weights_*`, etc.)
- __Relaciones__:
  - `productionLine()` → `belongsTo(ProductionLine)`
  - `productList()` → `belongsTo(ProductList)`
  - `operators()` / `shiftHistories()` → `belongsToMany` vía `order_stats_operators`
  - `orderStatOperators()` → `hasMany(OrderStatOperator)`

#### MonitorOee (`app/Models/MonitorOee.php`)

- __Fillable__: `production_line_id`, `sensor_active`, `modbus_active`, `mqtt_topic`, `mqtt_topic2`, `topic_oee`, `time_start_shift`
- __Relaciones__: `productionLine()`, `sensor()`, `modbus()`
- __Eventos__: en `updating`, `created`, `deleted` llama a `restartSupervisor()` (ejecuta `sudo supervisorctl restart all` y registra en el canal `supervisor`).

#### Sensor (`app/Models/Sensor.php`)

- __Fillable__: campos de configuración del sensor (tópicos MQTT, contadores, parámetros de corrección, etc.)
- __Relaciones__: `productionLine()`, `controlWeights()`, `controlHeights()`, `modbuses()`, `barcoder()`, `sensorCounts()`, `productList()`, `history()`
- __Eventos__:
  - `creating`: genera `token` único (`Str::uuid()`).
  - `updating`/`deleted`: si cambian `mqtt_topic_sensor`/`mqtt_topic_1` o se elimina, llama a `restartSupervisor()`.
  - `restartSupervisor()` usa `sudo supervisorctl restart all` con logs en canal `supervisor`.

## ⚙️ Servicios en Segundo Plano

## 🔄 Servicios en Segundo Plano

Sensorica implementa una arquitectura de microservicios donde múltiples procesos trabajan de forma coordinada para garantizar el funcionamiento del sistema en tiempo real. Estos servicios se gestionan mediante Supervisor y se dividen en dos categorías principales: comandos Laravel y servidores Node.js.

### 📊 Comandos Laravel (Supervisor)

Los siguientes comandos se ejecutan como procesos daemon gestionados por Supervisor:

#### Monitoreo OEE y Producción

- **CalculateProductionMonitorOee**: Calcula métricas OEE (Eficiencia Global del Equipo) en tiempo real, procesando datos de sensores y modbuses para determinar disponibilidad, rendimiento y calidad.
- **CalculateProductionDowntime**: Monitoriza y registra tiempos de inactividad en las líneas de producción, categorizando las paradas según su causa.
- **CalculateOptimalProductionTime**: Calcula tiempos teóricos óptimos para cada orden de producción basándose en históricos y configuraciones.
- **UpdateAccumulatedTimes**: Actualiza los tiempos acumulados de producción para órdenes en proceso, esencial para el cálculo de eficiencia.

#### Integración MQTT

- **MqttSubscriber**: Suscriptor principal que escucha tópicos MQTT relacionados con códigos de barras y actualiza órdenes de producción.
- **MqttSubscriberLocal**: Versión optimizada para entornos locales que reduce la latencia en la comunicación.
- **MqttShiftSubscriber**: Especializado en la gestión de mensajes MQTT relacionados con turnos de trabajo.

#### Sensores y Dispositivos

- **ReadSensors**: Procesa datos de sensores industriales recibidos vía MQTT y los almacena en la base de datos.
- **ReadRfidReadings**: Gestiona lecturas de dispositivos RFID, aplicando filtros y reglas de negocio específicas.
- **ReadModbus**: Integra con sistemas SCADA/Modbus para control de maquinaria industrial y dosificación.
- **ReadBluetoothReadings**: Procesa datos de sensores Bluetooth para seguimiento de activos y personal.

#### Sincronización y Mantenimiento

- **CheckOrdersFromApi**: Sincroniza órdenes de producción con sistemas ERP externos mediante APIs configurables.
- **CheckShiftList**: Verifica y actualiza la información de turnos activos.
- **ClearOldRecords**: Realiza limpieza periódica de registros antiguos para optimizar el rendimiento de la base de datos.
- **ResetWeeklyCounts**: Reinicia contadores semanales para estadísticas y reportes.

### 🔌 Servidores Node.js

Complementando los comandos Laravel, Sensorica utiliza servidores Node.js para tareas que requieren alta concurrencia y comunicación en tiempo real:

#### Servidores MQTT

- **sender-mqtt-server1.js**: Servidor MQTT principal que gestiona la comunicación entre sensores y el sistema central. Monitoriza la carpeta `/storage/app/mqtt/server1` y publica mensajes almacenados localmente.
- **sender-mqtt-server2.js**: Servidor MQTT secundario que proporciona redundancia y balanceo de carga. Monitoriza la carpeta `/storage/app/mqtt/server2`.

#### Integración Industrial

- **client-modbus.js**: Cliente Modbus/TCP que se comunica con PLCs y sistemas SCADA industriales. Implementa caché de configuración y manejo de reconexiones.
- **mqtt-rfid-to-api.js**: Gateway que traduce mensajes MQTT de lectores RFID a llamadas a la API REST de Sensorica. Incluye interfaz web de monitoreo en tiempo real.
- **config-rfid.js**: Servidor de configuración para lectores RFID con interfaz WebSocket para administración remota.

#### Comunicación Externa

- **connect-whatsapp.js**: Servidor de integración con WhatsApp Business API que permite enviar notificaciones sobre incidencias y estados de producción a través de WhatsApp.

### 🔧 Configuración de Supervisor

Todos estos servicios se gestionan mediante archivos de configuración en `/etc/supervisor/conf.d/` que definen parámetros como:

- Número de procesos worker
- Reinicio automático
- Rotación de logs
- Prioridades de ejecución
- Dependencias entre servicios

La arquitectura distribuida permite alta disponibilidad y escalabilidad horizontal, con capacidad para procesar miles de eventos por segundo provenientes de sensores industriales.

#### 🧭 Mapa Supervisor → Comando/Script (archivo → programa → ejecución)

- `laravel-auto-finish-operator-post.conf` → `[program:operator-post-finalize]` → `php artisan operator-post:finalize`
- `laravel-calculate-optimal-production-time.conf` → `[program:calculate_optimal_time]` → `php artisan production:calculate-optimal-time`
- `laravel-calculate-production-downtime.conf` → `[program:calculate-production-downtime]` → `php artisan production:calculate-production-downtime`
- `laravel-check-bluetooth.conf` → `[program:laravel-bluetooth-check-exit]` → `php artisan bluetooth:check-exit`
- `laravel-clear-db.conf` → `[program:clear-old-records]` → `php artisan clear:old-records`
- `laravel-connect-whatsapp.conf` → `[program:connect-whatsapp]` → `node node/connect-whatsapp.js` (dir: `node/`, user: root)
- `laravel-control-antena-rfid.conf` → `[program:laravel-config-rfid-antena]` → `node node/config-rfid.js` (dir: `node/`)
- `laravel-created-production-orders.conf` → `[program:laravel-created-production-orders]` → bucle `orders:list-stock` cada 60 s
- `laravel-modbus-subscriber.conf` → `[program:laravel-modbus-subscriber]` → `node node/client-modbus.js` (dir: `node/`)
- `laravel-modbus-web-8001.conf` → `[program:modbus-web.8001]` → `python3 modbus-web-8001.py`
- `laravel-monitor-oee.conf` → `[program:calculate-monitor-oee]` → `php artisan production:calculate-monitor-oee`
- `laravel-monitor-server.conf` → `[program:servermonitor]` → `python3 servermonitor.py`
- `laravel-mqtt-rfid-to-api.conf` → `[program:laravel-mqtt-rfid-to-api]` → `node node/mqtt-rfid-to-api.js` (dir: `node/`)
- `laravel-mqtt-shift-subscriber.conf` → `[program:laravel-shift-subscriber]` → `php artisan mqtt:shiftsubscribe`
- `laravel-mqtt-subscriber-local-ordermac.conf` → `[program:subscribe-local-ordermac]` → `php artisan mqtt:subscribe-local-ordermac`
- `laravel-mqtt-subscriber-local.conf` → `[program:subscribe-local]` → `php artisan mqtt:subscribe-local`
- `laravel-mqtt_send_server1.conf` → `[program:laravel-mqtt-sendserver1]` → `node node/sender-mqtt-server1.js` (dir: `node/`)
- `laravel-orders-check.conf` → `[program:laravel-orders-check]` → bucle `orders:check` cada 1800 s (30 min)
- `laravel-production-updated-accumulated-times.conf.conf` → `[program:laravel-production-update-accumulated-times]` → bucle `production:update-accumulated-times` cada 60 s
- `laravel-read-bluetooth.conf` → `[program:laravel-read-bluetooth]` → `php artisan bluetooth:read`
- `laravel-read-rfid.conf` → `[program:laravel-read-rfid]` → `node node/client-mqtt-rfid.js` (dir: `node/`)
- `laravel-read-sensors.conf` → `[program:laravel-read-sensors]` → `node node/client-mqtt-sensors.js` (dir: `node/`)
- `laravel-reset-weekly-counts.conf` → `[program:reset-weekly-counts]` → `php artisan reset:weekly-counts`
- `laravel-sensor-transformers.conf` → `[program:laravel-sensor-transformers]` → `node node/sensor-transformer.js` (dir: `node/`)
- `laravel-server-check-host-monitor.conf` → `[program:check_host_monitor]` → `php artisan hostmonitor:check`
- `laravel-shift-list.conf` → `[program:laravel-shift-list]` → `php artisan shift:check`
- `laravel-tcp-client-local.conf` → `[program:laravel-tcp-client-local]` → `php artisan tcp:client-local`
- `laravel-tcp-client.conf` → `[program:laravel-tcp-client]` → `php artisan tcp:client`
- `laravel-tcp-server.conf` → `[program:tcp-server]` → `python3 tcp-server.py`
- `laravel-telegram-server.conf` → `[program:connect-telegram-server]` → `node telegram/telegram.js` (dir: `telegram/`, user: root)

## 📱 Sistemas Especializados

Sensorica integra varios sistemas especializados para cubrir necesidades específicas de entornos industriales:

### 💪 Sistema RFID

El sistema RFID (Radio Frequency Identification) permite el seguimiento de activos, operarios y productos en la planta de producción:

#### Componentes del Sistema RFID

- **Lectores RFID**: Dispositivos físicos que leen etiquetas RFID y envían datos a través de MQTT.
- **Antenas RFID**: Configurables por zonas para detectar entrada/salida de productos y personal.
- **Gateway MQTT-RFID**: Procesa y filtra lecturas RFID antes de enviarlas al sistema central.
- **Panel de Monitoreo**: Interfaz web en `/live-rfid/index.html` para visualización en tiempo real de lecturas.

#### Funcionalidades RFID

- **Asignación de Operarios**: Vinculación de tarjetas RFID con operarios específicos.
- **Control de Acceso**: Restricción de acceso a áreas específicas mediante RFID.
- **Seguimiento de Productos**: Trazabilidad completa del producto durante el proceso de fabricación.
- **Sistema de Bloqueo**: Capacidad para bloquear tarjetas RFID específicas (por EPC o TID).
- **Filtrado por RSSI**: Configuración de potencia mínima de señal para evitar lecturas fantasma.

### 🎛️ Sistema SCADA/Modbus

Integración con sistemas de control industrial para monitoreo y control de maquinaria:

#### Componentes SCADA

- **Cliente Modbus/TCP**: Comunicación con PLCs y controladores industriales.
- **Tolvas y Dosificadores**: Control de sistemas de dosificación industrial con precisión configurable.
- **ScadaList**: Gestión de materiales y fórmulas para sistemas de mezcla automática.

#### Funcionalidades SCADA

- **Lectura de Registros**: Lectura periódica de registros Modbus de dispositivos industriales.
- **Control de Dosificación**: Envío de comandos para dosificación precisa de materiales.
- **Alarmas y Eventos**: Detección y registro de alarmas en sistemas industriales.
- **Sincronización de Fórmulas**: Envío automático de fórmulas a sistemas de dosificación.

### 💬 Integración con WhatsApp

Sensorica incluye un sistema de notificaciones vía WhatsApp para mantener informados a supervisores y gerentes:

#### Características de la Integración WhatsApp

- **Notificaciones de Incidencias**: Envío automático de alertas cuando se registran incidencias en producción.
- **Resúmenes de Producción**: Envío programado de informes de producción diarios/semanales.
- **Comandos Remotos**: Capacidad para ejecutar comandos básicos mediante mensajes de WhatsApp.
- **Autenticación QR**: Sistema de conexión mediante código QR para vincular la cuenta de WhatsApp.

#### Configuración WhatsApp

- **Panel de Administración**: Interfaz web para configurar destinatarios y tipos de notificaciones.
- **Plantillas de Mensajes**: Mensajes predefinidos para diferentes tipos de eventos.
- **Programación de Envíos**: Configuración de horarios para envío automático de informes.

### 📚 Inventario Completo (Archivos Reales)

A continuación se listan los archivos reales detectados en el repositorio para trazabilidad directa.

#### Comandos Artisan (app/Console/Commands/)

- CalculateOptimalProductionTime.php
- CalculateProductionDowntime.php
- CalculateProductionMonitorOee.php
- CalculateProductionMonitorOeev2.php
- CheckBluetoothExit.php
- CheckHostMonitor.php
- CheckOrdersFromApi.php
- CheckShiftList.php
- ClearOldRecords.php
- ConnectWhatsApp.php
- FinalizeOperatorPosts.php
- ListStockOrdersCommand.php
- MonitorConnections.php
- MqttShiftSubscriber.php
- MqttSubscriber.php
- MqttSubscriberLocal.php
- MqttSubscriberLocalMac.php
- PublishOrderStatsCommand.php
- ReadBluetoothReadings.php
- ReadModbuBackup.php
- ReadModbus.php
- ReadModbusGroup.php
- ReadRfidReadings.php
- ReadSensors.php
- ReplicateDatabaseNightly.php
- ResetWeeklyCounts.php
- TcpClient.php
- TcpClientLocal.php
- UpdateAccumulatedTimes.php

#### Archivos Supervisor (.conf en raíz del proyecto)

- laravel-auto-finish-operator-post.conf
- laravel-calculate-optimal-production-time.conf
- laravel-calculate-production-downtime.conf
- laravel-check-bluetooth.conf
- laravel-clear-db.conf
- laravel-connect-whatsapp.conf
- laravel-control-antena-rfid.conf
- laravel-created-production-orders.conf
- laravel-modbus-subscriber.conf
- laravel-modbus-web-8001.conf
- laravel-monitor-oee.conf
- laravel-monitor-server.conf
- laravel-mqtt-rfid-to-api.conf
- laravel-mqtt-shift-subscriber.conf
- laravel-mqtt-subscriber-local-ordermac.conf
- laravel-mqtt-subscriber-local.conf
- laravel-mqtt_send_server1.conf
- laravel-orders-check.conf
- laravel-production-updated-accumulated-times.conf.conf
- laravel-read-bluetooth.conf
- laravel-read-rfid.conf
- laravel-read-sensors.conf
- laravel-reset-weekly-counts.conf
- laravel-sensor-transformers.conf
- laravel-server-check-host-monitor.conf
- laravel-shift-list.conf
- laravel-tcp-client-local.conf
- laravel-tcp-client.conf
- laravel-tcp-server.conf
- laravel-telegram-server.conf

Nota: la configuración efectiva suele residir en `/etc/supervisor/conf.d/`, pero estos `.conf` de proyecto documentan los programas y comandos a declarar allí.

#### Servidores Node.js

- node/client-modbus.js
- node/client-mqtt-rfid.js
- node/client-mqtt-sensors.js
- node/config-rfid.js
- node/connect-whatsapp.js
- node/mqtt-rfid-to-api.js
- node/sender-mqtt-server1.js
- node/sender-mqtt-server2.js
- node/sensor-transformer.js
- telegram/telegram.js

Relación con secciones previas:
- SCADA/Modbus: `node/client-modbus.js`
- Gateway RFID: `node/mqtt-rfid-to-api.js`, `node/config-rfid.js`, `node/client-mqtt-rfid.js`
- MQTT publishers: `node/sender-mqtt-server1.js`, `node/sender-mqtt-server2.js`
- Transformación de sensores: `node/sensor-transformer.js`
- WhatsApp: `node/connect-whatsapp.js`
- Telegram: `telegram/telegram.js`

### 📦 Documentación detallada de servidores y servicios

#### node/client-modbus.js
- __Propósito__: Suscriptor MQTT para valores Modbus; aplica reglas de repetición/variación y publica a API cuando corresponde.
- __ENV__: `MQTT_SENSORICA_SERVER`, `MQTT_SENSORICA_PORT`, `DB_HOST/PORT/USERNAME/PASSWORD/DB_DATABASE`.
- __DB__: Lee `modbuses` (campos: `mqtt_topic_modbus`, `rep_number`, `model_name`, `variacion_number`, `conversion_factor`, `dimension_default`).
- __MQTT__: Suscribe dinámico por `modbuses.mqtt_topic_modbus` (QoS 1). Cachea config por tópico y controla repeticiones/umbrales.
- __HTTP__: Llama APIs internas según lógica (ver controlador correspondiente).
- __Supervisor__: `[program:laravel-modbus-subscriber]` → `node node/client-modbus.js`.
- __Operación/Logs__: Reconexión a MQTT/DB con backoff, limpieza de cachés en reconnect, logs con timestamps.

#### node/client-mqtt-rfid.js
- __Propósito__: Consumidor de lecturas RFID desde tópicos por antena; valida turnos y filtra duplicados por RSSI/intervalo.
- __ENV__: `MQTT_SENSORICA_*`, `LOCAL_SERVER`, `DB_*`.
- __DB__: Lee `rfid_ants` (topic, rssi_min, min_read_interval_ms, production_line_id), `shift_history` (estado turno), `rfid_blocked` (EPCs).
- __MQTT__: Suscribe a `rfid_ants.mqtt_topic`. Caches por antena, mapas de EPC/TID ignorados temporales.
- __HTTP__: POST a `${LOCAL_SERVER}/api/...` para registrar eventos RFID.
- __Supervisor__: `[program:laravel-read-rfid]` → `node node/client-mqtt-rfid.js`.
- __Operación__: Re-suscribe al reconectar; actualización periódica de caches; logs de control de flujo.

#### node/client-mqtt-sensors.js
- __Propósito__: Consumidor de sensores genéricos; extrae valores con rutas JSON y envía a API con reintentos y backoff.
- __ENV__: `MQTT_SENSORICA_*`, `LOCAL_SERVER` (HTTPS permitido), `DB_*`.
- __DB__: Lee `sensors` (mqtt_topic_sensor, sensor_type, invers_sensors, json_api).
- __MQTT__: Suscribe/unsuscribe dinámico según `sensors`.
- __HTTP__: POST `${LOCAL_SERVER}/api/sensor-insert` con `https.Agent({ rejectUnauthorized:false })` para entornos con TLS propio.
- __Supervisor__: `[program:laravel-read-sensors]` → `node node/client-mqtt-sensors.js`.
- __Operación__: Reintentos exponenciales y logging detallado de extracciones JSON.

#### node/config-rfid.js
- __Propósito__: Panel Socket.IO para administrar el lector RFID (tarea MQTT, lectura, antenas) vía API HTTP del lector.
- __ENV__: `MQTT_SENSORICA_*`, `RFID_READER_IP`, `RFID_READER_PORT` en `.env` de Laravel.
- __DB__: No requiere; lee `.env` para parámetros del lector.
- __MQTT__: Publica/escucha en `rfid_command` para comandos/estados.
- __HTTP externo__: `http://RFID_READER_IP:RFID_READER_PORT/API/Task` (endpoints `getMQTTInfo`, enable/disable, start/stop reading, etc.).
- __Supervisor__: `[program:laravel-config-rfid-antena]` → `node node/config-rfid.js`.
- __Operación__: Auto-monitoreo periódico, caché de estado/antenas, logs coloreados y reconexión controlada.

#### node/mqtt-rfid-to-api.js
- __Propósito__: Gateway Express + WebSocket para visualización en tiempo real de mensajes RFID y gestión de suscripciones por DB.
- __ENV__: `MQTT_SENSORICA_*`, `DB_*`, `MQTT_GATEWAY_PORT`, `USE_HTTPS`, `SSL_KEY_PATH`, `SSL_CERT_PATH`.
- __DB__: Lee tópicos y metadatos de antenas; mantiene `antennaDataMap`.
- __MQTT__: Suscribe a tópicos definidos en DB; re-sync en reconexiones.
- __HTTP__: 
  - REST: `/api/gateway-messages` (incluye topics_info)
  - UI: `/gateway-test` (viewer con WebSocket)
  - WebSocket: broadcast de mensajes y lista de tópicos/antenas
- __Supervisor__: `[program:laravel-mqtt-rfid-to-api]` → `node node/mqtt-rfid-to-api.js`.
- __Operación__: Soporta HTTP/WS y HTTPS/WSS; almacena histórico acotado en memoria.

#### node/sender-mqtt-server1.js
- __Propósito__: Publica archivos JSON como mensajes MQTT para “server1”. Elimina archivos tras éxito.
- __ENV__: `MQTT_SENSORICA_*`.
- __FS__: Lee `storage/app/mqtt/server1/` recursivamente.
- __MQTT__: Publica según `data.topic` y `data.message` del JSON.
- __Supervisor__: `[program:laravel-mqtt-sendserver1]` → `node node/sender-mqtt-server1.js`.
- __Operación__: Vigila cambios de `.env`, reconexión automática, manejo de JSON inválidos (eliminación segura + log).

#### node/sender-mqtt-server2.js
- __Propósito__: Igual a server1, usando broker alterno (`MQTT_SERVER`/`MQTT_PORT`).
- __ENV__: `MQTT_SERVER`, `MQTT_PORT`.
- __FS__: `storage/app/mqtt/server2/`.
- __Supervisor__: (si aplica) `[program:laravel-mqtt-sendserver2]` → `node node/sender-mqtt-server2.js`.

#### node/sensor-transformer.js
- __Propósito__: Transforma valores de sensores según `sensor_transformations` y publica a tópicos de salida sólo si cambia el resultado.
- __ENV__: `DB_*`, `MQTT_SENSORICA_*`.
- __DB__: Lee `sensor_transformations` (min/mid/max, output_topic, etc.).
- __MQTT__: Suscribe a `input_topic[]`; publica a `output_topic` tras `transformValue()` y deduplicación por cache.
- __Supervisor__: `[program:laravel-sensor-transformers]` → `node node/sensor-transformer.js`.
- __Operación__: Reconexión DB y MQTT; recarga periódica y detección de cambios de configuración.

#### node/connect-whatsapp.js
- __Propósito__: Servicio de WhatsApp basado en Baileys (QR login), persistencia de credenciales filtradas y callbacks a API Laravel.
- __ENV__: Dependen de Baileys/puerto local.
- __HTTP__: 
  - POST `/start-whatsapp`, `/logout`, `/get-qr`
  - Callback a `http://localhost/api/whatsapp-credentials` para guardar creds/keys filtrados
- __Supervisor__: `[program:connect-whatsapp]` → `node node/connect-whatsapp.js` (user `root`).
- __Operación__: Reconecta al cerrar no intencional; imprime QR en terminal; rota store a `baileys_store_multi.json`.

#### telegram/telegram.js
- __Propósito__: API completa para Telegram con Swagger (autenticación, mensajes, media, grupos, contactos, reglas y programación).
- __ENV__: `API_ID`, `API_HASH`, `PORT`, `API_EXTERNAL*`, `DATA_FOLDER`, `CALLBACK_BASE`.
- __HTTP__: Amplia lista de endpoints REST documentados en `/api-docs` (Swagger UI).
- __FS__: Maneja sesiones y media en `DATA_FOLDER`.
- __Supervisor__: `[program:connect-telegram-server]` → `node telegram/telegram.js` (user `root`).
- __Operación__: Carga sesiones al inicio, deduplicación de mensajes, manejo de tareas programadas en memoria.

### 🌐 Catálogo de Endpoints HTTP

Para el detalle completo revisar `routes/web.php` y `routes/api.php`. A continuación, un mapa de alto nivel de los grupos más relevantes:

#### Web (`routes/web.php`)
- __Kanban de órdenes__: 
  - `POST /production-orders/update-batch`, `/toggle-priority`, `/update-note`
  - `GET /customers/{customer}/order-organizer`, `/order-kanban/{process}`
  - `GET /kanban-data` (AJAX)
- __Clientes y Órdenes Originales__: `Route::resource('customers', ...)`, anidados `customers.original-orders.*` y utilidades `field-mapping-row`
- __Líneas de Producción__: `productionlines.*`, `.../productionlinesjson`, `liststats`
- __Procesos por Línea__: `productionlines/{production_line}/processes.*`
- __Sensores (SmartSensors)__: `smartsensors.*`, vistas `live`, `history`; detalle `sensors/{id}`
- __RFID__: `rfid.*`, categorías `rfid-categories.*`, colores `rfid.colors.*`, bloqueo `DELETE /rfid-blocked/destroy-all`
- __Turnos__: `shift-lists` CRUD, `shift-history/{productionLineId}`, `POST /shift-event`
- __Usuarios/Roles/Permisos__: `roles`, `users`, `permission`, `modules`, util `GET /roles/list`
- __Ajustes__: `settings` y POSTs específicos (`email`, `datetime`, `rfid`, `redis`, `upload-stats`, réplica DB)
- __Códigos de barras__: `barcodes.*`, impresoras `Route::resource('printers', ...)`
- __Modbus__: `modbuses.*`, `modbusesjson`, `queue-print`, `liststats`
- __OEE y Transformaciones__: `Route::resource('oee', ...)`, `sensor-transformations.*`
- __Monitor y Servidores__: `GET /server`, `GET /logs`
- __Puestos de Operario__: `worker-post.*`, `GET /scan-post`
- __SCADA/Producción__: `GET /scada-order`, `GET /production-order-kanban`
- __Varios__: `GET /debug`, `Auth::routes()`, `GET /` (dashboard)

#### API (`routes/api.php`)
- __Sistema/Servidor__: `/server-monitor-store`, `/register-server`, `/server-stats`, `/server-ips`, `restart|start|stop-supervisor`, `reboot`, `poweroff`, `restart-mysql`, `verne-update`, `app-update`, `update-env`, `check-db-connection`, `verify-and-sync-database`, `run-update`, `check-485-service`, `install-485-service`, `getSupervisorStatus`
- __Barcodes__: `/barcode`, `/barcode-info{,/POST}`, `/barcode-info-by-customer/{customerToken}`
- __Token/Producción__: `/production-lines/{customerToken}`, `/modbus-info/{token}`
- __Control de Peso__: `/control-weights/{token}/all`, throttled `/control-weight/{token}`, `GET /control_weight/{supplierOrderId}` consolidado
- __Modbus/SCADA__: `/modbuses`, `/tolvas/{id}/dosificacion/recalcular-automatico`, `POST /modbus/send|zero|tara|tara/reset|cancel`, `GET scada/{token}`, `PUT /modbus/{modbusId}/material`, grupo `scada/*` de material types
- __Sensores__: `/sensors{,/token}`, `POST /sensor-insert` (throttle alto)
- __Estadísticas de órdenes__: `/order-stats`, `/order-stats-all`
- __Producción (Kanban)__: `GET /kanban/orders`
- __Órdenes de producción API__: `/production-orders` (CRUD parcial), incidentes `production-orders/{order}/incidents`
- __Producción Topflow__: `reference-Topflow/*`, `topflow-production-order/*`
- __Disponibilidad y estado de líneas__: `GET /production-line/status/{token}`, `GET/POST /production-lines/{id}/availability`, `GET /production-lines/statuses/{customerId?}`
- __RFID__: `POST /rfid-insert`, `GET /rfid-history`, `GET /get-filters`
- __WhatsApp__: `POST /whatsapp-credentials`, `GET|POST /send-message`, `/whatsapp/logout`, `GET /whatsapp-qr{,/svg,/base64}`
- __Bluetooth Scanner__: `bluetooth/*` (`insert`, `history`, `filters`)
- __Operadores/Trabajadores__: `workers/*` (update/replace/list/show/reset-password/verify/destroy), `operators` y `operators/internal`, `workers/all-list/completed`, `scada/get-logins`
- __Listas de Producto__: `product-lists/*`, `product-list-selecteds/*`
- __TCP Publish__: `POST /publish-message`
- __Transferencias__: `POST /transfer-external-db`
- __Puestos de Operario (API)__: `operator-post/*` y `POST /operator-post/update-count`
- __Shift__: `/shift-event` (MQTT), `GET /shift-history{,/production-line/{id}}`, `GET /shift/statuses`, `GET /shift-lists`
- __IA Prompts__: `GET /ia-prompts{,/\{key\}}`
- __Barcode Scans__: `GET|POST /barcode-scans`
- __SCADA Orders__: `GET /scada-orders/{token}`, `POST /scada-orders/update`, `DELETE /scada-orders/delete`, `GET /scada-orders/{scadaOrderId}/lines`, `POST /scada-orders/process/update-used`


### 🛠️ Comandos Artisan (Supervisor y mantenimiento)

Extraídos de `app/Console/Commands/*`:

- `shift:check` — Check shift list and publish MQTT message if current time matches start time
- `bluetooth:read` — Read data from Bluetooth API and publish to MQTT
- `bluetooth:check-exit` — Verifica si los dispositivos Bluetooth han salido de la zona de detección
- `reset:weekly-counts` — Reset count_week_0 and count_week_1 to 0 every Monday at 00:00
- `tcp:client` — Connect to multiple TCP servers and read messages continuously
- `modbus:read {group}` — Read data from Modbus API and publish to MQTT for a specific group
- `hostmonitor:check` — Envía un correo de alerta si un host no tiene registros en host_monitors en los últimos 3 minutos
- `mqtt:subscribe-local` — Subscribe to MQTT topics and update order notices
- `operator-post:finalize` — Cierra y gestiona los registros de operadores según el inicio y fin de turno.
- `mqtt:subscribe-local-ordermac` — Subscribe to MQTT topics and update production orders
- `tcp:client-local` — Connect to TCP server using .env values and log messages in a loop
- `production:calculate-monitor-oee-vieja` — Calcular y gestionar el monitoreo de la producción (versión previa)
- `orders:check` — Verifica pedidos desde la API y los compara con la base de datos local
- `db:replicate-nightly` — Dumps the primary database and replaces the secondary (mysql/mariadb autodetect)
- `clear:old-records` — Clear old records from varias tablas según CLEAR_DB_DAY
- `production:calculate-monitor-oee` — Calcular y gestionar el monitoreo de la producción (OEE v2)
- `sensors:read` — Read data from Sensors API and publish to MQTT
- `rfid:read` — Read data from RFID API and publish to MQTT
- `modbus:read-ant` — Read data from Modbus API and publish to MQTT
- `monitor:connections` — Monitor MQTT topics for connections and update their status in the database
- `mqtt:subscribe` — Subscribe to MQTT topics and update order notices
- `whatsapp:connect` — Conecta a WhatsApp usando Baileys sin generar QR
- `production:calculate-production-downtime` — Calculate production downtime and publish MQTT
- `modbus:read-backup` — Read data from Modbus API and publish to MQTT
- `mqtt:shiftsubscribe` — Subscribe to MQTT topics and update shift control information from sensors
- `production:update-accumulated-times {line_id?}` — Actualiza tiempos acumulados de órdenes activas (opcional por línea)
- `production:calculate-optimal-time` — Calculate the optimal production time per product from sensor data
- `orders:list-stock` — Busca órdenes en stock y procesa siguiente tarea pendiente por grupo
- `mqtt:publish-order-stats` — Extrae barcodes/order_stats y publica JSON por MQTT cada 1s

### 🧩 Variables de entorno (.env) requeridas

Agrupadas por subsistema. Ver también `resources/views/settings/*.blade.php` para formularios de administración que dependen de estas claves.

- __Core/Laravel__
  - `APP_URL`, `ASSET_URL`
  - `APP_TIMEZONE`, `TIMEZONE` (zona horaria)
  - `SITE_RTL` (on/off)

- __Base de Datos__
  - `DB_CONNECTION` (mysql|pgsql|sqlsrv)
  - `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

- __Correo__
  - `MAIL_DRIVER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`
  - `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`

- __MQTT (principal y Sensorica)__
  - Broker genérico: `MQTT_SERVER`, `MQTT_PORT`
  - Broker Sensorica: `MQTT_SENSORICA_SERVER`, `MQTT_SENSORICA_PORT`
  - Backup: `MQTT_SENSORICA_SERVER_BACKUP`, `MQTT_SENSORICA_PORT_BACKUP`
  - Credenciales/opciones: `MQTT_USERNAME`, `MQTT_PASSWORD`, `MQTT_TOPIC`
  - Tiempos de envío por lotes (senders): `MQTT_SERVER1_CHECK_INTERVAL_MS`, `MQTT_SERVER2_CHECK_INTERVAL_MS`, `MQTT_CHECK_INTERVAL_MS`

- __RFID__
  - Panel/config lector: `RFID_READER_IP`, `RFID_READER_PORT`
  - Monitor externo (link en `server/index.blade.php`): `RFID_MONITOR_URL`

- __Gateway MQTT-RFID (Express/WebSocket)__
  - `MQTT_GATEWAY_PORT`
  - HTTPS opcional: `USE_HTTPS` (true/false), `SSL_KEY_PATH`, `SSL_CERT_PATH`
  - Puerto alternativo servidor lector: `NODE_RFID_PORT`

- __WhatsApp (Baileys)__
  - Usa callbacks HTTP locales; puede requerir `PORT` si se expone servidor HTTP local del script.

- __Telegram API server__
  - `API_ID`, `API_HASH`, `PORT`
  - `API_EXTERNAL`/`API_EXTERNAL_*` (si se usa reverse proxy o URLs públicas)
  - `DATA_FOLDER` (almacenamiento de sesiones/media)
  - `CALLBACK_BASE` (URL base para callbacks webhooks)

- __Backups y SFTP__
  - `BACKUP_ARCHIVE_PASSWORD`, `BACKUP_ARCHIVE_ENCRYPTION`
  - `SFTP_HOST`, `SFTP_PORT`, `SFTP_USERNAME`, `SFTP_PASSWORD`, `SFTP_ROOT`

- __Producción/OEE/limpieza__
  - `SHIFT_TIME` (HH:MM:SS inicio de turno)
  - `PRODUCTION_MIN_TIME`, `PRODUCTION_MAX_TIME`, `PRODUCTION_MIN_TIME_WEIGHT`
  - `CLEAR_DB_DAY` (retención de registros en días)

- __Sistema/Operaciones__
  - `TOKEN_SYSTEM` (token de autenticación para endpoints de sistema)
  - `USE_CURL` (true/false), `EXTERNAL_API_QUEUE_TYPE` (get|post|put|delete)
  - Entorno runtime: `APP_ENV` (Node gateway), `NODE_ENV` (scripts Node)
  - Base URL backend para clientes Node: `LOCAL_SERVER` (ej. https://mi-backend)

Notas:
- Algunos servicios Node.js leen credenciales DB vía `.env` de Laravel (usado por scripts con `mysql2`). Asegura consistencia.
- Si se usa HTTPS propio, `https.Agent({ rejectUnauthorized:false })` en `client-mqtt-sensors.js` tolera TLS autofirmado.

## 📝 Licencia

AiXmart es un software propietario. Todos los derechos reservados.

---

Desarrollado por el equipo de AppNet Developer, Boisolo Y AiXmart 2025
