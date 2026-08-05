#!/usr/bin/env node
/*
 * Regenera los bloques de datos embebidos en practico-generator.html y catalogo.html
 * a partir de data/curriculum.json, data/laminas.json y data/fuentes.json.
 *
 * DigitalDuck embebe estos datos como JS/JSON inline en cada página (en vez de hacer
 * fetch()) para que las páginas funcionen abiertas directo desde el disco, sin servidor.
 * Eso significa que cada página tiene su propia copia — este script es lo que las
 * mantiene sincronizadas con la fuente real (data/*.json) en un solo paso.
 *
 * Uso: node scripts/sync-embedded-data.js
 * Correr después de editar cualquier archivo en data/, antes de hacer commit.
 */

const fs = require('fs');
const path = require('path');
const root = path.join(__dirname, '..');

function findMatchingBrace(text, startIdx) {
  let depth = 0, inString = false, quoteChar = null, escaped = false;
  for (let i = startIdx; i < text.length; i++) {
    const ch = text[i];
    if (escaped) { escaped = false; continue; }
    if (ch === '\\') { escaped = true; continue; }
    if (inString) {
      if (ch === quoteChar) inString = false;
      continue;
    }
    if (ch === '"' || ch === "'") { inString = true; quoteChar = ch; continue; }
    if (ch === '{' || ch === '[') depth++;
    else if (ch === '}' || ch === ']') { depth--; if (depth === 0) return i; }
  }
  return -1;
}

function replaceBlock(text, marker, newLiteral) {
  const idx = text.indexOf(marker);
  if (idx === -1) { console.warn('  ! marcador no encontrado:', marker.trim()); return text; }
  const start = idx + marker.length;
  const end = findMatchingBrace(text, start);
  if (end === -1) { console.warn('  ! no se pudo encontrar el cierre para:', marker.trim()); return text; }
  // conservar cualquier sufijo entre el cierre y el ';' (ej. '.laminas', '.fuentes')
  let after = end + 1;
  while (after < text.length && text[after] !== ';') after++;
  if (after >= text.length) { console.warn('  ! no se encontró ";" después de:', marker.trim()); return text; }
  return text.slice(0, start) + newLiteral + text.slice(after);
}

function updateFile(file, replacements) {
  const fp = path.join(root, file);
  let text = fs.readFileSync(fp, 'utf-8');
  let changed = text;
  replacements.forEach(([marker, literal]) => { changed = replaceBlock(changed, marker, literal); });
  if (changed === text) {
    console.log('Sin cambios:', file);
  } else {
    fs.writeFileSync(fp, changed, 'utf-8');
    console.log('Actualizado:', file);
  }
}

const curriculum = JSON.parse(fs.readFileSync(path.join(root, 'data/curriculum.json'), 'utf-8'));
const laminas = JSON.parse(fs.readFileSync(path.join(root, 'data/laminas.json'), 'utf-8'));
const fuentes = JSON.parse(fs.readFileSync(path.join(root, 'data/fuentes.json'), 'utf-8'));

const curriculumLiteral = JSON.stringify(curriculum, null, 2);
const laminasLiteral = JSON.stringify(laminas, null, 2) + '.laminas';
const fuentesLiteral = JSON.stringify(fuentes, null, 2) + '.fuentes';

updateFile('practico-generator.html', [
  ['const CURRICULUM_TAXONOMY = ', curriculumLiteral],
  ['const LAMINAS_BANK = ', laminasLiteral]
]);

updateFile('catalogo.html', [
  ['const CURRICULUM = ', curriculumLiteral],
  ['const LAMINAS = ', laminasLiteral],
  ['const FUENTES = ', fuentesLiteral]
]);

console.log('\nListo. Revisa los diffs (git diff) y prueba antes de hacer commit.');
