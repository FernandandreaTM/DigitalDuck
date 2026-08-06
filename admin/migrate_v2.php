<?php
error_reporting(0);
ob_start();

$log = [];
try {
    require_once __DIR__ . '/../api/db.php';
    $pdo = get_db();

    // Separa el tema "Inflamación Aguda y Crónica" en dos temas (Aguda / Crónica),
    // igual que Neoplasias Benignas/Malignas. Reclasifica las láminas ya sembradas.
    $r1 = $pdo->prepare("UPDATE laminas SET tema_id='inflamacion-aguda', tema_nombre='Inflamación Aguda' WHERE tema_id='inflamacion-aguda-cronica' AND slug='pra5-lamina-1'");
    $r1->execute();
    $log[] = 'pra5-lamina-1 (Apendicitis Aguda) -> inflamacion-aguda: ' . $r1->rowCount() . ' fila(s).';

    $r2 = $pdo->prepare("UPDATE laminas SET tema_id='inflamacion-cronica', tema_nombre='Inflamación Crónica' WHERE tema_id='inflamacion-aguda-cronica' AND slug='pra5-lamina-2'");
    $r2->execute();
    $log[] = 'pra5-lamina-2 (Cirrosis hepática) -> inflamacion-cronica: ' . $r2->rowCount() . ' fila(s).';

    $left = (int) $pdo->query("SELECT COUNT(*) FROM laminas WHERE tema_id='inflamacion-aguda-cronica'")->fetchColumn();
    $log[] = 'Láminas que quedan con el tema antiguo: ' . $left . ($left > 0 ? ' — revísalas manualmente en el panel.' : ' (ok).');

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
<title>DigitalDuck — Migración v2</title>
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
<h1>🦆 DigitalDuck — Migración v2: separar Inflamación Aguda/Crónica</h1>
<p class="<?= $status ?>"><strong><?= strtoupper($status) ?></strong></p>
<ul>
<?php foreach ($log as $line): ?>
  <li class="<?= strpos($line,'ERROR') !== false ? 'error' : 'ok' ?>"><?= htmlspecialchars($line) ?></li>
<?php endforeach; ?>
</ul>
<p><a href="laminas.php">→ Ir a Láminas</a></p>
</body>
</html>
