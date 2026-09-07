#!/usr/bin/env node
/*
 * Regenera data/laminas.json a partir de los prácticos publicados (pra1.html … pra8.html).
 *
 * Es el paso inverso de sync-embedded-data.js: aquí se LEEN los prácticos y se escribe
 * data/laminas.json; después hay que correr sync-embedded-data.js para reinyectar el
 * bloque embebido en practico-generator.html y catalogo.html.
 *
 * Las láminas que no vienen de un práctico (practico: null, cargadas a mano desde
 * data/<lamina>.json) se conservan tal cual.
 *
 * Uso: node scripts/extract-laminas.js
 */

const fs = require('fs');
const path = require('path');
const root = path.join(__dirname, '..');

const LINK_ROLES = {caso:'caso', histologia:'histologia', medline:'clinica', msd:'referencia'};
const LINK_LABELS = {caso:'Lámina virtual', histologia:'Histología normal', medline:'MedlinePlus', msd:'MSD Manual'};

/* El array `const slides = [...]` de un práctico es JS, no JSON (claves sin comillas,
   comentarios, comillas simples): se evalúa en vez de parsearse. */
function readSlides(file) {
  const text = fs.readFileSync(file, 'utf8');
  const start = text.indexOf('const slides = [');
  if (start === -1) return [];
  const open = text.indexOf('[', start);
  let depth = 0, inString = false, quote = null, escaped = false, end = -1;
  for (let i = open; i < text.length; i++) {
    const ch = text[i];
    if (escaped) { escaped = false; continue; }
    if (ch === '\\') { escaped = true; continue; }
    if (inString) { if (ch === quote) inString = false; continue; }
    if (ch === '"' || ch === "'" || ch === '`') { inString = true; quote = ch; continue; }
    if (ch === '[' || ch === '{') depth++;
    else if (ch === ']' || ch === '}') { depth--; if (depth === 0) { end = i; break; } }
  }
  if (end === -1) throw new Error('No se pudo delimitar el array de láminas en ' + file);
  return eval(text.slice(open, end + 1));
}

/* Los prácticos guardan clinical con campos planos; el generador usa listas para
   laboratorio e imagenología y campos separados para la biopsia. Se traduce sin inventar
   datos: lo que en el práctico es texto libre queda como texto libre. */
function toClinical(cl) {
  cl = cl || {};
  const labs = cl.labs ? [{value: String(cl.labs), url: null}] : [];
  return {
    patient: cl.patient || '',
    edad: cl.edad || '',
    sexo: cl.sexo || '',
    tipoBiopsia: cl.tipoBiopsia || '',
    sitioBiopsia: cl.sitioBiopsia || '',
    sample: cl.sample || '',
    symptoms: cl.symptoms || '',
    labs,
    imagenologia: []
  };
}

function toLinks(content) {
  const links = content.links || {};
  const notes = content.linkNotes || {};
  const labels = content.linkLabels || {};
  if (Array.isArray(links)) return links;
  return Object.keys(links).map(k => ({
    role: LINK_ROLES[k] || 'referencia',
    label: labels[k] || LINK_LABELS[k] || k,
    url: links[k],
    note: notes[k] || ''
  }));
}

const curriculum = JSON.parse(fs.readFileSync(path.join(root, 'data/curriculum.json'), 'utf8'));
const temaPorPractico = {};
curriculum.unidades.forEach(u => u.temas.forEach(t => {
  if (t.practico) temaPorPractico[t.practico] = {unidadNumero: u.numero, unidadNombre: u.nombre, temaId: t.id, temaNombre: t.nombre};
}));

const previas = JSON.parse(fs.readFileSync(path.join(root, 'data/laminas.json'), 'utf8')).laminas;
/* Las láminas sueltas (cargadas a mano desde data/<lamina>.json) se conservan; solo se
   les asigna el rol de cada enlace por posición y se renombran las etiquetas por defecto
   antiguas. La nota de cada enlace de apoyo hay que escribirla a mano: no se infiere. */
const ROLES_POR_POSICION = ['caso', 'histologia', 'clinica', 'referencia'];
const ETIQUETAS_ANTIGUAS = {'Ver Lámina':'Lámina virtual', 'Histología Referencia':'Histología normal', 'MSD Manuals':'MSD Manual'};
const sueltas = previas.filter(l => !l.practico).map(l => Object.assign({}, l, {
  links: (l.links || []).map((k, i) => ({
    role: k.role || ROLES_POR_POSICION[i] || 'referencia',
    label: ETIQUETAS_ANTIGUAS[k.label] || k.label,
    url: k.url,
    note: k.note || ''
  }))
}));

const nuevas = [];
for (let n = 1; n <= 8; n++) {
  const archivo = 'pra' + n + '.html';
  const ruta = path.join(root, archivo);
  if (!fs.existsSync(ruta)) continue;
  const meta = temaPorPractico[archivo] || {};
  readSlides(ruta).filter(s => s.type === 'case').forEach((s, idx) => {
    const c = s.content;
    nuevas.push(Object.assign({
      id: 'pra' + n + '-lamina-' + (idx + 1),
      practico: archivo,
      indiceEnPractico: idx
    }, meta, {
      title: (c.title || '').replace(/^Caso\s*\d+\s*:\s*/i, ''),
      concept: c.concept || '',
      clinical: toClinical(c.clinical),
      questions: (c.questions || []).slice(),
      links: toLinks(c)
    }));
  });
}

const salida = {laminas: nuevas.concat(sueltas)};
fs.writeFileSync(path.join(root, 'data/laminas.json'), JSON.stringify(salida, null, 2) + '\n', 'utf8');
console.log('data/laminas.json: ' + nuevas.length + ' láminas de los prácticos + ' + sueltas.length + ' sueltas.');
console.log('Ahora correr: node scripts/sync-embedded-data.js');
