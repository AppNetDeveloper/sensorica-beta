<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Xmart | Plataforma Industrial de Producción y OEE</title>
  <meta name="description" content="Xmart: Kanban en tiempo real, OEE, integración con sensores, RFID, Modbus/SCADA, WhatsApp/Telegram y analítica para la industria." />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <header class="site-header">
    <div class="container header-inner">
      <div class="brand">
        <div class="logo">Xmart</div>
        <div class="tag">Industria 4.0</div>
      </div>
      <nav class="nav">
        <a href="#solucion">Solución</a>
        <a href="#logistica">Logística</a>
        <a href="#mantenimiento">Mantenimiento</a>
        <a href="#calidad">Calidad</a>
        <a href="#whatsapp">WhatsApp</a>
        <a href="#erp">ERP</a>
        <a href="#ia">IA</a>
        <a href="#beneficios">Beneficios</a>
        <a href="#sectores">Sectores</a>
        <a href="#casos">Casos de uso</a>
        <a href="#contacto" class="btn btn-primary">Solicitar presupuesto</a>
      </nav>
      <button class="menu-toggle" aria-label="Abrir menú">☰</button>
    </div>
  </header>

  <section class="hero">
    <div class="container hero-grid">
      <div class="hero-copy">
        <h1>La manera más sencilla de controlar tu producción</h1>
        <p>Xmart te ayuda a planificar, ejecutar y mejorar tu fábrica con un tablero visual y métricas claras. Reduce paradas, aumenta rendimiento y toma decisiones con datos.</p>
        <div class="cta-row">
          <a class="btn btn-primary" href="#contacto">Solicitar presupuesto</a>
          <a class="btn btn-secondary" href="#solucion">Cómo funciona</a>
        </div>
