// Dashboard Animations & Modern KPIs

class DashboardAnimations {
    constructor() {
        this.init();
    }

    init() {
        this.setupCounters();
        this.setupSparklines();
        this.setupAutoRefresh();
        this.setupDragAndDrop();
    }

    // KPI Counter Animation
    animateCounter(element, targetValue, duration = 2000, suffix = '') {
        const startValue = 0;
        const startTime = performance.now();
        
        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Easing function for smooth animation
            const easeOutQuart = 1 - Math.pow(1 - progress, 4);
            const currentValue = Math.round(startValue + (targetValue - startValue) * easeOutQuart);
            
            element.textContent = currentValue + suffix;
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                // Add completion effect
                element.classList.add('counter-complete');
                setTimeout(() => element.classList.remove('counter-complete'), 300);
            }
        };
        
        requestAnimationFrame(animate);
    }

    // Sparkline Generator
    createSparkline(container, data, color = '#667eea') {
        const width = container.offsetWidth || 100;
        const height = 30;
        const max = Math.max(...data);
        const min = Math.min(...data);
        const range = max - min || 1;
        
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('width', width);
        svg.setAttribute('height', height);
        svg.classList.add('sparkline');
        
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        
        // Generate path data
        const points = data.map((value, index) => {
            const x = (index / (data.length - 1)) * width;
            const y = height - ((value - min) / range) * height;
            return `${index === 0 ? 'M' : 'L'} ${x} ${y}`;
        }).join(' ');
        
        path.setAttribute('d', points);
        path.setAttribute('stroke', color);
        path.setAttribute('stroke-width', 2);
        path.setAttribute('fill', 'none');
        path.setAttribute('stroke-linecap', 'round');
        path.setAttribute('stroke-linejoin', 'round');
        
        // Add animation
        const length = path.getTotalLength();
        path.style.strokeDasharray = length;
        path.style.strokeDashoffset = length;
        path.style.animation = 'drawLine 1.5s ease-out forwards';
        
        svg.appendChild(path);
        container.appendChild(svg);
    }

    // Gauge Chart Animation
    createGaugeChart(container, value, max = 100) {
        const percentage = (value / max) * 100;
        const rotation = (percentage / 100) * 180 - 90; // -90 to 90 degrees
        
        const gaugeHTML = `
            <div class="gauge-chart">
                <svg width="200" height="100" viewBox="0 0 200 100">
                    <path d="M 20 80 A 60 60 0 0 1 180 80" 
                          stroke="#e0e0e0" 
                          stroke-width="10" 
                          fill="none"/>
                    <path d="M 20 80 A 60 60 0 0 1 180 80" 
                          stroke="url(#gradient)" 
                          stroke-width="10" 
                          fill="none"
                          stroke-dasharray="${percentage * 2.513}" 
                          stroke-dashoffset="0"/>
                    <line class="gauge-needle" 
                          x1="100" y1="80" 
                          x2="100" y2="30"
                          stroke="#333"
                          stroke-width="3"
                          stroke-linecap="round"
                          style="--target-rotation: ${rotation}deg"/>
                    <text x="100" y="95" text-anchor="middle" font-size="16" font-weight="bold">
                        ${Math.round(value)}%
                    </text>
                </svg>
            </div>
        `;
        
        container.innerHTML = gaugeHTML;
    }

    // Auto-refresh every 5 minutes
    setupAutoRefresh() {
        setInterval(() => {
            this.refreshData();
        }, 300000); // 5 minutes
    }

    async refreshData() {
        try {
            // Add loading animation
            document.body.classList.add('refreshing');
            
            // Simulate refresh (replace with actual API call)
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            // Update KPIs with animation
            this.updateKPIs();
            
            document.body.classList.remove('refreshing');
        } catch (error) {
            console.error('Error refreshing data:', error);
        }
    }

    updateKPIs() {
        // Update all counters with animation
        const kpis = document.querySelectorAll('.kpi-value');
        kpis.forEach(kpi => {
            const newValue = Math.floor(Math.random() * 100); // Replace with actual data
            this.animateCounter(kpi, newValue);
        });
    }

    // Drag and Drop functionality
    setupDragAndDrop() {
        const widgets = document.querySelectorAll('.draggable-widget');
        let draggedElement = null;

        widgets.forEach(widget => {
            widget.addEventListener('dragstart', (e) => {
                draggedElement = e.target;
                e.target.classList.add('dragging');
            });

            widget.addEventListener('dragend', (e) => {
                e.target.classList.remove('dragging');
                this.saveLayout();
            });

            widget.addEventListener('dragover', (e) => {
                e.preventDefault();
                const afterElement = this.getDragAfterElement(e.clientX, e.clientY);
                const container = e.target.closest('.dashboard-grid');
                
                if (afterElement == null) {
                    container.appendChild(draggedElement);
                } else {
                    container.insertBefore(draggedElement, afterElement);
                }
            });
        });
    }

    getDragAfterElement(x, y) {
        const draggableElements = [...document.querySelectorAll('.draggable-widget:not(.dragging)')];
        
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = x - box.left - box.width / 2;
            
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    saveLayout() {
        const layout = {};
        const widgets = document.querySelectorAll('.draggable-widget');
        
        widgets.forEach((widget, index) => {
            layout[widget.dataset.widgetId] = {
                position: index,
                visible: !widget.classList.contains('hidden')
            };
        });
        
        localStorage.setItem('dashboardLayout', JSON.stringify(layout));
    }

    loadLayout() {
        const layout = JSON.parse(localStorage.getItem('dashboardLayout')) || {};
        
        Object.keys(layout).forEach(widgetId => {
            const widget = document.querySelector(`[data-widget-id="${widgetId}"]`);
            if (widget && layout[widgetId].visible === false) {
                widget.classList.add('hidden');
            }
        });
    }

    // Heatmap generation
    createHeatmap(container, data) {
        const heatmap = document.createElement('div');
        heatmap.className = 'heatmap';
        
        data.forEach((value, index) => {
            const cell = document.createElement('div');
            cell.className = 'heatmap-cell';
            cell.style.backgroundColor = this.getHeatmapColor(value);
            cell.title = `Value: ${value}`;
            heatmap.appendChild(cell);
        });
        
        container.appendChild(heatmap);
    }

    getHeatmapColor(value) {
        const colors = [
            '#e8f5e8', '#c8e6c9', '#a5d6a7', '#81c784', '#66bb6a',
            '#4caf50', '#43a047', '#388e3c', '#2e7d32', '#1b5e20'
        ];
        
        const index = Math.min(Math.floor(value / 10), colors.length - 1);
        return colors[index];
    }

    // Utility functions
    formatNumber(num) {
        return new Intl.NumberFormat('es-ES').format(num);
    }

    formatTime(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
}

// Initialize dashboard
const dashboard = new DashboardAnimations();

// Export for use in other files
window.DashboardAnimations = DashboardAnimations;
