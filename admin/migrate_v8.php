<?php
error_reporting(0);
ob_start();

$log = [];
try {
    require_once __DIR__ . '/../api/db.php';
    $pdo = get_db();

    // Esta migración incluye todo lo de la v7: se puede correr sola, o después de ella.
    // Deja la base con EXACTAMENTE el contenido de los prácticos, que es la condición para
    // que los pra*.html puedan empezar a leerla sin que cambie nada de lo que se ve hoy.
    $cols = array_column($pdo->query("PRAGMA table_info(laminas)")->fetchAll(), 'name');
    if (!in_array('clinical_sample', $cols, true)) {
        $pdo->exec("ALTER TABLE laminas ADD COLUMN clinical_sample TEXT");
        $log[] = 'Columna clinical_sample agregada.';
    } else {
        $log[] = 'Columna clinical_sample ya existía.';
    }

    $jsonPath = __DIR__ . '/../data/laminas.json';
    if (!is_readable($jsonPath)) {
        throw new Exception('No se encuentra data/laminas.json en el servidor. Súbelo antes de correr esta migración.');
    }
    $laminas = json_decode(file_get_contents($jsonPath), true)['laminas'] ?? [];
    if (!$laminas) {
        throw new Exception('data/laminas.json no trae láminas.');
    }

    // Láminas de práctico: se sincroniza también el texto clínico, porque el práctico es
    // el que lo tiene rediseñado. Se conservan edad, sexo y tipo/sitio de biopsia, que son
    // trabajo de las migraciones v4-v6 y el práctico no los trae separados.
    $conPractico = $pdo->prepare("UPDATE laminas SET title=?, concept=?, clinical_patient=?, clinical_sample=?,
        clinical_symptoms=?, labs_json=?, imagenologia_json=?, questions_json=?, links_json=?,
        updated_at=datetime('now') WHERE slug=?");
    // Láminas sueltas: no viven en ningún práctico, así que solo se les toca lo que cambió
    // con el rediseño (título, concepto, muestra, preguntas y enlaces con rol y nota).
    $sueltas = $pdo->prepare("UPDATE laminas SET title=?, concept=?, clinical_sample=?,
        questions_json=?, links_json=?, updated_at=datetime('now') WHERE slug=?");

    $jsonUtf = JSON_UNESCAPED_UNICODE;
    $nPractico = 0; $nSuelta = 0; $ausentes = [];
    foreach ($laminas as $l) {
        $slug = $l['id'] ?? '';
        if ($slug === '') continue;
        $c    = is_array($l['clinical'] ?? null) ? $l['clinical'] : [];
        $preg = json_encode(is_array($l['questions'] ?? null) ? $l['questions'] : [], $jsonUtf);
        $enl  = json_encode(is_array($l['links'] ?? null) ? $l['links'] : [], $jsonUtf);

        if (!empty($l['practico'])) {
            $conPractico->execute([
                $l['title'] ?? '', ($l['concept'] ?? '') ?: null,
                trim($c['patient'] ?? '') ?: null,
                trim($c['sample'] ?? '') ?: null,
                trim($c['symptoms'] ?? '') ?: null,
                json_encode(is_array($c['labs'] ?? null) ? $c['labs'] : [], $jsonUtf),
                json_encode(is_array($c['imagenologia'] ?? null) ? $c['imagenologia'] : [], $jsonUtf),
                $preg, $enl, $slug
            ]);
            if ($conPractico->rowCount()) { $nPractico++; } else { $ausentes[] = $slug; }
        } else {
            $sueltas->execute([
                $l['title'] ?? '', ($l['concept'] ?? '') ?: null,
                trim($c['sample'] ?? '') ?: null, $preg, $enl, $slug
            ]);
            if ($sueltas->rowCount()) { $nSuelta++; } else { $ausentes[] = $slug; }
        }
    }
    $log[] = 'Láminas de práctico sincronizadas con el archivo del práctico: ' . $nPractico . '.';
    $log[] = 'Láminas sueltas actualizadas (título, concepto, muestra, preguntas y enlaces): ' . $nSuelta . '.';
    if ($ausentes) {
        $log[] = 'Sin cambios (el slug no existe en la base, o ya era idéntico): ' . implode(', ', $ausentes);
    }

    // Control 1: ningún enlace de apoyo sin su nota.
    // Control 2: toda lámina de práctico con su enlace de lámina virtual, que es lo que
    // el práctico necesita para dibujar el botón amarillo.
    $sinNota = 0; $sinCaso = [];
    foreach ($pdo->query("SELECT slug, practico, links_json FROM laminas")->fetchAll() as $r) {
        $links = json_decode($r['links_json'] ?? '[]', true) ?: [];
        $tieneCaso = false; $falta = false;
        foreach ($links as $k) {
            if (($k['role'] ?? '') === 'caso') { $tieneCaso = true; }
            elseif (trim($k['note'] ?? '') === '') { $falta = true; }
        }
        if ($falta) $sinNota++;
        if (!$tieneCaso && !empty($r['practico'])) $sinCaso[] = $r['slug'];
    }
    $log[] = $sinNota ? ('Atención: ' . $sinNota . ' lámina(s) con algún enlace de apoyo sin nota.')
                      : 'Todas las láminas tienen la nota de cada enlace de apoyo.';
    $log[] = $sinCaso ? ('Atención: sin enlace de lámina virtual: ' . implode(', ', $sinCaso))
                      : 'Todas las láminas de práctico tienen su enlace de lámina virtual.';

    $status = 'ok';
} catch (Exception $e) {
    $log[] = 'ERROR: ' . $e->getMessage();
    $status = 'error';
}

ob_end_clean();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>DigitalDuck — Migración v8</title>
<style>
  body { font-family: monospace; background: #4c1d95; color: #f9f4f2; padding: 2rem; }
  h1   { color: #facc15; }
  .ok    { color: #4ade80; }
  .error { color: #f87171; }
  li   { margin: .3rem 0; }
  a { color: #facc15; }
</style>
</head>
<body>
<h1>🦆 DigitalDuck — Migración v8: la base queda igual a los prácticos</h1>
<p class="<?= $status ?>"><strong><?= strtoupper($status) ?></strong></p>
<ul>
<?php foreach ($log as $line): ?>
  <li class="<?= (strpos($line,'ERROR') !== false || strpos($line,'Atención') !== false) ? 'error' : 'ok' ?>"><?= htmlspecialchars($line) ?></li>
<?php endforeach; ?>
</ul>
<p><a href="laminas.php">→ Ir a Láminas</a></p>
</body>
</html>
