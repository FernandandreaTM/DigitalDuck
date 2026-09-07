<?php
error_reporting(0);
ob_start();

$log = [];
try {
    require_once __DIR__ . '/../api/db.php';
    $pdo = get_db();

    // 1) Columna nueva: la muestra (tipo de espécimen) pasó a ser un dato propio de la
    //    lámina en el rediseño de los prácticos, con su propia ranura en la tarjeta clínica.
    $cols = array_column($pdo->query("PRAGMA table_info(laminas)")->fetchAll(), 'name');
    if (!in_array('clinical_sample', $cols, true)) {
        $pdo->exec("ALTER TABLE laminas ADD COLUMN clinical_sample TEXT");
        $log[] = 'Columna clinical_sample agregada.';
    } else {
        $log[] = 'Columna clinical_sample ya existía.';
    }

    // 2) Resincroniza desde data/laminas.json lo que cambió con el rediseño: título,
    //    concepto, muestra, preguntas (ahora 4 por caso) y enlaces (ahora con rol y nota).
    //    NO toca edad, sexo, tipo/sitio de biopsia, laboratorio ni imagenología: eso se
    //    homogeneizó en las migraciones v4-v6 y el JSON extraído de los prácticos no lo trae.
    $jsonPath = __DIR__ . '/../data/laminas.json';
    if (!is_readable($jsonPath)) {
        throw new Exception('No se encuentra data/laminas.json en el servidor. Súbelo antes de correr esta migración.');
    }
    $laminas = json_decode(file_get_contents($jsonPath), true)['laminas'] ?? [];
    if (!$laminas) {
        throw new Exception('data/laminas.json no trae láminas.');
    }

    $upd = $pdo->prepare("UPDATE laminas SET title=?, concept=?, clinical_sample=?,
        questions_json=?, links_json=?, updated_at=datetime('now') WHERE slug=?");

    $actualizadas = 0; $ausentes = [];
    foreach ($laminas as $l) {
        $slug     = $l['id'] ?? '';
        if ($slug === '') continue;
        $clinical = is_array($l['clinical'] ?? null) ? $l['clinical'] : [];
        $upd->execute([
            $l['title'] ?? '',
            ($l['concept'] ?? '') ?: null,
            (trim($clinical['sample'] ?? '')) ?: null,
            json_encode(is_array($l['questions'] ?? null) ? $l['questions'] : [], JSON_UNESCAPED_UNICODE),
            json_encode(is_array($l['links'] ?? null) ? $l['links'] : [], JSON_UNESCAPED_UNICODE),
            $slug
        ]);
        if ($upd->rowCount()) { $actualizadas++; } else { $ausentes[] = $slug; }
    }
    $log[] = 'Láminas actualizadas desde el JSON: ' . $actualizadas . ' de ' . count($laminas) . '.';
    if ($ausentes) {
        $log[] = 'Sin cambios (el slug no existe en la base o el contenido ya era idéntico): ' . implode(', ', $ausentes);
    }

    // 3) Control: cuántas quedaron con la nota de cada enlace de apoyo escrita.
    $sinNota = 0;
    foreach ($pdo->query("SELECT slug, links_json FROM laminas")->fetchAll() as $r) {
        $links = json_decode($r['links_json'] ?? '[]', true) ?: [];
        foreach ($links as $k) {
            if (($k['role'] ?? '') !== 'caso' && trim($k['note'] ?? '') === '') { $sinNota++; break; }
        }
    }
    $log[] = $sinNota
        ? ('Atención: ' . $sinNota . ' lámina(s) siguen con algún enlace de apoyo sin nota.')
        : 'Todas las láminas tienen la nota de cada enlace de apoyo.';

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
<title>DigitalDuck — Migración v7</title>
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
<h1>🦆 DigitalDuck — Migración v7: muestra, preguntas y enlaces con rol y nota</h1>
<p class="<?= $status ?>"><strong><?= strtoupper($status) ?></strong></p>
<ul>
<?php foreach ($log as $line): ?>
  <li class="<?= (strpos($line,'ERROR') !== false || strpos($line,'Atención') !== false) ? 'error' : 'ok' ?>"><?= htmlspecialchars($line) ?></li>
<?php endforeach; ?>
</ul>
<p><a href="laminas.php">→ Ir a Láminas</a></p>
</body>
</html>
