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

**3. Vistas de Monitoreo OEE:**

- **Descripción:** Conjunto de vistas para visualizar y analizar métricas OEE (Overall Equipment Effectiveness).
- **Características principales:**
  - **Gráficos interactivos:** Visualización de métricas mediante gráficos circulares y de barras.
  - **Filtrado por período:** Selección de rangos de fechas para análisis específicos.
  - **Desglose de métricas:** Visualización detallada de disponibilidad, rendimiento y calidad.
  - **Comparativa entre líneas:** Análisis comparativo del rendimiento de diferentes líneas de producción.
  - **Exportación de datos:** Generación de informes en formatos PDF, Excel y CSV.

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

- **Registro Automático**: Creación de incidencias al mover tarjetas a la columna correspondiente.
- **Categorización**: Clasificación de incidencias por tipo y gravedad.
- **Asignación**: Asignación de responsables para la resolución.
- **Seguimiento**: Monitoreo del estado y tiempo de resolución.
- **Análisis**: Herramientas para identificar patrones y causas recurrentes.

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

## 📝 Licencia

Sensorica es un software propietario. Todos los derechos reservados.

---

Desarrollado por el equipo de AppNet Developer y Boisolo Y AiXmart 2025