- Gestión de rutas de entrega y flota de vehículos
      </div>
      <div class="hero-visual" aria-hidden="true">
        <div class="card demo">
          <div class="badge">Realtime</div>
          <h3>Kanban + OEE</h3>
          <ul>
            <li>Drag & Drop seguro</li>
            <li>Tiempos teóricos y reales</li>
            <li>Incidencias y notas</li>
          </ul>
        </div>
        <div class="card demo alt">
          <div class="badge green">IoT</div>
          <h3>Sensores + RFID</h3>
          <ul>
            <li>MQTT de alta carga</li>
            <li>RFID gateways</li>
            <li>Modbus/SCADA</li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <section id="ia" class="section">
    <div class="container">
      <div class="ia-intro">
        <h2>IA para tu fábrica</h2>
        <p class="subtitle">Xmart incorpora una capa de inteligencia para organizar órdenes, controlar maquinaria y producción en tiempo real, y avisarte antes de que algo se detenga.</p>
      </div>
      
      <div class="ia-grid">
        <div class="ia-content">
          <div class="features-grid">
            <div class="feature">
              <h3>Respuestas al instante</h3>
              <p>Pregunta por pedidos, turnos o líneas y obtén el estado en lenguaje natural.</p>
            </div>
            <div class="feature">
              <h3>Estimaciones</h3>
              <p>Fechas previstas de finalización por pedido y proceso según el avance real.</p>
            </div>
            <div class="feature">
              <h3>Alertas proactivas</h3>
              <p>Detección anticipada de retrasos y recomendaciones simples para corregir rumbo.</p>
            </div>
            <div class="feature">
              <h3>Contexto de planta</h3>
              <p>Entiende tus órdenes, máquinas, operarios y prioridades actuales.</p>
            </div>
          </div>

          <div class="stats-grid">
            <div class="stat-card"><div class="value">-18%</div><div class="label">Paradas no planificadas</div></div>
            <div class="stat-card"><div class="value">+12%</div><div class="label">Cumplimiento de plazos</div></div>
            <div class="stat-card"><div class="value">-9%</div><div class="label">Retrabajos</div></div>
            <div class="stat-card"><div class="value">+8 pts</div><div class="label">OEE medio</div></div>
          </div>

          <div class="feature-list">
            <div class="feature-item"><span class="dot"></span><div>Organización inteligente de órdenes y prioridades por carga y turno.</div></div>
            <div class="feature-item"><span class="dot"></span><div>Control de maquinaria con señales, sensores y estados (disponible, parada, ciclo).</div></div>
            <div class="feature-item"><span class="dot"></span><div>Producción en tiempo real con KPIs y comparación vs objetivo.</div></div>
            <div class="feature-item"><span class="dot"></span><div>Avisos multicanal: tablero, email, WhatsApp y Telegram.</div></div>
            <div class="feature-item"><span class="dot"></span><div>Predicción de tiempos por pedido y proceso basada en histórico y contexto.</div></div>
            <div class="feature-item"><span class="dot"></span><div>Consultas en lenguaje natural sobre pedidos, líneas, operarios y OEE.</div></div>
          </div>

          <div class="accordion">
            <div class="acc-item open">
              <div class="acc-header">¿Qué datos utiliza la IA?</div>
              <div class="acc-content">Órdenes, procesos, estados de máquina, sensores (peso, RFID, dosificación), OEE, turnos y tiempos reales. Solo usa tus propios datos de planta.</div>
            </div>
            <div class="acc-item">
              <div class="acc-header">¿Cómo calcula las estimaciones?</div>
              <div class="acc-content">Combina el rendimiento actual, el histórico de procesos similares, la disponibilidad de equipos/operarios y la carga planificada para proyectar fechas probables de finalización.</div>
            </div>
            <div class="acc-item">
              <div class="acc-header">Privacidad y control</div>
              <div class="acc-content">Tus datos se procesan con fines operativos. Controles de acceso por rol y registro de auditoría. Integraciones cifradas con tus sistemas.</div>
            </div>
          </div>
        </div>
        
        <div class="ia-demo">
          <div class="chat-container">
            <div class="chat-header">
              <div class="chat-title">Asistente IA</div>
              <div class="chat-status">En línea</div>
            </div>
            <div class="chat">
              <div class="chat-msg chat-user">
                <div class="bubble">¿Cuándo se terminará el pedido PO-1234?</div>
                <span>Jefe de planta</span>
              </div>
              <div class="chat-msg chat-ai">
                <div class="bubble">Estimación: hoy a las 16:40. Quedan 2 procesos y el ritmo actual supera el objetivo por 6%.</div>
                <span>Xmart · IA</span>
              </div>
              <div class="chat-msg chat-user">
                <div class="bubble">¿Algún riesgo en la Línea 2 ahora?</div>
                <span>Jefe de planta</span>
              </div>
              <div class="chat-msg chat-ai">
                <div class="bubble">Riesgo medio: paradas intermitentes en Dosificación. Recomiendo reasignar 1 operario 30 min.</div>
                <span>Xmart · IA</span>
              </div>
              <div class="chat-msg chat-user">
                <div class="bubble">Avísame si el OEE baja del 75% en el turno de tarde.</div>
                <span>Jefe de planta</span>
              </div>
              <div class="chat-msg chat-ai">
                <div class="bubble">Hecho. Te avisaré por WhatsApp y en el tablero.</div>
                <span>Xmart · IA</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="ia-footer">
        <div class="partner-badge">
          <div class="partner-label">Systema de Agentes LLM ofrecido por</div>
          <a href="https://www.appnet.dev" target="_blank" rel="noopener" class="partner-link">AppNet Developer</a>
        </div>
        <div class="cta-row">
          <a class="btn btn-primary" href="#contacto">Preguntar a la IA</a>
          <a class="btn btn-secondary" href="#solucion">Ver cómo funciona</a>
        </div>
      </div>
    </div>
  <section id="logistica" class="section">
    <div class="container">
      <div class="ia-intro">
        <h2>🚛 Control de Logística y Flota</h2>
        <p class="subtitle">Sistema completo de gestión de rutas de entrega, asignación de vehículos y optimización logística. Desde la planificación hasta la ejecución en tiempo real.</p>
      </div>

      <div class="ia-grid">
        <div class="ia-content">
          <div class="features-grid">
            <div class="feature">
              <h3>Planificación Visual</h3>
              <p>Calendario semanal con drag & drop para asignar rutas y vehículos de forma intuitiva.</p>
            </div>
            <div class="feature">
              <h3>Gestión Multi-Vehículo</h3>
              <p>Asigna múltiples vehículos a la misma ruta sin restricciones, optimizando recursos.</p>
            </div>
            <div class="feature">
              <h3>Órdenes Ficticias</h3>
              <p>Sistema de mini-tarjetas para simular y planificar pedidos antes de confirmar.</p>
            </div>
            <div class="feature">
              <h3>Auto-Refresh Inteligente</h3>
              <p>Actualización automática que respeta las interacciones del usuario y modals abiertos.</p>
            </div>
          </div>

          <div class="stats-grid">
            <div class="stat-card"><div class="value">-25%</div><div class="label">Tiempo de planificación</div></div>
            <div class="stat-card"><div class="value">+40%</div><div class="label">Eficiencia de rutas</div></div>
            <div class="stat-card"><div class="value">-30%</div><div class="label">Kilómetros innecesarios</div></div>
            <div class="stat-card"><div class="value">+15%</div><div class="label">Cumplimiento entregas</div></div>
          </div>

          <div class="feature-list">
            <div class="feature-item"><span class="dot"></span><div>Interfaz tipo calendario semanal para planificación visual de rutas.</div></div>
            <div class="feature-item"><span class="dot"></span><div>Arrastra clientes entre vehículos y reordena dentro de cada vehículo.</div></div>
            <div class="feature-item"><span class="dot"></span><div>Sistema de órdenes ficticias para simular escenarios antes de confirmar.</div></div>
            <div class="feature-item"><span class="dot"></span><div>Gestión ilimitada de vehículos por ruta y día sin restricciones técnicas.</div></div>
            <div class="feature-item"><span class="dot"></span><div>Auto-refresh que pausa durante operaciones de drag & drop y modals.</div></div>
            <div class="feature-item"><span class="dot"></span><div>Integración con sistemas de tracking GPS para seguimiento en tiempo real.</div></div>
          </div>

          <div class="accordion">
            <div class="acc-item open">
              <div class="acc-header">¿Cómo funciona el sistema de rutas?</div>
              <div class="acc-content">Selecciona la semana, asigna vehículos a rutas específicas por día, y arrastra clientes desde la lista disponible a los vehículos. El sistema permite múltiples vehículos por ruta y reordenación intuitiva.</div>
            </div>
            <div class="acc-item">
              <div class="acc-header">¿Puedo usar órdenes ficticias?</div>
              <div class="acc-content">Sí, crea "pedido-test1", "pedido-test2" para simular escenarios. Aparecen como mini-tarjetas al pasar el ratón sobre el cliente, permitiendo planificación sin afectar datos reales.</div>
            </div>
            <div class="acc-item">
              <div class="acc-header">¿Se integra con GPS?</div>
              <div class="acc-content">Totalmente. Conecta con sistemas de tracking GPS para mostrar ubicación en tiempo real, optimizar rutas automáticamente y calcular ETAs precisos.</div>
            </div>
          </div>
        </div>

        <div class="ia-demo">
          <div class="logistics-demo">
            <div class="logistics-header">
              <div class="logistics-title">Planificador de Rutas</div>
              <div class="logistics-week">Semana 42 - Octubre 2024</div>
            </div>
            <div class="logistics-calendar">
              <div class="logistics-day">
                <div class="day-header">Lun 21</div>
                <div class="day-routes">
                  <div class="route-card">
                    <div class="route-name">Ruta Norte</div>
                    <div class="vehicle-assigned">🚐 Furgón 3</div>
                    <div class="clients-count">5 clientes</div>
                  </div>
                </div>
              </div>
              <div class="logistics-day">
                <div class="day-header">Mar 22</div>
                <div class="day-routes">
                  <div class="route-card">
                    <div class="route-name">Ruta Centro</div>
                    <div class="vehicle-assigned">🚛 Camión 1</div>
                    <div class="clients-count">8 clientes</div>
                  </div>
                  <div class="route-card">
                    <div class="route-name">Ruta Sur</div>
                    <div class="vehicle-assigned">🚐 Furgón 1</div>
                    <div class="clients-count">3 clientes</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="logistics-actions">
              <button class="btn btn-secondary">📅 Cambiar Semana</button>
              <button class="btn btn-primary">✅ Confirmar Plan</button>
            </div>
          </div>
        </div>
      </div>

      <div class="ia-footer">
        <div class="cta-row">
          <a class="btn btn-primary" href="#contacto">Optimizar mis rutas</a>
          <a class="btn btn-secondary" href="#solucion">Ver demo</a>
        </div>
      </div>
    </div>
  </section>

  <section id="mantenimiento" class="section alt">
    <div class="container">
      <div class="ia-intro">
        <h2>🔧 Gestión de Mantenimientos</h2>
        <p class="subtitle">Sistema completo para registrar, iniciar y finalizar incidencias de mantenimiento por línea de producción, con trazabilidad de causas y piezas utilizadas.</p>
      </div>

      <div class="ia-grid">
        <div class="ia-content">
          <div class="features-grid">
            <div class="feature">
              <h3>Registro de Incidencias</h3>
              <p>Registra mantenimientos por línea de producción con información detallada de causas y síntomas.</p>
            </div>
            <div class="feature">
              <h3>Seguimiento de Piezas</h3>
              <p>Controla las piezas utilizadas en cada mantenimiento con sistema de inventario integrado.</p>
            </div>
            <div class="feature">
              <h3>Métricas de Downtime</h3>
              <p>Calcula tiempos de parada y métricas de disponibilidad por línea y tipo de mantenimiento.</p>
            </div>
            <div class="feature">
              <h3>Historial Completo</h3>
              <p>Base de datos histórica de todos los mantenimientos con filtros por fecha, línea y tipo.</p>
            </div>
          </div>

          <div class="stats-grid">
            <div class="stat-card"><div class="value">-35%</div><div class="label">Tiempo de respuesta</div></div>
            <div class="stat-card"><div class="value">+20%</div><div class="label">Disponibilidad equipos</div></div>
            <div class="stat-card"><div class="value">-40%</div><div class="label">Costes de mantenimiento</div></div>
            <div class="stat-card"><div class="value">+25%</div><div class="label">MTBF (tiempo entre fallos)</div></div>
          </div>

          <div class="feature-list">
            <div class="feature-item"><span class="dot"></span><div>Relación muchos-a-muchos entre mantenimientos y causas de fallo.</div></div>
            <div class="feature-item"><span class="dot"></span><div>Sistema de piezas utilizadas con control de stock y proveedores.</div></div>
            <div class="feature-item"><span class="dot"></span><div>Formulario de finalización con selección múltiple de causas y piezas.</div></div>
            <div class="feature-item"><span class="dot"></span><div>Vista índice con métricas agregadas: tiempo detenido, downtime y total.</div></div>
            <div class="feature-item"><span class="dot"></span><div>Endpoint AJAX para totales filtrados con actualización en tiempo real.</div></div>
            <div class="feature-item"><span class="dot"></span><div>Integración con sistema de alertas para mantenimientos programados.</div></div>
          </div>

          <div class="accordion">
            <div class="acc-item open">
              <div class="acc-header">¿Cómo calculo el downtime?</div>
              <div class="acc-content">El sistema calcula automáticamente: tiempo desde creación hasta inicio (parada antes de empezar), tiempo desde inicio hasta fin (downtime real), y tiempo total desde creación hasta resolución.</div>
            </div>
            <div class="acc-item">
              <div class="acc-header">¿Puedo filtrar por línea y fecha?</div>
              <div class="acc-content">Sí, DataTable completo con filtros por línea de producción, operario, usuario, fechas y tipos de mantenimiento. Los totales se recalculan automáticamente.</div>
            </div>
            <div class="acc-item">
              <div class="acc-header">¿Se integra con inventario?</div>
              <div class="acc-content">Totalmente. Cada mantenimiento registra piezas utilizadas, actualiza stock automáticamente y permite generar órdenes de reposición.</div>
            </div>
          </div>
        </div>

        <div class="ia-demo">
          <div class="maintenance-demo">
            <div class="maintenance-header">
              <div class="maintenance-title">Panel de Mantenimientos</div>
              <div class="maintenance-status">3 activos • 15 resueltos este mes</div>
            </div>
            <div class="maintenance-cards">
              <div class="maint-card active">
                <div class="maint-priority high">🔴 Alta</div>
                <div class="maint-info">
                  <h4>Línea 2 - Dosificación</h4>
                  <p>Motor vibrador defectuoso</p>
                  <div class="maint-time">Iniciado: 14:30 • Duración: 2h 15m</div>
                </div>
                <div class="maint-actions">
                  <button class="btn btn-small">Finalizar</button>
                </div>
              </div>
              <div class="maint-card scheduled">
                <div class="maint-priority medium">🟡 Media</div>
                <div class="maint-info">
                  <h4>Línea 1 - Pesaje</h4>
                  <p>Mantenimiento preventivo</p>
                  <div class="maint-time">Programado: 16:00 • Previsto: 1h 30m</div>
                </div>
                <div class="maint-actions">
                  <button class="btn btn-small">Iniciar</button>
                </div>
              </div>
            </div>
            <div class="maintenance-summary">
              <div class="summary-item">
                <span class="label">Tiempo detenido hoy:</span>
                <strong>4h 25m</strong>
              </div>
              <div class="summary-item">
                <span class="label">Disponibilidad mensual:</span>
                <strong>94.2%</strong>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="ia-footer">
        <div class="cta-row">
          <a class="btn btn-primary" href="#contacto">Optimizar mantenimiento</a>
          <a class="btn btn-secondary" href="#solucion">Ver métricas</a>
        </div>
      </div>
    </div>
  </section>

  <section id="calidad" class="section">
    <div class="container">
      <div class="ia-intro">
        <h2>✅ Control de Calidad (QC)</h2>
        <p class="subtitle">Sistema completo para gestionar incidencias de calidad y confirmaciones de QC en órdenes de producción, con trazabilidad total desde la detección hasta la resolución.</p>
      </div>

      <div class="ia-grid">
        <div class="ia-content">
          <div class="features-grid">
            <div class="feature">
              <h3>Incidencias de Calidad</h3>
              <p>Registra problemas de calidad detectados durante la producción con información detallada.</p>
            </div>
            <div class="feature">
              <h3>Confirmaciones de QC</h3>
              <p>Sistema de validación final por responsables de calidad antes de dar por terminada una orden.</p>
            </div>
            <div class="feature">
              <h3>Trazabilidad Completa</h3>
              <p>Vinculación directa entre incidencias de calidad y órdenes de producción específicas.</p>
            </div>
            <div class="feature">
              <h3>Badge de Estado</h3>
              <p>Indicadores visuales en cada orden: "QC confirmation done" o "QC confirmation pending".</p>
            </div>
          </div>

          <div class="stats-grid">
            <div class="stat-card"><div class="value">-30%</div><div class="label">Incidencias de calidad</div></div>
            <div class="stat-card"><div class="value">+95%</div><div class="label">Entregas a la primera</div></div>
            <div class="stat-card"><div class="value">-50%</div><div class="label">Tiempo de validación</div></div>
            <div class="stat-card"><div class="value">+100%</div><div class="label">Trazabilidad QC</div></div>
          </div>

          <div class="feature-list">
            <div class="feature-item"><span class="dot"></span><div>Registro de incidencias de calidad desde el tablero Kanban de producción.</div></div>
            <div class="feature-item"><span class="dot"></span><div>Sistema de confirmaciones QC vinculadas a órdenes originales y de producción.</div></div>
            <div class="feature-item"><span class="dot"></span><div>Badge visual en detalles de pedido: verde para confirmado, amarillo para pendiente.</div></div>
            <div class="feature-item"><span class="dot"></span><div>Relación con usuarios, líneas de producción y operarios responsables.</div></div>
            <div class="feature-item"><span class="dot"></span><div>Permisos basados en roles para control de acceso a funciones QC.</div></div>
            <div class="feature-item"><span class="dot"></span><div>Integración completa con el flujo de trabajo de producción existente.</div></div>
          </div>

          <div class="accordion">
            <div class="acc-item open">
              <div class="acc-header">¿Cómo funciona el flujo QC?</div>
              <div class="acc-content">1) Detecta incidencia desde Kanban → 2) Registra detalles y responsable → 3) Resuelve problema → 4) Responsable QC confirma validación → 5) Badge verde indica QC completado.</div>
            </div>
            <div class="acc-item">
              <div class="acc-header">¿Quién puede hacer confirmaciones QC?</div>
              <div class="acc-content">Usuarios con permisos específicos de QC pueden validar órdenes. El sistema registra quién, cuándo y qué orden confirmó para auditoría completa.</div>
            </div>
            <div class="acc-item">
              <div class="acc-header">¿Se integra con el proceso productivo?</div>
              <div class="acc-content">Totalmente integrado. Las órdenes no se consideran completamente terminadas hasta tener confirmación QC, manteniendo la trazabilidad del proceso.</div>
            </div>
          </div>
        </div>

        <div class="ia-demo">
          <div class="quality-demo">
            <div class="quality-header">
              <div class="quality-title">Panel de Control de Calidad</div>
              <div class="quality-status">12 confirmaciones • 3 pendientes</div>
            </div>
            <div class="quality-cards">
              <div class="qc-card confirmed">
                <div class="qc-status">✅ Confirmado</div>
                <div class="qc-info">
                  <h4>Pedido PO-2024-156</h4>
                  <p>Lote: 240915-001 • Línea 3</p>
                  <div class="qc-details">Confirmado por: M. García • 15/09/2024 16:45</div>
                </div>
              </div>
              <div class="qc-card pending">
                <div class="qc-status">⏳ Pendiente</div>
                <div class="qc-info">
                  <h4>Pedido PO-2024-157</h4>
                  <p>Lote: 240915-002 • Línea 1</p>
                  <div class="qc-details">Incidencia registrada: Tolerancia dimensional</div>
                </div>
                <div class="qc-actions">
                  <button class="btn btn-small">Confirmar QC</button>
                </div>
              </div>
            </div>
            <div class="quality-summary">
              <div class="summary-item">
                <span class="label">QC completado hoy:</span>
                <strong>8/12</strong>
              </div>
              <div class="summary-item">
                <span class="label">Tasa de aprobación:</span>
                <strong>94.7%</strong>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="ia-footer">
        <div class="cta-row">
          <a class="btn btn-primary" href="#contacto">Mejorar calidad</a>
          <a class="btn btn-secondary" href="#solucion">Ver proceso</a>
        </div>
      </div>
    </div>
  </section>

  <section id="whatsapp" class="section alt">
    <div class="container">
      <div class="ia-intro">
        <h2>💬 Notificaciones WhatsApp</h2>
        <p class="subtitle">Sistema de alertas automáticas por WhatsApp para incidencias, órdenes y mantenimientos. Mantén a tu equipo informado en tiempo real sin necesidad de estar en la planta.</p>
      </div>

      <div class="ia-grid">
        <div class="ia-content">
          <div class="features-grid">
            <div class="feature">
              <h3>Alertas de Incidencias</h3>
              <p>Recibe notificaciones automáticas cuando se mueven tarjetas a incidencias en el Kanban.</p>
            </div>
            <div class="feature">
              <h3>Finalizaciones Rápidas</h3>
              <p>Detecta órdenes finalizadas demasiado rápido que podrían indicar problemas de calidad.</p>
            </div>
            <div class="feature">
              <h3>Mantenimientos</h3>
              <p>Notificaciones sobre mantenimientos programados y emergencias en líneas de producción.</p>
            </div>
            <div class="feature">
              <h3>Configuración Flexible</h3>
              <p>Números de teléfono configurables por tipo de incidencia y nivel de urgencia.</p>
            </div>
          </div>

          <div class="stats-grid">
            <div class="stat-card"><div class="value">-60%</div><div class="label">Tiempo de respuesta</div></div>
            <div class="stat-card"><div class="value">+90%</div><div class="label">Incidencias detectadas</div></div>
            <div class="stat-card"><div class="value">-80%</div><div class="label">Comunicaciones manuales</div></div>
            <div class="stat-card"><div class="value">+100%</div><div class="label">Disponibilidad 24/7</div></div>
          </div>

          <div class="feature-list">
            <div class="feature-item"><span class="dot"></span><div>Alertas automáticas por WhatsApp para incidencias de órdenes de producción.</div></div>
            <div class="feature-item"><span class="dot"></span><div>Detección de finalizaciones "demasiado rápidas" con umbral configurable.</div></div>
            <div class="feature-item"><span class="dot"></span><div>Notificaciones de mantenimientos programados y emergencias.</div></div>
            <div class="feature-item"><span class="dot"></span><div>Configuración de números separados por tipo: incidencias, mantenimientos, etc.</div></div>
            <div class="feature-item"><span class="dot"></span><div>Integración con sistema de alertas proactivas de la IA.</div></div>
            <div class="feature-item"><span class="dot"></span><div>Historial completo de notificaciones enviadas y recibidas.</div></div>
          </div>

          <div class="accordion">
            <div class="acc-item open">
              <div class="acc-header">¿Qué tipos de alertas envía?</div>
              <div class="acc-content">Incidencias de producción, finalizaciones sospechosas (demasiado rápidas), mantenimientos programados y emergencias, y alertas proactivas de la IA sobre posibles problemas.</div>
            </div>
            <div class="acc-item">
              <div class="acc-header">¿Puedo configurar quién recibe qué?</div>
              <div class="acc-content">Sí, números separados para incidencias de órdenes, mantenimientos y alertas generales. Cada tipo tiene su lista de destinatarios configurable.</div>
            </div>
            <div class="acc-item">
              <div class="acc-header">¿Funciona 24/7?</div>
              <div class="acc-content">Totalmente. El sistema de notificaciones funciona continuamente, incluso fuera del horario laboral, para emergencias críticas.</div>
            </div>
          </div>
        </div>

        <div class="ia-demo">
          <div class="whatsapp-demo">
            <div class="whatsapp-header">
              <div class="whatsapp-title">Alertas WhatsApp</div>
              <div class="whatsapp-status">🟢 Activo • 3 números configurados</div>
            </div>
            <div class="whatsapp-messages">
              <div class="whatsapp-msg">
                <div class="msg-time">14:32</div>
                <div class="msg-content">
                  <strong>🚨 ALERTA ORDEN (tarjeta pasada a incidencias):</strong><br>
                  Centro: Fábrica Principal<br>
                  Línea: Línea 2 - Dosificación<br>
                  OrderID: PO-2024-157<br>
                  Status: Incidencia<br>
                  Fecha: 15/09/2024 14:32
                </div>
              </div>
              <div class="whatsapp-msg">
                <div class="msg-time">14:45</div>
                <div class="msg-content">
                  <strong>⚠️ ALERTA ORDEN (posible incidencia - menos de 60s en curso):</strong><br>
                  Centro: Fábrica Principal<br>
                  Línea: Línea 1 - Pesaje<br>
                  OrderID: PO-2024-158<br>
                  Status: Finalizada<br>
                  Tiempo en curso: 45 segundos<br>
                  Fecha: 15/09/2024 14:45
                </div>
              </div>
              <div class="whatsapp-msg">
                <div class="msg-time">15:00</div>
                <div class="msg-content">
                  <strong>🔧 MANTENIMIENTO PROGRAMADO:</strong><br>
                  Línea: Línea 3 - Mezclado<br>
                  Tipo: Preventivo mensual<br>
                  Programado: 15/09/2024 16:00<br>
                  Duración estimada: 2 horas
                </div>
              </div>
            </div>
            <div class="whatsapp-config">
              <div class="config-item">
                <span class="label">Incidencias de Órdenes:</span>
                <span class="value">+34 600 123 456, +34 600 123 457</span>
              </div>
              <div class="config-item">
                <span class="label">Mantenimientos:</span>
                <span class="value">+34 600 123 458</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="ia-footer">
        <div class="cta-row">
          <a class="btn btn-primary" href="#contacto">Configurar alertas</a>
          <a class="btn btn-secondary" href="#solucion">Ver ejemplos</a>
        </div>
      </div>
    </div>
  </section>

  <section id="erp" class="section">
    <div class="container">
      <div class="ia-intro">
        <h2>🔄 Integración ERP y Callbacks</h2>
        <p class="subtitle">Sistema robusto de notificaciones automáticas a sistemas ERP externos. Notifica cambios de estado de órdenes en tiempo real con mapeo de campos y sistema de reintentos.</p>
      </div>

      <div class="ia-grid">
        <div class="ia-content">
          <div class="features-grid">
            <div class="feature">
              <h3>Notificaciones HTTP</h3>
              <p>Envío automático de callbacks HTTP a URLs configuradas cuando órdenes cambian de estado.</p>
            </div>
            <div class="feature">
              <h3>Mapeo de Campos</h3>
              <p>Transforma datos de Sensorica a formato ERP con mapeos configurables por cliente.</p>
            </div>
            <div class="feature">
              <h3>Sistema de Reintentos</h3>
              <p>Reintentos automáticos con backoff exponencial para garantizar entrega de notificaciones.</p>
            </div>
            <div class="feature">
              <h3>Historial Completo</h3>
              <p>Registro detallado de todos los callbacks enviados con estados, respuestas y errores.</p>
            </div>
          </div>

          <div class="stats-grid">
            <div class="stat-card"><div class="value">+100%</div><div class="label">Fiabilidad entrega</div></div>
            <div class="stat-card"><div class="value">-90%</div><div class="label">Errores manuales</div></div>
            <div class="stat-card"><div class="value">-50%</div><div class="label">Tiempo de sincronización</div></div>
            <div class="stat-card"><div class="value">+95%</div><div class="label">Disponibilidad servicio</div></div>
          </div>

          <div class="feature-list">
            <div class="feature-item"><span class="dot"></span><div>Envío automático de notificaciones HTTP a sistemas ERP externos.</div></div>
            <div class="feature-item"><span class="dot"></span><div>Mapeo configurable de campos entre Sensorica y formato ERP.</div></div>
            <div class="feature-item"><span class="dot"></span><div>Transformaciones dinámicas: trim, uppercase, lowercase, number, date, to_boolean.</div></div>
            <div class="feature-item"><span class="dot"></span><div>Sistema de reintentos con backoff exponencial (hasta 20 intentos).</div></div>
            <div class="feature-item"><span class="dot"></span><div>Historial completo con estados, payloads y respuestas HTTP.</div></div>
            <div class="feature-item"><span class="dot"></span><div>Procesamiento en segundo plano con Supervisor para alta disponibilidad.</div></div>
          </div>

          <div class="accordion">
            <div class="acc-item open">
              <div class="acc-header">¿Cómo funciona la integración?</div>
              <div class="acc-content">Cuando una orden cambia de estado, el sistema crea automáticamente un callback que se procesa en segundo plano. Aplica mapeos configurados, envía HTTP POST y reintenta si falla.</div>
            </div>
            <div class="acc-item">
              <div class="acc-header">¿Qué datos se envían?</div>
              <div class="acc-content">Información completa de la orden: ID, procesos, estado, timestamps, y cualquier campo adicional configurado en los mapeos personalizados por cliente.</div>
            </div>
            <div class="acc-item">
              <div class="acc-header">¿Es seguro?</div>
              <div class="acc-content">Totalmente. Control de accesos por roles, logs de auditoría, y comunicación cifrada. Solo se envían los datos autorizados según configuración.</div>
            </div>
          </div>
        </div>

        <div class="ia-demo">
          <div class="erp-demo">
            <div class="erp-header">
              <div class="erp-title">Sistema de Callbacks</div>
              <div class="erp-status">🟢 145 enviados • 0 fallidos</div>
            </div>
            <div class="erp-flow">
              <div class="flow-step">
                <div class="step-number">1</div>
                <div class="step-content">
                  <h4>Orden Cambia Estado</h4>
                  <p>Orden PO-2024-157 → Finalizada</p>
                </div>
              </div>
              <div class="flow-arrow">↓</div>
              <div class="flow-step">
                <div class="step-number">2</div>
                <div class="step-content">
                  <h4>Aplicar Mapeos</h4>
                  <p>Transformar campos según configuración cliente</p>
                </div>
              </div>
              <div class="flow-arrow">↓</div>
              <div class="flow-step">
                <div class="step-number">3</div>
                <div class="step-content">
                  <h4>Enviar HTTP POST</h4>
                  <p>https://erp.cliente.com/api/orders/update</p>
                </div>
              </div>
              <div class="flow-arrow">↓</div>
              <div class="flow-step">
                <div class="step-number">4</div>
                <div class="step-content">
                  <h4>Confirmar Recepción</h4>
                  <p>Status: 200 OK • ERP actualizado</p>
                </div>
              </div>
            </div>
            <div class="erp-config">
              <div class="config-item">
                <span class="label">URL ERP:</span>
                <span class="value">https://erp.cliente.com/api/callbacks</span>
              </div>
              <div class="config-item">
                <span class="label">Mapeos activos:</span>
                <span class="value">8 campos configurados</span>
              </div>
              <div class="config-item">
                <span class="label">Máx. reintentos:</span>
                <span class="value">20</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="ia-footer">
        <div class="cta-row">
          <a class="btn btn-primary" href="#contacto">Conectar ERP</a>
          <a class="btn btn-secondary" href="#solucion">Ver API</a>
        </div>
      </div>
    </div>
  </section>

  <section id="tecnica" class="section alt">
    <div class="container">
      <div class="ia-intro">
        <h2>⚙️ Arquitectura Técnica</h2>
        <p class="subtitle">Sistema robusto y escalable construido con tecnologías modernas. Arquitectura de microservicios con alta disponibilidad y tolerancia a fallos.</p>
      </div>

      <div class="ia-grid">
        <div class="ia-content">
          <div class="features-grid">
            <div class="feature">
              <h3>Backend Laravel</h3>
              <p>Framework robusto con arquitectura MVC, API RESTful y sistema de colas para procesamiento asíncrono.</p>
            </div>
            <div class="feature">
              <h3>Servicios Node.js</h3>
              <p>Servidores especializados para MQTT, RFID, Modbus/SCADA y transformaciones de datos en tiempo real.</p>
            </div>
            <div class="feature">
              <h3>Base de Datos</h3>
              <p>Percona Server for MySQL con optimizaciones para alta concurrencia y consultas complejas.</p>
            </div>
            <div class="feature">
              <h3>Comunicación IoT</h3>
              <p>Protocolos MQTT, Modbus RTU/TCP, RS-485 y WebSockets para integración con dispositivos industriales.</p>
            </div>
          </div>

          <div class="stats-grid">
            <div class="stat-card"><div class="value">+99.9%</div><div class="label">Disponibilidad</div></div>
            <div class="stat-card"><div class="value">1000+</div><div class="label">Mensajes/minuto</div></div>
            <div class="stat-card"><div class="value">-50ms</div><div class="label">Latencia MQTT</div></div>
            <div class="stat-card"><div class="value">24/7</div><div class="label">Operación continua</div></div>
          </div>

          <div class="feature-list">
            <div class="feature-item"><span class="dot"></span><div><strong>Servicios en Segundo Plano:</strong> 8 comandos Artisan + 5 servidores Node.js gestionados por Supervisor.</div></div>
            <div class="feature-item"><span class="dot"></span><div><strong>Arquitectura de Dos Etapas:</strong> Procesamiento batch para miles de mensajes MQTT por minuto.</div></div>
            <div class="feature-item"><span class="dot"></span><div><strong>Integración Industrial:</strong> Modbus RTU/TCP, RS-485, básculas, dosificadores y PLCs.</div></div>
            <div class="feature-item"><span class="dot"></span><div><strong>Sistema RFID Completo:</strong> Gateway MQTT-RFID con WebSockets para seguimiento en tiempo real.</div></div>
            <div class="feature-item"><span class="dot"></span><div><strong>Transformación de Sensores:</strong> Algoritmos configurables para normalizar lecturas industriales.</div></div>
            <div class="feature-item"><span class="dot"></span><div><strong>Alta Disponibilidad:</strong> Reconexión automática, reintentos y balanceo de carga.</div></div>
          </div>

          <div class="accordion">
            <div class="acc-item open">
              <div class="acc-header">¿Cómo maneja miles de mensajes por minuto?</div>
              <div class="acc-content">Arquitectura de dos etapas: comandos PHP escriben a archivos JSON, servidores Node.js leen y envían por lotes a MQTT. Evita saturar el broker y garantiza entrega ordenada.</div>
            </div>
            <div class="acc-item">
              <div class="acc-header">¿Qué protocolos industriales soporta?</div>
              <div class="acc-content">MQTT (alta carga), Modbus RTU/TCP, RS-485 para básculas y dosificadores, integración SCADA, y protocolos propietarios vía configuración flexible.</div>
            </div>
            <div class="acc-item">
              <div class="acc-header">¿Es escalable?</div>
              <div class="acc-content">Totalmente. Arquitectura de microservicios permite escalar componentes independientes. Supervisor gestiona procesos críticos con reinicio automático.</div>
            </div>
          </div>
        </div>

        <div class="ia-demo">
          <div class="architecture-demo">
            <div class="architecture-header">
              <div class="architecture-title">Arquitectura del Sistema</div>
              <div class="architecture-status">🟢 Todos los servicios activos</div>
            </div>
            <div class="architecture-diagram">
              <div class="arch-layer">
                <div class="layer-title">Frontend</div>
                <div class="layer-items">
                  <span class="item">Kanban SPA</span>
                  <span class="item">Dashboard OEE</span>
                  <span class="item">Panel RFID</span>
                  <span class="item">Planificador Rutas</span>
                </div>
              </div>
              <div class="arch-arrow">↓ WebSockets / HTTP</div>
              <div class="arch-layer">
                <div class="layer-title">Backend Laravel</div>
                <div class="layer-items">
                  <span class="item">API REST</span>
                  <span class="item">WebSockets</span>
                  <span class="item">Queue System</span>
                  <span class="item">Database ORM</span>
                </div>
              </div>
              <div class="arch-arrow">↓ MQTT / Modbus</div>
              <div class="arch-layer">
                <div class="layer-title">Servicios Node.js</div>
                <div class="layer-items">
                  <span class="item">MQTT Gateway</span>
                  <span class="item">RFID Processor</span>
                  <span class="item">Modbus Client</span>
                  <span class="item">Sensor Transformer</span>
                </div>
              </div>
              <div class="arch-arrow">↓ Serial / TCP</div>
              <div class="arch-layer">
                <div class="layer-title">Dispositivos Industriales</div>
                <div class="layer-items">
                  <span class="item">PLC Siemens</span>
                  <span class="item">Básculas RS-485</span>
                  <span class="item">Lectores RFID</span>
                  <span class="item">Dosificadores</span>
                </div>
              </div>
            </div>
            <div class="architecture-services">
              <h4>Servicios Activos</h4>
              <div class="service-item active">🟢 CalculateProductionMonitorOeev2</div>
              <div class="service-item active">🟢 MqttSubscriberLocal</div>
              <div class="service-item active">🟢 CheckOrdersFromApi</div>
              <div class="service-item active">🟢 ReadSensors</div>
              <div class="service-item active">🟢 ReadRfidReadings</div>
              <div class="service-item active">🟢 ReadModbus</div>
              <div class="service-item active">🟢 ConnectWhatsApp</div>
              <div class="service-item active">🟢 SensorTransformer</div>
            </div>
          </div>
        </div>
      </div>

      <div class="ia-footer">
        <div class="cta-row">
          <a class="btn btn-primary" href="#contacto">Arquitectura personalizada</a>
          <a class="btn btn-secondary" href="#solucion">Ver especificaciones</a>
        </div>
      </div>
    </div>
  </section>

  <section id="por-que" class="section alt">
    <div class="container">
      <h2>¿Por qué Xmart?</h2>
      <div class="features-grid">
        <div class="feature">
          <h3>Ahorra tiempo</h3>
          <p>Menos llamadas y papel. Un mismo tablero al que todos acceden.</p>
        </div>
        <div class="feature">
          <h3>Reduce paradas</h3>
          <p>Prioridades claras y alertas tempranas para actuar a tiempo.</p>
        </div>
        <div class="feature">
          <h3>Mejora calidad</h3>
          <p>Incidencias visibles, responsable asignado y seguimiento.</p>
        </div>
        <div class="feature">
          <h3>Equipo alineado</h3>
          <p>Producción, ingeniería y dirección mirando los mismos objetivos.</p>
        </div>
      </div>
    </div>
  </section>

  <section id="testimonios" class="section">
    <div class="container">
      <h2>Lo que dicen nuestros clientes</h2>
      <div class="cards">
        <div class="card2">
          <p>“Pasamos de gestionar por WhatsApp a tener todo en un tablero. Las paradas bajaron y cumplimos fechas.”</p>
          <small>Director de Operaciones, Textil</small>
        </div>
        <div class="card2">
          <p>“Los turnos ven objetivos claros y cada día medimos igual. Las discusiones se convierten en acciones.”</p>
          <small>Jefe de Planta, Alimentación</small>
        </div>
        <div class="card2">
          <p>“Integrar puestos y pesajes fue sencillo. Ahora sabemos dónde mejorar sin adivinar.”</p>
          <small>Responsable de Mejora Continua, Químico</small>
        </div>
      </div>
    </div>
  </section>

  <section id="faq" class="section alt">
    <div class="container">
      <h2>Preguntas frecuentes</h2>
      <div class="faq">
        <details>
          <summary>¿En cuánto tiempo podemos arrancar?</summary>
          <p>Normalmente en semanas. Empezamos por una línea piloto y escalamos.</p>
        </details>
        <details>
          <summary>¿Necesito cambiar mis sistemas?</summary>
          <p>No. Nos adaptamos a tu forma de trabajar y datos actuales.</p>
        </details>
        <details>
          <summary>¿Hay formación para el equipo?</summary>
          <p>Sí, acompañamos el despliegue y la adopción con sesiones prácticas.</p>
        </details>
      </div>
    </div>
  </section>

  <section id="planes" class="section cta">
    <div class="container cta-inner">
      <h2>Planes a tu medida</h2>
      <p>Cuéntanos tu tamaño y necesidades. Preparamos una propuesta clara y escalable.</p>
      <div class="form-actions">
        <a class="btn btn-light" href="#contacto">Solicitar presupuesto</a>
        <a class="btn btn-secondary" href="#solucion">Ver cómo funciona</a>
      </div>
    </div>
  </section>

  <section id="solucion" class="section">
    <div class="container">
      <h2>¿Qué hace Xmart por tu fábrica?</h2>
      <div class="features-grid">
        <div class="feature">
          <h3>Organizador de pedidos</h3>
          <p>Prioriza pedidos por cliente y fecha. Visualiza el flujo y evita cuellos de botella.</p>
        </div>
        <div class="feature">
          <h3>Órdenes de trabajo</h3>
          <p>Desglosa por procesos y líneas. Arrastra tareas y comunica cambios al instante.</p>
        </div>
        <div class="feature">
          <h3>Control de máquinas</h3>
          <p>Supervisa estado y tiempos. Detecta paradas y actúa rápido.</p>
        </div>
        <div class="feature">
          <h3>Gestión de operarios</h3>
          <p>Asigna puestos y turnos. Claridad para todos, sin fricción.</p>
        </div>
        <div class="feature">
          <h3>Producción en tiempo real</h3>
          <p>Tablero vivo en planta para decidir con información actualizada.</p>
        </div>
        <div class="feature">
          <h3>Sistema RFID</h3>
          <p>Trazabilidad simple por colores y registros de paso.</p>
        </div>
        <div class="feature">
          <h3>Control de producción</h3>
          <p>Objetivos, avances y cumplimiento por turno y por orden.</p>
        </div>
        <div class="feature">
          <h3>Dosificación y básculas</h3>
          <p>Confianza en los pesos y la mezcla. Evita retrabajos.</p>
        </div>
      </div>
    </div>
  </section>

  <section id="indicadores" class="section">
    <div class="container">
      <h2>Indicadores clave</h2>
      <div class="cards">
        <div class="card2">
          <h4>Disponibilidad</h4>
          <p>Minimiza paradas y aprovecha mejor cada turno.</p>
        </div>
        <div class="card2">
          <h4>Rendimiento</h4>
          <p>Produce más en el mismo tiempo manteniendo la estabilidad.</p>
        </div>
        <div class="card2">
          <h4>Calidad</h4>
          <p>Menos mermas y retrabajos. Más entregas a la primera.</p>
        </div>
        <div class="card2">
          <h4>Paradas</h4>
          <p>Identifica causas y reduce su impacto con acciones simples.</p>
        </div>
        <div class="card2">
          <h4>Tiempos teóricos vs reales</h4>
          <p>Ajusta ritmos y expectativas con datos del día a día.</p>
        </div>
        <div class="card2">
          <h4>Prioridades</h4>
          <p>Todos alineados con lo que más importa cada jornada.</p>
        </div>
        <div class="card2">
          <h4>Trazabilidad</h4>
          <p>RFID y registro de eventos para saber qué pasó y cuándo.</p>
        </div>
      </div>
    </div>
  </section>

  <section id="beneficios" class="section alt">
    <div class="container">
      <h2>Beneficios clave</h2>
      <div class="benefits-grid">
        <div class="benefit">
          <h4>Tiempo real</h4>
          <p>De sensores a tablero en milisegundos con colas y tolerancia a fallos.</p>
        </div>
        <div class="benefit">
          <h4>Escalable</h4>
          <p>Empieza por una línea y crece a toda la planta sin complicaciones.</p>
        </div>
        <div class="benefit">
          <h4>Operable</h4>
          <p>Supervisor controla procesos críticos; observabilidad y reinicios automáticos.</p>
        </div>
        <div class="benefit">
          <h4>Adaptable</h4>
          <p>Se ajusta a tus procesos y sistemas actuales. Sin interrupciones.</p>
        </div>
        <div class="benefit">
          <h4>Trazable</h4>
          <p>RFID, notas e incidencias vinculadas a órdenes y procesos.</p>
        </div>
        <div class="benefit">
          <h4>Seguro</h4>
          <p>Control de accesos por perfiles y buenas prácticas de protección de datos.</p>
        </div>
      </div>
    </div>
  </section>

  <section id="sectores" class="section">
    <div class="container">
      <h2>Hecho para tu sector</h2>
      <div class="integrations">
        <div class="pill">Alimentación</div>
        <div class="pill">Textil</div>
        <div class="pill">Químico</div>
        <div class="pill">Automoción</div>
        <div class="pill">Packaging</div>
        <div class="pill">Plástico</div>
        <div class="pill">Madera</div>
        <div class="pill">Piedra</div>
        <div class="pill">Vidrio</div>
        <div class="pill">Otros</div>
      </div>
      <p class="muted">Hablamos tu idioma. Adaptamos Xmart a tus procesos reales.</p>
    </div>
  </section>

  <section id="resultados" class="section alt">
    <div class="container two-col">
      <div>
        <h2>Resultados que importan</h2>
        <p>Menos paradas, más rendimiento y mayor calidad. Comparte objetivos por turno y celebra mejoras visibles.</p>
        <ul class="checklist">
          <li>Avance por turno y orden</li>
          <li>KPIs claros para todo el equipo</li>
          <li>Gestión simple de incidencias</li>
        </ul>
      </div>
      <div class="panel">
        <div class="panel-head">Ejemplo de KPIs</div>
        <div class="panel-body">
          <div class="kpi">
            <span>OEE</span>
            <strong>86.4%</strong>
          </div>
          <div class="kpi">
            <span>Disponibilidad</span>
            <strong>91.2%</strong>
          </div>
          <div class="kpi">
            <span>Rendimiento</span>
            <strong>88.0%</strong>
          </div>
          <div class="kpi">
            <span>Calidad</span>
            <strong>96.1%</strong>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="casos" class="section">
    <div class="container">
      <h2>Casos de uso</h2>
      <div class="cards">
        <a class="card-link" href="#contacto">
          <div class="card2">
            <h4>Arranque de línea</h4>
            <p>Planifica, asigna y comunica prioridades al instante.</p>
          </div>
        </a>
        <a class="card-link" href="#contacto">
          <div class="card2">
            <h4>Control de calidad</h4>
            <p>Registra incidencias y mejora tus estándares.</p>
          </div>
        </a>
        <a class="card-link" href="#contacto">
          <div class="card2">
            <h4>Balanceo de carga</h4>
            <p>Mejora flujo y rendimiento manteniendo la calidad.</p>
          </div>
        </a>
      </div>
    </div>
  </section>

  <section id="contacto" class="section cta">
    <div class="container cta-inner">
      <h2>Solicita tu presupuesto en 2 minutos</h2>
      <p>Te respondemos en menos de 24h laborables. Sin compromiso.</p>

      <div class="badges">
        <span class="badge2">⚡ Implementación rápida</span>
        <span class="badge2">🤝 Acompañamiento experto</span>
        <span class="badge2">🎯 Demo incluida</span>
      </div>

      <div class="form-card">
        <h3>Cuéntanos sobre tu planta</h3>
        <form id="quote-form" class="quote-form">
          <div class="form-row">
            <input type="text" name="nombre" placeholder="Nombre y apellidos" required />
            <input type="email" name="email" placeholder="Email" required />
          </div>
          <div class="form-row">
            <input type="text" name="empresa" placeholder="Empresa" required />
            <input type="tel" name="telefono" placeholder="Teléfono" />
          </div>
          <div class="form-row">
            <textarea name="mensaje" rows="4" placeholder="Líneas, turnos, objetivos y retos actuales"></textarea>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-light">Solicitar presupuesto</button>
          </div>
          <p class="note">Al enviar, abriremos un borrador de email con tus datos. También puedes escribirnos a <a href="mailto:contacto@xmart-industria.com">contacto@xmart-industria.com</a>.</p>
        </form>
      </div>

      <div class="alt-contacts">
        <a class="btn btn-secondary" href="https://wa.me/34600000000" target="_blank">💬 Hablar por WhatsApp</a>
        <a class="btn btn-secondary" href="#planes">Ver planes</a>
      </div>
    </div>
  </section>

  <footer class="site-footer">
    <div class="container footer-inner">
      <div>© <span id="year"></span> Xmart</div>
      <div class="footer-links">
        <a href="../README.md" target="_blank">Documentación</a>
        <a href="#">Términos</a>
        <a href="#">Privacidad</a>
      </div>
    </div>
  </footer>

  <script src="script.js"></script>
</body>
</html>
