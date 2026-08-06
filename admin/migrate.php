<?php
error_reporting(0);
ob_start();

$db_path  = __DIR__ . '/../data/digitalduck.db';
$schema   = __DIR__ . '/../data/schema.sql';
$seed     = __DIR__ . '/../data/seed.sql';

$log = [];

function run_sql(PDO $pdo, string $file, array &$log): bool {
    $sql = file_get_contents($file);
    $statements = array_filter(
        array_map('trim', explode(";\n", $sql)),
        fn($s) => $s !== ''
    );
    foreach ($statements as $stmt) {
        try {
            $pdo->exec($stmt);
        } catch (PDOException $e) {
            $log[] = ['error' => $e->getMessage(), 'sql' => substr($stmt, 0, 80)];
            return false;
        }
    }
    return true;
}

try {
    $is_new = !file_exists($db_path);
    $pdo = new PDO('sqlite:' . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("PRAGMA journal_mode=WAL");
    $log[] = $is_new ? 'Base de datos creada.' : 'Base de datos existente detectada.';

    if (!file_exists($schema)) { throw new Exception('schema.sql no encontrado.'); }

    run_sql($pdo, $schema, $log);
    $log[] = 'Schema aplicado.';

    $practicos_count = (int) $pdo->query('SELECT COUNT(*) FROM practicos')->fetchColumn();
    $laminas_count   = (int) $pdo->query('SELECT COUNT(*) FROM laminas')->fetchColumn();

    if ($practicos_count === 0 && $laminas_count === 0) {
        if (!file_exists($seed)) { throw new Exception('seed.sql no encontrado.'); }
        run_sql($pdo, $seed, $log);
        $log[] = 'Seed aplicado (primera vez).';
    } else {
        $log[] = 'Seed omitido (ya hay datos en la base).';
    }

    $log[] = 'Prácticos: ' . $pdo->query('SELECT COUNT(*) FROM practicos')->fetchColumn();
    $log[] = 'Láminas:   ' . $pdo->query('SELECT COUNT(*) FROM laminas')->fetchColumn();

    $status = 'ok';
} catch (Exception $e) {
    $log[]  = 'ERROR: ' . $e->getMessage();
    $status = 'error';
}

ob_end_clean();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>DigitalDuck — Migración</title>
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
<h1>🦆 DigitalDuck — Migración DB</h1>
<p class="<?= $status ?>"><strong><?= strtoupper($status) ?></strong></p>
<ul>
<?php foreach ($log as $line): ?>
  <?php if (is_array($line)): ?>
  <li class="error">ERROR: <?= htmlspecialchars($line['error']) ?> — <?= htmlspecialchars($line['sql']) ?></li>
  <?php else: ?>
  <li class="<?= strpos($line,'ERROR') !== false ? 'error' : 'ok' ?>"><?= htmlspecialchars($line) ?></li>
  <?php endif; ?>
<?php endforeach; ?>
</ul>
<p><a href="login.php">→ Ir al panel de administración</a></p>
</body>
</html>
