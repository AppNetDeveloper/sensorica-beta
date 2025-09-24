#!/bin/bash

# Cloudflare Tunnel Monitor Script
# Monitorea el estado del túnel de Cloudflare y lo reinicia si es necesario
# Autor: Sistema Sensorica
# Fecha: $(date '+%Y-%m-%d')

# Configuración
TUNNEL_SERVICE="cloudflared.service"
LOG_FILE="/var/log/cloudflare-tunnel-monitor.log"
MAX_LOG_SIZE=10485760  # 10MB en bytes
BACKUP_LOG_FILE="/var/log/cloudflare-tunnel-monitor.log.old"

# Función para logging con timestamp
log_message() {
    local level="$1"
    local message="$2"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[$timestamp] [$level] $message" | tee -a "$LOG_FILE"
}

# Función para rotar logs si son muy grandes
rotate_logs() {
    if [[ -f "$LOG_FILE" ]] && [[ $(stat -c%s "$LOG_FILE") -gt $MAX_LOG_SIZE ]]; then
        log_message "INFO" "Rotando logs - tamaño excedido"
        mv "$LOG_FILE" "$BACKUP_LOG_FILE"
        touch "$LOG_FILE"
        chmod 644 "$LOG_FILE"
    fi
}

# Función para verificar si el servicio está activo
check_service_status() {
    systemctl is-active "$TUNNEL_SERVICE" >/dev/null 2>&1
    return $?
}

# Función para verificar si el servicio está habilitado
check_service_enabled() {
    systemctl is-enabled "$TUNNEL_SERVICE" >/dev/null 2>&1
    return $?
}

# Función para verificar conectividad del túnel
check_tunnel_connectivity() {
    # Verificar si cloudflared está respondiendo
    local pid=$(pgrep -f "cloudflared.*tunnel")
    if [[ -z "$pid" ]]; then
        return 1
    fi
    
    # Verificar si hay conexiones establecidas (simplificado)
    # Por ahora solo verificamos que el proceso esté ejecutándose
    # En futuras versiones se puede mejorar la verificación de conectividad
    return 0
}

# Función para reiniciar el servicio
restart_tunnel_service() {
    log_message "WARN" "Reiniciando servicio $TUNNEL_SERVICE"
    
    # Parar el servicio
    systemctl stop "$TUNNEL_SERVICE"
    sleep 5
    
    # Verificar que se detuvo
    if check_service_status; then
        log_message "ERROR" "No se pudo detener el servicio, forzando kill"
        pkill -f "cloudflared.*tunnel"
        sleep 3
    fi
    
    # Iniciar el servicio
    systemctl start "$TUNNEL_SERVICE"
    sleep 10
    
    # Verificar que se inició correctamente
    if check_service_status; then
        log_message "INFO" "Servicio $TUNNEL_SERVICE reiniciado exitosamente"
        return 0
    else
        log_message "ERROR" "Falló el reinicio del servicio $TUNNEL_SERVICE"
        return 1
    fi
}

# Función para habilitar el servicio si no está habilitado
enable_service() {
    if ! check_service_enabled; then
        log_message "WARN" "Servicio $TUNNEL_SERVICE no está habilitado, habilitando..."
        systemctl enable "$TUNNEL_SERVICE"
        if check_service_enabled; then
            log_message "INFO" "Servicio $TUNNEL_SERVICE habilitado exitosamente"
        else
            log_message "ERROR" "No se pudo habilitar el servicio $TUNNEL_SERVICE"
        fi
    fi
}

# Función principal de monitoreo
monitor_tunnel() {
    local restart_needed=false
    local restart_reason=""
    
    # Verificar si el servicio está habilitado
    enable_service
    
    # Verificar estado del servicio
    if ! check_service_status; then
        restart_needed=true
        restart_reason="Servicio no está activo"
    fi
    
    # Verificar conectividad del túnel
    if ! check_tunnel_connectivity; then
        restart_needed=true
        if [[ -z "$restart_reason" ]]; then
            restart_reason="Túnel sin conectividad"
        else
            restart_reason="$restart_reason y sin conectividad"
        fi
    fi
    
    # Reiniciar si es necesario
    if [[ "$restart_needed" == true ]]; then
        log_message "WARN" "Problema detectado: $restart_reason"
        restart_tunnel_service
    else
        log_message "INFO" "Túnel Cloudflare funcionando correctamente"
    fi
}

# Función para mostrar estado del sistema
show_status() {
    echo "=== Estado del Túnel Cloudflare ==="
    echo "Servicio activo: $(systemctl is-active $TUNNEL_SERVICE)"
    echo "Servicio habilitado: $(systemctl is-enabled $TUNNEL_SERVICE)"
    echo "PID del proceso: $(pgrep -f 'cloudflared.*tunnel' || echo 'No encontrado')"
    echo "Conexiones HTTPS: $(ss -an 2>/dev/null | grep -c ':443.*ESTAB' 2>/dev/null || echo '0')"
    echo "Última verificación: $(date '+%Y-%m-%d %H:%M:%S')"
    echo "=================================="
}

# Función principal
main() {
    # Rotar logs si es necesario
    rotate_logs
    
    case "${1:-monitor}" in
        "monitor")
            monitor_tunnel
            ;;
        "status")
            show_status
            ;;
        "restart")
            log_message "INFO" "Reinicio manual solicitado"
            restart_tunnel_service
            ;;
        "enable")
            enable_service
            ;;
        *)
            echo "Uso: $0 [monitor|status|restart|enable]"
            echo "  monitor  - Monitorear y reiniciar si es necesario (por defecto)"
            echo "  status   - Mostrar estado actual"
            echo "  restart  - Forzar reinicio del túnel"
            echo "  enable   - Habilitar el servicio"
            exit 1
            ;;
    esac
}

# Ejecutar función principal
main "$@"
