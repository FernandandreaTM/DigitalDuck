// Menú de navegación compartido por index.html, practicos.html y catalogo.html.
// Agregar, quitar o renombrar una sección aquí actualiza el menú en las tres páginas.
const NAV_LINKS = [
    { id: 'inicio', label: 'INICIO' },
    { id: 'sobre', label: 'SOBRE DIGITALDUCK' },
    { id: 'caracteristicas', label: 'CARACTERÍSTICAS' },
    { id: 'contenido', label: 'CONTENIDO' },
    { id: 'docentes', label: 'DOCENTES' },
    { id: 'contacto', label: 'CONTACTO' }
];

// basePath: '' en index.html (ancla directa), 'index.html' en otras páginas.
function renderNav(basePath) {
    const navLinks = document.getElementById('navLinks');
    if (!navLinks) return;
    const prefix = basePath || '';
    navLinks.innerHTML = NAV_LINKS.map(l => `<li><a href="${prefix}#${l.id}">${l.label}</a></li>`).join('');
}

// ===== Pill de Prácticos (dropdown compartido en la topbar) =====

// Fuente primaria: backend PHP. Respaldo: data/practicos.json (hosting estático sin PHP).
function fetchPracticos() {
    return fetch('api/practicos.php')
        .then(r => r.ok ? r.json() : Promise.reject())
        .then(d => (Array.isArray(d) && d.length) ? d : Promise.reject())
        .catch(() => fetch('data/practicos.json').then(r => r.json()))
        .catch(() => { console.warn('No se pudo cargar el listado de prácticos.'); return []; });
}

function renderPracticosPill(practicos) {
    const content = document.getElementById('practicosPillDropdownContent');
    if (!content) return;
    const items = ['<a href="practicos.html" class="practico-pill-item-all">Ver todos los prácticos →</a>']
        .concat(practicos.map(p => `
            <a href="${p.file}" class="practico-item">
                <span class="practico-num">${p.num}</span>
                <span class="practico-name">${p.name}</span>
            </a>
        `));
    content.innerHTML = items.join('');
}

function wirePracticosPillToggle() {
    const toggle = document.getElementById('practicosPillToggle');
    const dropdown = document.getElementById('practicosPillDropdown');
    if (!toggle || !dropdown) return;
    toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        toggle.classList.toggle('active');
        dropdown.classList.toggle('active');
    });
    document.addEventListener('click', (e) => {
        if (!toggle.contains(e.target) && !dropdown.contains(e.target)) {
            toggle.classList.remove('active');
            dropdown.classList.remove('active');
        }
    });
}

// Inicializa el pill de Prácticos (toggle + datos). Devuelve la promesa de PRACTICOS
// por si la página también necesita los datos completos (ej. practicos.html y su grilla).
function initPracticosNav() {
    wirePracticosPillToggle();
    return fetchPracticos().then(data => { renderPracticosPill(data); return data; });
}
