/**
 * Sidebar Toggle Functionality
 * Activa la funcionalidad de minimización del sidebar
 */

// Función principal que se ejecuta cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('Sidebar Toggle Script cargado');
    
    // Seleccionar el botón minimizador
    const minimizer = document.getElementById('sidebar-minimizer');
    console.log('Botón minimizador encontrado:', minimizer);
    
    if (minimizer) {
        // Marcar el botón para verificar que el script lo encontró
        minimizer.setAttribute('data-script-loaded', 'true');
        
        // Añadir evento de clic para alternar la clase minimenu en el body
        minimizer.addEventListener('click', function(event) {
            // Prevenir comportamiento por defecto del botón
            event.preventDefault();
            
            console.log('Botón minimizador clickeado');
            
            // Toggle de la clase dash-minimenu en el body
            document.body.classList.toggle('dash-minimenu');
            
            // Cambiar el icono según el estado
            const icon = minimizer.querySelector('i');
            if (icon) {
                if (document.body.classList.contains('dash-minimenu')) {
                    icon.className = 'ti ti-chevron-right';
                } else {
                    icon.className = 'ti ti-chevron-left';
                }
            }
        });
        
        console.log('Evento de clic añadido al botón minimizador');
    } else {
        console.error('No se encontró el botón minimizador con id "sidebar-minimizer"');
    }
});

// También intentar ejecutar si el DOM ya está cargado
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    const minimizer = document.getElementById('sidebar-minimizer');
    if (minimizer && !minimizer.hasAttribute('data-script-loaded')) {
        console.log('DOM ya cargado, configurando botón minimizador inmediatamente');
        
        minimizer.setAttribute('data-script-loaded', 'true');
        
        minimizer.addEventListener('click', function(event) {
            event.preventDefault();
            document.body.classList.toggle('dash-minimenu');
            
            const icon = minimizer.querySelector('i');
            if (icon) {
                if (document.body.classList.contains('dash-minimenu')) {
                    icon.className = 'ti ti-chevron-right';
                } else {
                    icon.className = 'ti ti-chevron-left';
                }
            }
        });
    }
}

