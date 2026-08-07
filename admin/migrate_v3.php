<?php
error_reporting(0);
ob_start();

$log = [];
try {
    require_once __DIR__ . '/../api/db.php';
    $pdo = get_db();

    // Renombra el label del link de la lámina (el visor de Virtual Pathology)
    // de "Caso Clínico" a "Ver Lámina" dentro de links_json. Es un simple
    // reemplazo de texto sobre el JSON almacenado — seguro de repetir.
    $r = $pdo->prepare("UPDATE laminas SET links_json = REPLACE(links_json, 'Caso Clínico', 'Ver Lámina') WHERE links_json LIKE '%Caso Clínico%'");
    $r->execute();
    $log[] = 'Láminas actualizadas: ' . $r->rowCount() . '.';

    $left = (int) $pdo->query("SELECT COUNT(*) FROM laminas WHERE links_json LIKE '%Caso Clínico%'")->fetchColumn();
    $log[] = 'Láminas que aún dicen "Caso Clínico": ' . $left . ($left > 0 ? ' — revísalas manualmente.' : ' (ok).');

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
<title>DigitalDuck — Migración v3</title>
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
<h1>🦆 DigitalDuck — Migración v3: "Caso Clínico" → "Ver Lámina"</h1>
<p class="<?= $status ?>"><strong><?= strtoupper($status) ?></strong></p>
<ul>
<?php foreach ($log as $line): ?>
  <li class="<?= strpos($line,'ERROR') !== false ? 'error' : 'ok' ?>"><?= htmlspecialchars($line) ?></li>
<?php endforeach; ?>
</ul>
<p><a href="laminas.php">→ Ir a Láminas</a></p>
</body>
</html>
