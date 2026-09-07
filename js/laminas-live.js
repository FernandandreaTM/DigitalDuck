/*
 * Sincroniza los casos de un práctico con la base de datos (api/laminas.php).
 *
 * Los pra*.html traen sus láminas escritas dentro del archivo. Este script, si hay
 * servidor con PHP, pide las láminas guardadas y reemplaza el contenido de las de tipo
 * 'case' para que lo que edites en el admin se vea también en el práctico.
 *
 * Regla de oro: ante cualquier duda, no toca nada. Si no hay red, si la respuesta no es
 * JSON, si falta un dato o si algo lanza una excepción, el práctico se queda exactamente
 * con el contenido que trae escrito. Por eso el práctico se dibuja primero y esto corre
 * después: nunca retrasa ni bloquea la primera lámina.
 */
(function (global) {
    'use strict';

    var TIEMPO_LIMITE_MS = 2500;
    // El práctico guarda los enlaces como objeto de claves fijas; la base, como lista con rol.
    var CLAVE_POR_ROL = { caso: 'caso', histologia: 'histologia', clinica: 'medline', referencia: 'msd' };
    /* Etiquetas genéricas: las que el extractor le puso a los enlaces que en el práctico no
       traían nombre propio. No se propagan, porque el práctico ya tiene su texto por
       defecto y no es el mismo en móvil que en escritorio (ej. "MSD Manual Professional"
       en móvil). Solo viaja el nombre que alguien escribió a mano, como PathologyOutlines. */
    var ETIQUETAS_GENERICAS = ['Lámina virtual', 'Histología normal', 'MedlinePlus', 'MSD Manual'];

    function archivoActual() {
        var nombre = (global.location.pathname.split('/').pop() || '').toLowerCase();
        return /^pra\d+\.html$/.test(nombre) ? nombre : '';
    }

    function texto(v) {
        return typeof v === 'string' ? v.trim() : '';
    }

    // labs e imagenología son listas de {value,url} en la base y un solo texto en el práctico.
    function listaATexto(lista) {
        if (!Array.isArray(lista)) return '';
        return lista.map(function (e) { return texto(e && e.value); }).filter(Boolean).join('. ');
    }

    function enlacesDesde(registro, actuales) {
        var lista = Array.isArray(registro.links) ? registro.links : [];
        var links = {}, notas = {}, etiquetas = {};
        lista.forEach(function (l) {
            var clave = CLAVE_POR_ROL[l && l.role];
            if (!clave || links[clave] || !texto(l.url)) return;   // el primero de cada rol manda
            links[clave] = texto(l.url);
            if (texto(l.note)) notas[clave] = texto(l.note);
            if (texto(l.label) && ETIQUETAS_GENERICAS.indexOf(texto(l.label)) === -1) etiquetas[clave] = texto(l.label);
        });
        // Sin el enlace a la lámina virtual la tarjeta pierde su botón: se descarta el cambio.
        if (!links.caso) return null;
        // Lo que la base no traiga se conserva del archivo.
        Object.keys(actuales.links || {}).forEach(function (k) {
            if (!links[k]) links[k] = actuales.links[k];
        });
        return { links: links, linkNotes: notas, linkLabels: etiquetas };
    }

    function contenidoDesde(registro, actual) {
        if (!registro || !texto(registro.title)) return null;
        var preguntas = Array.isArray(registro.questions) ? registro.questions.filter(function (q) { return texto(q); }) : [];
        if (!preguntas.length) return null;

        var nuevo = JSON.parse(JSON.stringify(actual));

        // El título del práctico lleva el prefijo "Caso N:" que la base no guarda.
        var prefijo = (texto(actual.title).match(/^(Caso\s*\d+\s*:\s*)/) || [''])[0];
        nuevo.title = prefijo + texto(registro.title);
        if (texto(registro.concept)) nuevo.concept = texto(registro.concept);
        nuevo.questions = preguntas;

        var clinica = registro.clinical || {};
        nuevo.clinical = nuevo.clinical || {};
        var paciente = texto(clinica.patient);
        var muestra = texto(clinica.sample);
        var examenes = [listaATexto(clinica.labs), listaATexto(clinica.imagenologia)].filter(Boolean).join(' ');
        if (paciente) nuevo.clinical.patient = paciente;
        if (muestra) nuevo.clinical.sample = muestra;
        if (texto(clinica.symptoms)) nuevo.clinical.symptoms = texto(clinica.symptoms);
        if (examenes) nuevo.clinical.labs = examenes;

        var enlaces = enlacesDesde(registro, actual);
        if (enlaces) {
            nuevo.links = enlaces.links;
            nuevo.linkNotes = Object.keys(enlaces.linkNotes).length ? enlaces.linkNotes : actual.linkNotes;
            if (Object.keys(enlaces.linkLabels).length) nuevo.linkLabels = enlaces.linkLabels;
        }
        return nuevo;
    }

    /*
     * slides: el array del práctico (se modifica en sitio).
     * alCambiar: se llama solo si alguna lámina quedó distinta, para volver a dibujar.
     */
    global.hydrateLaminas = function (slides, alCambiar) {
        try {
            var archivo = archivoActual();
            // Abierto desde el disco no hay servidor que consultar, y el intento deja un
            // error rojo en la consola: mejor ni preguntar.
            if (global.location.protocol === 'file:') return;
            if (!archivo || !Array.isArray(slides) || !global.fetch) return;

            var control = typeof AbortController !== 'undefined' ? new AbortController() : null;
            var corte = global.setTimeout(function () { if (control) control.abort(); }, TIEMPO_LIMITE_MS);

            global.fetch('api/laminas.php', control ? { signal: control.signal } : undefined)
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (datos) {
                    global.clearTimeout(corte);
                    if (!Array.isArray(datos) || !datos.length) return;

                    var deEstePractico = datos.filter(function (l) {
                        return l && String(l.practico || '').toLowerCase() === archivo;
                    }).sort(function (a, b) {
                        return (a.indiceEnPractico || 0) - (b.indiceEnPractico || 0);
                    });
                    if (!deEstePractico.length) return;

                    var cambio = false, n = 0;
                    slides.forEach(function (s) {
                        if (!s || s.type !== 'case' || !s.content) return;
                        var registro = deEstePractico[n++];
                        if (!registro) return;
                        var nuevo = contenidoDesde(registro, s.content);
                        if (!nuevo) return;
                        if (JSON.stringify(nuevo) !== JSON.stringify(s.content)) {
                            s.content = nuevo;
                            cambio = true;
                        }
                    });
                    if (cambio && typeof alCambiar === 'function') alCambiar();
                })
                .catch(function () { global.clearTimeout(corte); });   // sin servidor: el práctico se queda como está
        } catch (e) { /* nunca debe romper el práctico */ }
    };
})(window);
