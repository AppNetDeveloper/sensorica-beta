// Simple enhancements
const yearEl = document.getElementById('year');
if (yearEl) yearEl.textContent = new Date().getFullYear();

const toggle = document.querySelector('.menu-toggle');
const nav = document.querySelector('.nav');
if (toggle && nav) {
  toggle.addEventListener('click', () => {
    const isOpen = nav.style.display === 'flex';
    nav.style.display = isOpen ? 'none' : 'flex';
  });
}

// Simple accordion (IA FAQ)
document.querySelectorAll('.acc-header').forEach((hdr) => {
  hdr.addEventListener('click', () => {
    const item = hdr.parentElement;
    // optional: close siblings for single-open behavior
    const container = item.parentElement;
    if (container && container.classList.contains('accordion')) {
      container.querySelectorAll('.acc-item').forEach((it) => {
        if (it !== item) it.classList.remove('open');
      });
    }
    item.classList.toggle('open');
  });
});

// Quote form -> opens email draft
const form = document.getElementById('quote-form');
if (form) {
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const data = new FormData(form);
    const nombre = (data.get('nombre') || '').toString();
    const email = (data.get('email') || '').toString();
    const empresa = (data.get('empresa') || '').toString();
    const telefono = (data.get('telefono') || '').toString();
    const mensaje = (data.get('mensaje') || '').toString();

    const to = 'contacto@xmart-industria.com';
    const subject = encodeURIComponent(`Solicitud de presupuesto - ${empresa || nombre}`);
    const bodyLines = [
      `Nombre: ${nombre}`,
      `Email: ${email}`,
      `Empresa: ${empresa}`,
      `Teléfono: ${telefono}`,
      '',
      'Necesidad:',
      mensaje
    ];
    const body = encodeURIComponent(bodyLines.join('\n'));
    const url = `mailto:${to}?subject=${subject}&body=${body}`;
    window.location.href = url;
  });
}
