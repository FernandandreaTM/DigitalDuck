# DIGITALDUCK — Prácticos Digitales de Patología

Plataforma web de la Escuela de Tecnología Médica (UACh, Puerto Montt) para prácticos interactivos de Patología Básica, con dos partes:

1. **Portal de estudiantes** — `index.html` + `practicos.html` + 8 prácticos (`pra1.html`...`pra8.html`), accesibles en línea, responsivos, navegables por slides.
2. **Generador para docentes** — `practico-generator.html`, una herramienta sin código para crear, editar y reciclar prácticos nuevos, reutilizando el mismo motor de renderizado que usan los 8 prácticos existentes.

---

## Portal de estudiantes

- `index.html` — landing page del proyecto.
- `practicos.html` — índice de prácticos. El menú desplegable y la grilla de tarjetas se generan desde un array `PRACTICOS` al inicio del `<script>` del archivo — **agregar un práctico nuevo al índice es sumar una entrada a ese array**, no editar HTML en dos lugares.
- `pra1.html` ... `pra8.html` — cada uno es un práctico standalone: HTML + Tailwind CDN + Lucide + un array `slides` embebido, sin dependencias de build.
- `css/base.css`, `css/index.css`, `css/practicos.css` — estilos del portal (no de los prácticos individuales, que usan Tailwind vía CDN).
- `images/` — logos e imágenes del portal.

## Generador de prácticos (`practico-generator.html`)

Abrir el archivo directamente en el navegador (no requiere servidor ni instalación). Tiene 4 pestañas:

1. **Información General** — título, fecha, instructor, contacto. Se aplica a las láminas de Portada y Contacto con un botón.
2. **Contenidos** — objetivos de aprendizaje y bibliografía. Se aplica a las láminas de Objetivos y Recursos.
3. **Láminas** — agregar, editar, reordenar y eliminar láminas. Soporta 12 tipos: portada, objetivos, contenido (lista), proceso (pasos + imagen), plataforma externa, tutorial, actividad, **caso clínico** (paciente + preguntas + enlaces externos), **tabla comparativa**, evaluaciones, recursos/bibliografía, y contacto.
4. **Generar** — vista previa exacta (en iframe) del archivo final, y descarga como HTML standalone listo para publicar.

Autoguarda en `localStorage` del navegador mientras se trabaja.

### Reciclar prácticos existentes

- **Cargar HTML** — sube cualquier práctico ya publicado (incluidos `pra1.html`...`pra8.html`, generados antes de que existiera esta herramienta) y extrae sus láminas para seguir editando. Migra automáticamente el formato antiguo de enlaces de caso clínico al formato actual.
- **Descargar JSON / Cargar JSON** — guarda solo los datos del práctico (sin todo el HTML), útil para versionar cambios pequeños o para que otro docente reutilice la estructura de un práctico como plantilla.

Flujo recomendado para los 8 prácticos actuales: cargar cada `praN.html` una vez, exportar su JSON, y usar ese JSON como archivo de trabajo de ahí en adelante (los cambios quedan mucho más livianos de revisar en git que un HTML de 1000+ líneas).

### Actualizar el índice del portal

Desde la pestaña Generar también se puede actualizar `practicos.html` sin editar código: sube el `practicos.html` actual, el generador agrega o actualiza la entrada del práctico que estás creando, y descarga el archivo listo para reemplazar.

---

## Arquitectura técnica

- Sin build step, sin backend, sin dependencias salvo Tailwind CDN y Lucide CDN (cargados por `<script src>`).
- Cada práctico guarda su contenido como un array `slides` de objetos `{type, content}`. El motor que convierte ese array en HTML (`getSlideHTML`) vive tanto en `practico-generator.html` como, embebido, en cada archivo generado — así el archivo final es 100% standalone y no depende del generador para funcionar.
- 12 tipos de lámina soportados (ver detalle de campos en `practico-generator.html`, sección `SLIDE_TYPES`/`SIMPLE_FIELDS`/`OBJLIST_FIELDS`).

## Control de versiones

Repositorio git local. Recomendado: mantener también el `.json` de cada práctico junto al `.html`, para poder ver diffs legibles de los cambios de contenido.

---

## Equipo

**Desarrollo:** Fernanda López M.
**Institución:** Universidad Austral de Chile (UACh), Escuela de Tecnología Médica, Sede Puerto Montt
**Contacto:** fernanda.lopez@uach.cl

## Licencia

Uso educacional — Universidad Austral de Chile.
