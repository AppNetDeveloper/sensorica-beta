// Dashboard Animations - v1.0.1 - Cache buster
class DashboardAnimations {
    constructor() {
        console.log('DashboardAnimations constructor called');
        // Only initialize if we're on a page with KPI cards
        if (document.querySelector('.kpi-card')) {
            this.init();
        }
    }

    init() {
        console.log('Initializing Dashboard Animations...');
        this.setupCounters();
        this.animateKPIs();
    }

    setupCounters() {
        console.log('Setting up counters...');
        const counters = document.querySelectorAll('.kpi-value');
        if (!counters.length) {
            console.log('No counters found');
            return;
        }

        const speed = 200; // Animation speed for the counter

        counters.forEach(counter => {
            const updateCount = () => {
                const target = +counter.getAttribute('data-target');
                const count = +counter.innerText.replace(/,/g, '');

                // If the target is not a number, just display it without animation
                if (isNaN(target)) {
                    counter.innerText = counter.getAttribute('data-target');
                    return;
                }

                const inc = target / speed;

                if (count < target) {
                    counter.innerText = Math.ceil(count + inc).toLocaleString();
                    setTimeout(updateCount, 10);
                } else {
                    counter.innerText = target.toLocaleString();
                }
            };

            // Reset the initial value to 0 to ensure the animation always runs
            counter.innerText = '0';
            updateCount();
        });
    }

    animateKPIs() {
        console.log('Animating KPIs...');
        const kpis = document.querySelectorAll('.kpi-card');
        if (!kpis.length) {
            console.log('No KPI cards found');
            return;
        }

        kpis.forEach((kpi, index) => {
            // Reset initial state for animation
            kpi.style.opacity = '0';
            kpi.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                kpi.style.opacity = '1';
                kpi.style.transform = 'translateY(0)';
            }, index * 150);
        });
    }
}

// Make the class available globally
window.DashboardAnimations = DashboardAnimations;
