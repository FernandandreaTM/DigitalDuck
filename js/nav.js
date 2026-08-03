// Menú de navegación compartido por index.html y practicos.html.
// Agregar, quitar o renombrar una sección aquí actualiza el menú en ambas páginas.
const NAV_LINKS = [
    { id: 'inicio', label: 'INICIO' },
    { id: 'sobre', label: 'SOBRE DIGITALDUCK' },
    { id: 'caracteristicas', label: 'CARACTERÍSTICAS' },
    { id: 'contenido', label: 'CONTENIDO' },
    { id: 'practicos', label: 'PRÁCTICOS' },
    { id: 'contacto', label: 'CONTACTO' }
];

// basePath: '' en index.html (ancla directa), 'index.html' en otras páginas.
function renderNav(basePath) {
    const navLinks = document.getElementById('navLinks');
    if (!navLinks) return;
    const prefix = basePath || '';
    navLinks.innerHTML = NAV_LINKS.map(l => `<li><a href="${prefix}#${l.id}">${l.label}</a></li>`).join('');
}
