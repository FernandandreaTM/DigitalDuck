<?php
error_reporting(0);
ob_start();

$log = [];
try {
    require_once __DIR__ . '/../api/db.php';
    $pdo = get_db();

    // 1) Divide labs_json donde varios resultados quedaron juntos en un solo string
    //    separado por comas, en vez de una entrada por resultado.
    $labSplits = [
        'pra4-lamina-1' => [
            ['value' => 'Creatinina sérica: 3.5 mg/dL (valor de referencia: 0.6-1.2 mg/dL)', 'url' => null],
            ['value' => 'Urea sérica: 45 mg/dL (valor de referencia: 7-20 mg/dL)', 'url' => null],
        ],
        'pra4-lamina-2' => [
            ['value' => 'Aspartato Transaminasa (AST): 1,200 U/L (valor de referencia: 10-40 U/L)', 'url' => null],
            ['value' => 'Alanina Transaminasa (ALT): 1,500 U/L (valor de referencia: 7-56 U/L)', 'url' => null],
            ['value' => 'Bilirrubina total: 8 mg/dL (valor de referencia: 0.1-1.2 mg/dL)', 'url' => null],
        ],
    ];

    // 2) Normaliza el resto de labs_json que ya eran una entrada por resultado, pero con
    //    "Normal:"/"valor normal:"/"vr:" en vez de "valor de referencia:". ("previo:" en
    //    pra3-lamina-2 se deja igual: compara con un valor anterior, no es un rango de referencia).
    $labWording = [
        'pra2-lamina-3' => [['value' => 'PSA: 8.2 ng/mL (valor de referencia: <4 ng/mL)', 'url' => null]],
        'pra2-lamina-4' => [['value' => 'Creatina quinasa (CK) > 8000 U/L (valor de referencia: 39-308 U/L)', 'url' => null]],
        'pra3-lamina-1' => [['value' => 'Alanina transaminasa: 120 U/L (valor de referencia: 7-56 U/L)', 'url' => null]],
        'pra5-lamina-1' => [
            ['value' => 'Hemograma: Neutrófilos 14,000/µL (valor de referencia: 4,000-11,000/µL)', 'url' => null],
            ['value' => 'Proteína C reactiva (PCR): 25 mg/L (valor de referencia: <5 mg/L)', 'url' => null],
        ],
        'pra5-lamina-2' => [
            ['value' => 'Bilirrubina total 3.2 mg/dL (valor de referencia: 0.1-1.2 mg/dL)', 'url' => null],
            ['value' => 'AST 85 U/L (valor de referencia: 10-40 U/L)', 'url' => null],
            ['value' => 'ALT 78 U/L (valor de referencia: 7-56 U/L)', 'url' => null],
            ['value' => 'Albúmina 2.8 g/dL (valor de referencia: 3.5-5.0 g/dL)', 'url' => null],
            ['value' => 'INR: 1.6 (valor de referencia: 0.8-1.2)', 'url' => null],
        ],
        'tiroiditis-de-hashimoto' => [
            ['value' => 'TSH: 12.4 mIU/L (valor de referencia: 0.4-4.0 mIU/L)', 'url' => null],
            ['value' => 'T4 libre: 0.6 ng/dL (valor de referencia: 0.8-1.8 ng/dL)', 'url' => null],
            ['value' => 'Anticuerpos anti-TPO: 620 UI/mL (valor de referencia: <35 UI/mL)', 'url' => null],
        ],
    ];

    $stmtLabs = $pdo->prepare("UPDATE laminas SET labs_json = ? WHERE slug = ?");
    foreach ($labSplits + $labWording as $slug => $items) {
        $stmtLabs->execute([json_encode($items, JSON_UNESCAPED_UNICODE), $slug]);
        $log[] = 'labs ' . $slug . ': ' . ($stmtLabs->rowCount() ? 'actualizada' : 'sin cambios (¿slug no existe?)');
    }

    // 3) Sexo: "Masculino"/"Femenino" en vez de Varón/Hombre/Mujer/Niño (Niño mezclaba
    //    etapa de vida con sexo; la edad ya está en su propio campo).
    $sexoMap = [
        'pra2-lamina-1' => 'Masculino', 'pra2-lamina-2' => 'Femenino', 'pra2-lamina-3' => 'Masculino',
        'pra2-lamina-4' => 'Masculino', 'pra3-lamina-1' => 'Masculino', 'pra3-lamina-2' => 'Femenino',
        'pra3-lamina-3' => 'Masculino', 'pra4-lamina-1' => 'Masculino', 'pra4-lamina-2' => 'Femenino',
        'pra4-lamina-3' => 'Masculino', 'pra5-lamina-1' => 'Masculino', 'pra5-lamina-2' => 'Femenino',
        'pra6-lamina-1' => 'Femenino', 'pra6-lamina-2' => 'Femenino', 'pra6-lamina-3' => 'Masculino',
        'pra7-lamina-1' => 'Masculino', 'pra7-lamina-2' => 'Masculino', 'pra8-lamina-1' => 'Femenino',
        'pra8-lamina-2' => 'Masculino', 'pielonefritis-cronica' => 'Femenino',
        'salpingitis-aguda-purulenta' => 'Femenino', 'tiroiditis-de-hashimoto' => 'Femenino',
    ];
    $stmtSexo = $pdo->prepare("UPDATE laminas SET clinical_sexo = ? WHERE slug = ?");
    $sexoChanged = 0;
    foreach ($sexoMap as $slug => $sexo) {
        $stmtSexo->execute([$sexo, $slug]);
        $sexoChanged += $stmtSexo->rowCount();
    }
    $log[] = 'Sexo normalizado a Masculino/Femenino en ' . $sexoChanged . ' lámina(s).';

    // 4) Tipo/sitio de biopsia: minúscula y sin redundancia ("Pieza quirúrgica" -> "quirúrgica",
    //    "Tiroides" -> "tiroides"), para que la oración armada en la tarjeta quede bien.
    $r = $pdo->prepare("UPDATE laminas SET clinical_tipo_biopsia = 'quirúrgica', clinical_sitio_biopsia = 'tiroides' WHERE slug = 'tiroiditis-de-hashimoto'");
    $r->execute();
    $log[] = 'tiroiditis-de-hashimoto tipo/sitio de biopsia normalizado: ' . ($r->rowCount() ? 'ok' : 'sin cambios');

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
<title>DigitalDuck — Migración v6</title>
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
<h1>🦆 DigitalDuck — Migración v6: homogeneizar labs, sexo y biopsia</h1>
<p class="<?= $status ?>"><strong><?= strtoupper($status) ?></strong></p>
<ul>
<?php foreach ($log as $line): ?>
  <li class="<?= (strpos($line,'ERROR') !== false) ? 'error' : 'ok' ?>"><?= htmlspecialchars($line) ?></li>
<?php endforeach; ?>
</ul>
<p><a href="laminas.php">→ Ir a Láminas</a></p>
</body>
</html>
